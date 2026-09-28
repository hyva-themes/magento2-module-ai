<?php
declare(strict_types=1);

namespace Hyva\Ai\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FeatureExecution implements OptionSourceInterface
{
    public function __construct(private readonly ExecutionMode $modes, private readonly bool $allowPhp = true)
    {
    }

    public function toOptionArray(): array
    {
        return [
            ['value' => 'default', 'label' => __('Use default (Settings)')],
            ...array_values(array_filter($this->modes->toOptionArray(),
                fn (array $option): bool => $option['value'] !== 'legacy' && ($this->allowPhp || $option['value'] !== 'php'))),
        ];
    }
}
