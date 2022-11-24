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
    protected const DEFAULT_LOGO = 'default/logo.svg';
    protected const MIN_AMOUNT = 6000;
    protected const MAX_AMOUNT = 200000;
    private const PRODUCTION_DOMAINS = ['bnpl.kz', 'www.bnpl.kz'];

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->config = $scopeConfig;
    }

    public function getConfig(): array
    {
        $agreementFile = $this->getConfigValue('agreement_file');
        $logoFile = $this->getConfigValue('logo') ?? static::DEFAULT_LOGO;

        return [
            'payment' => [
                Factoring004::METHOD_CODE => [
                    'agreementUrl' => $agreementFile ? static::MEDIA_PATH. ltrim($agreementFile, '/') : null,
                    'logoUrl' => static::MEDIA_PATH . ltrim($logoFile, '/'),
                    'description' => $this->getConfigValue('description'),
                    'minAmount' => static::MIN_AMOUNT,
                    'maxAmount' => static::MAX_AMOUNT,
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
