<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Form;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class OtpDataProvider extends AbstractDataProvider
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->request = $request;
    }

    public function addFilter(Filter $filter): void
    {

    }

    public function getData(): array
    {
        $action = $this->request->getParam($this->getRequestFieldName(), 'shipment');

        return [
            $action => [
                'fields' => [$this->getRequestFieldName() => $action],
            ],
        ];
    }
}
