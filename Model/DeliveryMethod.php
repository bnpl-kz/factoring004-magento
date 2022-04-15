<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;

class DeliveryMethod implements ArrayInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function toOptionArray(): array
    {
        $result = [];

        foreach ($this->scopeConfig->getValue('carriers') as $name => $options) {
            if ($options['active']) {
                $result[] = ['label' => $options['title'], 'value' => $name];
            }
        }

        return $result;
    }
}
