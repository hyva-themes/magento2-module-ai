<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Api;

interface ProviderInterface
{
    /**
     * Process data using the AI provider
     */
    public function process(array $data, array $options = []): array;

    /**
     * Check if the provider is properly configured
     */
    public function isConfigured(): bool;

    /**
     * Get provider name/identifier
     */
    public function getName(): string;
}
