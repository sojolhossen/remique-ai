<?php

namespace App\Services\AI;

use Carbon\Carbon;

class LocalReminderParser
{
    /**
     * Parse natural language reminder or intent locally without network calls.
     *
     * @param string $text Raw input text
     * @param string $timezone User timezone
     * @param Carbon|null $referenceNow Reference time for testing or context
     * @return array|null Parsed data matching ReminderInterpretationResult schema or null
     */
    public static function parse(string $text, string $timezone = 'Asia/Dhaka', ?Carbon $referenceNow = null): ?array
    {
        $now = $referenceNow ? $referenceNow->copy()->setTimezone($timezone) : Carbon::now($timezone);
        $trimmed = trim($text);

        if (empty($trimmed)) {
            return null;
        }

        // Convert Bengali digits to English digits
        $bengaliDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $normalized = str_replace($bengaliDigits, $englishDigits, $trimmed);

        $isBengaliScript = (bool) preg_match('/[\x{0980}-\x{09FF}]/u', $trimmed);
        $lang = $isBengaliScript ? 'bengali' : 'banglish';

        // 1. Check for list reminders intent
        if (preg_match('/(?:reminder\s*list|list\s*reminders?|রিমাইন্ডার\s*লিস্ট|রিমাইন্ডারগুলো|কতো\s*সময়\s*বাকি|কত\s*সময়\s*বাকি|koto\s*(?:time|somoy)\s*baki|ar\s*koto\s*baki|amar\s*reminder|আমার\s*রিমাইন্ডার)/iu', $normalized)) {
            return [
                'intent' => 'list_reminders',
                'title' => null,
                'description' => null,
                'date' => null,
                'time' => null,
                'priority' => 'medium',
                'recurrence' => 'none',
                'detected_language' => $lang,
                'has_missing_info' => false,
                'missing_fields' => [],
                'confidence' => 0.95,
                'conversational_reply' => $isBengaliScript ? 'আপনার আসন্ন রিমাইন্ডার তালিকা:' : 'Apnar upcoming reminder list:',
            ];
        }

        // 2. Extract date
        $targetDate = $now->copy();
        $dateExplicit = false;

        if (preg_match('/(আগামীকাল|কাল|tomorrow|kal(?:ke)?)/iu', $normalized)) {
            $targetDate->addDay();
            $dateExplicit = true;
        } elseif (preg_match('/(পরশু|day after tomorrow|porshu)/iu', $normalized)) {
            $targetDate->addDays(2);
            $dateExplicit = true;
        } elseif (preg_match('/(আজ|আজকে|today|ajke)/iu', $normalized)) {
            $dateExplicit = true;
        }

        // 3. Extract time
        $hour = null;
        $minute = 0;
        $isPm = null;

        // Check time of day indicators
        if (preg_match('/(সকাল|ভোর|shokal|vhor|morning|am)/iu', $normalized)) {
            $isPm = false;
        } elseif (preg_match('/(রাত|সন্ধ্যা|বিকাল|দুপুর|rat|shondha|bikal|dupur|night|evening|pm)/iu', $normalized)) {
            $isPm = true;
        }

        // Match HH:MM (e.g., 8:40, 08.40)
        if (preg_match('/(\d{1,2})[:.](\d{2})/u', $normalized, $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
        } elseif (preg_match('/(\d{1,2})\s*(?:টা|টায়|tay|ta|am|pm|ঘণ্টায়|ঘন্টায়|hr|hours?)/iu', $normalized, $matches)) {
            $hour = (int) $matches[1];
        } elseif (preg_match('/(?:at|সময়|somoy)\s*(\d{1,2})/iu', $normalized, $matches)) {
            $hour = (int) $matches[1];
        }

        if ($hour === null || $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null; // No valid time could be inferred
        }

        // Adjust 12-hour format if PM indicator found
        if ($isPm === true && $hour < 12) {
            $hour += 12;
        } elseif ($isPm === false && $hour === 12) {
            $hour = 0;
        }

        $timeString = sprintf('%02d:%02d:00', $hour, $minute);

        // If no explicit date given, roll to tomorrow if the time has already passed today
        if (! $dateExplicit) {
            $candidateDateTime = Carbon::parse("{$targetDate->toDateString()} {$timeString}", $timezone);
            if ($candidateDateTime->lessThanOrEqualTo($now)) {
                $targetDate->addDay();
            }
        }

        // 4. Extract title: strip trigger and time phrases
        $title = $trimmed;
        $boilerplate = [
            '/[০-৯0-9]{1,2}[:.][০-৯0-9]{2}\s*(?:মিনিটে|এ|pm|am)?/iu',
            '/[০-৯0-9]{1,2}\s*(?:টা|টায়|tay|ta|am|pm|ঘণ্টায়|ঘন্টায়)/iu',
            '/(?:সকাল|রাত|সন্ধ্যা|বিকাল|দুপুর|আজ|আজকে|কাল|আগামীকাল|পরশু|shokal|rat|shondha|bikal|dupur|today|tomorrow|kal|porshu)/iu',
            '/(?:আমাকে\s*)?মনে\s*করিয়ে\s*(?:দিবে|দিও|দাও|দিস)/iu',
            '/(?:remind\s*me\s*(?:to)?|set\s*reminder\s*(?:for)?)/iu',
            '/(?:ঠিক\s*আছে|thik\s*ache|okay|ok)\s*$/iu',
            '/^(?:ঠিক\s*আছে|thik\s*ache|okay|ok)\s*,?\s*/iu',
        ];

        foreach ($boilerplate as $pattern) {
            $title = preg_replace($pattern, ' ', $title);
        }

        $title = trim(preg_replace('/\s+/', ' ', $title));
        if (empty($title) || in_array(mb_strtolower($title), ['ঠিক আছে', 'thik ache', 'ok', 'okay', 'reminder'])) {
            $title = $isBengaliScript ? 'রিমাইন্ডার' : 'Reminder';
        }

        $conversationalReply = $isBengaliScript
            ? "ঠিক আছে, আমি নির্দিষ্ট সময়ে আপনাকে রিমাইন্ডার দেব।"
            : "Thik ache, shomoy moto apnake reminder dewa hobe.";

        return [
            'intent' => 'create_reminder',
            'title' => $title,
            'description' => null,
            'date' => $targetDate->toDateString(),
            'time' => $timeString,
            'priority' => 'medium',
            'recurrence' => 'none',
            'target_reminder_identifier' => null,
            'search_query' => null,
            'file_type' => null,
            'detected_language' => $lang,
            'has_missing_info' => false,
            'missing_fields' => [],
            'confidence' => 0.90,
            'conversational_reply' => $conversationalReply,
        ];
    }
}
