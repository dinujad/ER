<?php

namespace App\Services\Accounting\Ai;

interface AiProviderInterface
{
    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     * @param  array<string, mixed>  $options
     * @return array{content:string, tool_calls:?array, prompt_tokens:int, completion_tokens:int, provider:string, model:?string, raw?:mixed}
     */
    public function chat(array $messages, array $options = []): array;

    public function name(): string;

    public function isAvailable(): bool;
}
