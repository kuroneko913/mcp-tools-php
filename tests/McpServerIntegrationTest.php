<?php

declare(strict_types=1);

namespace Tests;

use App\McpServer;
use App\ModelContextProtocol;
use App\GetExecutableTool;
use PHPUnit\Framework\TestCase;

class McpServerIntegrationTest extends TestCase
{
    /** @var array{tools: list<array{name: string, description: string, inputSchema?: array<string, mixed>}>} */
    private array $toolsSchema;

    protected function setUp(): void
    {
        putenv('OPENWEATHER_API_KEY=dummy_key');
        $this->toolsSchema = [
            'tools' => [
                [
                    'name' => 'clock',
                    'description' => 'Get current time',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'timezone' => ['type' => 'string']
                        ],
                        'required' => ['timezone']
                    ]
                ]
            ]
        ];
    }

    /**
     * サーバーの初期化 (initialize) をテスト
     */
    public function testInitialize(): void
    {
        $response = $this->executeRequest([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => ['protocolVersion' => '2024-11-05']
        ]);

        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('2024-11-05', $response['result']['protocolVersion']);
        $this->assertEquals('mcp-tools-php', $response['result']['serverInfo']['name']);
    }

    /**
     * ツール一覧の取得 (tools/list) をテスト
     */
    public function testToolsList(): void
    {
        $response = $this->executeRequest([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list'
        ]);

        $this->assertArrayHasKey('result', $response);
        $this->assertEquals($this->toolsSchema['tools'], $response['result']['tools']);
    }

    /**
     * ツール実行をテスト
     */
    public function testToolCall(): void
    {
        $response = $this->executeRequest([
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'clock',
                'arguments' => ['timezone' => 'UTC']
            ]
        ]);

        $this->assertArrayHasKey('result', $response);
        $this->assertArrayHasKey('content', $response['result']);
        $this->assertEquals('text', $response['result']['content'][0]['type']);
    }

    /**
     * リクエストを実行してレスポンスを返すヘルパーメソッド
     *
     * @param array<string, mixed> $requestData
     * @return array<string, mixed>
     */
    private function executeRequest(array $requestData): array
    {
        $toolFactory = new GetExecutableTool();
        $protocol = new ModelContextProtocol($this->toolsSchema, $toolFactory);
        $server = new McpServer($protocol);

        $input = fopen('php://temp', 'r+');
        $output = fopen('php://temp', 'r+');
        if ($input === false || $output === false) {
            $this->fail('Failed to open streams');
        }

        fwrite($input, (string) json_encode($requestData));
        rewind($input);

        $server->run($input, $output);
        rewind($output);

        $responseJson = stream_get_contents($output);
        $this->assertNotFalse($responseJson);

        $response = json_decode($responseJson, true);
        $this->assertIsArray($response);

        return $response;
    }
}
