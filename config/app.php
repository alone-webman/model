<?php

use AloneWebMan\Model\Db\ModelDb;
use Illuminate\Database\Events\QueryExecuted;

return [
    'enable' => true,

    /*
     * sql备份目录(绝对路径)
     */
    'sql'    => base_path('alone'),

    /*
     * 监听SQL
     */
    'listen' => [
        //监听状态
        'status' => false,
        //监听方法
        'method' => function($data, QueryExecuted $query) {
            print_r($data);
        }
    ],

    /*
     * model生成配置
     * php webman alone:model 生成文件
     */
    'model'  => [
        //model 命名空间或者相对路径
        'namespace'      => 'app\model',
        //model前缀
        "prefix"         => "",
        //model后缀
        "suffix"         => "",
        //model主类存在时是否更新
        "updateModel"    => false,
        //是否删除不存在表单的model
        "deleteNotModel" => true,
        //更新目录名称(此目录每次都会更新)
        "updateName"     => "update",
        //Common继承类名
        "extends"        => "\support\Model",
        //Common use trait类,多个使用array
        "trait"          => ["use \AloneWebMan\Model\ModelHelper"],
        //类参数设置
        "args"           => [
            'protected $guarded    = [];',
            'public    $primaryKey = "id";',
            'public    $timestamps = true;',
            'public    $dateFormat = "Y-m-d H:i:s";'
        ],
        //同连接model开关
        "switch"         => false,
        //生成同连接model配置 ["完整连接名"=>["目录名"=>"数据名"]...]
        "database"       => [
            "plugin.model.main" => [
                "alias" => "database_name"
            ]
        ]
    ],

    /*
     * 扩展model方法
     */
    'extend' => [
        /*
         * 扩展状态
         */
        'status' => true,
        /*
         * 扩展方法
         */
        'method' => function($loader) {
            //$loader('方法名称', "方法包", "是否使用model");
            \AloneWebMan\Model\Db\ModelDb::loader($loader, false);
            \AloneWebMan\Model\Db\RawDb::loader($loader, true);
        }
    ],
];