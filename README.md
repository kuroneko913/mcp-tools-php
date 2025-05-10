# MCP STDINサーバ（PHP版）

## 概要

このサーバは、tools.jsonで定義されたツール（get_weather, now）をJSON-RPC 2.0形式でSTDIN/STDOUT経由で処理するPHP製MCPサーバです。

## 実行例

### 1. initialize
```sh
# リクエスト
echo '{"jsonrpc": "2.0", "id": 1, "method": "initialize", "params": {"protocolVersion": "2024-11-05", "clientInfo": {"name": "test-client", "version": "0.1.0"}}}' | make run
# レスポンス例
# {"jsonrpc":"2.0","id":1,"result":{"protocolVersion":"2024-11-05","capabilities":{"tools":{"listChanged":false}},"serverInfo":{"name":"mcp-tools-php","version":"0.0.1"}}}
```

### 2. toolsList
```sh
# リクエスト
echo '{"jsonrpc": "2.0", "id": 2, "method": "tools/list"}' | make run 
# レスポンス例
# {"jsonrpc":"2.0","id":2,"result":{"tools":[{"name":"get_weather","description":"都市の天気を取得します","inputSchema":{"type":"object","properties":{"location":{"type":"string","description":"都市名 (例: Tokyo,JP)"}},"required":["location"]}},{"name":"now","description":"現在の時刻を取得します","inputSchema":{"type":"object","properties":{"timezone":{"type":"string","description":"タイムゾーン (例: Asia\/Tokyo)"}},"required":["timezone"]}}]}}
```

### 3. ツール実行（execute）
```sh
# 天気取得ツールの呼び出し
echo '{"jsonrpc": "2.0", "id": 3, "method": "tools/call", "params": {"name": "get_weather", "arguments": {"location": "Tokyo,JP"}}}' | make run
# レスポンス例
# {"jsonrpc":"2.0","id":3,"result":{"content":[{"type":"text","text":"{\"location\":\"Tokyo,JP\",\"weather\":\"\\u6674\\u308c\",\"temperature\":\"25\",\"unit\":\"C\"}"}]}}

# 現在時刻ツールの呼び出し
echo '{"jsonrpc": "2.0", "id": 4, "method": "tools/call", "params": {"name": "now", "arguments":{"timezone": "Asia/Tokyo"}}}' | make run
# レスポンス例
# {"jsonrpc":"2.0","id":4,"result":{"content":[{"type":"text","text":"{\"timezone\":\"Asia\\\/Tokyo\",\"now\":\"2025-05-11 00:08:43\"}"}]}}
```

## リクエスト形式

- 共通: JSON-RPC 2.0
  - `jsonrpc`: "2.0"
  - `id`: 任意のリクエストID
  - `method`: "initialize" | "tools/list" | "tools/call"
  - `params`: メソッドごとのパラメータ

## エラー例

- 不正なリクエストや未対応メソッドの場合は、JSON-RPC 2.0のerror形式で返します。
  - 例: ```{"jsonrpc":"2.0","id":4,"error":{"code":-32601,"message":"Tool not found"}}``` 
