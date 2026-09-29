<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Api;

use Hyva\Ai\Model\Provider\GenerationState;

/** Provider-owned request encoding, model validation and SSE parsing. */
interface StreamDialectInterface
{
    /** @return array{code:string,message:string,status:int}|null */
    public function validateRequest(array $request): ?array;

    /**
     * @param string $model
     * @return string
     */
    public function endpoint(string $model): string;

    /**
     * Headers in curl's own `Name: value` form.
     *
     * @param string $apiKey
     * @return array<int, string>
     */
    public function headers(string $apiKey): array;

    /**
     * The provider request body, as an array ready for json_encode.
     *
     * @param array<string, mixed> $request the neutral request
     * @return array<string, mixed>
     */
    public function buildBody(array $request): array;

    /**
     * Reads one SSE `data:` payload.
     *
     * Returns null when the payload was bookkeeping only — usage, a finish reason, a tool-call
     * fragment — all of which are recorded on the state instead. Otherwise one of:
     *   ['type' => 'text',  'text' => string]
     *   ['type' => 'done']
     *   ['type' => 'error', 'message' => string]
     *
     * @param string $data
     * @param GenerationState $state
     * @return array<string, mixed>|null
     */
    public function parse(string $data, GenerationState $state): ?array;
}
