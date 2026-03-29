<?php

declare(strict_types=1);

namespace App;

use App\Tools\ToolInterface;

/**
 * 実行可能なツールインスタンスを生成するクラス
 */
class GetExecutableTool
{
    /**
     * ツールインスタンスを生成して返す
     *
     * @param string $toolName ツール名
     * @param array<string, mixed> $arguments ツールの引数
     * @param array{tools: array<int, array{name: string}>} $toolsSchema ツールのリスト
     * @return ToolInterface
     * @throws \Exception
     */
    public function create(string $toolName, array $arguments, array $toolsSchema): ToolInterface
    {
        $this->validateToolName($toolName, $toolsSchema);
        return $this->getExecutableInstance($toolName);
    }

    /**
     * ツール名を公開リストと照合して検証する
     * @param string $toolName
     * @param array{tools: array<int, array{name: string}>} $toolsSchema
     * @return void
     * @throws \Exception
     */
    private function validateToolName(string $toolName, array $toolsSchema): void
    {
        if ($toolName === '') {
            throw new \Exception('Tool name is required', -32601);
        }

        if (!(array_column($toolsSchema['tools'], 'name', 'name')[$toolName] ?? false)) {
            throw new \Exception('Tool not found', -32601);
        }
    }

    /**
     * ツールクラスをインスタンス化する
     * @param string $toolName
     * @return ToolInterface
     * @throws \Exception
     */
    private function getExecutableInstance(string $toolName): ToolInterface
    {
        /** @var class-string $className */
        $className = "App\\Tools\\" . ucfirst($toolName);

        if (!class_exists($className)) {
            throw new \Exception("Tool class not found: $className", -32601);
        }

        /** @var ToolInterface $instance */
        $instance = new $className();

        return $instance;
    }
}
