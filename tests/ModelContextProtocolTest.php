<?php

namespace Tests;

use App\ModelContextProtocol;
use PHPUnit\Framework\TestCase;
use Exception;

class ModelContextProtocolTest extends TestCase
{
    /**
     * @var array{tools: array<int, array{name: string, description: string, inputSchema?: mixed}>}
     */
    private array $toolsSchema;
    private ModelContextProtocol $protocol;

    protected function setUp(): void
    {
        $this->toolsSchema = [
            'tools' => [
                ['name' => 'clock', 'description' => 'A clock tool']
            ]
        ];
        $this->protocol = new ModelContextProtocol($this->toolsSchema);
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testInitialize(): void
    {
        $result = $this->protocol->initialize(['protocolVersion' => '2024-11-05']);
        $this->assertEquals('2024-11-05', $result['protocolVersion']);
        $this->assertEquals('mcp-tools-php', $result['serverInfo']['name']);
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testToolsList(): void
    {
        $result = $this->protocol->toolsList();
        $this->assertEquals($this->toolsSchema['tools'], $result['tools']);
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testExecuteSuccessfulTool(): void
    {
        $params = [
            'name' => 'clock',
            'arguments' => ['timezone' => 'Asia/Tokyo']
        ];
        $result = $this->protocol->execute($params);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('text', $result['content'][0]['type']);
        $this->assertStringContainsString('-', $result['content'][0]['text']);
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testExecuteThrowsExceptionOnError(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Tool execution failed/');

        $params = [
            'name' => 'clock',
            'arguments' => ['timezone' => 'Invalid/Timezone']
        ];
        $this->protocol->execute($params);
    }
}
