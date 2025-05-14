<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Enums;

enum ModelFeature: string
{
    case StructuredOutput = 'structured_output';
    case JsonOutput = 'json_output';
    case ToolCalling = 'tool_calling';
    case Vision = 'vision';
    case AudioInput = 'audio_input';
    case AudioOutput = 'audio_output';
    case ToolChoice = 'tool_choice';
    case PromptCaching = 'prompt_caching';
    case Reasoning = 'reasoning';
    case WebSearch = 'web_search';
}
