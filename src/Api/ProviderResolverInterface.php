<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Api;

interface ProviderResolverInterface
{
    /**
     * Resolve provider name from model identifier
     *
     * @param string $model
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolveProviderFromModel(string $model): string;

    /**
     * Get all available model options from all providers
     *
     * @return array
     */
    public function getAvailableModelOptions(): array;

    /**
     * Get all provider configurations
     * Returns array keyed by provider ID with their configuration
     *
     * @return array
     */
    public function getProviderConfigurations(): array;
}
