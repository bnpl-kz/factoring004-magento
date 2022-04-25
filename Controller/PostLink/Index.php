<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Controller\PostLink;

use BnplPartners\Factoring004Magento\Helper\ConfigReaderTrait;
use BnplPartners\Factoring004Magento\Model\Factoring004;
use BnplPartners\Factoring004Magento\Model\OrderPreAppFactory;
use BnplPartners\Factoring004Magento\Model\ResourceModel\OrderPreApp;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class Index extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
    protected const STATUS_PREAPPROVED = 'preapproved';
    protected const STATUS_COMPLETED = 'completed';
    protected const STATUS_DECLINED = 'declined';
    protected const RESPONSE_OK = 'ok';

    use ConfigReaderTrait;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    private $request;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \BnplPartners\Factoring004Magento\Model\OrderPreAppFactory
     */
    private $orderPreAppFactory;

    /**
     * @var \BnplPartners\Factoring004Magento\Model\ResourceModel\OrderPreApp
     */
    private $orderPreappRepository;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $transactionBuilder;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $dbTransactionFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonFactory;

    public function __construct(
        Context $context,
        Request $request,
        OrderRepositoryInterface $orderRepository,
        OrderPreAppFactory $orderPreAppFactory,
        OrderPreApp $orderPreappRepository,
        BuilderInterface $transactionBuilder,
        TransactionFactory $dbTransactionFactory,
        JsonFactory $jsonFactory,
        ScopeConfigInterface $config
    ) {
        parent::__construct($context);

        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->orderPreAppFactory = $orderPreAppFactory;
        $this->orderPreappRepository = $orderPreappRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->dbTransactionFactory = $dbTransactionFactory;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        $data = $this->request->getBodyParams();

        $this->validateData($data);

        $order = $this->orderRepository->get((int) $data['billNumber']);
        $payment = $order->getPayment();

        if ($payment->getMethod() !== Factoring004::METHOD_CODE) {
            throw new LocalizedException(__('Order payment is not factoring004'));
        }

        if ($data['status'] === static::STATUS_PREAPPROVED) {
            return $this->jsonFactory->create()->setData(['response' => static::STATUS_PREAPPROVED]);
        }

        if ($data['status'] === static::STATUS_COMPLETED) {
            $invoiceState = Invoice::STATE_PAID;
            [$orderState, $orderStatus] = $this->getOrderStateAndStatus('order_paid_status');
            $responseValue = static::RESPONSE_OK;
        } elseif ($data['status'] === static::STATUS_DECLINED) {
            $invoiceState = Invoice::STATE_CANCELED;
            [$orderState, $orderStatus] = $this->getOrderStateAndStatus('order_declined_status');
            $responseValue = static::STATUS_DECLINED;
        } else {
            throw new LocalizedException(__('Unsupported %1 status received', $data['status']));
        }

        $dbTransaction = $this->dbTransactionFactory->create();
        $dbTransaction->addCommitCallback(function () use ($orderStatus, $invoiceState, $payment, $orderState, $order, $data) {
            $this->processInvoice($order, $invoiceState);

            $transaction = $this->buildTransaction($order, $data['preappId']);
            $transaction->save();

            $payment->addTransactionCommentsToOrder($transaction, 'PAID');
            $payment->setLastTransId($data['preappId']);
            $payment->setTransactionId($data['preappId']);
            $payment->save();

            $order->setState($orderState);
            $order->setStatus($orderStatus);
            $this->orderRepository->save($order);

            $orderPreApp = $this->orderPreAppFactory->create();
            $orderPreApp->setData('order_id', $order->getEntityId());
            $orderPreApp->setData('preapp_uid', $data['preappId']);
            $this->orderPreappRepository->save($orderPreApp);
        });

        $dbTransaction->save();

        return $this->jsonFactory->create()->setData(['response' => $responseValue]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function validateData(array $data): void
    {
        if (empty($data['status']) || !is_string($data['status'])) {
            throw new LocalizedException(__('Status is missing or is not a string'));
        }

        if (empty($data['billNumber']) || !is_string($data['billNumber'])) {
            throw new LocalizedException(__('BillNumber is missing or is not a string'));
        }

        if (empty($data['preappId']) || !is_string($data['preappId'])) {
            throw new LocalizedException(__('PreappId is missing or is not a string'));
        }
    }

    /**
     * @throws \Exception
     */
    private function processInvoice(OrderInterface $order, int $invoiceState): void
    {
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->register();

            if ($invoice->canCapture()) {
                $invoice->capture();
            }

            $invoice->setState($invoiceState);
            $invoice->save();
            return;
        }

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoice->setState($invoiceState);
            $invoice->save();
        }
    }

    private function buildTransaction(OrderInterface $order, string $preappId): TransactionInterface
    {
        return $this->transactionBuilder->setOrder($order)
            ->setFailSafe(true)
            ->setMessage('PreAppId: ' . $preappId)
            ->setTransactionId($preappId)
            ->setPayment($order->getPayment())
            ->build(TransactionInterface::TYPE_AUTH);
    }
}
