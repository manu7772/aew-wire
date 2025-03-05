<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Aequation\WireBundle\Repository\WireImageRepository;
use Aequation\WireBundle\Service\interface\WireImageServiceInterface;
use Aequation\WireBundle\Tools\HttpRequest;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Gedmo\Mapping\Annotation as Gedmo;
// PHP
use Exception;

#[Vich\Uploadable]
abstract class WireImage extends WireItem implements WireImageInterface
{

    public const ICON = [
        'ux' => 'tabler:photo',
        'fa' => 'fa-solid fa-camera'
    ];
    // public const SERIALIZATION_PROPS = ['id','euid','name','file','filename','size','mime','classname','shortname'];
    public const DEFAULT_LIIP_FILTER = "photo_q";
    public const THUMBNAIL_LIIP_FILTER = 'miniature_q';

    // #[Assert\NotNull(message: 'Le nom de fichier ne peut être null')]
    #[ORM\Column()]
    protected ?string $filename = null;

    #[Vich\UploadableField(mapping: '@vichmapping', fileNameProperty: 'filename', size: 'size', mimeType: 'mime', originalName: 'originalname', dimensions: 'dimensions')]
    #[Assert\File(
        maxSize: '12M',
        maxSizeMessage: 'Le fichier ne peut pas dépasser la taille de {{ limit }}{{ suffix }} : votre fichier fait {{ size }}{{ suffix }}',
        mimeTypes: ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"],
        mimeTypesMessage: "Format invalide. Formats valides : JPEG, PNG, GIF, WEBP"
    )]
    protected File|UploadedFile|null $file = null;

    #[ORM\Column()]
    protected string $vichmapping = 'photo';

    #[ORM\Column]
    protected ?int $size = null;

    #[ORM\Column()]
    protected ?string $mime = null;

    #[ORM\Column()]
    protected ?string $originalname = null;

    #[ORM\Column()]
    protected ?string $dimensions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $description = null;

    protected bool $deleteImage = false;
    protected ?string $liipDefaultFilter = null;

    public function __toString(): string
    {
        return $this->name ?? $this->filename ?? parent::__toString();
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
        $filter ??= $this->getLiipDefaultFilter();
        return $this->__estatus->wireEntityManager->getBrowserPath($this, $filter, $runtimeConfig, $resolver, $referenceType);
    }

    public function getLiipDefaultFilter(): string
    {
        return $this->liipDefaultFilter ??= static::DEFAULT_LIIP_FILTER;
    }

    public function setLiipDefaultFilter(
        string $liipDefaultFilter
    ): static
    {
        $this->liipDefaultFilter = $liipDefaultFilter;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onPersistOrUpdate(): static
    {
        $this->updateName();
        return $this;
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

    public function getVichmapping(): string
    {
        return $this->vichmapping;
    }

    public function setVichmapping(string $vichmapping): static
    {
        $this->vichmapping = $vichmapping;
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

    public function getDimensions(): ?string
    {
        return $this->dimensions;
    }

    public function setDimensions(mixed $dimensions): static
    {
        $this->dimensions = is_array($dimensions)
            ? implode('x',$dimensions)
            : (string)$dimensions;
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

    public function setDeleteImage(bool $deleteImage): static
    {
        $this->deleteImage = $deleteImage;
        $this->setUpdatedAt();
        return $this;
    }

    public function isDeleteImage(): bool
    {
        return $this->deleteImage;
    }

    // #[AppEvent(groups: FormEvents::PRE_SET_DATA)]
    // public function formEvent_preSetData(
    //     WireImageServiceInterface $service,
    //     array $data,
    //     ?string $group
    // ): void
    // {
    //     $event = $data['event'] ?? null;
    //     if($event instanceof FormEvent) {
    //         /** @var Form */
    //         $form = $event->getForm();
    //         if(!$form->isRoot() && !$form->isRequired()) {
    //             $event->getForm()->add(child: 'deleteImage', type: CheckboxType::class, options: [
    //                 'label' => 'Supprimer la photo',
    //                 'by_reference' => false,
    //             ]);
    //         }
    //     }
    // }

}