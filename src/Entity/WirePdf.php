<?php
namespace Aequation\WireBundle\Entity;

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
use Symfony\Component\Routing\RouterInterface;
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
abstract class WirePdf extends WireItem implements WirePdfInterface
{

    public const ICON = [
        'ux' => 'tabler:file-type-pdf',
        'fa' => 'fa-solid fa-file-pdf'
    ];
    public const PAPERS = ['A4', 'A5', 'A6', 'letter', 'legal'];
    public const ORIENTATIONS = ['portrait', 'landscape'];
    public const SOURCETYPES = ['undefined', 'document', 'file'];

    #[ORM\Column(type: Types::INTEGER)]
    protected int $sourcetype = 0;

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
    protected File|UploadedFile|null $file = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $content = null;

    #[ORM\Column]
    protected ?int $size = null;

    #[ORM\Column(length: 255)]
    protected ?string $mime = null;

    #[ORM\Column(length: 255)]
    protected ?string $originalname = null;

    #[ORM\Column(length: 32)]
    protected ?string $paper = 'A4';

    #[ORM\Column(length: 32)]
    protected ?string $orientation = 'portrait';


    public function __toString(): string
    {
        return $this->filename ?? parent::__toString();
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
            $dest = $filesystem->tempnam(dir: $this->__estatus->appWire->getTempDir(), prefix: pathinfo($this->file->getFilename(), PATHINFO_FILENAME).'_', suffix: '.'.pathinfo($this->file->getFilename(), PATHINFO_EXTENSION));
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

    public function getFile(): File|UploadedFile|null
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
        return $this->__estatus->wireEntityManager->getBrowserPath($this, $filter, $runtimeConfig, $resolver, $referenceType);
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;
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

    public function getPaper(): ?string
    {
        return $this->paper;
    }

    public function setPaper(?string $paper): static
    {
        $papers = static::PAPERS;
        $this->paper = in_array($paper, static::PAPERS) ? $paper : reset($papers);
        return $this;
    }

    public static function getPaperChoices(): array
    {
        return array_combine(static::PAPERS, static::PAPERS);
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

    public function isPdfExportable(): bool
    {
        return $this->isActive();
    }

    public function getPdfUrlAccess(
        ?int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL,
        string $action = 'inline'
    ): ?string
    {
        return $this->__estatus->appWire->get('router')->generate('output_pdf_action', ['action' => $action, 'pdf' => $this->getSlug()], $referenceType ?? UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getSourcetype(): int
    {
        return $this->sourcetype;
    }

    public function getSourcetypeName(): string
    {
        return static::SOURCETYPES[$this->sourcetype] ?? 'undefined';
    }

    public function setSourcetype(int|string $sourcetype): static
    {
        // Can not change sourcetype if already set
        // if(!empty($this->getId())) return $this;

        $sourcetypes = static::SOURCETYPES;
        if(in_array($sourcetype, $sourcetypes)) {
            // got name
            $this->sourcetype = array_search($sourcetype, $sourcetypes);
        } else if(array_key_exists($sourcetype, $sourcetypes)) {
            // got key
            $this->sourcetype = $sourcetype;
        } else {
            // default
            $this->sourcetype = reset($sourcetypes);
        }
        return $this;
    }

    public static function getSourcetypeChoices(): array
    {
        return array_flip(static::SOURCETYPES);
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function setOrientation(?string $orientation): static
    {
        $orients = static::ORIENTATIONS;
        $this->orientation = in_array($orientation, $orients) ? $orientation : reset($orients);
        return $this;
    }

    public static function getOrientationChoices(): array
    {
        return array_combine(static::ORIENTATIONS, static::ORIENTATIONS);
    }

}
