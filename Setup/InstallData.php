<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Setup;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private const DIR_ASSETS = 'factoring004/default/';
    private const LOGO_FILENAME = 'logo.svg';

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $this->mediaDirectory->create(static::DIR_ASSETS);

        $dest = $this->mediaDirectory->getAbsolutePath(static::DIR_ASSETS . static::LOGO_FILENAME);

        $this->mediaDirectory->getDriver()->copy(__DIR__ . '/../' . static::LOGO_FILENAME, $dest);
    }
}
