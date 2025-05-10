<?php

namespace App\Tools;

/**
 * 天気情報を取得するツール
 */
class Weather implements ToolInterface
{
    /**
     * @inheritdoc
     */
    public function handle(array $params) : array
    {
        $location = $params['location'] ?? '';
        if ($location === '') {
            throw new \Exception('location is required');
        }
        return [
            "content" => [
                [
                    "type" => 'text',
                    "text" => json_encode([
                        'location' => $location,
                        'weather' => '晴れ',
                        'temperature' => '25',
                        'unit' => 'C'
                    ])
                ]
            ]
        ];
    }
}
