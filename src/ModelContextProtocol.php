<?php

namespace App;

/**
 * Model Context Protocol
 * tools.json を読み込んで、ツールを実行する
 * @see https://modelcontextprotocol.io/specification/2024-11-05
 */
class ModelContextProtocol
{
    /**
     * @var array{tools: array<int, array{name: string, description: string, inputSchema?: mixed}>}
     */
    private array $tools;

    /**
     * @param array{tools: array<int, array{name: string, description: string, inputSchema?: mixed}>} $tools
     */
    public function __construct(array $tools)
    {
        $this->tools = $tools;
    }

    /**
     * 初期化レスポンスを返す
     *
     * @param array<string, mixed> $params
     * @return array{protocolVersion: string, serverInfo: array{name: string, version: string}}
     */
    public function initialize(array $params): array
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
     * 利用可能なツールのリストを返す
     *
     * @return array{tools: array<int, array{name: string, description: string, inputSchema?: mixed}>}
     */
    public function toolsList(): array
    {
        return [
            'tools' => $this->tools['tools']
        ];
    }

    /**
     * ツールを実行する
     *
     * @param array{name: string, arguments: array<string, mixed>} $params
     * @return array{content: list<array{type: string, text: string}>}
     */
    public function execute(array $params): array
    {
        $toolName = $params['name'];
        $arguments = $params['arguments'];
        $tool = (new GetExecutableTool($toolName, $arguments, $this->tools))->handle();
        try {
            /** @var array{content: list<array{type: string, text: string}>} $result */
            $result = $tool->invoke(...$arguments);
            return $result;
        } catch (\Throwable $e) {
            throw new \Exception('Tool execution failed: ' . $e->getMessage(), -32601, $e);
        }
    }
}
