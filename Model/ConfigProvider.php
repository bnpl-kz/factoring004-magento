<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use BnplPartners\Factoring004Magento\Helper\ConfigReaderTrait;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigProvider implements ConfigProviderInterface
{
    use ConfigReaderTrait;

    private const PRODUCTION_DOMAINS = ['bnpl.kz', 'www.bnpl.kz'];

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->config = $scopeConfig;
    }

    public function getConfig(): array
    {
        return [
            'payment' => [
                Factoring004::METHOD_CODE => [
                    'paymentGatewayType' => $this->getConfigValue('payment_gateway_type'),
                    'isModalProd' => $this->isModalProd(),
                ],
            ],
        ];
    }

    private function isModalProd(): bool
    {
        $host = parse_url($this->getConfigValue('api_host'), PHP_URL_HOST);

        return in_array($host, static::PRODUCTION_DOMAINS, true);
    }
}
