<?php

declare(strict_types=1);

namespace Tests\Tools;

use App\Tools\Clock;
use Lcobucci\Clock\FrozenClock;
use PHPUnit\Framework\TestCase;
use Exception;
use DateTimeImmutable;

class ClockTest extends TestCase
{
    private FrozenClock $frozenClock;

    protected function setUp(): void
    {
        $this->frozenClock = new FrozenClock(new DateTimeImmutable('2024-01-01 12:00:00'));
    }

    /**
     * デフォルトのコンストラクタ（SystemClock）で動作することを確認
     */
    public function testInvokeWithDefaultClock(): void
    {
        $clock = new Clock();
        $result = $clock->invoke('UTC');
        $this->assertArrayHasKey('content', $result);
        $this->assertNotEmpty($result['content'][0]['text']);
    }

    /**
     * 指定されたタイムゾーンで正しくフォーマットされることを確認 (FrozenClock)
     */
    public function testInvokeReturnsCorrectFormat(): void
    {
        $now = new DateTimeImmutable('2024-03-29 12:00:00', new \DateTimeZone('UTC'));
        $frozenClock = new FrozenClock($now);
        $clock = new Clock($frozenClock);

        $result = $clock->invoke('Asia/Tokyo');

        $this->assertArrayHasKey('content', $result);
        $this->assertCount(1, $result['content']);

        $content = $result['content'][0];
        $this->assertEquals('text', $content['type']);
        // Asia/Tokyo (+09:00) なので 12:00:00 -> 21:00:00
        $this->assertEquals('2024-03-29 21:00:00', $content['text']);
    }

    /**
     * 正常系: 別のタイムゾーンでも正しく計算されるか
     */
    public function testInvokeReturnsCorrectFormatForUtc(): void
    {
        $clock = new Clock($this->frozenClock);
        $result = $clock->invoke('UTC');

        $this->assertEquals('2024-01-01 12:00:00', $result['content'][0]['text']);
    }

    /**
     * 異常系: タイムゾーンが空の場合に例外が発生することを確認
     */
    public function testInvokeThrowsExceptionWhenTimezoneIsEmpty(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('timezone is required');

        $clock = new Clock($this->frozenClock);
        $clock->invoke('');
    }

    /**
     * 異常系: 無効なタイムゾーンの場合に例外が発生することを確認
     */
    public function testInvokeThrowsExceptionForInvalidTimezone(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Failed to get current time/');

        $clock = new Clock($this->frozenClock);
        $clock->invoke('Invalid/Timezone');
    }
}
