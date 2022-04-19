<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model\ResourceModel;

class OrderPreApp extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('factoring004_order_preapps_entity', 'id');
    }
}
