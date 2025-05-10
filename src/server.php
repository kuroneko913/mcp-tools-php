<?php

namespace App;

require_once dirname(__DIR__,1) . '/vendor/autoload.php';

$tools = json_decode(file_get_contents(dirname(__DIR__,1) . '/tools.json'), true);
$modelContextProtocol = new ModelContextProtocol($tools);

// MCP STDINサーバ (JSON-RPC 2.0)
while ($line = fgets(STDIN)) {
    $input = trim($line);
    if ($input === '') continue;
    $request = json_decode($input, true);
    if (!is_array($request) || !isset($request['jsonrpc']) || !isset($request['method']) || !isset($request['id'])) {
        echo json_encode([
            'jsonrpc' => '2.0',
            'id' => $request['id'] ?? null,
            'error' => [
                'code' => -32600,
                'message' => 'Invalid Request'
            ]
        ], JSON_UNESCAPED_UNICODE) . "\n";
        continue;
    }
    $id = $request['id'] ?? null;
    $method = $request['method'] ?? '';
    $params = $request['params'] ?? [];
    try {
        $result = match($method) {
            'initialize' => $modelContextProtocol->initialize($params),
            'tools/list' => $modelContextProtocol->toolsList(),
            'tools/call' => $modelContextProtocol->execute($params),
            default => throw new \Exception('Method not found', -32601),
        };

        echo json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result
        ], JSON_UNESCAPED_UNICODE) . "\n";
    } catch (\Exception $e) {
        echo json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $e->getCode() ?: -32603,
                'message' => $e->getMessage()
            ]
        ], JSON_UNESCAPED_UNICODE) . "\n";
    }
} 
