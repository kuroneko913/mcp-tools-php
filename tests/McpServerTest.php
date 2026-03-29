<?php

declare(strict_types=1);

namespace Tests;

use App\McpServer;
use App\ModelContextProtocol;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class McpServerTest extends TestCase
{
    /**
     * @var ModelContextProtocol&\PHPUnit\Framework\MockObject\MockObject
     */
    private $protocol;

    protected function setUp(): void
    {
        $this->protocol = $this->getMockBuilder(ModelContextProtocol::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * initialize メソッドが正しく処理されることを確認
     */
    public function testRunHandlesInitialize(): void
    {
        $inputData = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => ['protocolVersion' => '2024-11-05']
        ]) . "\n";

        $expectedResult = [
            'protocolVersion' => '2024-11-05',
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => 'mcp-tools-php', 'version' => '0.0.1']
        ];

        $this->protocol->expects($this->once())
            ->method('initialize')
            ->willReturn($expectedResult);

        $input = fopen('php://memory', 'r+');
        $output = fopen('php://memory', 'r+');
        if (!is_resource($input) || !is_resource($output)) {
            $this->fail('Failed to open memory streams');
        }

        fwrite($input, (string)$inputData);
        rewind($input);

        $server = new McpServer($this->protocol);
        $server->run($input, $output);

        rewind($output);
        $response = stream_get_contents($output);
        if ($response === false) {
            $this->fail('Failed to get output from stream');
        }

        $responseData = json_decode($response, true);
        $this->assertEquals('2.0', $responseData['jsonrpc']);
        $this->assertEquals(1, $responseData['id']);
        $this->assertEquals($expectedResult, $responseData['result']);

        fclose($input);
        fclose($output);
    }

    /**
     * 未定義のメソッドが呼ばれた時に Method not found (-32601) を返すことを確認
     */
    public function testRunHandlesMethodNotFound(): void
    {
        $inputData = json_encode([
            'jsonrpc' => '2.0',
            'id' => 99,
            'method' => 'unknown/method'
        ]) . "\n";

        $input = fopen('php://memory', 'r+');
        $output = fopen('php://memory', 'r+');
        if (!is_resource($input) || !is_resource($output)) {
            $this->fail('Failed to open memory streams');
        }

        fwrite($input, (string)$inputData);
        rewind($input);

        // プロトコルは呼ばれないはず
        $this->protocol->expects($this->never())->method('initialize');

        $server = new McpServer($this->protocol);
        $server->run($input, $output);

        rewind($output);
        $response = (string)stream_get_contents($output);

        $responseData = json_decode($response, true);
        $this->assertEquals('2.0', $responseData['jsonrpc']);
        $this->assertEquals(99, $responseData['id']);
        $this->assertEquals(-32601, $responseData['error']['code']);
        $this->assertStringContainsString('Method not found', $responseData['error']['message']);

        fclose($input);
        fclose($output);
    }

    /**
     * 不正な JSON リクエスト時に Invalid Request (-32600) を返すことを確認
     */
    public function testRunHandlesInvalidRequest(): void
    {
        $inputData = "invalid-json\n";

        $input = fopen('php://memory', 'r+');
        $output = fopen('php://memory', 'r+');
        if (!is_resource($input) || !is_resource($output)) {
            $this->fail('Failed to open memory streams');
        }

        fwrite($input, $inputData);
        rewind($input);

        // プロトコルは呼ばれないはず
        $this->protocol->expects($this->never())->method('initialize');

        $server = new McpServer($this->protocol);
        $server->run($input, $output);

        rewind($output);
        $response = (string)stream_get_contents($output);

        $responseData = json_decode($response, true);
        $this->assertEquals('2.0', $responseData['jsonrpc']);
        $this->assertNull($responseData['id']);
        $this->assertEquals(-32600, $responseData['error']['code']);

        fclose($input);
        fclose($output);
    }

    /**
     * id が null のリクエストを正しく処理できることを確認
     */
    public function testRunHandlesIdNull(): void
    {
        $inputData = json_encode([
            'jsonrpc' => '2.0',
            'id' => null,
            'method' => 'tools/list'
        ]) . "\n";

        $this->protocol->expects($this->once())
            ->method('toolsList')
            ->willReturn(['tools' => []]);

        $input = fopen('php://memory', 'r+');
        $output = fopen('php://memory', 'r+');
        if (!is_resource($input) || !is_resource($output)) {
            $this->fail('Failed to open memory streams');
        }

        fwrite($input, (string)$inputData);
        rewind($input);

        $server = new McpServer($this->protocol);
        $server->run($input, $output);

        rewind($output);
        $response = (string)stream_get_contents($output);
        $responseData = json_decode($response, true);

        $this->assertEquals('2.0', $responseData['jsonrpc']);
        $this->assertNull($responseData['id']);
        $this->assertArrayHasKey('result', $responseData);

        fclose($input);
        fclose($output);
    }

    /**
     * 通知 (id なし) のリクエスト時にレスポンスを返さないことを確認
     */
    public function testRunHandlesNotificationNoResponse(): void
    {
        $inputData = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'notifications/initialized'
        ]) . "\n";

        $input = fopen('php://memory', 'r+');
        $output = fopen('php://memory', 'r+');
        if (!is_resource($input) || !is_resource($output)) {
            $this->fail('Failed to open memory streams');
        }

        fwrite($input, (string)$inputData);
        rewind($input);

        $server = new McpServer($this->protocol);
        $server->run($input, $output);

        rewind($output);
        $response = (string)stream_get_contents($output);
        $this->assertEmpty($response, 'Notification should not produce a response');

        fclose($input);
        fclose($output);
    }
}
