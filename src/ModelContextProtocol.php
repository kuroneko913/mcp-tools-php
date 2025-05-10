<?php

namespace App;

class ModelContextProtocol
{
    /**
     * @var array
     */
    private array $tools;

    public function __construct(array $tools) 
    {
        $this->tools = $tools;
    }

    public function initialize(array $params) : array
    {
        return [
            'protocolVersion' => $params['protocolVersion'],
            'capabilities' => [
                'tools' => [
                    'listChanged' => false
                ]
            ],
            'serverInfo' => [
                'name' => 'mcp-tools-php',
                'version' => '0.0.1'
            ]
        ];
    }
    public function toolsList() : array
    {
        return [
            'tools' => $this->tools['tools']
        ];
    }
    public function execute(array $params) : array
    {
        $toolName = $params['name'] ?? '';
        
        // 各ツールに応じた処理を実装
        if ($toolName === 'now') {
            $timezone = $params['arguments']['timezone'] ?? 'Asia/Tokyo';
            $datetime = new \DateTime('now', new \DateTimeZone($timezone));
            $datetime_str = $datetime->format('Y-m-d H:i:s');
            
            return [
                "content" => [
                    [
                        "type" => 'text',
                        "text" => json_encode([
                            'timezone' => $timezone,
                            'now' => $datetime_str
                        ])
                    ]
                ]
            ];
        } elseif ($toolName === 'get_weather') {
            $location = $params['arguments']['location'] ?? 'Tokyo,JP';
            
            // 天気のモックデータを返す
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
        } else {
            throw new \Exception('Tool not found', -32601);
        }
    }
}
