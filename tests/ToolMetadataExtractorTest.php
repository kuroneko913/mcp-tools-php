<?php

namespace Tests;

use App\ToolMetadataExtractor;
use PHPUnit\Framework\TestCase;

class ToolMetadataExtractorTest extends TestCase
{
    /**
     * 正常系や異常系のテスト
     */
    public function testExtract(): void
    {
        require_once __DIR__ . '/Fixtures/DummyTool.php';

        $extractor = new ToolMetadataExtractor();
        $toolPaths = [
            __DIR__ . '/Fixtures/DummyTool.php'
        ];

        $toolsInfo = $extractor->extract($toolPaths);

        // toolのリストが1件であること
        $this->assertCount(1, $toolsInfo);

        $tool = $toolsInfo[0];

        // 名前がクラス名の小文字であること
        $this->assertEquals('dummytool', $tool['name']);

        // 説明がクラスのPHPDocから取得できていること
        $this->assertEquals('テスト用のダミーツール', $tool['description']);

        // inputSchemaが正しく構築されていること
        $inputSchema = $tool['inputSchema'];
        $this->assertEquals('object', $inputSchema['type']);

        // propertiesの確認
        $this->assertArrayHasKey('message', $inputSchema['properties']);
        $this->assertEquals('string', $inputSchema['properties']['message']['type']);
        $this->assertEquals('送信するメッセージ', $inputSchema['properties']['message']['description']);

        $this->assertArrayHasKey('count', $inputSchema['properties']);
        $this->assertEquals('integer', $inputSchema['properties']['count']['type']); // intはintegerとして解釈されるべき
        $this->assertEquals('繰り返し回数 (例: 5)', $inputSchema['properties']['count']['description']);

        // requiredの確認
        $this->assertEquals(['message', 'count'], $inputSchema['required']);
    }
}
