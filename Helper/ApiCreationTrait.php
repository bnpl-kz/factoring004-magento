<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Helper;

use BnplPartners\Factoring004\Api;
use BnplPartners\Factoring004\Auth\BearerTokenAuth;
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

    abstract protected function getOAuthToken(): string;
}
