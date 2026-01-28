<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\Ai\Api;

interface HandlerInterface
{
    /**
     * Process and prepare data for AI provider
     */
    public function getData(array $requestData): array;

    /**
     * Get options/configuration for the AI provider
     */
    public function getOptions(): array;

    /**
     * Get handler name/identifier
     */
    public function getName(): string;
}
