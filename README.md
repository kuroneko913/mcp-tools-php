# MCP STDINサーバ（PHP版）

## 概要
JSON-RPC 2.0形式でSTDIN/STDOUT経由で処理するPHP製の簡易MCPサーバです。

自作することでプロトコルの理解を深めるのが目的です。

一応、Cursor と GitHub Copilot と MCP Inspector で、initialize, tools/list, tools/call が動作することは確認しました。


参考:

[ModelContextProtocol](https://github.com/modelcontextprotocol)

[MCP Inspector](https://github.com/modelcontextprotocol/inspector)

## OpenWeatherAPIキーの登録
天気を取得するために、無料で使える OpenWeather のAPI ([Built-in API request by city name](https://openweathermap.org/current))を使用しています。

APIキーはログインして My API Keys で発行してください。

https://home.openweathermap.org/api_keys

発行したキーは、以下のコマンドを叩くか、.envファイルに追加してください。
(.envファイルはmakeコマンドから試す用です)

```
export OPENWEATHER_API_KEY=XXXXXX
```

## エージェントへの登録
Cursor や GitHubCopilot への設定例

```json
{
  "mcpServers": {
    "mcp-tools-php": {
      "command": "env",
      "args": [
        "docker",
        "run",
        "-i",
        "--rm",
        "-e",
        "OPENWEATHER_API_KEY",
        "mcp-tools-php"
      ]
    }
  }
}
```

## コマンド実行例

### 1. initialize
```sh
# リクエスト
echo '{"jsonrpc": "2.0", "id": 1, "method": "initialize", "params": {"protocolVersion": "2024-11-05", "clientInfo": {"name": "test-client", "version": "0.1.0"}}}' | make run
# レスポンス例
# {"jsonrpc":"2.0","id":1,"result":{"protocolVersion":"2024-11-05","capabilities":{"tools":{"listChanged":false}},"serverInfo":{"name":"mcp-tools-php","version":"0.0.1"}}}
```

### 2. tools/list
```sh
# リクエスト
echo '{"jsonrpc": "2.0", "id": 2, "method": "tools/list"}' | make run 
# レスポンス例
# {"jsonrpc":"2.0","id":2,"result":{"tools":[{"name":"weather","description":"都市の天気を取得します","inputSchema":{"type":"object","properties":{"location":{"type":"string","description":"都市名 (例: Tokyo,JP)"}},"required":["location"]}},{"name":"clock","description":"現在の時刻を取得します","inputSchema":{"type":"object","properties":{"timezone":{"type":"string","description":"タイムゾーン (例: Asia\/Tokyo)"}},"required":["timezone"]}}]}}
```

### 3. tools/call
```sh
# 天気取得ツールの呼び出し
echo '{"jsonrpc": "2.0", "id": 3, "method": "tools/call", "params": {"name": "weather", "arguments": {"location": "Tokyo,JP"}}}' | make run
# レスポンス例
# {"jsonrpc":"2.0","id":3,"result":{"content":[{"type":"text","text":"{\"location\":\"Tokyo,JP\",\"weather\":\"\\u66c7\\u308a\\u304c\\u3061\",\"temperature\":17.36,\"humidity\":92}"}]}}

# 現在時刻ツールの呼び出し
echo '{"jsonrpc": "2.0", "id": 4, "method": "tools/call", "params": {"name": "clock", "arguments":{"timezone": "Asia/Tokyo"}}}' | make run
# レスポンス例
# {"jsonrpc":"2.0","id":4,"result":{"content":[{"type":"text","text":"2025-05-11 04:54:23"}]}}
```

## リクエスト形式

- 共通: JSON-RPC 2.0
  - `jsonrpc`: "2.0"
  - `id`: 任意のリクエストID
  - `method`: "initialize" | "tools/list" | "tools/call"
  - `params`: メソッドごとのパラメータ

## エラー例

- 不正なリクエストや未対応メソッドの場合は、JSON-RPC 2.0のerror形式で返します。(コードは厳密ではないかも)
  ```sh
  # 引数が間違っている
  echo '{"jsonrpc": "2.0", "id": 4, "method": "tools/call", "params": {"name": "clock", "arguments":{"timezone": ""}}}' | make run
  # エラーレスポンス例
  # {"jsonrpc":"2.0","id":4,"error":{"code":-32601,"message":"Tool execution failed: timezone is required"}}

  # ツール名が間違っている
  echo '{"jsonrpc": "2.0", "id": 4, "method": "tools/call", "params": {"name": "watch", "arguments":{"timezone": "Asia/Tokyo"}}}' | make run 
  # エラーレスポンス例
  # {"jsonrpc":"2.0","id":4,"error":{"code":-32601,"message":"Tool not found"}}

  # 存在しないメソッドを指定した場合
  echo '{"jsonrpc": "2.0", "id": 4, "method": "tools/invoke", "params": {"name": "watch", "arguments":{"timezone": "Asia/Tokyo"}}}' | make run
  # エラーレスポンス例
  # {"jsonrpc":"2.0","id":4,"error":{"code":-32601,"message":"Method not found"}}
  ```
