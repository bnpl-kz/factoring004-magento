<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use Magento\Config\Model\Config\Backend\Image;

class Logo extends Image
{
    protected function _afterLoad(): Logo
    {
        if (!$this->getValue()) {
            $this->setValue('default/logo.svg');
        }

        return parent::_afterLoad();
    }
}
