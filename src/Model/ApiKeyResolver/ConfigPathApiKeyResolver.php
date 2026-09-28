<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model\ApiKeyResolver;

use Hyva\Ai\Api\ProviderApiKeyResolverInterface;
use Hyva\Ai\Model\SecretReader;

/** Provider packages inject their encrypted configuration path and optional environment variable. */
class ConfigPathApiKeyResolver implements ProviderApiKeyResolverInterface
{
    public function __construct(
        private readonly SecretReader $secretReader,
        private readonly string $configPath,
        private readonly string $envVar
    ) {
    }

    public function getApiKey($store = null): string
    {
        return $this->secretReader->read($this->envVar, $this->configPath);
    }

    public function isConfigured($store = null): bool
    {
        return $this->getApiKey($store) !== '';
    }
}
