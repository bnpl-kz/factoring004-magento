<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Controller;

use BnplPartners\Factoring004\Api;
use BnplPartners\Factoring004\Auth\BearerTokenAuth;
use BnplPartners\Factoring004\ChangeStatus\MerchantsOrders;
use BnplPartners\Factoring004\ChangeStatus\ReturnOrder;
use BnplPartners\Factoring004\ChangeStatus\ReturnStatus;
use BnplPartners\Factoring004\Exception\ErrorResponseException;
use BnplPartners\Factoring004\Otp\CheckOtpReturn;
use BnplPartners\Factoring004\Otp\SendOtpReturn;
use BnplPartners\Factoring004\Response\ErrorResponse;
use BnplPartners\Factoring004Magento\Helper\ConfigReaderTrait;
use BnplPartners\Factoring004Magento\Model\Factoring004;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Controller\Adminhtml\Order\Creditmemo\Save;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Helper\Data as SalesData;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\OrderRepository;

class SaveCreditMemo extends Save
{
    use ConfigReaderTrait;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    private $session;

    public function __construct(
        Action\Context $context,
        CreditmemoLoader $creditmemoLoader,
        CreditmemoSender $creditmemoSender,
        ForwardFactory $resultForwardFactory,
        OrderRepository $orderRepository,
        ScopeConfigInterface $config,
        RedirectFactory $redirectFactory,
        Session $session,
        SalesData $salesData = null
    ) {
        parent::__construct($context, $creditmemoLoader, $creditmemoSender, $resultForwardFactory, $salesData);

        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->redirectFactory = $redirectFactory;
        $this->session = $session;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        /*header('Content-Type: text/plain');
        print_r($this->getRequest()->getParams());
        exit;*/

        $order = $this->orderRepository->get($this->getRequest()->getParam('order_id'));

        if ($order->getPayment()->getMethod() !== Factoring004::METHOD_CODE || !$this->getRequest()->isPost()) {
            return parent::execute();
        }

        $fields = $this->getRequest()->getParam('fields') ?? ['otp' => null];
        $creditMemo = $this->getRequest()->getParam('creditmemo');
        $otp = $fields['otp'];

        $amountRefund = array_sum([
            $order->getSubtotalInclTax(),
            $creditMemo['shipping_amount'],
            $creditMemo['adjustment_positive'],
            $creditMemo['adjustment_negative'],
        ]);

        $amountRemaining = (int) ceil($order->getGrandTotal() - $amountRefund);

        if ($otp === null) {
            return $this->handleRefund($order, $amountRemaining);
        }

        $this->validateOtp($otp);

        return $this->handleCheckOtp($otp, (string) $order->getEntityId(), $amountRemaining);
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function handleRefund(OrderInterface $order, int $amountRemaining): ResultInterface
    {
        $code = $order->getShippingMethod(true)->getCarrierCode();

        if (in_array($code, $this->getConfirmableDeliveryMethods(), true)) {
            $this->sendOtp((string) $order->getEntityId(), $amountRemaining);
            $this->storeRefundDataToSession();

            return $this->redirectFactory->create()
                ->setPath($this->_backendUrl->getUrl('factoring004/otp/index/do/refund'));
        }

        $this->confirmWithoutOtp((string) $order->getEntityId(), $amountRemaining);

        return parent::execute();
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function handleCheckOtp(string $otp, string $orderId, int $amountRemaining): ResultInterface
    {
        $this->checkOtp($otp, $orderId, $amountRemaining);
        $this->removeRefundDataFromSession();

        return parent::execute();
    }

    /**
     * @return string[]
     */
    private function getConfirmableDeliveryMethods(): array
    {
        return explode(',', $this->getConfigValue('confirmable_delivery_methods'));
    }

    private function createApi(): Api
    {
        return Api::create(
            $this->getConfigValue('api_host'),
            new BearerTokenAuth($this->getConfigValue('oauth_accounting_service_token'))
        );
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function sendOtp(string $orderId, int $amountRemaining): void
    {
        $this->createApi()
            ->otp
            ->sendOtpReturn(new SendOtpReturn($amountRemaining, $this->getConfigValue('partner_code'), $orderId));
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function confirmWithoutOtp(string $orderId, int $amountRemaining): void
    {
        $response = $this->createApi()
            ->changeStatus
            ->changeStatusJson([
                new MerchantsOrders($this->getConfigValue('partner_code'), [
                    new ReturnOrder(
                        $orderId,
                        $amountRemaining > 0 ? ReturnStatus::PARTRETURN() : ReturnStatus::RETURN(),
                        $amountRemaining
                    ),
                ])
            ]);

        foreach ($response->getErrorResponses() as $errorResponse) {
            throw new ErrorResponseException(new ErrorResponse(
                $errorResponse->getCode(),
                $errorResponse->getMessage(),
                null,
                null,
                $errorResponse->getError()
            ));
        }
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function checkOtp(string $otp, string $orderId, int $amountRemaining): void
    {
        $this->createApi()
            ->otp
            ->checkOtpReturn(
                new CheckOtpReturn($amountRemaining, $this->getConfigValue('partner_code'), $orderId, $otp)
            );
    }

    private function storeRefundDataToSession(): void
    {
        $params = $this->getRequest()->getParams();

        unset($params['key']);
        unset($params['form_key']);

        $this->session->setData('factoring004_refund_data', $params);
    }

    private function removeRefundDataFromSession(): void
    {
        $this->session->unsetData('factoring004_refund_data');
    }

    /**
     * @param mixed $otp
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function validateOtp($otp): void
    {
        if (!is_string($otp) || !preg_match('/^\d{4}$/', $otp)) {
            throw new LocalizedException(__('OTP code is not valid'));
        }
    }
}
