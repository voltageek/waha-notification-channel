<?php

namespace Cactus\Notifications\Channels\WAHA;

use Illuminate\Support\Facades\Storage;
use Cactus\Notifications\Channels\WAHA\Contracts\WhatsappSenderContract;
use Cactus\Notifications\Channels\WAHA\Enums\FileType;
use Cactus\Notifications\Channels\WAHA\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class WhatsappFile
 *
 * Handles file-based Whatsapp notifications with support for various file types
 * and content formats.
 */
class WhatsappFile extends WhatsappBase implements WhatsappSenderContract
{
    /** @var FileType The file content type */
    public FileType $type = FileType::Document;

    /** @var array File types that don't support captions */
    protected array $captionSupportedTypes = [
        FileType::Photo,
        FileType::Document,
    ];

    /**
     * Create a new WhatsappFile instance.
     */
    public function __construct(string $content = '')
    {
        parent::__construct();
        $this->content($content);
    }

    /**
     * Create a new instance of WhatsappFile.
     */
    public static function create(string $content = ''): self
    {
        return new self($content);
    }

    /**
     * Set notification caption for supported file types with markdown support.
     */
    public function content(string $content): self
    {
        $this->payload['caption'] = $content;

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param  resource|StreamInterface|string  $file  The file content or path
     * @param  FileType|string  $type  The file type
     * @param  string|null  $filename  Optional custom filename
     *
     * @throws CouldNotSendNotification
     */
    public function file(mixed $file, FileType|string $type, ?string $filename = null): self
    {
        $this->type = is_string($type) ? FileType::tryFrom($type) ?? FileType::Document : $type;
        $typeValue = $this->type->value;
        
        // // Handle file URLs or Whatsapp file IDs
        // if (is_string($file) && ! $this->isReadableFile($file) && $filename === null) {
        //     if (! filter_var($file, FILTER_VALIDATE_URL) && ! preg_match('/^[a-zA-Z0-9_-]+$/', $file)) {
        //         dump($file);
        //         throw CouldNotSendNotification::invalidFileIdentifier($file);
        //     }

        //     $this->payload[$typeValue] = $file;

        //     return $this;
        // }
        $mimeType = Storage::mimeType($file);
        $contents = Storage::get($file);
        
        $fileData = [];

        if ($mimeType != null) {
            $fileData['mimetype'] = $mimeType;
        }

        if ($contents != null) {
            $fileData['data'] = \Str::toBase64($contents);
        }

        if ($filename !== null) {
            $fileData['filename'] = $filename;
        }

        $this->payload['file'] = $fileData;

        return $this;
    }

    /**
     * Attach a photo.
     */
    public function photo(string $file): self
    {
        return $this->file($file, FileType::Photo);
    }

    /**
     * Attach an audio file.
     */
    public function audio(string $file): self
    {
        return $this->file($file, FileType::Audio);
    }

    /**
     * Attach a document file.
     */
    public function document(string $file, ?string $filename = null): self
    {
        return $this->file($file, FileType::Document, $filename);
    }

    /**
     * Attach a video file.
     */
    public function video(string $file): self
    {
        return $this->file($file, FileType::Video);
    }

    /**
     * Attach a voice message file.
     */
    public function voice(string $file): self
    {
        return $this->file($file, FileType::Voice);
    }

    /**
     * Attach a sticker.
     */
    public function sticker(string $file): self
    {
        return $this->file($file, FileType::Sticker);
    }

    /**
     * Check if the current file type supports captions.
     */
    protected function supportsCaptions(): bool
    {
        return  in_array($this->type, $this->captionSupportedTypes);
    }

    /**
     * Convert the notification to an array for API consumption.
     */
    public function toArray(): array
    {
        $payload = $this->payload;

        // Remove caption for unsupported file types
        if (! $this->supportsCaptions() && isset($payload['caption'])) {
            unset($payload['caption']);
        }

        return $payload;
    }

    /**
     * Send the notification through Whatsapp.
     *
     * @throws CouldNotSendNotification
     */
    public function send(): ?ResponseInterface
    {
        // Get the method endpoint based on file type
        $method = $this->type->value;

        return $this->whatsapp->sendFile(
            $this->toArray(),
            $method
        );
    }

    /**
     * Determine if it's a regular and readable file.
     */
    protected function isReadableFile(string $file): bool
    {
        return is_file($file) && is_readable($file);
    }
}
