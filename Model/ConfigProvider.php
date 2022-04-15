<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigProvider implements ConfigProviderInterface
{
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig(): array
    {
        $agreementFile = $this->scopeConfig->getValue('payment/' . Factoring004::METHOD_CODE . '/agreement_file');
        $agreementUrl = null;

        if ($agreementFile) {
            $pos = strpos($agreementFile, '/media');

            if ($pos !== false) {
                $agreementUrl = substr($agreementFile, $pos);
            }
        }

        return [
            'payment' => [
                Factoring004::METHOD_CODE => compact('agreementUrl'),
            ],
        ];
    }
}
