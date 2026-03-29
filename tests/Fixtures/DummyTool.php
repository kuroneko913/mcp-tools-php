<?php

namespace Tests\Fixtures;

use App\Tools\ToolInterface;

/**
 * テスト用のダミーツール
 *
 * このクラスはリフレクションのテストに使用します。
 */
class DummyTool implements ToolInterface
{
    /**
     * @param string $message 送信するメッセージ
     * @param int $count 繰り返し回数 (例: 5)
     * @return array{content: list<array{type: string, text: string}>}
     */
    public function invoke(string $message, int $count): array
    {
        return [
            "content" => [
                [
                    "type" => "text",
                    "text" => str_repeat($message, $count)
                ]
            ]
        ];
    }
}
