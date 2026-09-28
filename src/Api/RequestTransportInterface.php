<?php
declare(strict_types=1);

namespace Hyva\Ai\Api;

interface RequestTransportInterface
{
    public function supports(string $provider): bool;

    public function process(string $provider, array $data, array $options = []): array;
}
