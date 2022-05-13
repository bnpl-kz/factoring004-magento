<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Observer;

use BnplPartners\Factoring004\PreApp\PreAppMessage;
use BnplPartners\Factoring004Magento\Helper\ApiCreationTrait;
use BnplPartners\Factoring004Magento\Model\Factoring004;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order\Item;
use Psr\Log\LoggerInterface;

class OrderSaveAfter implements ObserverInterface
{
    use ApiCreationTrait;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $session;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        ScopeConfigInterface $config,
        Session $session,
        UrlInterface $url,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->url = $url;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        $paymentCode = $order->getPayment()->getMethod();

        if ($paymentCode !== Factoring004::METHOD_CODE) {
            return;
        }

        [$state] = $this->getOrderStateAndStatus('order_status');

        if ($order->getState() !== $state) {
            return;
        }

        $billingAddress = $order->getBillingAddress();
        $items = $this->collectItems($order->getItems());

        $response = $this->createApi()->preApps->preApp(PreAppMessage::createFromArray([
            'partnerData' => $this->getPartnerData(),
            'billNumber' => (string) $order->getId(),
            'billAmount' => (int) ceil($order->getGrandTotal()),
            'itemsQuantity' => array_reduce($items, function (int $prev, array $current) {
                return $prev + $current['itemQuantity'];
            }, 0),
            'successRedirect' => $this->url->getDirectUrl('checkout/onepage/success'),
            'failRedirect' => $this->url->getDirectUrl('checkout/onepage/failure'),
            'postLink' => $this->url->getDirectUrl('factoring004/postlink'),
            'phoneNumber' => $billingAddress ? preg_replace('/^\+7|8/', '7', $billingAddress->getTelephone()) : null,
            'deliveryPoint' => $billingAddress ? $this->collectDeliveryPoint($billingAddress) : [],
            'items' => $items,
        ]));

        $this->session->setData(Factoring004::PREAPP_URI_SESSION_KEY, $response->getRedirectLink());
    }

    /**
     * @return array<string, string>
     */
    private function getPartnerData(): array
    {
        return [
            'partnerName' => $this->getConfigValue('partner_name'),
            'partnerCode' => $this->getConfigValue('partner_code'),
            'pointCode' => $this->getConfigValue('point_code'),
            'partnerEmail' => $this->getConfigValue('partner_email'),
            'partnerWebsite' => $this->getConfigValue('partner_website'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function collectDeliveryPoint(OrderAddressInterface $billingAddress): array
    {
        return [
            'region' => $billingAddress->getRegion(),
            'city' => $billingAddress->getCity(),
            'street' => $billingAddress->getStreet()[0],
            'house' => $billingAddress->getStreet()[1] ?? '',
            'flat' => $billingAddress->getStreet()[2] ?? '',
        ];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface[] $items
     *
     * @return array<string, mixed>[]
     */
    private function collectItems(array $items): array
    {
        return array_values(array_map(function (Item $item) {
            return [
                'itemId' => (string) $item->getProductId(),
                'itemName' => $item->getProduct()->getName(),
                'itemCategory' => (string) $item->getProduct()->getCategoryIds()[0] ?? '',
                'itemQuantity' => (int) $item->getQtyOrdered(),
                'itemPrice' => (int) ceil($item->getProduct()->getFinalPrice()),
                'itemSum' => (int) ceil($item->getProduct()->getFinalPrice() * $item->getQtyOrdered()),
            ];
        }, array_filter($items, function (Item $item) {
            return !empty($item->getPrice());
        })));
    }

    protected function getTransportLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
