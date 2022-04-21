<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Form;

use Magento\Cms\Block\Adminhtml\Page\Edit\GenericButton;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class CheckButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label' => __('Check'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'otp_form.otp_form',
                                'actionName' => 'save',
                                'params' => []
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }
}
