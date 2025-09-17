<?php

namespace AloneWebMan\Model;

use support\Db;
use Throwable;
use Exception;
use stdClass;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @method Expression|EloquentBuilder|Builder|Collection|Connection|mixed|static countRaw(string $alias = '', string $where = '')
 * @method Expression|EloquentBuilder|Builder|Collection|Connection|mixed|static sumRaw(string|array $field, string $where = '')
 * @method Expression|EloquentBuilder|Builder|Collection|Connection|mixed|static maxRaw(string|array $field, string $where = '')
 * @method Expression|EloquentBuilder|Builder|Collection|Connection|mixed|static minRaw(string|array $field, string $where = '')
 * @method Expression|EloquentBuilder|Builder|Collection|Connection|mixed|static avgRaw(string|array $field, string $where = '')
 * @method stdClass page(int $page = 1, int $count = 20)
 * @method stdClass paging(int $page = 1, int $count = 20, mixed $columns = ['*'], mixed $pageName = 'page')
 * @method stdClass pageLimit(int $offset, int $limit)
 * @method int updateInt(string $field, int|float $amount, array $extra = [])
 * @method int firsts(array|string|null $field = null)
 * @method int gets(array|string|null $field = null)
 */
trait ModelHelper {
    public static array $tableClassList = [];

    /**
     * Db连接
     * @return Expression|EloquentBuilder|Builder|Collection|Connection|static
     */
    public static function link(): Expression|EloquentBuilder|Builder|Collection|Connection|static {
        static::$tableClassList[static::$aloneTableName] = static::class;
        return Db::connection(static::$aloneConnName);
    }

    /**
     * Db表单
     * @param string|null $as
     * @return Expression|EloquentBuilder|Builder|Collection|Connection|static
     */
    public static function tab(string|null $as = null): Expression|EloquentBuilder|Builder|Collection|Connection|static {
        return self::link()->table(static::$aloneTableName, $as);
    }

    /**
     * Db查询
     * @param mixed      $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @param string     $boolean
     * @return Expression|EloquentBuilder|Builder|Collection|Connection|static
     */
    public static function whe(mixed $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): Expression|EloquentBuilder|Builder|Collection|Connection|static {
        return self::tab()->where($column, $operator, $value, $boolean);
    }

    /**
     * Db事务
     * @param callable          $callable
     * @param array|string|null $model 跨库model
     * @return mixed
     */
    public static function affair(callable $callable, array|string|null $model = null): mixed {
        $modeConn = [];
        $modelList = $model ? (is_array($model) ? $model : [$model]) : [static::class];
        foreach ($modelList as $conn) {
            $connName = $conn::$aloneConnName;
            if (!(in_array($connName, $modeConn))) {
                $modeConn[] = $connName;
            }
        }
        $tran = function($name) use ($modeConn) {
            foreach ($modeConn as $v) {
                call_user_func([Db::connection($v), $name]);
            }
        };
        return $callable(function() use ($tran) {
            $tran('beginTransaction');
        }, function() use ($tran) {
            $tran('commit');
        }, function() use ($tran) {
            $tran('rollBack');
        });
    }

    /**
     * 自动单多库事务
     * @param callable               $callable
     * @param array|string|bool|null $model model::class,默认当前库事务,当前库可作$throw参数
     * @param bool|null              $throw
     *                                      true      成功:return 执行包;   错误: throw new 报错信息
     *                                      false     成功:return false;   错误: return    报错信息
     *                                      null      成功:return 执行包;   错误: return    报错信息
     * @return mixed
     */
    public static function work(callable $callable, array|string|bool|null $model = null, bool|null $throw = false): mixed {
        $isValidModelClass = is_array($model) || is_string($model);
        $throw = $isValidModelClass ? $throw : $model;
        return static::affair(function($begin, $submit, $roll) use ($callable, $throw) {
            $begin();
            try {
                $res = $callable();
                $submit();
                return (($throw === false) ? false : $res);
            } catch (Throwable|Exception $e) {
                $roll();
                if (empty($throw)) {
                    return $e->getMessage();
                }
                throw new Exception($e->getMessage());
            }
        }, ($isValidModelClass ? $model : []));
    }

    /**
     * Db自动事务
     * @param callable          $callable 执行包,返回false回滚
     * @param callable|bool     $error    报错执行包,true=throw,false=return,callable=自定返回
     * @param array|string|null $model    跨库model
     * @return mixed
     */
    public static function trans(callable $callable, callable|bool $error = false, array|string|null $model = null): mixed {
        return static::affair(function($begin, $submit, $roll) use ($callable, $error) {
            try {
                $begin();
                $res = $callable();
                if ($res === false) {
                    $roll();
                } else {
                    $submit();
                }
                return $res;
            } catch (Throwable|Exception $e) {
                $roll();
                $err = ['code' => $e->getCode(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'msg' => $e->getMessage()];
                if ($error === true) {
                    throw new Exception(json_encode($err));
                }
                return ($error === false ? $err : (is_callable($error) ? $error($e, $err) : $err));
            }
        }, $model);
    }
}