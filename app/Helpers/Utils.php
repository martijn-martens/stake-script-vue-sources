<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;

class Utils
{
    /**
     * Get an array of Carbon dates based on the period
     *
     * @param string $period
     * @return array
     */
    public static function getDateRange(?string $period): array
    {
        if ($period == 'prev_week') {
            return [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->startOfWeek()];
        } elseif ($period == 'month') {
            return [Carbon::now()->startOfMonth(), Carbon::now()];
        } elseif ($period == 'prev_month') {
            return [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->startOfMonth()];
        } elseif ($period == 'year') {
            return [Carbon::now()->startOfYear(), Carbon::now()];
        } elseif ($period == 'prev_year') {
            return [Carbon::now()->subYear()->startOfYear(), Carbon::now()->startOfYear()];
        // $period = 'week' is the default value
        } else {
            return [Carbon::now()->startOfWeek(), Carbon::now()];
        }
    }

    public static function assert($class, $hash, $cb)
    {
        try {
            return Cache::remember('hash_' . class_basename($class), 300, function () use ($class, $hash) {
                return sha1(preg_replace('#\s+#', '', file_get_contents((new ReflectionClass($class))->getFileName()))) == $hash;
            }) ?: $cb();
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Get class constant name by its instance value
     *
     * @param  string  $class
     * @param  object  $instance
     * @param $value
     * @return string
     * @throws \ReflectionException
     */
    public static function getConstantNameByValue(string $class, object $instance, $value): string
    {
        $r = new ReflectionClass($class);

        return collect($r->getConstants())
            ->filter(function ($constantValue, $constantName) use ($value) {
                return $value === $constantValue;
            })
            ->keys()
            ->first();
    }
}
