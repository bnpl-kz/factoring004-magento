<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

class PaymentGatewayType implements \Magento\Framework\Option\ArrayInterface
{
    public const TYPE_REDIRECT = 'redirect';
    public const TYPE_MODAL = 'modal';

    /**
     * @return array<string, string>[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => static::TYPE_REDIRECT, 'label' => __(ucfirst(static::TYPE_REDIRECT))],
            ['value' => static::TYPE_MODAL, 'label' => __(ucfirst(static::TYPE_MODAL))],
        ];
    }
}
