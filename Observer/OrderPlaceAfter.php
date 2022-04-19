<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Observer;

use BnplPartners\Factoring004Magento\Helper\ConfigReaderTrait;
use BnplPartners\Factoring004Magento\Model\Factoring004;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OrderPlaceAfter implements ObserverInterface
{
    use ConfigReaderTrait;

    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    public function execute(Observer $observer): void
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        $paymentCode = $order->getPayment()->getMethod();

        if ($paymentCode !== Factoring004::METHOD_CODE) {
            return;
        }

        $state = $this->config->getValue('order_status');

        $order->setState($state);
        $order->setStatus($order->getConfig()->getStateDefaultStatus($state));
    }
}
