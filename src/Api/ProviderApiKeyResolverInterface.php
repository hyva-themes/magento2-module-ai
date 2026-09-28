<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Api;

/** Resolves credentials owned by a provider package for use by shared AI transports. */
interface ProviderApiKeyResolverInterface
{
    /**
     * The decrypted API key, or an empty string if not configured.
     */
    public function getApiKey($store = null): string;

    /**
     * Whether this provider has a usable key configured.
     */
    public function isConfigured($store = null): bool;
}
