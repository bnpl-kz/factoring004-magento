<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use BnplPartners\Factoring004\Exception\PackageException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

class GuestPaymentInformationManagement extends \Magento\Checkout\Model\GuestPaymentInformationManagement
{
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        try {
            return parent::savePaymentInformationAndPlaceOrder(
                $cartId,
                $email,
                $paymentMethod,
                $billingAddress
            );
        } catch (CouldNotSaveException $e) {
            if ($e->getPrevious() instanceof PackageException) {
                $e->addError(new Phrase($e->getMessage()));
                $e->addError(new Phrase($paymentMethod->getMethod()));
            }

            throw $e;
        }
    }
}
