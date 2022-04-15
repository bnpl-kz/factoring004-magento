<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Response;

use BnplPartners\Factoring004Magento\Model\Factoring004;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\State;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Webapi\Rest\Response as BaseResponse;
use Magento\Framework\Webapi\Rest\Response\RendererFactory;

class Response extends BaseResponse
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $session;

    public function __construct(
        RendererFactory $rendererFactory,
        ErrorProcessor $errorProcessor,
        State $appState,
        Session $session
    ) {
        parent::__construct($rendererFactory, $errorProcessor, $appState);

        $this->session = $session;
    }

    public function prepareResponse($outputData = null): BaseResponse
    {
        parent::prepareResponse($outputData);

        $value = $this->session->getData(Factoring004::PREAPP_URI_SESSION_KEY, true);

        if ($value) {
            $this->setHeader('X-Location', $value);
        }

        return $this;
    }
}
