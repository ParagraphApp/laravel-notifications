<?php

namespace Paragraph\LaravelNotifications\Services;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Support\Facades\Cache;

class CachingClassFinder
{
    /**
     * @param $namespace
     * @return array
     */
    public static function getClassesInNamespace($namespace)
    {
        return Cache::store('file')->rememberForever("classes-{$namespace}", function () use ($namespace) {
            return ClassFinder::getClassesInNamespace($namespace, ClassFinder::RECURSIVE_MODE);
        });
    }
}
