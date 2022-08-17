<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader as BaseCreditMemo;

class CreditMemoLoader extends BaseCreditMemo
{
    public static function createFromBase(BaseCreditMemo $loader): CreditMemoLoader
    {
        return new self(
            $loader->creditmemoRepository,
            $loader->creditmemoFactory,
            $loader->orderFactory,
            $loader->invoiceRepository,
            $loader->eventManager,
            $loader->backendSession,
            $loader->messageManager,
            $loader->registry,
            $loader->stockConfiguration,
            $loader->_data,
        );
    }

    /**
     * @return \Magento\Sales\Model\Order\Creditmemo|false
     */
    public function loadOnly()
    {
        $creditMemo = $this->load();

        $this->registry->unregister('current_creditmemo');

        return $creditMemo;
    }
}
