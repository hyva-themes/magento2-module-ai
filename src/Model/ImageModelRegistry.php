<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model;

class ImageModelRegistry
{
    public function __construct(private readonly array $models = [])
    {
    }

    public function modelToProvider(string $model): string
    {
        return (string) ($this->models[$model]['provider'] ?? '');
    }

    public function getChoices(): array
    {
        $choices = [];
        foreach ($this->models as $model => $definition) {
            unset($definition['current']);
            $choices[] = ['model' => $model] + $definition;
        }
        return $choices;
    }

    public function isCurrent(string $model): bool
    {
        return (bool) ($this->models[$model]['current'] ?? true);
    }
}
