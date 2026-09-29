<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model\Provider;

/** Neutral stream metadata and accumulated tool-call fragments. */
class GenerationState
{
    /** A complete result may end at EOF without a separate done frame. */
    public bool $complete = false;

    /** @var int */
    public int $inputTokens = 0;

    /** @var int */
    public int $outputTokens = 0;

    /** @var string */
    public string $finishReason = '';

    /**
     * Tool calls under the provider's own slot index, NOT arrival order — OpenAI numbers the
     * caller's slots explicitly and fragments may interleave.
     *
     * @var array<int, array{id: string, name: string, argumentsText: string}>
     */
    public array $toolCalls = [];

    /**
     * Gemini mints no call ids, so this worker invents them. Scoped per generation because a bare
     * counter repeats across turns: turn two's first call would collide with turn one's in any map
     * the feature keys by id.
     *
     * @var string
     */
    public string $toolIdPrefix = '';

    /**
     * @param string $toolIdPrefix
     */
    public function __construct(string $toolIdPrefix = '')
    {
        $this->toolIdPrefix = $toolIdPrefix;
    }

    /**
     * Opens a tool-call slot, or returns the one already there.
     *
     * @param int $index
     * @param string $id
     * @param string $name
     * @return void
     */
    public function openToolCall(int $index, string $id = '', string $name = ''): void
    {
        if (!isset($this->toolCalls[$index])) {
            $this->toolCalls[$index] = ['id' => $id, 'name' => $name, 'argumentsText' => ''];

            return;
        }

        if ($id !== '') {
            $this->toolCalls[$index]['id'] = $id;
        }
        if ($name !== '') {
            $this->toolCalls[$index]['name'] = $name;
        }
    }

    /**
     * @param int $index
     * @param string $fragment
     * @return void
     */
    public function appendToolArguments(int $index, string $fragment): void
    {
        if (isset($this->toolCalls[$index])) {
            $this->toolCalls[$index]['argumentsText'] .= $fragment;
        }
    }

    /**
     * The neutral tool_calls shape, arguments parsed.
     *
     * Fragments only add up to valid JSON once the stream ends, and anything that still will not
     * parse is surfaced in an _unparsed object rather than dropped: the feature validates before it
     * executes, and silently losing a call the model asked for would be the worse failure.
     *
     * @return array<int, array{id: string, name: string, arguments: mixed}>
     */
    public function finalisedToolCalls(): array
    {
        $calls = [];
        ksort($this->toolCalls);

        foreach ($this->toolCalls as $call) {
            if (trim($call['name']) === '') {
                continue;
            }

            $arguments = json_decode($call['argumentsText'] === '' ? '{}' : $call['argumentsText']);
            $calls[] = [
                'id' => $call['id'],
                'name' => $call['name'],
                'arguments' => json_last_error() === JSON_ERROR_NONE
                    ? $arguments : ['_unparsed' => $call['argumentsText']],
            ];
        }

        return $calls;
    }
}
