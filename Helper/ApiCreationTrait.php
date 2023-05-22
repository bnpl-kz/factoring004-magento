<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Helper;

use BnplPartners\Factoring004\Api;
use BnplPartners\Factoring004\Auth\BearerTokenAuth;
use BnplPartners\Factoring004\OAuth\CacheOAuthTokenManager;
use BnplPartners\Factoring004\OAuth\OAuthTokenManager;
use BnplPartners\Factoring004\Transport\GuzzleTransport;
use BnplPartners\Factoring004\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait ApiCreationTrait
{
    use ConfigReaderTrait;

    protected function createApi(): Api
    {
        return Api::create(
            $this->getConfigValue('api_host'),
            new BearerTokenAuth($this->getOAuthToken()),
            $this->createTransport()
        );
    }

    protected function createTransport(): TransportInterface
    {
        $transport = new GuzzleTransport();
        $transport->setLogger($this->getTransportLogger());

        return $transport;
    }

    protected function getTransportLogger(): LoggerInterface
    {
        return new NullLogger();
    }

    protected function getOAuthToken(): string
    {
        $authManager = new OAuthTokenManager(
            $this->getConfigValue('api_host') . '/users/api/v1',
            $this->getConfigValue('oauth_login'),
            $this->getConfigValue('oauth_password'),
            $this->createTransport());

        $cacheAuthManager = new CacheOAuthTokenManager($authManager, $this->cacheAdapter, 'bnpl.payment');

        return $cacheAuthManager->getAccessToken()->getAccess();
    }
}
