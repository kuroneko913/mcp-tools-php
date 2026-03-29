<?php

namespace Tests\Tools;

use App\Tools\Weather;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Exception;

class WeatherTest extends TestCase
{
    /**
     * 正常系や異常系のテスト
     */
    public function testInvokeReturnsWeatherDataSuccessfully(): void
    {
        // 正常なJSONレスポンスを返すモックハンドラを準備
        $mockResponse = [
            'weather' => [['description' => '晴れ']],
            'main' => ['temp' => 25.5, 'humidity' => 60]
        ];
        $mock = new MockHandler([
            new Response(200, [], (string) json_encode($mockResponse))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $weather = new Weather('dummy_api_key', $client);
        $result = $weather->invoke('Tokyo');

        $this->assertArrayHasKey('content', $result);
        $content = $result['content'][0];

        $this->assertEquals('text', $content['type']);

        $decodedText = json_decode($content['text'], true);
        $this->assertEquals('Tokyo', $decodedText['location']);
        $this->assertEquals('晴れ', $decodedText['weather']);
        $this->assertEquals(25.5, $decodedText['temperature']);
        $this->assertEquals(60, $decodedText['humidity']);
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testInvokeThrowsExceptionWhenLocationIsEmpty(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('location is required');

        $weather = new Weather('dummy_key');
        $weather->invoke('');
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testInvokeThrowsExceptionOnApiError(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to get weather data');

        // エラー（500）レスポンスを返すモック
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error')
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $weather = new Weather('dummy_api_key', $client);
        $weather->invoke('Tokyo');
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testConstructorThrowsExceptionWhenApiKeyIsMissing(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('OPENWEATHER_API_KEY is not set');

        // 環境変数を一時的に削除してテスト
        $originalEnv = getenv('OPENWEATHER_API_KEY');
        putenv('OPENWEATHER_API_KEY');

        try {
            new Weather(null);
        } finally {
            // 元に戻す
            if ($originalEnv !== false) {
                putenv('OPENWEATHER_API_KEY=' . $originalEnv);
            }
        }
    }
}
