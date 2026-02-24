<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Hyva\Ai\Api\ProviderInterface;
use Hyva\Ai\Api\ProviderPoolInterface;
use Magento\Framework\Exception\LocalizedException;

class ProviderPool implements ProviderPoolInterface
{
    /**
     * @param ProviderInterface[] $providers
     */
    public function __construct(
        private readonly array $providers = []
    ) {
    }

    public function get(string $providerName): ProviderInterface
    {
        if (!$this->has($providerName)) {
            throw new LocalizedException(__('AI provider "%1" is not available.', $providerName));
        }

        return $this->providers[$providerName];
    }

    public function has(string $providerName): bool
    {
        return isset($this->providers[$providerName]);
    }

    public function getAll(): array
    {
        return $this->providers;
    }
}
