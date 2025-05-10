<?php

namespace App;

use App\Tools\ToolInterface;

/**
 * Model Context Protocol
 * tools.json を読み込んで、ツールを実行する
 * @see https://modelcontextprotocol.io/specification/2024-11-05
 */
class ModelContextProtocol
{
    /**
     * @var array{
     *      tools: array{
     *          name: string,
     *          description: string
     *      }[]
     * }
     */
    private array $tools;

    /**
     * @param array{
     *      tools: array{
     *          name: string,
     *          description: string
     *      }[]
     * } $tools
     */
    public function __construct(array $tools)
    {
        $this->tools = $tools;
    }

    /**
     * 初期化する
     * @param array $params
     * @return array{
     *      protocolVersion: string,
     *      capabilities: array{
     *          tools: array{
     *              listChanged: boolean
     *          }
     *      },
     *      serverInfo: array{
     *          name: string,
     *          version: string
     *      }
     * }
     */
    public function initialize(array $params) : array
    {
        return [
            'protocolVersion' => $params['protocolVersion'],
            'capabilities' => [
                'tools' => [
                    'listChanged' => false
                ]
            ],
            'serverInfo' => [
                'name' => 'mcp-tools-php',
                'version' => '0.0.1'
            ]
        ];
    }

    /**
     * ツールのリストを取得する
     * @return array{
     *      tools: array{
     *          name: string,
     *          description: string
     *      }[]
     * }
     */
    public function toolsList() : array
    {
        return [
            'tools' => $this->tools['tools']
        ];
    }

    /**
     * ツールを実行する
     * @param array{
     *      name: string,
     *      arguments: array
     * } $params
     * @return array{content: array{type: string, text: string}}
     */
    public function execute(array $params) : array
    {
        $toolName = $params['name'] ?? '';
        $this->validateToolName($toolName);
        $arguments = $params['arguments'] ?? [];
        $this->validateArguments($arguments);
        try {
            $tool = $this->getExecutableInstance($toolName);
            return $tool->handle($arguments);
        } catch (\Exception $e) {
            throw new \Exception('Tool execution failed: ' . $e->getMessage(), -32601, $e);
        }
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
        if (!array_column($this->tools['tools'], 'name', 'name')[$toolName]) {
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

    private function getExecutableInstance(string $toolName) : ToolInterface
    {
        $className = "App\\Tools\\" . ucfirst($toolName);
        if (!class_exists($className)) {
            throw new \Exception("Tool class not found: $className", -32601);
        }
        if (!($className instanceof ToolInterface)) {
            throw new \Exception("Tool class must implement ToolInterface: $className", -32601);
        }
        if (!method_exists($className, 'handle')) {
            throw new \Exception("Tool method 'handle' not found in $className", -32601);
        }
        return new $className();
    }
}
