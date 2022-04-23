<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;

class PartnerWebsite extends Value
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $url;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        UrlInterface $url,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);

        $this->url = $url;
    }

    protected function _afterLoad(): PartnerWebsite
    {
        parent::_afterLoad();

        if (!$this->getValue()) {
            $this->setValue(rtrim($this->url->getBaseUrl(), '/'));
        }

        return $this;
    }
}
