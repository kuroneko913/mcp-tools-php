<?php

declare(strict_types=1);

namespace Tests\Tools;

use App\Tools\Weather;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class WeatherTest extends TestCase
{
    private ?string $originalApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalApiKey = getenv('OPENWEATHER_API_KEY') ?: null;
    }

    /**
     * 異常系: APIキーが未設定の場合に例外が発生することを確認
     */
    public function testConstructorThrowsExceptionWhenApiKeyIsMissing(): void
    {
        // 環境変数をクリアして確実にテストが失敗するようにする
        putenv('OPENWEATHER_API_KEY');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OPENWEATHER_API_KEY is not set');

        new Weather();
    }


    /**
     * 正常系: 天気データが正しく返ることを確認
     */
    public function testInvokeReturnsWeatherDataSuccessfully(): void
    {
        $mockData = [
            'weather' => [['description' => '晴れ']],
            'main' => ['temp' => 25.5, 'humidity' => 50]
        ];

        $mockResponse = new Response(200, [], (string) json_encode($mockData));
        $mockHandler = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $weather = new Weather('dummy_api_key', $client);
        $result = $weather->invoke('Tokyo');

        $this->assertArrayHasKey('content', $result);
        $content = $result['content'][0];
        $this->assertEquals('text', $content['type']);
        $this->assertStringContainsString('晴れ', $content['text']);
        $this->assertStringContainsString('25.5', $content['text']);
    }

    /**
     * 異常系: location が空の場合
     */
    public function testInvokeThrowsExceptionWhenLocationIsEmpty(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('location is required');

        $weather = new Weather('dummy_key');
        $weather->invoke('');
    }

    /**
     * 異常系: API エラーの場合
     */
    public function testInvokeThrowsExceptionOnApiError(): void
    {
        $mockHandler = new MockHandler([
            new \GuzzleHttp\Exception\TransferException('API Error')
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Weather lookup failed/');

        $weather = new Weather('dummy_key', $client);
        $weather->invoke('Tokyo');
    }

    protected function tearDown(): void
    {
        if ($this->originalApiKey !== null) {
            putenv('OPENWEATHER_API_KEY=' . $this->originalApiKey);
        } else {
            putenv('OPENWEATHER_API_KEY');
        }
        parent::tearDown();
    }
}
