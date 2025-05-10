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
        $arguments = $params['arguments'] ?? [];
        $tool = (new GetExecutableTool($toolName, $arguments, $this->tools))->handle();
        try {   
            return $tool->invoke($arguments);
        } catch (\Exception $e) {
            throw new \Exception('Tool execution failed: ' . $e->getMessage(), -32601, $e);
        }
    }
}
