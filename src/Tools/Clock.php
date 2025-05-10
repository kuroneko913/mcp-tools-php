<?php

namespace App\Tools;

/**
 * 現在時刻を取得するツール
 */
class Clock implements ToolInterface
{
    /**
     * @inheritdoc
     */
    public function handle(array $params) : array
    {
        $timezone = $params['timezone'] ?? '';
        if ($timezone === '') {
            throw new \Exception('timezone is required');
        }
        $datetime = new \DateTime('now', new \DateTimeZone($timezone));
        $datetime_str = $datetime->format('Y-m-d H:i:s');
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
