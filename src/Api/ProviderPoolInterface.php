<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\Ai\Api;

use Magento\Framework\Exception\LocalizedException;

interface ProviderPoolInterface
{
    /**
     * Get a provider by name
     *
     * @param string $providerName
     * @return ProviderInterface
     * @throws LocalizedException
     */
    public function get(string $providerName): ProviderInterface;

    /**
     * Check if a provider exists
     *
     * @param string $providerName
     * @return bool
     */
    public function has(string $providerName): bool;

    /**
     * Get all registered providers
     *
     * @return ProviderInterface[]
     */
    public function getAll(): array;
}
