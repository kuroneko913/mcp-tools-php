<?php

declare(strict_types=1);

namespace Tests;

use App\GetExecutableTool;
use App\Tools\Clock;
use App\Tools\Weather;
use PHPUnit\Framework\TestCase;

class GetExecutableToolTest extends TestCase
{
    /** @var array{tools: list<array{name: string, description: string}>} */
    private array $toolsData;

    protected function setUp(): void
    {
        putenv('OPENWEATHER_API_KEY=dummy_key');
        $this->toolsData = [
            'tools' => [
                ['name' => 'clock', 'description' => 'A clock tool'],
                ['name' => 'weather', 'description' => 'A weather tool']
            ]
        ];
    }

    /**
     * 正常系: ツール名が登録されている場合に正しいインスタンスを返す
     */
    public function testCreateReturnsCorrectInstance(): void
    {
        $factory = new GetExecutableTool();

        $tool = $factory->create('clock', ['timezone' => 'Asia/Tokyo'], $this->toolsData);
        $this->assertInstanceOf(Clock::class, $tool);

        $tool = $factory->create('weather', ['location' => 'Yokohama'], $this->toolsData);
        $this->assertInstanceOf(Weather::class, $tool);
    }

    /**
     * 異常系: 登録されていないツール名を指定した場合に例外を投げる
     */
    public function testCreateThrowsExceptionForUnknownTool(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tool not found');

        $factory = new GetExecutableTool();
        $factory->create('unknown', [], $this->toolsData);
    }

    /**
     * 異常系: ツール名が空の場合に例外を投げる
     */
    public function testCreateThrowsExceptionForEmptyToolName(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tool name is required');

        $factory = new GetExecutableTool();
        $factory->create('', [], $this->toolsData);
    }

    /**
     * Clock ツールがデフォルトの時刻で正しく生成されること
     */
    public function testCreateClockTool(): void
    {
        $factory = new GetExecutableTool();
        $tool = $factory->create('clock', ['timezone' => 'Asia/Tokyo'], $this->toolsData);

        $this->assertInstanceOf(Clock::class, $tool);
        /** @var Clock $tool */
        $result = $tool->invoke('Asia/Tokyo');
        $this->assertArrayHasKey('content', $result);
    }
}
