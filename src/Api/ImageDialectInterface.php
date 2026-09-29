<?php
declare(strict_types=1);

namespace Hyva\Ai\Api;

interface ImageDialectInterface
{
    /** @return array{url:string,headers:string[],body:array} */
    public function request(string $model, string $key, string $prompt, ?string $ratio): array;

    /** @return array{images:array,usage:array,reason?:string} */
    public function parseResponse(array $data): array;
}
