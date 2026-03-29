<?php

declare(strict_types=1);

namespace App;

/**
 * Model Context Protocol (MCP) のコアロジックを管理するクラス
 */
class ModelContextProtocol
{
    /**
     * @var GetExecutableTool
     */
    private GetExecutableTool $toolFactory;

    /**
     * @param array{tools: array<int, array{name: string, description: string, inputSchema?: mixed}>} $toolsSchema
     *        ツール定義のスキーマ
     * @param GetExecutableTool|null $toolFactory ツール生成用ロジック（オプション）
     */
    public function __construct(
        private array $toolsSchema,
        ?GetExecutableTool $toolFactory = null
    ) {
        $this->toolFactory = $toolFactory ?? new GetExecutableTool();
    }

    /**
     * initialize メソッドのレスポンスを生成する
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function initialize(array $params): array
    {
        return [
            "protocolVersion" => $params['protocolVersion'] ?? "2024-11-05",
            "capabilities" => [
                "tools" => [
                    "listChanged" => false
                ]
            ],
            "serverInfo" => [
                "name" => "mcp-tools-php",
                "version" => "0.0.1"
            ]
        ];
    }

    /**
     * 登録されているツール一覧を取得する
     * @return array{tools: array<int, array{name: string, description: string, inputSchema?: mixed}>}
     */
    public function toolsList(): array
    {
        return $this->toolsSchema;
    }

    /**
     * 指定されたツールを実行する
     *
     * @param array<string, mixed> $params JSON-RPC パラメータ
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function execute(array $params): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        // ファクトリを通じてツールインスタンスを生成
        $tool = $this->toolFactory->create($toolName, $arguments, $this->toolsSchema);

        // リフレクションを使用して名前付き引数として invoke を呼び出す
        try {
            $reflectionMethod = new \ReflectionMethod($tool, 'invoke');
            /** @var array{content: list<array{type: string, text: string}>} $result */
            $result = $reflectionMethod->invokeArgs($tool, $arguments);
            return $result;
        } catch (\Throwable $e) {
            throw new \Exception('Tool execution failed: ' . $e->getMessage(), -32601);
        }
    }
}
