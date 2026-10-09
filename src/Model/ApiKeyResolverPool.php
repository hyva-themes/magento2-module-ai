<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Hyva\Ai\Api\ProviderApiKeyResolverInterface;
use Magento\Framework\Exception\LocalizedException;

/** Provider packages register their credential resolver here. */
class ApiKeyResolverPool
{
    /**
     * @param ProviderApiKeyResolverInterface[] $resolvers
     */
    public function __construct(
        private readonly array $resolvers = []
    ) {
    }

    public function has(string $provider): bool
    {
        return isset($this->resolvers[$provider]);
    }

    /**
     * @throws LocalizedException when no resolver is registered for the provider
     */
    public function get(string $provider): ProviderApiKeyResolverInterface
    {
        if (!$this->has($provider)) {
            throw new LocalizedException(__('No API key resolver is registered for provider "%1".', $provider));
        }
        return $this->resolvers[$provider];
    }

    /**
     * @return string[] Provider ids with a resolver registered.
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->resolvers);
    }
}
