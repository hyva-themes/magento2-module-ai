<?php
declare(strict_types=1);

namespace Hyva\Ai\Plugin;

use Magento\Config\Model\Config\Structure\Element\Group;

class ProviderGroupLabel
{
    public function afterGetLabel(Group $subject, $label)
    {
        return str_starts_with($subject->getPath(), 'hyva_ai/')
            ? preg_replace('/ Configuration$/', '', (string) $label) : $label;
    }
}
