<?php

namespace App\Tools;

interface ToolInterface
{
    /**
     * ツールの処理を実行する
     * @param array $params
     * @return array{content: array{type: string, text: string}}
     */
    public function invoke(array $params) : array;
}
