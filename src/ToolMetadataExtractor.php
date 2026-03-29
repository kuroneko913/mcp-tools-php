<?php

declare(strict_types=1);

namespace App;

use App\Tools\ToolInterface;
use ReflectionClass;

/**
 * ツールクラスからReflectionを用いて名前、説明、スキーマを抽出する
 */
class ToolMetadataExtractor
{
    /**
     * 指定されたディレクトリ内のツールからメタデータを抽出する
     *
     * @param list<string> $filePaths
     * @return list<array{name: string, description: string, inputSchema: array<string, mixed>}>
     */
    public function extract(array $filePaths): array
    {
        $toolsInfo = [];

        foreach ($filePaths as $file) {
            $className = $this->getClassNameFromFile($file);
            if (!$className) {
                continue;
            }


            if (!class_exists($className)) {
                require_once $file;
            }

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if (!$reflection->implementsInterface(ToolInterface::class) || $reflection->isAbstract()) {
                continue;
            }

            // ツール名の取得（クラス名を小文字化）
            $toolName = strtolower($reflection->getShortName());

            // 説明の取得
            $description = $this->parseClassDescription($reflection->getDocComment());

            // "invoke" メソッドのパラメーターからスキーマを構築
            $inputSchema = $this->extractInputSchema($reflection);

            $toolsInfo[] = [
                'name' => $toolName,
                'description' => $description,
                'inputSchema' => $inputSchema,
            ];
        }

        return $toolsInfo;
    }

    /**
     * ファイルからクラス名を抽出する（簡易版）
     */
    private function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }
        if (
            preg_match('/namespace\s+([^;]+);/i', $content, $matchNamespace) &&
            preg_match('/class\s+([a-zA-Z0-9_]+)/i', $content, $matchClass)
        ) {
            return trim($matchNamespace[1]) . '\\' . trim($matchClass[1]);
        }
        return null;
    }

    /**
     * invokeメソッドのパラメータからJSONスキーマ情報を抽出する
     *
     * @param ReflectionClass<object> $reflection
     * @return array{type: string, properties: array<string, array<string, mixed>>, required: list<string>}
     */
    private function extractInputSchema(ReflectionClass $reflection): array
    {
        $inputSchema = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];

        if (!$reflection->hasMethod('invoke')) {
            return $inputSchema;
        }

        $method = $reflection->getMethod('invoke');
        $paramDocs = $this->parseMethodParamDocs($method->getDocComment());

        foreach ($method->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $this->mapTypeToJsonSchemaType((string) $parameter->getType());
            $paramDesc = $paramDocs[$paramName]['description'] ?? '';

            $inputSchema['properties'][$paramName] = [
                'type' => $paramType,
                'description' => $paramDesc,
            ];

            if (!$parameter->isOptional()) {
                $inputSchema['required'][] = $paramName;
            }
        }

        return $inputSchema;
    }

    /**
     * PHPDocからクラスの説明部分を取得
     */
    private function parseClassDescription(string|false $docComment): string
    {
        if (!$docComment) {
            return '';
        }

        $lines = explode("\n", $docComment);
        foreach ($lines as $line) {
            $line = trim($line);
            $line = ltrim($line, '/* ');
            $line = rtrim($line, '*/ ');
            $line = trim($line);

            if (empty($line)) {
                continue;
            }
            if (str_starts_with($line, '@')) {
                break;
            }

            return $line; // 最初の非空行を説明として返す
        }

        return '';
    }

    /**
     * paramタグをパースする
     *
     * @param string|false $docComment
     * @return array<string, array{description: string, type: string}>
     */
    private function parseMethodParamDocs(string|false $docComment): array
    {
        if (!$docComment) {
            return [];
        }

        $paramDocs = [];
        $lines = explode("\n", $docComment);
        foreach ($lines as $line) {
            $line = trim(preg_replace('/^\/?\**\/?\s?/', '', $line) ?? '');
            if (preg_match('/@param\s+([^\s]+)\s+\$?([a-zA-Z0-9_]+)\s*(.*)?$/', $line, $matches)) {
                $type = $matches[1];
                $name = ltrim($matches[2], '$');
                $desc = trim((string)($matches[3] ?? ''));
                $paramDocs[$name] = [
                    'type' => $type,
                    'description' => $desc,
                ];
            }
        }

        return $paramDocs;
    }

    /**
     * PHPの型をJSON Schemaの型にマッピング
     */
    private function mapTypeToJsonSchemaType(string $type): string
    {
        $type = ltrim($type, '?'); // Nullable対応
        return match ($type) {
            'int', 'integer' => 'integer',
            'float', 'double' => 'number',
            'bool', 'boolean' => 'boolean',
            'array' => 'array',
            default => 'string',
        };
    }
}
