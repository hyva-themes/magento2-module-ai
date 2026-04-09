<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\Ai\Model\Provider;

use Hyva\Ai\Api\Provider\OptionsSchemaInterface;

/**
 * Abstract base class for provider option schemas
 *
 * Provides default implementation of normalizeOptions() that converts
 * configuration values to their correct PHP types based on field definitions.
 */
abstract class AbstractOptionsSchema implements OptionsSchemaInterface
{
    public function normalizeOptions(array $options): array
    {
        $fields = $this->getFields();
        $fieldDefs = [];

        foreach ($fields as $field) {
            if (isset($field['id'], $field['type'])) {
                $fieldDefs[$field['id']] = $field;
            }
        }

        $normalized = [];
        foreach ($options as $key => $value) {
            if (!isset($fieldDefs[$key])) {
                $normalized[$key] = $value;
                continue;
            }

            $normalized[$key] = $this->normalizeValue($value, $fieldDefs[$key]);
        }

        return array_filter($normalized, fn($val) => $val !== null);
    }

    /**
     * Normalize a single value based on its field definition
     */
    protected function normalizeValue(mixed $value, array $field): mixed
    {
        $type = $field['type'];

        return match ($type) {
            'number' => $this->normalizeNumber($value, $field),
            'boolean' => is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'select', 'text' => is_string($value) ? $value : (string) $value,
            default => $value,
        };
    }

    /**
     * Normalize a number value, using default value type to determine int vs float
     */
    protected function normalizeNumber(mixed $value, array $field): int|float|null
    {
        if (!is_numeric($value)) {
            return null;
        }

        // Use the default value's type to determine if this should be int or float
        $default = $field['default'] ?? null;
        if (is_int($default)) {
            return (int) $value;
        }

        return (float) $value;
    }
}
