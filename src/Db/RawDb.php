<?php

namespace AloneWebMan\Model\Db;

use support\Db;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class RawDb {
    /**
     * @param mixed $builder
     * @param bool  $model
     * @return void
     */
    public static function loader(mixed $builder, bool $model = true): void {
        $builder('countRaw', function(string $alias = 'count', string $where = '') {
            return RawDb::countRaw($this, $alias, $where);
        }, $model);
        $builder('sumRaw', function(string|array $field, string $where = '') {
            return RawDb::sumRaw($this, $field, $where);
        }, $model);
        $builder('maxRaw', function(string|array $field, string $where = '') {
            return RawDb::maxRaw($this, $field, $where);
        }, $model);
        $builder('minRaw', function(string|array $field, string $where = '') {
            return RawDb::minRaw($this, $field, $where);
        }, $model);
        $builder('avgRaw', function(string|array $field, string $where = '') {
            return RawDb::avgRaw($this, $field, $where);
        }, $model);
    }

    /**
     * 总数
     * @param Expression|EloquentBuilder|Builder|Collection|mixed|static $builder
     * @param string                                                     $alias 返回名
     * @param string                                                     $where 查询条件
     * @return Expression|EloquentBuilder|Builder|Collection|mixed|static
     */
    public static function countRaw(mixed $builder, string $alias = 'count', string $where = ''): mixed {
        $sql = "COALESCE(COUNT(" . (!empty($where) ? ("CASE WHEN " . $where . " THEN 0 END") : "*") . "),0) as " . $alias;
        return $builder->selectRaw(Db::raw(trim($sql, ',')));
    }

    /**
     * 合计
     * @param Expression|EloquentBuilder|Builder|Collection|mixed|static $builder
     * @param string                                                     $field 字段名 或者 (字段名 as 别名)
     * @param string                                                     $where 查询条件
     * @return Expression|EloquentBuilder|Builder|Collection|mixed|static
     */
    public static function sumRaw(mixed $builder, string $field, string $where = ''): mixed {
        $alias = alone_model_as($field);
        $sql = "COALESCE(SUM(" . (!empty($where) ? ("CASE WHEN " . $where . " THEN " . $alias[0] . " ELSE 0 END") : $alias[0]) . "),0) as " . $alias[1];
        return $builder->selectRaw(Db::raw(trim($sql, ',')));
    }

    /**
     * 最大
     * @param Expression|EloquentBuilder|Builder|Collection|mixed|static $builder
     * @param string                                                     $field 字段名 或者 (字段名 as 别名)
     * @param string                                                     $where 查询条件
     * @return Expression|EloquentBuilder|Builder|Collection|mixed|static
     */
    public static function maxRaw(mixed $builder, string $field, string $where = ''): mixed {
        $alias = alone_model_as($field);
        $sql = "COALESCE(MAX(" . (!empty($where) ? ("CASE WHEN " . $where . " THEN " . $alias[0] . " END") : $alias[0]) . "),0) as " . $alias[1];
        return $builder->selectRaw(Db::raw(trim($sql, ',')));
    }

    /**
     * 最小
     * @param Expression|EloquentBuilder|Builder|Collection|mixed|static $builder
     * @param string                                                     $field 字段名 或者 (字段名 as 别名)
     * @param string                                                     $where 查询条件
     * @return Expression|EloquentBuilder|Builder|Collection|mixed|static
     */
    public static function minRaw(mixed $builder, string $field, string $where = ''): mixed {
        $alias = alone_model_as($field);
        $sql = "COALESCE(MIN(" . (!empty($where) ? ("CASE WHEN " . $where . " THEN " . $alias[0] . " END") : $alias[0]) . "),0) as " . $alias[1];
        return $builder->selectRaw(Db::raw(trim($sql, ',')));
    }

    /**
     * 平均值
     * @param Expression|EloquentBuilder|Builder|Collection|mixed|static $builder
     * @param string                                                     $field 字段名 或者 (字段名 as 别名)
     * @param string                                                     $where 查询条件
     * @return Expression|EloquentBuilder|Builder|Collection|mixed|static
     */
    public static function avgRaw(mixed $builder, string $field, string $where = ''): mixed {
        $alias = alone_model_as($field);
        $sql = "COALESCE(AVG(" . (!empty($where) ? ("CASE WHEN " . $where . " THEN " . $alias[0] . " END") : $alias[0]) . "),0) as " . $alias[1];
        return $builder->selectRaw(Db::raw(trim($sql, ',')));
    }
}