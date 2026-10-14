<?php

namespace App\Services\AI;

class ReminderInterpretationResult
{
    public function __construct(
        public bool $success = true,
        public string $intent = 'general_chat',
        public ?string $title = null,
        public ?string $description = null,
        public ?string $date = null, // YYYY-MM-DD
        public ?string $time = null, // HH:MM:SS
        public string $priority = 'medium', // low, medium, high, urgent
        public string $recurrence = 'none', // none, daily, weekly, monthly, yearly
        public string $detectedLanguage = 'bengali', // bengali, banglish, english, other
        public bool $hasMissingInfo = false,
        public array $missingFields = [],
        public ?string $clarificationQuestion = null,
        public float $confidence = 1.0,
        public ?string $errorMessage = null,
        public ?string $errorCategory = null,
        public int $durationMs = 0,
        public ?int $totalTokens = null,
        public ?string $searchQuery = null,
        public ?string $fileType = null,
        public ?string $targetReminderIdentifier = null,
        public ?string $conversationalReply = null,
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'intent' => $this->intent,
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date,
            'time' => $this->time,
            'priority' => $this->priority,
            'recurrence' => $this->recurrence,
            'detected_language' => $this->detectedLanguage,
            'has_missing_info' => $this->hasMissingInfo,
            'missing_fields' => $this->missingFields,
            'clarification_question' => $this->clarificationQuestion,
            'confidence' => $this->confidence,
            'error_message' => $this->errorMessage,
            'error_category' => $this->errorCategory,
            'duration_ms' => $this->durationMs,
            'total_tokens' => $this->totalTokens,
            'search_query' => $this->searchQuery,
            'file_type' => $this->fileType,
            'target_reminder_identifier' => $this->targetReminderIdentifier,
            'conversational_reply' => $this->conversationalReply,
        ];
    }
}
