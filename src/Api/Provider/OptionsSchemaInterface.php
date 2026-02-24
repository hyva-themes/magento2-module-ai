<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Api\Provider;

/**
 * Interface for declaring provider-specific configuration options
 *
 * Each AI provider can implement this interface to declare what configuration
 * options it supports. This allows for a decoupled architecture where provider
 * modules don't need to be hard dependencies.
 */
interface OptionsSchemaInterface
{
    /**
     * Get the provider identifier this schema is for
     *
     * Should match the provider name (e.g., 'openai', 'deepl', 'gemini')
     *
     * @return string
     */
    public function getProviderId(): string;

    /**
     * Get the option fields schema
     *
     * Returns an array of field definitions. Each field should have:
     * - id: Field identifier (string)
     * - type: Field type (text, select, number, boolean)
     * - label: Display label (string)
     * - comment: Help text/description (string, optional)
     * - default: Default value (mixed, optional)
     * - validate: Validation rules (string, optional)
     * - options: Array of options for select fields (array, optional)
     *   - Each option: ['value' => '', 'label' => '']
     * - required: Whether field is required (bool, optional)
     *
     * Example:
     * [
     *     [
     *         'id' => 'temperature',
     *         'type' => 'number',
     *         'label' => 'Temperature',
     *         'comment' => 'Controls randomness (0.0 - 2.0)',
     *         'default' => 0.3,
     *         'validate' => 'validate-number validate-number-range number-range-0-2',
     *         'required' => false
     *     ]
     * ]
     *
     * @return array
     */
    public function getFields(): array;

    /**
     * Normalize option values to their correct types based on field definitions
     *
     * Converts string values from configuration to their appropriate PHP types
     * (e.g., "0.3" -> 0.3 for number fields, "true" -> true for boolean fields)
     *
     * @param array $options Raw options from configuration
     * @return array Normalized options with correct types
     */
    public function normalizeOptions(array $options): array;
}
