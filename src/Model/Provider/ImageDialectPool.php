<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model\Provider;

use Hyva\Ai\Api\ImageDialectInterface;

class ImageDialectPool
{
    /** @param array<string, ImageDialectInterface> $dialects */
    public function __construct(private readonly array $dialects = [])
    {
    }

    public function has(string $provider): bool
    {
        return isset($this->dialects[$provider]);
    }

    public function get(string $provider): ImageDialectInterface
    {
        if (!$this->has($provider)) {
            throw new \InvalidArgumentException('No provider dialect is registered for "' . $provider . '".');
        }
        return $this->dialects[$provider];
    }

    /** @return string[] */
    public function names(): array
    {
        return array_keys($this->dialects);
    }
}
