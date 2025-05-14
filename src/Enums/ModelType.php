<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Enums;

enum ModelType: string
{
    case Chat = 'chat';
    case Completion = 'completion';
    case Embedding = 'embedding';
    case ImageGeneration = 'image_generation';
    case TextToSpeech = 'text_to_speech';
    case SpeechToText = 'speech_to_text';
    case Moderation = 'moderation';
    case Other = 'other';
}
