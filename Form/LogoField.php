<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Form;

use Magento\Config\Block\System\Config\Form\Field\Image;

class LogoField extends Image
{
    protected function _getDeleteCheckbox(): string
    {
        return '';
    }
}
