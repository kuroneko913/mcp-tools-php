<?php

declare(strict_types=1);

namespace Tests;

use App\ModelContextProtocol;
use App\GetExecutableTool;
use App\Tools\Clock;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ModelContextProtocolTest extends TestCase
{
    /** @var array{tools: list<array{name: string, description: string}>} */
    private array $toolsSchema;
    private ModelContextProtocol $protocol;
    /** @var GetExecutableTool&MockObject */
    private $toolFactory;

    protected function setUp(): void
    {
        $this->toolFactory = $this->createMock(GetExecutableTool::class);
        $this->toolsSchema = [
            'tools' => [
                ['name' => 'clock', 'description' => 'A clock tool']
            ]
        ];
        $this->protocol = new ModelContextProtocol($this->toolsSchema, $this->toolFactory);
    }

    /**
     * @return Clock&MockObject
     */
    private function createClockMock(): MockObject
    {
        return $this->createMock(Clock::class);
    }

    /**
     * initialize メソッドのテスト
     */
    public function testInitialize(): void
    {
        $result = $this->protocol->initialize(['protocolVersion' => '2024-11-05']);
        $this->assertEquals('2024-11-05', $result['protocolVersion']);
        $this->assertEquals('mcp-tools-php', $result['serverInfo']['name']);
    }

    /**
     * tools/list メソッドのテスト
     */
    public function testToolsList(): void
    {
        $result = $this->protocol->toolsList();
        $this->assertEquals($this->toolsSchema['tools'], $result['tools']);
    }

    /**
     * 正常系: ツールの実行が成功する場合
     */
    public function testExecuteSuccessfulTool(): void
    {
        $params = [
            'name' => 'clock',
            'arguments' => ['timezone' => 'Asia/Tokyo']
        ];

        $toolMock = $this->createClockMock();
        $toolMock->expects($this->once())
            ->method('invoke')
            ->with('Asia/Tokyo')
            ->willReturn(['content' => [['type' => 'text', 'text' => '2024-01-01 21:00:00']]]);

        $this->toolFactory->expects($this->once())
            ->method('create')
            ->with('clock', ['timezone' => 'Asia/Tokyo'], $this->toolsSchema)
            ->willReturn($toolMock);

        $result = $this->protocol->execute($params);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('text', $result['content'][0]['type']);
        $this->assertEquals('2024-01-01 21:00:00', $result['content'][0]['text']);
    }

    /**
     * 異常系: ツール実行に失敗した場合
     */
    public function testExecuteThrowsExceptionOnError(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Tool execution failed/');

        $params = [
            'name' => 'clock',
            'arguments' => ['timezone' => 'Asia/Tokyo']
        ];

        $toolMock = $this->createClockMock();
        $toolMock->method('invoke')
            ->willThrowException(new \Exception('Some error'));

        $this->toolFactory->method('create')
            ->willReturn($toolMock);

        $this->protocol->execute($params);
    }
}
