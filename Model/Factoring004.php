<?php

namespace BnplPartners\Factoring004Magento\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

class Factoring004 extends AbstractMethod
{
    public const METHOD_CODE = 'bnplpartners_factoring004magento';
    public const PREAPP_URI_SESSION_KEY = self::METHOD_CODE . '_PREAPP_URI';

    protected $_code = self::METHOD_CODE;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canAuthorize = true;
    protected $_canUseCheckout = true;

    /**
     * @var string[]
     */
    protected $supportedCurrencyCodes;

    /**
     * @var int
     */
    protected $minOrderTotal;

    /**
     * @var int
     */
    protected $maxOrderTotal;

    protected function initializeData($data = [])
    {
        parent::initializeData($data);

        $this->supportedCurrencyCodes = (array) $this->getConfigData('currency');
        $this->minOrderTotal = (int) $this->getConfigData('min_order_total');
        $this->maxOrderTotal = (int) $this->getConfigData('max_order_total');
    }

    public function canUseForCurrency($currencyCode): bool
    {
        return in_array($currencyCode, $this->supportedCurrencyCodes, true);
    }

    public function isAvailable(CartInterface $quote = null): bool
    {
        if ($quote) {
            return $quote->getBaseGrandTotal() >= $this->minOrderTotal
                && $quote->getBaseGrandTotal() <= $this->maxOrderTotal;
        }

        return parent::isAvailable($quote);
    }
}
