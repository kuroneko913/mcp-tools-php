<?php

declare(strict_types=1);

namespace App\Tools;

use GuzzleHttp\Client;

/**
 * 天気情報を取得するツール
 */
class Weather implements ToolInterface
{
    /**
     * @var string
     */
    private const API_URL = 'https://api.openweathermap.org/data/2.5/weather';

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var string
     */
    private string $apiKey;

    /**
     * @param string|null $apiKey
     * @param Client|null $client
     */
    public function __construct(?string $apiKey = null, ?Client $client = null)
    {
        $envKey = getenv('OPENWEATHER_API_KEY');
        // 環境変数が設定されていない（false）か空文字の場合は引数の $apiKey を使用
        $resolvedKey = ($envKey !== false && $envKey !== '') ? $envKey : ($apiKey ?? '');

        if ($resolvedKey === '') {
            throw new \Exception('OPENWEATHER_API_KEY is not set');
        }

        $this->apiKey = $resolvedKey;
        $this->client = $client ?? new Client();
    }


    /**
     * @param string $location
     * @return array{content: list<array{type: string, text: string}>}
     * @throws \Exception
     */
    public function invoke(string $location): array
    {
        if ($location === '') {
            throw new \Exception('location is required');
        }
        $city = $this->getCity($location);
        try {
            $response = $this->client->request(
                'GET',
                self::API_URL,
                [
                    'query' => [
                        'q' => $city,
                        'appid' => $this->apiKey,
                        'units' => 'metric',
                        'lang' => 'ja',
                    ],
                    'http_errors' => true,
                    'timeout' => 2000,
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ]
            );
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            $weather = $data['weather'][0]['description'] ?? '不明';
            $temperature = $data['main']['temp'] ?? '不明';
            $humidity = $data['main']['humidity'] ?? '不明';
        } catch (\Exception $e) {
            throw new \Exception('Weather lookup failed: ' . $e->getMessage(), -32601);
        }
        return [
            "content" => [
                [
                    "type" => 'text',
                    "text" => "現在地: {$location}\n天気: {$weather}\n気温: {$temperature}℃\n湿度: {$humidity}%"
                ]
            ]
        ];
    }

    /**
     * @param string $location 地名
     * @return string 地名のAPI用の文字列
     */
    private function getCity(string $location): string
    {
        $cityMapping = [
            '横浜' => 'Yokohama, JP',
            '東京' => 'Tokyo, JP',
            '大阪' => 'Osaka, JP',
            '札幌' => 'Sapporo, JP',
            '仙台' => 'Sendai, JP',
            '名古屋' => 'Nagoya, JP',
            '京都' => 'Kyoto, JP',
            '神戸' => 'Kobe, JP',
            '広島' => 'Hiroshima, JP',
            '北九州' => 'Kitakyushu, JP',
            '福岡' => 'Fukuoka, JP',
            '那覇' => 'Naha, JP',
        ];
        return $cityMapping[$location] ?? $location;
    }
}
