<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Controller\Error;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $pageFactory;

    public function __construct(Context $context, PageFactory $pageFactory)
    {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        return $this->pageFactory->create(false, ['template' => 'BnplPartners_Factoring004Magento::error.html']);
    }
}
