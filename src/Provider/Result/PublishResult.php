<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Result;

use Throwable;

/**
 * Result of a publish operation to a social network.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class PublishResult
{
    public function __construct(
        private string $providerName,
        private bool $success,
        private ?string $postId = null,
        private ?string $postUrl = null,
        private ?string $errorMessage = null,
        private ?Throwable $exception = null,
        private array $metadata = [],
    ) {
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getPostId(): ?string
    {
        return $this->postId;
    }

    public function getPostUrl(): ?string
    {
        return $this->postUrl;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public static function success(
        string $providerName,
        ?string $postId = null,
        ?string $postUrl = null,
        array $metadata = [],
    ): self {
        return new self(
            providerName: $providerName,
            success: true,
            postId: $postId,
            postUrl: $postUrl,
            metadata: $metadata,
        );
    }

    public static function failure(
        string $providerName,
        string $errorMessage,
        ?Throwable $exception = null,
        array $metadata = [],
    ): self {
        return new self(
            providerName: $providerName,
            success: false,
            errorMessage: $errorMessage,
            exception: $exception,
            metadata: $metadata,
        );
    }
}
