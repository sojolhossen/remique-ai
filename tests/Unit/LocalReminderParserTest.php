<?php

namespace Tests\Unit;

use App\Services\AI\LocalReminderParser;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class LocalReminderParserTest extends TestCase
{
    public function test_parses_bengali_numerals_and_time(): void
    {
        $ref = Carbon::parse('2026-10-15 08:00:00', 'Asia/Dhaka');
        $text = '৮:৪০ মিনিটে আমাকে মনে করিয়ে দিবে ক্লায়েন্টের একটা ঠিক করে দিতে হবে ঠিক আছে';

        $result = LocalReminderParser::parse($text, 'Asia/Dhaka', $ref);

        $this->assertNotNull($result);
        $this->assertEquals('create_reminder', $result['intent']);
        $this->assertEquals('08:40:00', $result['time']);
        $this->assertEquals('2026-10-15', $result['date']);
        $this->assertEquals('ক্লায়েন্টের একটা ঠিক করে দিতে হবে', $result['title']);
        $this->assertEquals('bengali', $result['detected_language']);
    }

    public function test_parses_banglish_reminder_with_tomorrow(): void
    {
        $ref = Carbon::parse('2026-10-15 14:00:00', 'Asia/Dhaka');
        $text = 'kal shokal 10 tay doctor appointment reminder dio';

        $result = LocalReminderParser::parse($text, 'Asia/Dhaka', $ref);

        $this->assertNotNull($result);
        $this->assertEquals('create_reminder', $result['intent']);
        $this->assertEquals('10:00:00', $result['time']);
        $this->assertEquals('2026-10-16', $result['date']);
        $this->assertStringContainsString('doctor appointment', $result['title']);
    }

    public function test_parses_list_reminders_intent(): void
    {
        $text = 'reminder list dekhao';
        $result = LocalReminderParser::parse($text, 'Asia/Dhaka');

        $this->assertNotNull($result);
        $this->assertEquals('list_reminders', $result['intent']);

        $bengaliText = 'আমার কত সময় বাকি আছে';
        $bResult = LocalReminderParser::parse($bengaliText, 'Asia/Dhaka');
        $this->assertNotNull($bResult);
        $this->assertEquals('list_reminders', $bResult['intent']);
    }
}
