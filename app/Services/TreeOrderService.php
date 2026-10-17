<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class TreeOrderService
{
    public static function normalize(string $modelClass, ?string $parentColumn = null, ?int $parentId = null): void
    {
        $query = $modelClass::query();

        if ($parentColumn !== null && $parentId !== null) {
            $query->where($parentColumn, $parentId);
        }

        $items = $query
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'orden']);

        foreach ($items as $index => $item) {
            $orden = $index + 1;

            if ((int) $item->orden !== $orden) {
                $modelClass::whereKey($item->getKey())->update(['orden' => $orden]);
            }
        }
    }

    public static function next(string $modelClass, ?string $parentColumn = null, ?int $parentId = null): int
    {
        self::normalize($modelClass, $parentColumn, $parentId);

        $query = $modelClass::query();

        if ($parentColumn !== null && $parentId !== null) {
            $query->where($parentColumn, $parentId);
        }

        return ((int) $query->max('orden')) + 1;
    }
}
