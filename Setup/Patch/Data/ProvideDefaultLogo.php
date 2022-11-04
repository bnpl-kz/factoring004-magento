<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Setup\Patch\Data;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class ProvideDefaultLogo implements DataPatchInterface, PatchRevertableInterface
{
    private const DIR_ASSETS = 'factoring004/default/';
    private const SRC_PATH = __DIR__ . '/../../../';
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
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function apply(): ProvideDefaultLogo
    {
        $this->mediaDirectory->create(static::DIR_ASSETS);

        $dest = $this->mediaDirectory->getAbsolutePath(static::DIR_ASSETS . static::LOGO_FILENAME);

        $this->mediaDirectory->getDriver()->copy(static::SRC_PATH . static::LOGO_FILENAME, $dest);

        return $this;
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function revert(): void
    {
        $this->mediaDirectory->getDriver()
            ->deleteDirectory($this->mediaDirectory->getAbsolutePath(pathinfo(static::DIR_ASSETS, PATHINFO_DIRNAME)));
    }
}
