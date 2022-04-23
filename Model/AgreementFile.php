<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use Magento\Config\Model\Config\Backend\File;

class AgreementFile extends File
{
    protected function _getAllowedExtensions(): array
    {
        return ['pdf'];
    }
}
