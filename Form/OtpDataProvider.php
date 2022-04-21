<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Form;

use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\AbstractDataProvider;

class OtpDataProvider extends AbstractDataProvider
{
    public function addFilter(Filter $filter): void
    {

    }

    public function getData(): array
    {
        return [];
    }
}
