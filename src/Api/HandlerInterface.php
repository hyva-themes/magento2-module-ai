<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
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
