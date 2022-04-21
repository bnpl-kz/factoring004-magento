<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Controller;

use BnplPartners\Factoring004\Api;
use BnplPartners\Factoring004\Auth\BearerTokenAuth;
use BnplPartners\Factoring004\ChangeStatus\DeliveryOrder;
use BnplPartners\Factoring004\ChangeStatus\DeliveryStatus;
use BnplPartners\Factoring004\ChangeStatus\MerchantsOrders;
use BnplPartners\Factoring004\Exception\ErrorResponseException;
use BnplPartners\Factoring004\Otp\CheckOtp;
use BnplPartners\Factoring004\Otp\SendOtp;
use BnplPartners\Factoring004\Response\ErrorResponse;
use BnplPartners\Factoring004Magento\Helper\ConfigReaderTrait;
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

class SaveOrderShipment extends Save
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
        Context $context,
        ShipmentLoader $shipmentLoader,
        LabelGenerator $labelGenerator,
        ShipmentSender $shipmentSender,
        ScopeConfigInterface $config,
        OrderRepository $orderRepository,
        RedirectFactory $redirectFactory,
        Session $session,
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
    }

    /**
     * @throws \Exception
     */
    public function execute(): ResultInterface
    {
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

        return $this->handleCheckOtp($otp, (string) $order->getEntityId());
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function handleDelivery(OrderInterface $order): ResultInterface
    {
        $code = $order->getShippingMethod(true)->getCarrierCode();

        if (in_array($code, $this->getConfirmableDeliveryMethods(), true)) {
            $this->sendOtp((string) $order->getEntityId());
            $this->storeShipmentDataToSession();

            return $this->redirectFactory->create()
                ->setPath($this->_backendUrl->getUrl('factoring004/otp/index'));
        }

        $this->confirmWithoutOtp((string) $order->getEntityId());

        return parent::execute();
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function handleCheckOtp(string $otp, string $orderId): ResultInterface
    {
        $this->checkOtp($otp, $orderId);
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
    private function sendOtp(string $orderId): void
    {
        $this->createApi()
            ->otp
            ->sendOtp(new SendOtp($this->getConfigValue('partner_code'), $orderId));
    }

    /**
     * @throws \BnplPartners\Factoring004\Exception\PackageException
     */
    private function confirmWithoutOtp(string $orderId): void
    {
        $response = $this->createApi()
            ->changeStatus
            ->changeStatusJson([
                new MerchantsOrders($this->getConfigValue('partner_code'), [
                    new DeliveryOrder($orderId, DeliveryStatus::DELIVERED()),
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
    private function checkOtp(string $otp, string $orderId): void
    {
        $this->createApi()
            ->otp
            ->checkOtp(new CheckOtp($this->getConfigValue('partner_code'), $orderId, $otp));
    }

    private function storeShipmentDataToSession(): void
    {
        $params = $this->getRequest()->getParams();

        unset($params['key']);
        unset($params['form_key']);

        $this->session->setShipmentData($params);
    }

    private function removeShipmentDataFromSession(): void
    {
        // pull shipment data
        $this->session->getShipmentData(true);
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
