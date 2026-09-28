<?php
declare(strict_types=1);

namespace Hyva\Ai\Api;

interface ToolTransportInterface extends RequestTransportInterface
{
    public function chatWithTools(string $provider, array $messages, array $tools, array $options = []): array;
}
