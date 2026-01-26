<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\Ai\Api;

/**
 * Interface for AI provider configuration defaults
 *
 * Provides default values for provider-specific settings like model,
 * temperature, and max tokens. Each AI provider module should implement
 * this interface with its specific defaults.
 */
interface ProviderConfigInterface
{
    /**
     * Get the provider identifier
     *
     * @return string Provider name (e.g., 'openai', 'gemini', 'deepl')
     */
    public function getProviderName(): string;

    /**
     * Get the default model for this provider
     *
     * @return string Default model identifier
     */
    public function getDefaultModel(): string;

    /**
     * Get the default temperature setting
     *
     * @return float Temperature value (typically 0.0 - 2.0)
     */
    public function getDefaultTemperature(): float;

    /**
     * Get the default max tokens setting
     *
     * @return int Maximum tokens for responses
     */
    public function getDefaultMaxTokens(): int;
}
