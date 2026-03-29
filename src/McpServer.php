<?php

declare(strict_types=1);

namespace App;

/**
 * MCP サーバーの入出力および JSON-RPC メッセージハンドリングを担当するクラス
 */
class McpServer
{
    /**
     * @param ModelContextProtocol $protocol
     */
    public function __construct(private ModelContextProtocol $protocol)
    {
    }

    /**
     * サーバーを実行し、入出力を処理する
     *
     * @param resource $input 入力ストリーム
     * @param resource $output 出力ストリーム
     * @return void
     */
    public function run($input, $output): void
    {
        while ($line = fgets($input)) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $request = json_decode($line, true);
            if (
                !is_array($request)
                || ($request['jsonrpc'] ?? null) !== '2.0'
                || !isset($request['method'])
            ) {
                $errorResponse = [
                    'jsonrpc' => '2.0',
                    'id' => is_array($request) ? ($request['id'] ?? null) : null,
                    'error' => [
                        'code' => -32600,
                        'message' => 'Invalid Request',
                    ],
                ];
                fwrite($output, json_encode($errorResponse, JSON_UNESCAPED_UNICODE) . "\n");
                continue;
            }

            // 'id' がない場合は通知 (Notification) とみなして処理を継続 (プロトコル仕様)
            // または無視して次へ（通知にはレスポンスを返さない）
            if (!array_key_exists('id', $request)) {
                continue;
            }

            $id = $request['id'];
            $method = $request['method'];
            $params = $request['params'] ?? [];

            try {
                $result = match ($method) {
                    'initialize' => $this->protocol->initialize($params),
                    'tools/list' => $this->protocol->toolsList(),
                    'tools/call' => $this->protocol->execute($params),
                    default => throw new \Exception('Method not found', -32601),
                };

                $response = [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => $result,
                ];
                fwrite($output, json_encode($response, JSON_UNESCAPED_UNICODE) . "\n");
            } catch (\Throwable $e) {
                $errorResponse = [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => $e->getCode() ?: -32603,
                        'message' => $e->getMessage(),
                    ],
                ];
                fwrite($output, json_encode($errorResponse, JSON_UNESCAPED_UNICODE) . "\n");
            }
        }
    }
}
