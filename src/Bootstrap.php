<?php

namespace AloneWebMan\Model;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Bootstrap {
    public static array $tableClassList = [];

    public static function start($worker): void {
        $extend = config('plugin.alone.model.app.extend', []);
        if (!empty($extend['status'] ?? '') && !empty($method = ($extend['method'] ?? '')) && is_callable($method)) {
            $method(function($name, $macro, $model = true) {
                Builder::macro($name, $macro);
                ($model === true) && EloquentBuilder::macro($name, $macro);
            });
        }
        if ($worker) {
            $listen = config('plugin.alone.model.app.listen', []);
            if (!empty($listen['status'] ?? '') && !empty($method = ($listen['method'] ?? '')) && is_callable($method)) {
                alone_model_sql($method);
            }
        }
    }
}