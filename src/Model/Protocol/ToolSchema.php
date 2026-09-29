<?php
declare(strict_types=1);

namespace Hyva\Ai\Model\Protocol;

class ToolSchema
{
    public static function fromTool(array $tool): array
    {
        foreach (['parameters', 'input_schema'] as $key) {
            if (isset($tool[$key]) && is_array($tool[$key]) && $tool[$key] !== []) {
                return $tool[$key];
            }
        }

        return ['type' => 'object', 'properties' => new \stdClass()];
    }
}
