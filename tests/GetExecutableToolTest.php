<?php

namespace Tests;

use App\GetExecutableTool;
use App\Tools\Clock;
use PHPUnit\Framework\TestCase;
use Exception;

class GetExecutableToolTest extends TestCase
{
    /**
     * @var array{tools: array<int, array{name: string}>}
     */
    private array $toolsSchema;

    protected function setUp(): void
    {
        $this->toolsSchema = [
            'tools' => [
                ['name' => 'clock']
            ]
        ];
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testHandleReturnsExecutableInstance(): void
    {
        $getExecutable = new GetExecutableTool('clock', ['timezone' => 'Asia/Tokyo'], $this->toolsSchema);
        $tool = $getExecutable->handle();

        $this->assertInstanceOf(Clock::class, $tool);
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testValidateToolNameThrowsExceptionWhenEmpty(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tool name is required');

        $getExecutable = new GetExecutableTool('', ['timezone' => 'Asia/Tokyo'], $this->toolsSchema);
        $getExecutable->handle();
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testValidateToolNameThrowsExceptionWhenNotFoundInSchema(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tool not found');

        $getExecutable = new GetExecutableTool('unknown', ['dummy' => 'val'], $this->toolsSchema);
        $getExecutable->handle();
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testGetExecutableInstanceThrowsExceptionWhenClassNotFound(): void
    {
        $schema = [
            'tools' => [
                ['name' => 'notexists']
            ]
        ];
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tool class not found: App\Tools\Notexists');

        $getExecutable = new GetExecutableTool('notexists', ['dummy' => 'val'], $schema);
        $getExecutable->handle();
    }

    /**

     * 正常系や異常系のテスト

     */

    public function testValidateArgumentsThrowsExceptionWhenEmptyArray(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Arguments must be an array');

        $getExecutable = new GetExecutableTool('clock', [], $this->toolsSchema);
        $getExecutable->handle();
    }
}
