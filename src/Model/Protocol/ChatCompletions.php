<?php
declare(strict_types=1);

namespace Hyva\Ai\Model\Protocol;

use Hyva\Ai\Model\Provider\GenerationState;

class ChatCompletions
{
    public function messages(array $request): array
    {
        $messages = [];

        foreach ($request['messages'] as $message) {
            $role = (string) ($message['role'] ?? 'user');

            if ($role === 'tool') {
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) ($message['tool_call_id'] ?? ''),
                    'content' => (string) ($message['content'] ?? ''),
                ];
                continue;
            }

            if ($role === 'assistant' && !empty($message['tool_calls'])) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => ($message['content'] ?? '') !== '' ? (string) $message['content'] : null,
                    'tool_calls' => array_map(
                        static fn (array $call): array => [
                            'id' => (string) ($call['id'] ?? ''),
                            'type' => 'function',
                            'function' => [
                                'name' => (string) ($call['name'] ?? ''),
                                'arguments' => (string) json_encode(
                                    $call['arguments'] ?? new \stdClass(),
                                    JSON_UNESCAPED_SLASHES
                                ),
                            ],
                        ],
                        array_values((array) $message['tool_calls'])
                    ),
                ];
                continue;
            }

            $messages[] = ['role' => $role, 'content' => (string) ($message['content'] ?? '')];
        }

        if (($request['system'] ?? '') !== '') {
            array_unshift($messages, ['role' => 'system', 'content' => (string) $request['system']]);
        }

        return $messages;
    }

    public function tools(array $tools): array
    {
        return array_map(
            fn (array $tool): array => [
                'type' => 'function',
                'function' => [
                    'name' => (string) ($tool['name'] ?? ''),
                    'description' => (string) ($tool['description'] ?? ''),
                    'parameters' => ToolSchema::fromTool($tool),
                ],
            ],
            array_values($tools)
        );
    }

    public function parse(string $data, GenerationState $state): ?array
    {
        if ($data === '[DONE]') {
            return ['type' => 'done'];
        }

        $event = json_decode($data, true);
        if (!is_array($event)) {
            return ['type' => 'error', 'message' => 'The provider returned an invalid stream frame.'];
        }

        if (isset($event['error'])) {
            return [
                'type' => 'error',
                'message' => (string) ($event['error']['message'] ?? 'provider error'),
            ];
        }

        if (isset($event['usage']) && is_array($event['usage'])) {
            $state->inputTokens = (int) ($event['usage']['prompt_tokens'] ?? $state->inputTokens);
            $state->outputTokens = (int) ($event['usage']['completion_tokens'] ?? $state->outputTokens);
        }

        $choice = $event['choices'][0] ?? [];

        // The finish reason rides a chunk whose delta is empty, so capture it before the text
        // check below returns null for that chunk.
        if (($choice['finish_reason'] ?? null) !== null && $choice['finish_reason'] !== '') {
            $state->finishReason = (string) $choice['finish_reason'];
        }

        // Tool calls arrive as indexed fragments: the first carries id and name, the rest append to
        // the argument string. The index is the caller's slot, not the arrival order.
        foreach ((array) ($choice['delta']['tool_calls'] ?? []) as $delta) {
            if (!is_array($delta)) {
                continue;
            }
            $index = (int) ($delta['index'] ?? 0);
            $state->openToolCall(
                $index,
                (string) ($delta['id'] ?? ''),
                (string) ($delta['function']['name'] ?? '')
            );
            if (($delta['function']['arguments'] ?? '') !== '') {
                $state->appendToolArguments($index, (string) $delta['function']['arguments']);
            }
        }

        $text = (string) ($choice['delta']['content'] ?? '');

        return $text !== '' ? ['type' => 'text', 'text' => $text] : null;
    }
}
