<?php

namespace AloneWebMan\Model\Db;

use stdClass;
use AloneWebMan\Model\SqlHelper;
use AloneWebMan\Model\Bootstrap;
use AloneWebMan\Model\ModelHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class ModelDb {
    /**
     * @param mixed $builder
     * @param bool  $model
     * @return void
     */
    public static function loader(mixed $builder, bool $model = true): void {
        $builder('updateInt', function(string $field, int|float $amount, array $extra = []) {
            return ModelDb::updateInt($this, $field, $amount, $extra);
        }, $model);
        $builder('page', function(int $page = 1, int $count = 20) {
            return ModelDb::page($this, $page, $count);
        }, $model);
        $builder('paging', function(mixed $page = 1, mixed $count = 20, mixed $columns = ['*'], mixed $pageName = 'page') {
            return ModelDb::paging($this, $page, $count, $columns, $pageName);
        }, $model);
        $builder('pageLimit', function(int $offset, int $limit) {
            return ModelDb::pageLimit($this, $offset, $limit);
        }, $model);
        $builder('firsts', function(array|string|null $field = null) {
            return ModelDb::firsts($this, $field);
        }, $model);
        $builder('gets', function(array|string|null $field = null) {
            return ModelDb::gets($this, $field);
        }, $model);
    }

    /**
     * @param mixed             $builder
     * @param array|string|null $field
     * @return mixed
     */
    public static function firsts(mixed $builder, array|string|null $field = null): mixed {
        $item = $builder->first();
        if (empty($field) && !empty($tab = ($builder->from ?? null))) {
            $class = Bootstrap::$tableClassList[$tab] ?? "";
            $field = !empty($class) ? ($class::$aloneArrayList ?? []) : null;
        }
        if (!empty($field) && !empty($item)) {
            $fields = is_array($field) ? $field : explode(',', $field);
            foreach ($fields as $key) {
                if (isset($item->$key)) {
                    $item->$key = is_array($item->$key) ? $item->$key : SqlHelper::isJson($item->$key);
                }
            }
        }
        return $item;
    }

    /**
     * @param mixed             $builder
     * @param array|string|null $field
     * @return mixed
     */
    public static function gets(mixed $builder, array|string|null $field = null): mixed {
        $items = $builder->get();
        if (empty($field) && !empty($tab = ($builder->from ?? null))) {
            $class = Bootstrap::$tableClassList[$tab] ?? "";
            $field = !empty($class) ? ($class::$aloneArrayList ?? []) : null;
        }
        if (!empty($field) && !empty($items)) {
            $fields = is_array($field) ? $field : explode(',', $field);
            foreach ($items as &$item) {
                $value = get_object_vars($item);
                $array = array_intersect_key($value, array_flip($fields));
                foreach ($array as $key => $val) {
                    $item->$key = is_array($val) ? $val : SqlHelper::isJson($val);
                }
            }
        }
        return $items;
    }

    /**
     * 自增 & 自减
     * lockForUpdate(); 会对选中的记录加排他锁，确保在事务内其他事务无法读取或修改这些记录
     * sharedLock();    允许其他事务读取被锁定的记录，但不允许修改 ,允许其他会话读取，但阻止写入
     * @param Expression|EloquentBuilder|Builder|mixed $builder
     * @param string                                   $field  字段名
     * @param int|float                                $amount 正数自增 & 负数自减
     * @param array                                    $extra  要修改的数据
     * @return float|int increment decrement
     */
    public static function updateInt(mixed $builder, string $field, int|float $amount, array $extra = []): float|int {
        if ($amount > 0) {
            return $builder->increment($field, abs($amount), $extra);
        } elseif ($amount < 0) {
            return $builder->decrement($field, -abs($amount), $extra);
        } elseif (!empty($extra)) {
            return $builder->update($extra);
        }
        return 0;
    }

    /**
     * 分页 forPage
     * @param Expression|EloquentBuilder|Builder|Collection|mixed|static $builder
     * @param int                                                        $page  第几页
     * @param int                                                        $count 每页记录数
     * @return stdClass
     */
    public static function page(mixed $builder, int $page = 1, int $count = 20): stdClass {
        $array['count'] = $count;                                       //每页记录数。
        $array['total'] = $builder->count();                            //总记录数。
        $array['page'] = $page;                                         //当前页码。
        $array['pages'] = (int) ceil($array['total'] / $array['count']);//总页数。
        $array['data'] = $builder->forPage($page, $count)->get();       //数据列表。
        return alone_arr_to_obj($array);
    }

    /**
     * 分页 带总数和分页信息
     * @param Expression|EloquentBuilder|Builder|Collection|mixed|static $builder
     * @param int|null                                                   $page  第几页
     * @param int|null|Closure                                           $count 每页记录数
     * @param array|string                                               $columns
     * @param string                                                     $pageName
     * @return stdClass
     */
    public static function paging(mixed $builder, mixed $page = 1, mixed $count = 20, mixed $columns = ['*'], mixed $pageName = 'page'): stdClass {
        $item = $builder->paginate($count, $columns, $pageName, $page);
        $array['count'] = $item->perPage();   //每页记录数。
        $array['total'] = $item->total();     //总记录数。
        $array['page'] = $item->currentPage();//当前页码。
        $array['pages'] = $item->lastPage();  //总页数。
        $array['data'] = $item->items();      //数据列表。
        return alone_arr_to_obj($array);
    }

    /**
     * 分页,未计算
     * @param Expression|EloquentBuilder|Builder|Collection|mixed|static $builder
     * @param int                                                        $offset
     * @param int                                                        $limit
     * @return stdClass
     */
    public static function pageLimit(mixed $builder, int $offset, int $limit): stdClass {
        $array['count'] = $limit;                                         //每页记录数。
        $array['total'] = $builder->count();                              //总记录数。
        $array['page'] = (int) ceil($offset / $limit) + 1;                //当前页码。
        $array['pages'] = (int) ceil($array['total'] / $array['count']);  //总页数。
        $array['data'] = $builder->offset($offset)->limit($limit)->get(); //数据列表。
        return alone_arr_to_obj($array);
    }
}