<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Controller;

use BnplPartners\Factoring004\ChangeStatus\DeliveryOrder;
use BnplPartners\Factoring004\ChangeStatus\DeliveryStatus;
use BnplPartners\Factoring004\ChangeStatus\MerchantsOrders;
use BnplPartners\Factoring004\Exception\ErrorResponseException;
use BnplPartners\Factoring004\Exception\PackageException;
use BnplPartners\Factoring004\Otp\CheckOtp;
use BnplPartners\Factoring004\Otp\SendOtp;
use BnplPartners\Factoring004\Response\ErrorResponse;
use BnplPartners\Factoring004Magento\Helper\ApiCreationTrait;
use BnplPartners\Factoring004Magento\Model\Factoring004;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Helper\Data as SalesData;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Shipment\ShipmentValidatorInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Psr\Log\LoggerInterface;

class SaveOrderShipment extends Save
{
    use ApiCreationTrait;

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

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        ShipmentLoader $shipmentLoader,
        LabelGenerator $labelGenerator,
        ShipmentSender $shipmentSender,
        ScopeConfigInterface $config,
        OrderRepository $orderRepository,
        RedirectFactory $redirectFactory,
        Session $session,
        LoggerInterface $logger,
        ShipmentValidatorInterface $shipmentValidator = null,
        SalesData $salesData = null
    ) {
        parent::__construct(
            $context,
            $shipmentLoader,
            $labelGenerator,
            $shipmentSender,
            $shipmentValidator,
            $salesData
        );

        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->redirectFactory = $redirectFactory;
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function execute(): ResultInterface
    {
        try {
            $order = $this->orderRepository->get($this->getRequest()->getParam('order_id'));

            if ($order->getPayment()->getMethod() !== Factoring004::METHOD_CODE || !$this->getRequest()->isPost()) {
                return parent::execute();
            }

            $fields = $this->getRequest()->getParam('fields') ?? ['otp' => null];
            $otp = $fields['otp'];

            if ($otp === null) {
                return $this->handleDelivery($order);
            }

            $this->validateOtp($otp);

            $orderAmount = ceil($order->getPayment()->getAmountPaid());

            return $this->handleCheckOtp($otp, (string) $order->getEntityId(), (int) $orderAmount);
        } catch (ErrorResponseException $e) {
            $response = $e->getErrorResponse();

            $this->messageManager->addErrorMessage($response->getError() . ': ' . $response->getMessage());
        } catch (LocalizedException $e) {
            $this->logger->error($e);
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (PackageException $e) {
            $this->logger->error($e);
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $this->redirectFactory->create()->setRefererOrBaseUrl();
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function handleDelivery(OrderInterface $order): ResultInterface
    {
        $code = $order->getShippingMethod(true)->getCarrierCode();

        $orderAmount = ceil($order->getPayment()->getAmountPaid());

        if (in_array($code, $this->getConfirmableDeliveryMethods(), true)) {
            $this->sendOtp((string) $order->getEntityId(), (int) $orderAmount);
            $this->storeShipmentDataToSession();

            return $this->redirectFactory->create()
                ->setPath($this->_backendUrl->getUrl('factoring004/otp/index/do/shipment'));
        }

        $this->confirmWithoutOtp((string) $order->getEntityId(), (int) $orderAmount);

        return parent::execute();
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function handleCheckOtp(string $otp, string $orderId, int $orderAmount): ResultInterface
    {
        $this->checkOtp($otp, $orderId, $orderAmount);
        $this->removeShipmentDataFromSession();

        return parent::execute();
    }

    /**
     * @return string[]
     */
    private function getConfirmableDeliveryMethods(): array
    {
        return explode(',', $this->getConfigValue('confirmable_delivery_methods'));
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function sendOtp(string $orderId, int $orderAmount): void
    {
        $this->createApi()
            ->otp
            ->sendOtp(new SendOtp($this->getConfigValue('partner_code'), $orderId, $orderAmount));
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function confirmWithoutOtp(string $orderId, int $orderAmount): void
    {
        $response = $this->createApi()
            ->changeStatus
            ->changeStatusJson([
                new MerchantsOrders($this->getConfigValue('partner_code'), [
                    new DeliveryOrder($orderId, DeliveryStatus::DELIVERED(), $orderAmount),
                ]),
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
    private function checkOtp(string $otp, string $orderId, int $orderAmount): void
    {
        $this->createApi()
            ->otp
            ->checkOtp(new CheckOtp($this->getConfigValue('partner_code'), $orderId, $otp, $orderAmount));
    }

    private function storeShipmentDataToSession(): void
    {
        $params = $this->getRequest()->getParams();

        unset($params['key']);
        unset($params['form_key']);

        $this->session->setData('factoring004_shipment_data', $params);
    }

    private function removeShipmentDataFromSession(): void
    {
        $this->session->unsetData('factoring004_shipment_data');
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

    protected function getTransportLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
