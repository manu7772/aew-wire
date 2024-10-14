<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Entity\interface\TraitSlugInterface;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Entity\trait\Slug;
use Aequation\WireBundle\Repository\WirePdfRepository;
use Aequation\WireBundle\Service\interface\WirePdfServiceInterface;
use Aequation\WireBundle\Tools\HttpRequest;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
// PHP
use Exception;

#[ORM\Entity(repositoryClass: WirePdfRepository::class)]
#[ORM\HasLifecycleCallbacks]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[ClassCustomService(WirePdfServiceInterface::class)]
#[Vich\Uploadable]
#[Slugable('name')]
class WirePdf extends WireItem implements WirePdfInterface, TraitSlugInterface
{

    use Slug;

    public const ICON = 'tabler:file-type-pdf';
    public const FA_ICON = 'file-pdf';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // #[Assert\NotNull(message: 'Le nom de fichier ne peut être null')]
    #[ORM\Column(length: 255)]
    protected ?string $filename = null;

    #[Vich\UploadableField(mapping: 'pdf', fileNameProperty: 'filename', size: 'size', mimeType: 'mime', originalName: 'originalname')]
    #[Assert\File(
        maxSize: '12M',
        maxSizeMessage: 'Le fichier ne peut pas dépasser la taille de {{ limit }}{{ suffix }} : votre fichier fait {{ size }}{{ suffix }}',
        mimeTypes: ["application/pdf"],
        mimeTypesMessage: "Format invalide, vous devez indiquer un fichier PDF",
        // binaryFormat: false,
    )]
    #[Serializer\Ignore]
    protected File|UploadedFile|null $file = null;

    #[ORM\Column]
    protected ?int $size = null;

    #[ORM\Column(length: 255)]
    protected ?string $mime = null;

    #[ORM\Column(length: 255)]
    protected ?string $originalname = null;


    public function __toString(): string
    {
        return $this->originalname ?? parent::__toString();
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     * @see https://github.com/dustin10/VichUploaderBundle/blob/master/docs/usage.md
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setFile(File $file): static
    {
        $this->file = $file;
        if(HttpRequest::isCli()) {
            $filesystem = new Filesystem();
            $dest = $filesystem->tempnam(dir: $this->_estatus->appWire->getTempDir(), prefix: pathinfo($this->file->getFilename(), PATHINFO_FILENAME).'_', suffix: '.'.pathinfo($this->file->getFilename(), PATHINFO_EXTENSION));
            $filesystem->copy($this->file->getRealPath(), $dest, true);
            try {
                $this->file = new UploadedFile(path: $dest, originalName: $this->file->getFilename(), test: true);
                $invalid = !$this->file->isValid();
            } catch (\Throwable $th) {
                //throw $th;
                $invalid = $th->getMessage();
            }
            if($invalid) {
                // failed copying file!
                throw new Exception(vsprintf('Error %s line %d: failed to copy file %s in temp dir %s%s!', [__METHOD__, __FILE__, $file->getRealPath(), $dest, is_string($invalid) ? ' ('.$invalid.')' : '']));
            }
        }
        if(!empty($this->getId())) $this->updateUpdatedAt();
        if(empty($this->filename)) $this->setFilename($this->file->getFilename());
        $this->updateName();
        return $this;
    }

    #[Serializer\Ignore]
    public function getFile(): File|null
    {
        return $this->file;
    }

    public function getFilepathname(
        $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): ?string
    {
        // $filter ??= $this->getLiipDefaultFilter();
        return $this->_estatus->wireEntityManager->getBrowserPath($this, $filter, $runtimeConfig, $resolver, $referenceType);
    }

    public function updateName(): static
    {
        if(empty($this->name) && !empty($this->filename)) $this->setName($this->filename);
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;
        $this->updateName();
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setMime(?string $mime): static
    {
        $this->mime = $mime;
        return $this;
    }

    public function getOriginalname(): ?string
    {
        return $this->originalname;
    }

    public function setOriginalname(?string $originalname): static
    {
        $this->originalname = $originalname;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }


}
