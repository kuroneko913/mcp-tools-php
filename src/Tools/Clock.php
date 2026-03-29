<?php

declare(strict_types=1);

namespace App\Tools;

use Lcobucci\Clock\SystemClock;
use Psr\Clock\ClockInterface;

/**
 * 現在時刻を取得するツール
 */
class Clock implements ToolInterface
{
    /**
     * @var ClockInterface
     */
    private ClockInterface $clock;

    /**
     * @param ClockInterface|null $clock 時計（テスト用などの注入が可能）
     */
    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock ?? SystemClock::fromSystemTimezone();
    }

    /**
     * @param string $timezone
     * @return array{content: list<array{type: string, text: string}>}
     * @throws \Exception
     */
    public function invoke(string $timezone): array
    {
        if ($timezone === '') {
            throw new \Exception('timezone is required');
        }
        try {
            $datetime = $this->clock->now()->setTimezone(new \DateTimeZone($timezone));
            $datetime_str = $datetime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            throw new \Exception('Failed to get current time: ' . $e->getMessage(), -32601);
        }
        return [
            "content" => [
                [
                    "type" => 'text',
                    "text" => $datetime_str
                ]
            ]
        ];
    }
}
