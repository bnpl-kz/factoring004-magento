<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use BnplPartners\Factoring004Magento\Model\ResourceModel\OrderPreApp as OrderPreAppResourceModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class OrderPreApp extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'bnplpartners_factoring004_order_preapps';

    protected $_cacheTag = self::CACHE_TAG;
    protected $_eventPrefix = self::CACHE_TAG;

    protected function _construct(): void
    {
        $this->_init(OrderPreAppResourceModel::class);
    }

    public function getIdentities(): array
    {
        return [static::CACHE_TAG . '_' . $this->getId()];
    }
}
