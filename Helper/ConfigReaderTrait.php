<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Helper;

use BnplPartners\Factoring004Magento\Model\Factoring004;

trait ConfigReaderTrait
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @return mixed
     */
    protected function getConfigValue(string $key)
    {
        return $this->config->getValue('payment/' . Factoring004::METHOD_CODE . '/' . $key);
    }
}
