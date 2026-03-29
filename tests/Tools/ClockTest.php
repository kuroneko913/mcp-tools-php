<?php

namespace Tests\Tools;

use App\Tools\Clock;
use PHPUnit\Framework\TestCase;
use Exception;

class ClockTest extends TestCase
{
    /**
     * 正常系や異常系のテスト
     */
    public function testInvokeReturnsCorrectFormat(): void
    {
        $clock = new Clock();
        $result = $clock->invoke('Asia/Tokyo');

        $this->assertArrayHasKey('content', $result);
        $this->assertCount(1, $result['content']);

        $content = $result['content'][0];
        $this->assertEquals('text', $content['type']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $content['text']);
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testInvokeThrowsExceptionWhenTimezoneIsEmpty(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('timezone is required');

        $clock = new Clock();
        $clock->invoke('');
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testInvokeThrowsExceptionForInvalidTimezone(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Failed to get current time/');

        $clock = new Clock();
        $clock->invoke('Invalid/Timezone');
    }
}
