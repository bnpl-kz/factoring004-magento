<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model\ResourceModel\OrderPreApp;

use BnplPartners\Factoring004Magento\Model\OrderPreApp;
use BnplPartners\Factoring004Magento\Model\ResourceModel\OrderPreApp as OrderPreAppResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'bnplpartners_factoring004_order_preapps_collection';
    protected $_eventObject = 'order_preapps_collection';

    protected function _construct(): void
    {
        $this->_init(OrderPreApp::class, OrderPreAppResourceModel::class);
    }
}
