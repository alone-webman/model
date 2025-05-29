<?php

use support\Db;
use AloneWebMan\Model\Helper;
use Illuminate\Database\Events\QueryExecuted;

/**
 * 获取数据库配置列表
 * @param array|string $driver 支持的数据库
 * @return mixed
 */
function alone_get_data_base(array|string $driver = []): mixed {
    $list = config('database.connections', []);
    if (count($list) > 0) {
        $driver = is_array($driver) ? $driver : explode(",", $driver);
        $driver = array_map('strtolower', $driver);
        foreach ($list as $key => $item) {
            if (!empty($driver) && empty(in_array(strtolower($item['driver'] ?? ''), $driver))) {
                unset($list[$key]);
            }
        }
    }
    return $list;
}

if (!function_exists('alone_model')) {
    /**
     * 生成model文件
     * @param array $config [namespace=相对路径,prefix=文件前缀,suffix=文件后缀,extends=继承类名,trait=引入类]
     * @param bool  $update 是否更新已存在model文件头property
     * @return array|string
     */
    function alone_model(array $config, bool $update = true): array|string {
        return Helper::model(alone_get_data_base(config('plugin.alone.model.app.driver', '')), $config, $update);
    }
}

if (!function_exists('alone_model_table')) {
    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param string $name    字符串
     * @param bool   $type    转换类型 true不使用_,false=使用_
     * @param bool   $ucFirst 首字母是否大写（驼峰规则）
     * @return string
     */
    function alone_model_table(string $name, bool $type = true, bool $ucFirst = true): string {
        return Helper::modeTable($name, $type, $ucFirst);
    }
}
if (!function_exists('alone_model_as')) {
    /**
     * 获取别名
     * @param string $name
     * @param bool   $type true=只返回别名string,false返回array(名,别名)
     * @return array|string
     */
    function alone_model_as(string $name, bool $type = false): array|string {
        preg_match('/(\w+[^,]*)\s+as\s+(\w+[^,]*)/i', trim($name), $arr);
        return (!empty($type) ? trim($arr[2] ?? $name) : [trim($arr[1] ?? $name), trim($arr[2] ?? $name)]);
    }
}
if (!function_exists('alone_model_sql')) {
    /**
     * 接收执行的sql
     * @param callable $pull
     * @return void
     */
    function alone_model_sql(callable $pull): void {
        Db::connection()->listen(function(QueryExecuted $queryExecuted) use ($pull) {
            if (isset($queryExecuted->sql) and $queryExecuted->sql !== "select 1") {
                $finalQuery = $queryExecuted->sql;
                if (!empty($queryExecuted->bindings)) {
                    $replacedQuery = str_replace('?', '%s', $finalQuery);
                    $finalQuery = vsprintf($replacedQuery, array_map(function($param) {
                        return is_string($param) ? "'" . addslashes($param) . "'" : $param;
                    }, $queryExecuted->bindings));
                }
                $pull([
                    'connect' => $queryExecuted->connectionName,
                    'sql'     => $finalQuery,
                    'exec'    => $queryExecuted->time,
                    'date'    => date("Y-m-d H:i:s")
                ], $queryExecuted);
            }
        });
    }
}
if (!function_exists('alone_get_dir_file')) {
    /**
     * 获取目录下全部文件列表
     * @param string $path
     * @param string $route
     * @param array  $result
     * @return mixed
     */
    function alone_get_dir_file(string $path, string $route = '', array $result = []): mixed {
        if (!empty(is_dir($path))) {
            $files = scandir($path);
            $route = $route ?: $path;
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . '/' . $file)) {
                        $result = alone_get_dir_file($path . '/' . $file, $route, $result);
                    } else {
                        $key = trim($file, '/');
                        if (!empty($route)) {
                            $route_ = trim(realpath($route), '/');
                            $path_ = trim(realpath($path), '/');
                            if (!empty($route_) && !empty($path_) && str_starts_with($path_, $route_)) {
                                $key = trim(substr($path_, strlen($route_)), '/') . '/' . $key;
                            }
                        }
                        $result[trim($key, '/')] = $path . '/' . $file;
                    }
                }
            }
        }
        return $result;
    }
}

if (!function_exists('alone_arr_to_obj')) {
    /**
     * @param array $array 要转换的array
     * @param bool  $type  是否支持多级
     * @return stdClass
     */
    function alone_arr_to_obj(array $array, bool $type = false): stdClass {
        $obj = new stdClass();
        foreach ($array as $key => $value) {
            if (!empty($type) && is_array($value)) {
                $obj->$key = alone_arr_to_obj($value, true);
            } else {
                $obj->$key = $value;
            }
        }
        return $obj;
    }
}