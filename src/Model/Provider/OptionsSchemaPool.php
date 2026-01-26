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
use Hyva\Ai\Api\Provider\OptionsSchemaPoolInterface;

/**
 * Pool for aggregating provider option schemas
 */
class OptionsSchemaPool implements OptionsSchemaPoolInterface
{
    /**
     * @var OptionsSchemaInterface[]
     */
    private array $schemas = [];

    /**
     * @param OptionsSchemaInterface[] $schemas
     */
    public function __construct(
        array $schemas = []
    ) {
        foreach ($schemas as $schema) {
            if ($schema instanceof OptionsSchemaInterface) {
                $this->schemas[$schema->getProviderId()] = $schema;
            }
        }
    }

    public function getSchema(string $providerId): ?OptionsSchemaInterface
    {
        return $this->schemas[$providerId] ?? null;
    }

    public function getAllSchemas(): array
    {
        return $this->schemas;
    }

    public function hasSchema(string $providerId): bool
    {
        return isset($this->schemas[$providerId]);
    }
}
