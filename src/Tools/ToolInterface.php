<?php

declare(strict_types=1);

namespace App\Tools;

/**
 * ツールであることを示すマーカーインターフェース
 */
interface ToolInterface
{
    // invoke メソッドは各実装クラスで具体的な型シグネチャを持って定義されます。
    // 実行時にはリフレクションを用いて名前付き引数として呼び出されます。
}
