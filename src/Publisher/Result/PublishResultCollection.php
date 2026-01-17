<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Publisher\Result;

use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use IteratorAggregate;
use Traversable;
use ArrayIterator;

/**
 * Collection of publish results from multiple providers.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 * @implements IteratorAggregate<string, PublishResult>
 */
final readonly class PublishResultCollection implements IteratorAggregate
{
    /**
     * @param array<string, PublishResult> $results Keyed by provider name
     */
    public function __construct(
        private array $results = [],
    ) {
    }

    /**
     * @return Traversable<string, PublishResult>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }

    /**
     * Get result for specific provider.
     */
    public function getResult(string $providerName): ?PublishResult
    {
        return $this->results[$providerName] ?? null;
    }

    /**
     * Get all results.
     *
     * @return array<string, PublishResult>
     */
    public function getAll(): array
    {
        return $this->results;
    }

    /**
     * Get only successful results.
     *
     * @return array<string, PublishResult>
     */
    public function getSuccessful(): array
    {
        return array_filter($this->results, fn(PublishResult $result) => $result->isSuccess());
    }

    /**
     * Get only failed results.
     *
     * @return array<string, PublishResult>
     */
    public function getFailed(): array
    {
        return array_filter($this->results, fn(PublishResult $result) => $result->isFailure());
    }

    /**
     * Check if all publications were successful.
     */
    public function isAllSuccessful(): bool
    {
        if (empty($this->results)) {
            return false;
        }

        foreach ($this->results as $result) {
            if ($result->isFailure()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any publication was successful.
     */
    public function hasAnySuccessful(): bool
    {
        foreach ($this->results as $result) {
            if ($result->isSuccess()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get count of total results.
     */
    public function count(): int
    {
        return count($this->results);
    }
}
