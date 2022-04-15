<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Controller;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Config\Controller\Adminhtml\System\Config\Save;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Config\Model\Config\Factory;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\File\UploaderFactory;

class SaveConfig extends Save
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Framework\File\UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var string|null
     */
    private $uploadedAgreementFileName;

    public function __construct(
        Context $context,
        Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        Factory $configFactory,
        FrontendInterface $cache,
        StringUtils $string
    ) {
        parent::__construct($context, $configStructure, $sectionChecker, $configFactory, $cache, $string);

        $this->filesystem = $context->getObjectManager()->get(Filesystem::class);
        $this->uploaderFactory = $context->getObjectManager()->get(UploaderFactory::class);
    }

    protected function _savePayment(): void
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $file = $this->getRequest()->getFiles('groups');

        if (isset($file['bnplpartners_factoring004magento']['fields']['agreement_file'])) {
            try {
                $mediaDirectory->delete('agreements');
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage($e);
                return;
            }
        }

        if ($file['bnplpartners_factoring004magento']['fields']['agreement_file']['value']['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $_FILES['agreement_file'] = $file['bnplpartners_factoring004magento']['fields']['agreement_file']['value'];

        $uploader = $this->uploaderFactory->create(['fileId' => 'agreement_file']);
        $uploader->setAllowedExtensions(['pdf']);
        $uploader->setAllowRenameFiles(true);

        try {
            $filename = uniqid('factoring004-') . '.' . $uploader->getFileExtension();
            $result = $uploader->save($mediaDirectory->getAbsolutePath('agreements'), $filename);

            if (!$result) {
                throw new \RuntimeException('Unable to upload an agreement');
            }

            $this->uploadedAgreementFileName = $mediaDirectory->getAbsolutePath('agreements/' . $filename);
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }
    }

    protected function _getGroupsForSave(): array
    {
        $groups = parent::_getGroupsForSave();

        if (empty($groups['bnplpartners_factoring004magento'])) {
            return $groups;
        }

        $value = $groups['bnplpartners_factoring004magento']['fields']['agreement_file']['value'];

        if ($this->uploadedAgreementFileName) {
            $groups['bnplpartners_factoring004magento']['fields']['agreement_file']['value'] = [
                'value' => $this->uploadedAgreementFileName,
            ];
        } elseif (isset($value['delete'])) {
            $groups['bnplpartners_factoring004magento']['fields']['agreement_file']['value'] = null;
        } else {
            $groups['bnplpartners_factoring004magento']['fields']['agreement_file']['value'] = $value['value'];
        }

        return $groups;
    }
}
