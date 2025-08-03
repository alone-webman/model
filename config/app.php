<?php

use Illuminate\Database\Events\QueryExecuted;

return [
    'enable' => true,

    /*
     * 备份还原和model支持那些数据库
     */
    'driver' => 'mysql',

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
        /*
         * model 命名空间或者相对路径
         */
        'namespace' => 'app\model',
        /*
         * model 文件前缀
         */
        'prefix'    => 'model',
        /*
         * model 文件后缀
         */
        'suffix'    => '',
        /*
         * 是否删除不存在表单的model
         */
        'delete'    => true,
        /*
         * model 继承类名
         */
        'extends'   => 'support\Model',
        /*
         * model 引入类名到common.php
         * 类要使用trait
         */
        'trait'     => [
            '\AloneWebMan\Model\ModelHelper', //自带可使用db
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
            \AloneWebMan\Model\Db\ModelDb::loader($loader, false);
            \AloneWebMan\Model\Db\RawDb::loader($loader);
        }
    ],
];