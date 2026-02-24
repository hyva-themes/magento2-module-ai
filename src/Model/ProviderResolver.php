<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Hyva\Ai\Api\ProviderResolverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Data\OptionSourceInterface;

class ProviderResolver implements ProviderResolverInterface
{
    public function __construct(
        private readonly array $providerModels = []
    ) {
    }

    public function resolveProviderFromModel(string $model): string
    {
        $mapping = $this->getModelProviderMapping();
        $provider = $mapping[$model] ?? null;

        if (!$provider) {
            throw new LocalizedException(__('Could not determine provider for model "%1".', $model));
        }

        return $provider;
    }

    public function getAvailableModelOptions(): array
    {
        $options = [];

        foreach ($this->providerModels as $providerConfig) {
            $sourceModelClass = $providerConfig['source_model'] ?? null;
            $modelOptions = $providerConfig['options'] ?? [];

            if ($sourceModelClass && class_exists($sourceModelClass)) {
                try {
                    $sourceModel = new $sourceModelClass($modelOptions);
                    if ($sourceModel instanceof OptionSourceInterface) {
                        $providerOptions = $sourceModel->toOptionArray();

                        foreach ($providerOptions as $option) {
                            $options[] = [
                                'value' => $option['value'],
                                'label' => $option['label'],
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $options;
    }

    public function getProviderConfigurations(): array
    {
        return $this->providerModels;
    }

    /**
     * Get mapping of model values to their providers
     */
    private function getModelProviderMapping(): array
    {
        $mapping = [];

        foreach ($this->providerModels as $provider => $providerConfig) {
            $sourceModelClass = $providerConfig['source_model'] ?? null;
            $modelOptions = $providerConfig['options'] ?? [];

            if ($sourceModelClass && class_exists($sourceModelClass)) {
                try {
                    $sourceModel = new $sourceModelClass($modelOptions);
                    if ($sourceModel instanceof OptionSourceInterface) {
                        $providerOptions = $sourceModel->toOptionArray();

                        foreach ($providerOptions as $option) {
                            $mapping[$option['value']] = $provider;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $mapping;
    }
}
