<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Model;

use Magento\Sales\Model\Config\Source\Order\Status;

class OrderStatus extends Status
{
    public const SEPARATOR = '::';

    public function toOptionArray(): array
    {
        $result = [
            ['value' => '', 'label' => __(static::UNDEFINED_OPTION_LABEL)],
        ];

        foreach ($this->_orderConfig->getStates() as $state => $statePhrase) {
            $statuses = $this->_orderConfig->getStateStatuses($state);

            foreach ($statuses as $status => $statusPhrase) {
                $result[] = [
                    'value' => $state . static::SEPARATOR . $status,
                    'label' => sprintf('%s / %s', $statusPhrase, $statePhrase),
                ];
            }
        }

        return $result;
    }
}
