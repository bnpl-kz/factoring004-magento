<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use BnplPartners\Factoring004Magento\Helper\ConfigReaderTrait;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigProvider implements ConfigProviderInterface
{
    use ConfigReaderTrait;

    protected const MEDIA_PATH = '/media/factoring004/';

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->config = $scopeConfig;
    }

    public function getConfig(): array
    {
        $agreementFile = $this->getConfigValue('agreement_file');

        return [
            'payment' => [
                Factoring004::METHOD_CODE => [
                    'agreementUrl' => $agreementFile ? static::MEDIA_PATH. ltrim($agreementFile, '/') : null,
                ],
            ],
        ];
    }
}
