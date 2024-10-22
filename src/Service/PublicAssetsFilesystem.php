<?php
namespace Aequation\WireBundle\Service;

// use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// // Symfony
// use Symfony\Component\AssetMapper\Path\PublicAssetsFilesystemInterface;
// use Symfony\Component\DependencyInjection\Attribute\AsAlias;
// use Symfony\Component\Filesystem\Filesystem;
// // PHP
// use Exception;

// #[AsAlias('asset_mapper.local_public_assets_filesystem', public: false)]
class PublicAssetsFilesystem // implements PublicAssetsFilesystemInterface
{

    // private Filesystem $filesystem;
    // private string $publicDir;

    // public function __construct(
    //     private readonly AppWireServiceInterface $appWire
    // )
    // {
    //     $this->filesystem = new Filesystem();
    //     $this->publicDir = $this->appWire->getProjectDir('public');
    // }

    // public function write(string $path, string $contents): void
    // {
    //     if($this->appWire->isDev()) {
    //         throw new Exception(vsprintf('STOPPED %s line %d: do not compile assetmaps while in %s environment!', [__METHOD__, __LINE__, $this->appWire->getEnvironment()]));
    //     }
    //     $targetPath = $this->publicDir.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    //     $this->filesystem->dumpFile($targetPath, $contents);
    // }

    // public function copy(string $originPath, string $path): void
    // {
    //     if($this->appWire->isDev()) {
    //         throw new Exception(vsprintf('STOPPED %s line %d: do not compile assetmaps while in %s environment!', [__METHOD__, __LINE__, $this->appWire->getEnvironment()]));
    //     }
    //     $targetPath = $this->publicDir.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    //     $this->filesystem->copy($originPath, $targetPath, true);
    // }

    // public function getDestinationPath(): string
    // {
    //     return $this->publicDir;
    // }

}