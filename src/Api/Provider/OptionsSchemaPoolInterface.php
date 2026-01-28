<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\Ai\Api\Provider;

/**
 * Pool interface for aggregating provider option schemas
 */
interface OptionsSchemaPoolInterface
{
    /**
     * Get option schema for a specific provider
     *
     * @param string $providerId Provider identifier (e.g., 'openai', 'deepl')
     * @return OptionsSchemaInterface|null
     */
    public function getSchema(string $providerId): ?OptionsSchemaInterface;

    /**
     * Get all registered option schemas
     *
     * @return OptionsSchemaInterface[] Array keyed by provider ID
     */
    public function getAllSchemas(): array;

    /**
     * Check if a provider has an option schema registered
     *
     * @param string $providerId
     * @return bool
     */
    public function hasSchema(string $providerId): bool;
}
