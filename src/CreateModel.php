<?php

namespace AloneWebMan\Model;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class CreateModel {
    public static function webMan(): void {
        $mysqlList = alone_get_data_base('mysql');
        if (count($mysqlList) == 0) {
            print_r("No database");
            return;
        }
        $appConfig = config('plugin.alone.model.app.model', []);
        $database = !empty($appConfig["switch"] ?? null) ? $appConfig["database"] ?? [] : [];
        $database = is_array($database) ? $database : [];
        foreach ($mysqlList as $connect => $config) {
            //获取名称
            $connectArr = explode(".", $connect);
            //获取目录名称
            $dirName = count($connectArr) == 1 ? $connect : join('', array_slice($connectArr, -1));
            $isDatabase = false;
            if (!empty($list = ($database[$connect] ?? []))) {
                foreach ($list as $dir => $name) {
                    if ($name == ($config['database'] ?? null)) {
                        $isDatabase = true;
                        break;
                    }
                }
            }
            if ($isDatabase === false) {
                $database[$connect][$dirName] = $config['database'] ?? null;
            }
        }
        foreach ($database as $connect => $list) {
            foreach ($list as $dir => $name) {
                $config = $mysqlList[$connect] ?? [];
                if (!empty($config)) {
                    // 数据库配置
                    $mysqlConfig = [
                        //数据库类型
                        'driver'      => $config["driver"] ?? 'mysql',
                        //服务器地址
                        'host'        => $config["host"] ?? '127.0.0.1',
                        //服务器端口
                        'port'        => $config["port"] ?? '3306',
                        //用户名
                        'username'    => $config["username"] ?? 'root',
                        //密码
                        'password'    => $config["password"] ?? '',
                        //数据库名
                        'database'    => $name,
                        //表前缀
                        'prefix'      => $config["prefix"] ?? '',
                        //字符集
                        'charset'     => $config["charset"] ?? 'utf8mb4',
                        //Unix域套
                        'unix_socket' => $config["unix_socket"] ?? null,
                    ];
                    // 生成配置
                    $buildConfig = [
                        //根目录(绝对路径)
                        "rootBase"       => base_path(),
                        //保存目录(相对路径)
                        "savePath"       => trim(trim($appConfig["namespace"], '/'), '\\') . "\\" . $dir,
                        //连接名称
                        "connectName"    => $connect,
                        //model前缀
                        "prefix"         => $appConfig["prefix"] ?? "",
                        //model后缀
                        "suffix"         => $appConfig["suffix"] ?? "",
                        //model主类存在时是否更新
                        "updateModel"    => $appConfig["updateModel"] ?? false,
                        //是否主库 (别库时生成database.table)
                        "main"           => $name === ($config["database"] ?? null),
                        //是否删除不存在表单的model
                        "deleteNotModel" => $appConfig["deleteNotModel"] ?? true,
                        //更新目录名称(此目录每次都会更新)
                        "updateName"     => $appConfig["updateName"] ?? "update",
                        //Common继承类名
                        "extends"        => $appConfig["extends"] ?? "\support\Model",
                        //Common use trait类,多个使用array
                        "trait"          => $appConfig["trait"] ?? ["use \AloneWebMan\Model\ModelHelper"],
                        //类参数设置
                        "args"           => $appConfig["args"] ?? [
                                'protected $guarded    = [];',
                                'public    $primaryKey = "id";',
                                'public    $timestamps = true;',
                                'public    $dateFormat = "Y-m-d H:i:s";'
                            ]
                    ];
                    print_r("==========================$name [start]==========================");
                    print_r("\r\n");
                    $helper = new Helper($mysqlConfig, $buildConfig);
                    $build = $helper->workerManLaravelModel();
                    if (empty($build['error'])) {
                        unset($build['error']);
                    }
                    if (empty($build['success'])) {
                        unset($build['success']);
                    }
                    print_r(count($build) == 1 ? $build[key($build)] : $build);
                    print_r("==========================$name [end]==========================");
                    print_r("\r\n\r\n");
                }
            }
        }
    }

    /**
     * 获取表单名
     * @param Expression|EloquentBuilder|Builder|Collection|mixed|static $builder
     * @return string
     */
    public static function getTableName(mixed $builder): string {
        $from = $builder->from;
        if (is_string($from)) {
            return $from;
        }
        $from = $from->getValue($builder->getGrammar());
        return is_string($from) ? $from : "";
    }
}