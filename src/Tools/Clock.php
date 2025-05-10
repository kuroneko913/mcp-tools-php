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
    public function invoke(array $params) : array
    {
        $timezone = $params['timezone'] ?? '';
        if ($timezone === '') {
            throw new \Exception('timezone is required');
        }
        try {
            $datetime = new \DateTime('now', new \DateTimeZone($timezone));
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
