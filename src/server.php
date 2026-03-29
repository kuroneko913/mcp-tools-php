<?php

declare(strict_types=1);

namespace App;

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

// 1. ツールのメタデータを抽出
$extractor = new ToolMetadataExtractor();
$toolsDir = __DIR__ . '/Tools';
$filePaths = glob($toolsDir . '/*.php');
if ($filePaths === false) {
    $filePaths = [];
}
$toolsSchema = ['tools' => $extractor->extract($filePaths)];

// 2. プロトコル層の初期化（ツール生成ロジックは内部でデフォルト生成されます）
$modelContextProtocol = new ModelContextProtocol($toolsSchema);

// 3. MCP サーバーの起動
$mcpServer = new McpServer($modelContextProtocol);
$mcpServer->run(STDIN, STDOUT);
