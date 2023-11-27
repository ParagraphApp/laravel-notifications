<?php

namespace Paragraph\LaravelNotifications\Services;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Support\Facades\Cache;

class CachingClassFinder
{
    /**
     * @param $namespace
     * @param bool $ignoreCache
     * @return array
     */
    public static function getClassesInNamespace($namespace, $ignoreCache = false)
    {
        if ($ignoreCache) {
            return ClassFinder::getClassesInNamespace($namespace, ClassFinder::RECURSIVE_MODE);
        }

        return Cache::store('file')->rememberForever("classes-{$namespace}", function () use ($namespace) {
            return ClassFinder::getClassesInNamespace($namespace, ClassFinder::RECURSIVE_MODE);
        });
    }
}
