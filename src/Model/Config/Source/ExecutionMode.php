<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ExecutionMode implements OptionSourceInterface
{
    public function __construct(private readonly array $transports = [])
    {
    }

    public function toOptionArray(): array
    {
        $options = [
            ['value' => 'legacy', 'label' => __('Keep existing feature choices')],
            ['value' => 'php', 'label' => __('Direct PHP (short requests)')],
        ];
        foreach ($this->transports as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }
        return $options;
    }
}
