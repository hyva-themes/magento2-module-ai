<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Optional capability for Hyva_Ai providers whose backing API supports native
 * tool / function calling. Consumers should detect this interface on a provider
 * instance and prefer it over prompt-based tool protocols.
 */
interface ToolCapableProviderInterface
{
    /**
     * Run one model turn with native tool calling.
     *
     * Messages use a provider-neutral format:
     *  - ['role' => 'system'|'user'|'assistant', 'content' => string]
     *  - ['role' => 'assistant', 'content' => string, 'tool_calls' =>
     *        [['id' => string, 'name' => string, 'arguments' => array], ...]]
     *  - ['role' => 'tool', 'tool_call_id' => string, 'content' => string]
     *
     * Tool definitions: [['name' => string, 'description' => string,
     *   'parameters' => <JSON schema object as array>], ...]
     *
     * Returns:
     *  ['text' => string,
     *   'tool_calls' => [['id' => string, 'name' => string, 'arguments' => array], ...],
     *   'stop_reason' => string|null]
     *
     * @param array $messages
     * @param array $tools
     * @param array $options Provider options (model, max_tokens, ...)
     * @return array
     * @throws LocalizedException
     */
    public function chatWithTools(array $messages, array $tools, array $options = []): array;
}
