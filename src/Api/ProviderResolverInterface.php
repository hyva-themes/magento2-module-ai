<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
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
}