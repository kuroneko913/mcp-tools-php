<?php

namespace App;

use App\Tools\ToolInterface;

/**
 * 実行可能なツールを取得する
 */
class GetExecutableTool
{
    /**
     * コンストラクタ
     * @param string $toolName ツール名
     * @param array $arguments ツールの引数
     * @param array $tools ツールのリスト
     */
    public function __construct(
        private string $toolName,
        private array $arguments,
        private array $tools,
    ) {
    }

    /**
     * ツールを実行する
     * @return ToolInterface
     * @throws \Exception
     */
    public function handle() : ToolInterface
    {
        $this->validateToolName($this->toolName);
        $this->validateArguments($this->arguments);
        return $this->getExecutableInstance($this->toolName);
    }

    /**
     * ツール名を検証する
     * @param string $toolName
     * @return void
     * @throws \Exception
     */
    private function validateToolName(string $toolName) : void
    {
        if ($toolName === '') {
            throw new \Exception('Tool name is required', -32601);
        }
        if (!is_string($toolName)) {
            throw new \Exception('Tool name must be a string', -32601);
        }
        if (!(array_column($this->tools['tools'], 'name', 'name')[$toolName] ?? false)) {
            throw new \Exception('Tool not found', -32601);
        }
        return;
    }

    /**
     * 引数を検証する
     * @param array $arguments
     * @return void
     * @throws \Exception
     */
    private function validateArguments(array $arguments) : void
    {
        if ($arguments === []) {
            throw new \Exception('Arguments must be an array', -32601);
        }
        return;
    }

    /**
     * 実行可能なツールを取得する
     * @param string $toolName
     * @return ToolInterface
     * @throws \Exception
     */
    private function getExecutableInstance(string $toolName) : ToolInterface
    {
        $className = "App\\Tools\\" . ucfirst($toolName);
        if (!class_exists($className)) {
            throw new \Exception("Tool class not found: $className", -32601);
        }
        $instance = new $className();
        if (!($instance instanceof ToolInterface)) {
            throw new \Exception("Tool class must implement ToolInterface: $className", -32601);
        }
        if (!method_exists($instance, 'invoke')) {
            throw new \Exception("Tool method 'invoke' not found in $instance", -32601);
        }
        return $instance;
    }
}
