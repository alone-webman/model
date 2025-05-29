<?php

namespace AloneWebMan\Model;

use PDO;
use PDOStatement;

class SqlHelper {

    /**
     * @var array 数据库配置
     */
    public array $config = [
        'driver'      => "mysql",//数据库类型
        'host'        => "localhost", //服务器地址
        'port'        => 3306, //端口号
        'username'    => "root", //用户名
        'password'    => "", //密码
        'database'    => "", //数据库名
        'prefix'      => "",//表前缀
        'charset'     => "utf8mb4",//字符集
        'collation'   => "utf8mb4_unicode_ci",//排序规则
        'row_format'  => "DYNAMIC",//行格式
        'unix_socket' => null,//Unix域套
        'engine'      => "InnoDB",//引擎
    ];

    /**
     * @var array mysql字段类型分类
     */
    public array $field = [
        //数值类型
        'int'    => [
            'tinyint',
            'smallint',
            'mediumint',
            'int',
            'bigint',
            'float',
            'double',
            'decimal'
        ],
        //二进制类型
        'hex'    => [
            'tinyblob',
            'blob',
            'mediumblob',
            'longblob',
            'binary',
            'varbinary'
        ],
        //空间类型
        'room'   => [
            'geometry',
            'point',
            'linestring',
            'polygon',
            'multipoint',
            'multilinestring',
            'multipolygon',
            'geometrycollection'
        ],
        //JSON类型
        'json'   => [
            'json'
        ],
        //字符串类型
        'string' => [
            'char',
            'varchar',
            'tinytext',
            'text',
            'mediumtext',
            'longtext'
        ],
        //枚举与集合类型
        'enum'   => [
            'enum',
            'set'
        ],
        //日期与时间类型
        'time'   => [
            'time',
            'date',
            'datetime',
            'timestamp',
            'year'
        ]
    ];

    /**
     * @var array 设置数据
     */
    public array $data = [];

    /**
     * @var PDO|null 数据库连接
     */
    public PDO|null $connect;

    /**
     * @param array $config
     */
    public function __construct(array $config = []) {
        $this->config = [
            'driver'      => ($config['driver'] ?? "mysql"),//类型
            'host'        => ($config['host'] ?? ($config['hostname'] ?? "127.0.0.1")), //服务器地址
            'port'        => ($config['port'] ?? ($config['hostport'] ?? 3306)), //端口号
            'username'    => ($config['username'] ?? ""), //用户名
            'password'    => ($config['password'] ?? ""), //密码
            'database'    => ($config['database'] ?? ""), //数据库名
            'prefix'      => ($config['prefix'] ?? ""),//表前缀
            'charset'     => ($config['charset'] ?? "utf8mb4"),//字符集
            'collation'   => ($config['collation'] ?? "utf8mb4_general_ci"),//排序规则
            'row_format'  => ($config['row_format'] ?? ''),//行格式
            'unix_socket' => ($config['unix_socket'] ?? ($config['socket'] ?? null)),//Unix域套
            'engine'      => ($config['engine'] ?? "InnoDB"),//引擎
        ];
    }

    /**
     * =======================================================
     * 设置方法
     * =======================================================
     */
    /**
     * 设置默认
     * 数据库名+表前缀+字符集+排序规则+引擎+行格式
     * @param bool $type true=默认数据库一样,false=清空
     * @return $this
     */
    public function reset(bool $type = true): static {
        return $this->database($type)->prefix($type)->charset($type)->collation($type)->engine($type)->rowFormat($type);
    }

    /**
     * 重设数据库名
     * @param string|bool $data (true=默认,false=清空)
     * @return $this
     */
    public function database(string|bool $data = ''): static {
        $this->data['database'] = (($data === true) ? ($this->getConfig('database')) : (!empty($data) ? $data : ""));
        return $this;
    }

    /**
     * 重设表前缀
     * @param string|bool $data (true=默认,false=清空)
     * @return $this
     */
    public function prefix(string|bool $data = ''): static {
        $this->data['prefix'] = (($data === true) ? ($this->getConfig('prefix')) : (!empty($data) ? $data : ""));
        return $this;
    }

    /**
     * 重设字符集
     * @param string|bool $data (true=默认,false=清空)
     * @return $this
     */
    public function charset(string|bool $data = ''): static {
        $this->data['charset'] = (($data === true) ? ($this->getConfig('charset')) : (!empty($data) ? $data : ""));
        return $this;
    }

    /**
     * 重设排序规则
     * @param string|bool $data (true=默认,false=清空)
     * @return $this
     */
    public function collation(string|bool $data = ''): static {
        $this->data['collation'] = (($data === true) ? ($this->getConfig('collation')) : (!empty($data) ? $data : ""));
        return $this;
    }

    /**
     * 重设引擎
     * @param string|bool $data (true=默认,false=清空)
     * @return $this
     */
    public function engine(string|bool $data = ''): static {
        $this->data['engine'] = (($data === true) ? ($this->getConfig('engine')) : (!empty($data) ? $data : ""));
        return $this;
    }

    /**
     * 重设行格式
     * @param string|bool $data (true=默认,false=清空)
     * @return $this
     */
    public function rowFormat(string|bool $data = ''): static {
        $this->data['row_format'] = (($data === true) ? ($this->getConfig('row_format')) : (!empty($data) ? $data : ""));
        return $this;
    }

    /**
     * 导出,导入时设置
     * @param array $table 表名数组(默认为空，即导出,导入所有表)
     * @param bool  $type  true=选择,false=排除
     * @return $this
     */
    public function optExclude(array $table, bool $type = false): static {
        $this->data['optExcludeTable'] = $table;
        $this->data['optExcludeType'] = $type;
        return $this;
    }

    /**
     * 导出备注
     * @param string $data
     * @return $this
     */
    public function remark(string $data): static {
        $this->data['remark'] = $data;
        return $this;
    }

    /**
     * 导出时判断表数据，返回true=导出,false=不导出
     * @param callable|bool $callable (表名,当前记录数据)
     * @return $this
     */
    public function exportIs(callable|bool $callable): static {
        $this->data['exportIs'] = $callable;
        return $this;
    }

    /**
     * 导出时实时闭包
     * @param callable|bool $callable true=使用默认,false=关闭
     * @param callable|bool $res      输出包
     * @param string        $eol      分行符号
     * @param array         $lang     语言包
     * @return $this
     */
    public function exportCallable(callable|bool $callable = true, callable|bool $res = false, string $eol = PHP_EOL, array $lang = []): static {
        $this->data['exportCallable'] = (is_callable($callable))
            ? $callable
            : ($callable === false
                ? ""
                : (function($data) use ($res, $eol, $lang) {
                    $content = "";
                    $help = array_merge($data['help'], $lang);
                    $data['help'] = $help;
                    switch ($data['type']) {
                        case "start":
                            $content .= $help['count'] . "  - " . $data['total'];
                            break;
                        case "delete":
                            $content .= $data['count'] . "/" . $data['total'] . ".";
                            $content .= $help[$data['type']];
                            $content .= " " . $data['table_percentage'] . "% ";
                            $content .= $data['table'];
                            break;
                        case "select":
                            $content .= $data['count'] . "/" . $data['total'] . ".";
                            $content .= $help[$data['type']];
                            $content .= " " . $data['table_percentage'] . "% ";
                            $content .= $data['table'];
                            if ($data['sum'] > 0) {
                                $content .= " - " . $data['sum'];
                            }
                            break;
                        case "insert":
                            if ($data['sum'] > 0) {
                                $content .= $data['count'] . "/" . $data['total'] . "-";
                                $content .= $data['child'] . "/" . $data['sum'] . ".";
                                $content .= $help[$data['type']];
                                $content .= " " . $data['child_percentage'] . "% ";
                                $content .= $data['table'];
                            }
                            break;
                        case "error":
                        case "success":
                            $content .= $help[$data['type']];
                            break;
                    }
                    if (!empty($res) && is_callable($res)) {
                        $res($content, $data['type'], $data);
                    } else {
                        echo $content . $eol;
                    }
                }));
        return $this;
    }

    /**
     * 导入时判断表数据，返回true=导入,false=不导入
     * @param callable|bool $callable (表名,当前记录数据)
     * @return $this
     */
    public function importIs(callable|bool $callable): static {
        $this->data['importIs'] = $callable;
        return $this;
    }


    /**
     * 导入时实时闭包
     * @param callable|bool $callable true=使用默认,false=关闭
     * @param callable|bool $res      输出包
     * @param string        $eol      分行符号
     * @param array         $lang     语言包
     * @return $this
     */
    public function importCallable(callable|bool $callable = true, callable|bool $res = false, string $eol = PHP_EOL, array $lang = []): static {
        $this->data['importCallable'] = (is_callable($callable))
            ? $callable
            : ($callable === false
                ? ""
                : (function($data) use ($res, $eol, $lang) {
                    $content = "";
                    $help = array_merge($data['help'], $lang);
                    $data['help'] = $help;
                    switch ($data['type']) {
                        case "start":
                            $content .= $help['count'] . "  - " . $data['total'];
                            break;
                        case "delete":
                        case "drop":
                        case "create":
                        case "insert":
                        case "tool":
                            $content .= $data['count'] . "/" . $data['total'] . ".";
                            $content .= $help[$data['type']];
                            $content .= " " . $data['table_percentage'] . "% ";
                            if (!empty($table = ($data['table'] ?? ''))) {
                                $content .= $table;
                            }
                            break;
                        case "error":
                        case "success":
                            $content .= $help[$data['type']];
                            break;
                    }
                    if (!empty($res) && is_callable($res)) {
                        $res($content, $data['type'], $data);
                    } else {
                        echo $content . $eol;
                    }
                }));
        return $this;
    }

    /**
     * =======================================================
     * 导出,导入sql文件
     * =======================================================
     */
    /**
     * 导入sql文件
     * @param string $FilePath    sql文件路径
     * @param bool   $IsTableData 是否导入表数据(默认为true)
     * @return array
     */
    public function import(string $FilePath, bool $IsTableData = true): array {
        $start = microtime(true);
        $callable = function(string $type, array $data = []) {
            $callable = $this->getData('importCallable');
            if (!empty($callable) && is_callable($callable)) {
                $array['type'] = $type;//类型
                $array['help'] = [
                    'count'   => 'Line total count',//行总数
                    'start'   => 'Start processing',//开始处理
                    'delete'  => 'Exclusion',//排除表单
                    'drop'    => 'Table processing',//处理表单
                    'create'  => 'Create Table',//创造表单
                    'insert'  => 'Insert Table',//添加表单记录
                    'tool'    => 'Sql record',//SQL记录
                    'success' => 'Success',//处理成功
                    'error'   => 'Fail',//处理失败
                ];//类型说明
                if ($type == 'start' || $type == 'success' || $type == 'error') {
                    $array = array_merge($array, $data);
                } else {
                    $array['total'] = $data['total'] ?? 0;                                                                          //表单总数
                    $array['count'] = $data['count'] ?? 0;                                                                          //第几个表单
                    $array['table'] = $data['table'] ?? '';                                                                         //表单名称
                    $array['table_percentage'] = $array['total'] > 0 ? static::decimal($array['count'] / $array['total'] * 100) : 0;//总进度百分比
                }
                $callable($array);
            }
        };
        if (!empty(is_file($FilePath))) {
            try {
                $body = @file_get_contents($FilePath);
                $body = preg_replace('/\/\*(?![^\/]*\*\/)(.*?)(?=\*\/)/s', '', $body);//删除注解内容
                $body = str_replace("\r\n", PHP_EOL, $body);
                $array = explode(PHP_EOL, trim($body, PHP_EOL));
                $total = count($array);
                if (!empty($array)) {
                    //表名数组,判断前缀
                    if (!empty($optExcludeTable = $this->getData('optExcludeTable', []))) {
                        foreach ($optExcludeTable as $k => $v) {
                            $optExcludeTable[$k] = $this->geTableName($v);
                        }
                    }
                    $callable('start', ['total' => $total]);
                    $ds = 0;     //删除表单成功数量
                    $de = 0;     //删除表单失败数量
                    $cs = 0;     //创建表单成功数量
                    $ce = 0;     //创建表单失败数量
                    $is = 0;     //添加记录成功数量
                    $ie = 0;     //添加记录失败数量
                    $ss = 0;     //其它成功数量
                    $se = 0;     //其它失败数量
                    $ov = 0;     //表单总数
                    $ks = 0;     //不导入数量
                    $sql = '';   //失败的记录
                    $fail = [];  //错误的sql
                    $tabArr = [];//表单列表
                    $count = 0;
                    foreach ($array as $v) {
                        ++$count;
                        if (
                            empty($v)
                            || str_starts_with($v, '--')
                            || str_starts_with($v, '/*')
                            || str_starts_with($v, '*/')
                        ) {
                            continue;
                        }
                        $sql .= " " . $v;
                        if (str_ends_with($v, ';')) {
                            $sql = static::strips(trim($sql));
                            if (str_starts_with($sql, 'DROP')) {
                                //表名数组
                                $table = static::getSqlTableName($sql);
                                if (!empty($this->isOptTable($table, $optExcludeTable))) {
                                    $sql = "";
                                    $callable('delete', [
                                        'total' => $total,
                                        'count' => $count,
                                        'table' => $table
                                    ]);
                                    continue;
                                }
                                $callable('drop', [
                                    'total' => $total,
                                    'count' => $count,
                                    'table' => $table
                                ]);
                                ++$ov;
                                if ($this->exec($sql) !== false) {
                                    ++$ds;
                                } else {
                                    ++$de;
                                    $fail[] = $sql;
                                }
                            } elseif (str_starts_with($sql, 'CREATE')) {
                                //表名数组
                                $table = static::getSqlTableName($sql);
                                if (!empty($this->isOptTable($table, $optExcludeTable))) {
                                    $sql = "";
                                    continue;
                                }
                                $callable('create', [
                                    'total' => $total,
                                    'count' => $count,
                                    'table' => $table
                                ]);
                                $sql = static::handleCreAteSql(
                                    $sql,
                                    $this->getData('charset'),
                                    $this->getData('collation'),
                                    $this->getData('engine'),
                                    $this->getData('row_format')
                                );
                                if ($this->exec($sql) !== false) {
                                    ++$cs;
                                } else {
                                    ++$ce;
                                    $fail[] = $sql;
                                }
                            } elseif (str_starts_with($sql, 'INSERT')) {
                                //表名数组
                                $table = static::getSqlTableName($sql);
                                if (!empty($table)) {
                                    if (!empty($this->isOptTable($table, $optExcludeTable))) {
                                        $sql = "";
                                        continue;
                                    }
                                    $tabArr[$table] = ($tabArr[$table] ?? 0) + 1;
                                }
                                if (!empty($IsTableData)) {
                                    //导出时判断表数据
                                    $importIs = $this->getData('importIs');
                                    if (!empty($importIs) && !empty(is_callable($importIs))) {
                                        $insertArr = static::insertSqlToArray($sql);
                                        $tableName = key($insertArr) ?: '';
                                        $tableArray = $insertArr[key($insertArr)] ?? [];
                                        $isSave = $importIs($tableName, $tableArray);
                                    } else {
                                        $isSave = true;
                                    }
                                    if (!empty($isSave)) {
                                        $callable('insert', [
                                            'total' => $total,
                                            'count' => $count,
                                            'table' => $table
                                        ]);
                                        if ($this->exec($sql) !== false) {
                                            ++$is;
                                        } else {
                                            ++$ie;
                                            $fail[] = $sql;
                                        }
                                    } else {
                                        ++$ks;
                                    }
                                } else {
                                    $this->exec(static::setTableAutoSql($table));
                                }
                            } else {
                                $callable('tool', [
                                    'total' => $total,
                                    'count' => $count
                                ]);
                                if ($this->exec($sql) !== false) {
                                    ++$ss;
                                } else {
                                    ++$se;
                                    $fail[] = $sql;
                                }
                            }
                            $sql = "";
                        }
                    }
                    $callable('tool', [
                        'total' => $total,
                        'count' => $count
                    ]);
                    $data['code'] = 200;
                    $data['data'] = [
                        'table'          => $ov,//表单总数
                        'count'          => ($ds + $de + $cs + $ce + $is + $ie + $ss + $se + $ks),//执行总数
                        'success'        => ($ds + $cs + $is + $ss),//成功总数
                        'error'          => ($de + $ce + $ie + $se),//失败总数
                        'drop_success'   => $ds,//删除表单成功数量
                        'drop_error'     => $de,//删除表单失败数量
                        'create_success' => $cs,//创建表单成功数量
                        'create_error'   => $ce,//创建表单失败数量
                        'insert_success' => $is,//添加记录成功数量
                        'insert_error'   => $ie,//添加记录失败数量
                        'set_success'    => $ss,//其它成功数量
                        'set_error'      => $se,//其它失败数量
                        'not_import'     => $ks,//不导入数量
                        'fail'           => $fail,//失败的记录
                        'list'           => $tabArr//成功的表单和记录数
                    ];
                    $callable('success', $data);//成功
                } else {
                    $data['code'] = 400;
                    $data['data'] = "File has no data";
                    $callable('error', $data);//失败
                }
            } catch (\Throwable $e) {
                $data['code'] = 400;
                $data['data'] = $e->getMessage();
                $callable('error', $data);//失败
            }
        } else {
            $data['code'] = 400;
            $data['data'] = "File does not exist";
            $callable('error', $data);//失败
        }
        $this->close();
        $this->data['optExcludeTable'] = null;
        $this->data['optExcludeType'] = null;
        $this->data['importCallable'] = null;
        $this->data['importIs'] = null;
        $data['time'] = static::decimal((microtime(true) - $start));//执行时间秒
        return $data;
    }

    /**
     * 导出sql文件
     * @param string $FilePath    sql文件路径(为空不保存，返回sql)
     * @param bool   $IsTableData 是否导出表数据(默认为true)
     * @return array
     */
    public function export(string $FilePath = "", bool $IsTableData = true): array {
        $start = microtime(true);
        $callable = function(string $type, array $data = []) {
            $callable = $this->getData('exportCallable');
            if (!empty($callable) && is_callable($callable)) {
                $array['type'] = $type;//类型
                $array['help'] = [
                    'count'   => 'Table total count',//表单总数
                    'start'   => 'Start processing',//开始处理
                    'delete'  => 'Exclusion',//排除表单
                    'select'  => 'Table processing',//处理表单
                    'insert'  => 'Table record',//表单记录
                    'success' => 'Success',//处理成功
                    'error'   => 'Fail',//处理失败
                ];//类型说明
                if ($type == 'start' || $type == 'success' || $type == 'error') {
                    $array = array_merge($array, $data);
                } else {
                    $array['total'] = $data['total'] ?? 0;                                                                          //表单总数
                    $array['count'] = $data['count'] ?? 0;                                                                          //第几个表单
                    $array['table'] = $data['table'] ?? '';                                                                         //表单名称
                    $array['sum'] = $data['sum'] ?? 0;                                                                              //当前表单记录总数
                    $array['child'] = $data['child'] ?? 0;                                                                          //当前表单第几条记录
                    $array['table_percentage'] = $array['total'] > 0 ? static::decimal($array['count'] / $array['total'] * 100) : 0;//总进度百分比
                    $array['child_percentage'] = $array['sum'] > 0 ? static::decimal($array['child'] / $array['sum'] * 100) : 0;    //表单记录百分比
                }
                $callable($array);
            }
        };
        try {
            $ss = 3;                                                  //其它数量
            $ds = 0;                                                  //删除表单数量
            $cs = 0;                                                  //创建表单数量
            $is = 0;                                                  //添加记录数量
            $ov = 0;                                                  //表单总数
            $ks = 0;                                                  //不导出数量
            $tabArr = [];                                             //表单列表
            $database = $this->config('database');                    //数据库名
            $driver = ucfirst(strtolower($this->getConfig('driver')));//数据库类型
            $gloss = [
                "Top: " . date("Y-m-d H:i:s"),
                "PHP: " . PHP_VERSION,
                "Host: " . $this->config('host') . ":" . $this->config('port'),
                "Driver: " . $driver,
                $driver . ": " . $this->version(),
                "Name: " . $database,
                "Execution: [{:ExecutionTime}]"
            ];
            if (!empty($remark = $this->getData('remark'))) {
                $gloss[] = "Remark: " . $remark;
            }
            $content = static::gloss($gloss);
            $content .= "SET NAMES " . $this->getConfig('charset') . ";" . PHP_EOL;
            $content .= "SET FOREIGN_KEY_CHECKS = 0;" . PHP_EOL;
            $tabNameArray = $this->getTableName();//获取所有表名
            $count = 0;                           //第几个表单
            $total = count($tabNameArray);
            $callable('start', ['total' => $total]);
            if (!empty($tabNameArray)) {
                //表名数组,判断前缀
                if (!empty($optExcludeTable = $this->getData('optExcludeTable', []))) {
                    foreach ($optExcludeTable as $k => $v) {
                        $optExcludeTable[$k] = $this->geTableName($v);
                    }
                }
                foreach ($tabNameArray as $table) {
                    ++$count;
                    //如果设置了表前缀,且传入的表名不包含表前缀,则补上
                    $tables = $this->geTableName($table);
                    //表名数组
                    if (!empty($this->isOptTable($table, $optExcludeTable))) {
                        $callable('delete', [
                            'total' => $total,
                            'count' => $count,
                            'table' => $tables
                        ]);
                        continue;
                    }
                    ++$ov;
                    $content .= PHP_EOL;
                    $content .= static::gloss("Table structure for " . $tables);
                    $content .= static::deleteTableSql($tables) . PHP_EOL;
                    ++$ds;
                    $tableSql = $this->getAllTableName($table);//获取表单sql
                    $content .= static::handleCreAteSql(
                            $tableSql,
                            $this->getData('charset'),
                            $this->getData('collation'),
                            $this->getData('engine'),
                            $this->getData('row_format')
                        ) . PHP_EOL;
                    ++$cs;
                    $child = 0;
                    if (!empty($IsTableData)) {
                        $columns = "*";
                        $fieldArray = $this->getTableField($table, true);//获取表单字段信息
                        //空间类型处理
                        foreach ($fieldArray as $k => $v) {
                            if (in_array(strtolower(($v['DATA_TYPE'] ?? '')), $this->field['room'])) {
                                $columns .= ",ST_AsText(" . $k . ") as " . $k;
                            }
                        }
                        $tableArray = $this->getTableData($table, $columns, true);//获取表单数据
                        $sum = count($tableArray);
                        $callable('select', [
                            'total' => $total,
                            'count' => $count,
                            'table' => $tables,
                            'sum'   => $sum
                        ]);
                        if (!empty($tableArray)) {
                            $exportIs = $this->getData('exportIs');
                            $content .= static::gloss("Records of " . $tables);
                            $content .= "BEGIN;" . PHP_EOL;
                            ++$ss;
                            foreach ($tableArray as $val) {
                                ++$child;
                                //导出时判断表数据
                                $isSave = (!empty($exportIs) && !empty(is_callable($exportIs))) ? $exportIs($table, $val) : true;
                                if ($isSave) {
                                    $field = ""; //字段
                                    $values = "";//值
                                    $callable('insert', [
                                        'total' => $total,
                                        'count' => $count,
                                        'table' => $tables,
                                        'sum'   => $sum,
                                        'child' => $child
                                    ]);
                                    foreach ($val as $k => $v) {
                                        $field .= "`{$k}`,";
                                        $fieldType = strtolower($fieldArray[$k]['DATA_TYPE'] ?? '');//字段类型
                                        $default = $fieldArray[$k]['COLUMN_DEFAULT'] ?? null;       //默认值
                                        $value = (!empty($v) ? $v : $default);                      //值
                                        if (!empty(in_array($fieldType, $this->field['int']))) {
                                            $values .= (!empty($v) ? $v : (!empty($default) ? $default : 0)) . ",";
                                        } elseif ($value == null) {
                                            $values .= "NULL, ";
                                        } elseif (!empty(in_array($fieldType, $this->field['hex']))) {
                                            $value = "0x" . bin2hex($value);
                                            $values .= $value . ", ";
                                        } elseif (!empty(in_array($fieldType, $this->field['room']))) {
                                            $values .= "ST_GeomFromText('" . $value . "'),";
                                        } else {
                                            if (!empty($jsonArr = static::isJson($value)) || in_array($fieldType, $this->field['json'])) {
                                                $value = static::jsonCompress($jsonArr);
                                            }
                                            $values .= $this->quote($value) . ",";
                                        }
                                    }
                                    if (!empty($field)) {
                                        $field = trim(trim($field), ",");
                                        $values = trim(trim($values), ",");
                                        $content .= "INSERT INTO `{$tables}` ({$field}) VALUES ({$values});" . PHP_EOL;
                                        ++$is;
                                    }
                                } else {
                                    ++$ks;
                                }
                            }
                            $content .= "COMMIT;" . PHP_EOL;
                            ++$ss;
                        }
                    }
                    $tabArr[$table] = $child;
                }
            }
            $content .= PHP_EOL . "SET FOREIGN_KEY_CHECKS = 1;" . PHP_EOL;
            $executionTime = static::decimal((microtime(true) - $start));//执行时间秒
            $content = str_replace("[{:ExecutionTime}]", $executionTime, $content);
            $content .= static::gloss("End: " . date("Y-m-d H:i:s"));
            $data['code'] = 200;
            $data['data'] = $content;
            $data['count'] = [
                'table'          => $ov,//表单总数
                'count'          => ($ds + $cs + $is + $ss + $ks),//执行总数
                'drop_success'   => $ds,//删除表单数量
                'create_success' => $cs,//创建表单数量
                'insert_success' => $is,//添加记录数量
                'set_success'    => $ss,//其它数量
                'not_export'     => $ks,//不导出数量
                'list'           => $tabArr//成功的表单和记录数
            ];
            $data['time'] = $executionTime;//执行时间秒
            $callable('success', $data);   //成功
            if (!empty($FilePath)) {
                $save = @file_put_contents(static::createFilePath($FilePath), trim($content, PHP_EOL));
                $data['data'] = $save > 0 ? (static::decimal($save / 1024) . 'KB') : $content;
            }
        } catch (\Throwable $e) {
            $data['code'] = 400;
            $data['data'] = $e->getMessage();
            $data['time'] = static::decimal((microtime(true) - $start));//执行时间秒
            $data['count'] = [];
            $callable('error', $data);//失败
        }
        $this->close();
        $this->data['optExcludeTable'] = null;
        $this->data['optExcludeType'] = null;
        $this->data['exportCallable'] = null;
        $this->data['exportIs'] = null;
        return $data;
    }

    /**
     * 判断是否执行表单
     * @param string $table 表单
     * @param array  $optExcludeTable
     * @return bool
     */
    protected function isOptTable(string $table, array $optExcludeTable) {
        $optType = $this->getData('optExcludeType', false);
        //表名数组
        if (
            !empty($table)
            && !empty($optExcludeTable)
            && (
                (!empty($optType) && empty(in_array($table, $optExcludeTable)))
                || //选择
                (empty($optType) && !empty(in_array($table, $optExcludeTable))) //排除
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * =======================================================
     * 数据库方法
     * =======================================================
     */
    /**
     * 数据库连接
     * @param bool $type 是否重连
     * @return PDO|null
     */
    public function connect(bool $type = false): PDO|null {
        if (empty($this->connect) || !empty($type)) {
            $dsn = $this->getConfig('driver') . ":";
            if (!empty($socket = $this->getConfig('unix_socket') ?? "")) {
                $dsn .= "unix_socket=" . $socket . ";";
            } else {
                $dsn .= "host=" . $this->getConfig('host') . ";";
                $dsn .= "port=" . $this->getConfig('port') . ";";
            }
            $dsn .= "dbname=" . $this->config('database') . ";";
            $dsn .= "charset=" . $this->config('charset') . ";";
            $this->connect = new PDO(
                $dsn,
                $this->getConfig('username'),
                $this->getConfig('password')
            );
            $this->connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->connect;
    }

    /**
     * 查询多条
     * @param string $sql
     * @param bool   $type 是否重连
     * @return bool|array
     */
    public function select(string $sql, bool $type = false): bool|array {
        return $this->connect($type)->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 查询单条
     * @param string $sql
     * @param bool   $type 是否重连
     * @return mixed
     */
    public function first(string $sql, bool $type = false): mixed {
        return $this->connect($type)->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 执行
     * @param string $sql
     * @param bool   $type 是否重连
     * @return int|bool
     */
    public function exec(string $sql, bool $type = false): bool|int {
        return $this->connect($type)->exec($sql);
    }

    /**
     * 查询
     * @param string $sql
     * @param bool   $type 是否重连
     * @return bool|PDOStatement
     */
    public function query(string $sql, bool $type = false): bool|PDOStatement {
        return $this->connect($type)->query($sql);
    }

    /**
     * =======================================================
     * 数据方法
     * =======================================================
     */

    /**
     * 创建数据库
     * @param string $dbname
     * @param bool   $type 是否强制创建数据库(数据库存在时删除原数据库)
     * @return bool
     */
    public function addBase(string $dbname, bool $type = false): bool {
        $isBase = $this->isBase($dbname);
        if (!empty($isBase)) {
            if (!empty($type)) {
                $this->exec(static::delBaseSql($dbname));
            } else {
                return true;
            }
        }
        return (bool) $this->exec(static::addBaseSql($dbname));
    }

    /**
     * 判断mysql数据库是否存在
     * @param string $dbname
     * @return bool
     */
    public function isBase(string $dbname): bool {
        return ($this->query(static::isBaseSql($dbname))->rowCount() > 0);
    }

    /**
     * 获取表单全称
     * @param string $table
     * @param bool   $base 是否添加数据库名
     * @return string
     */
    public function geTableName(string $table, bool $base = false): string {
        return (!empty($base) ? ($this->config('database') . ".") : "") .
               ((!empty($prefix = $this->config('prefix')) && empty(str_starts_with($table, $prefix))) ? $prefix : "")
               . $table;
    }

    /**
     * 获取设置信息
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function config(string $key, mixed $default = ""): mixed {
        return $this->getData($key, $this->getConfig($key, $default));
    }

    /**
     * 获取配置
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = ""): mixed {
        return (isset($this->config[$key]) ? ($this->config[$key] ?: $default) : $default);
    }

    /**
     * 获取设置
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getData(string $key, mixed $default = ""): mixed {
        return (isset($this->data[$key]) ? ($this->data[$key] ?: $default) : $default);
    }

    /**
     * 转义字符串 addslashes
     * @param $string
     * @return string
     */
    public function quote($string): string {
        return $this->connect()->quote($string);
    }

    /**
     * 反转义字符串 stripslashes
     * @param $string
     * @return string
     */
    public function strips($string): string {
        return $string;//stripslashes($string);
    }

    /**
     * 关闭连接
     */
    public function close() {
        $this->connect = null;
    }

    /**
     * 对象被销毁时
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * =======================================================
     * 查询方法
     * =======================================================
     */
    /**
     * 获取MySQL版本号
     * @return string
     */
    public function version(): string {
        $data = $this->first(static::getMysqlVer());
        return $data['version'] ?? "";
    }

    /**
     * 获取所有表名
     * @return array|bool
     */
    public function getTableName(): bool|array {
        return $this->query(static::getTableNameSql())->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 获取表单sql信息
     * @param string $table 表单名
     * @return string
     */
    public function getAllTableName(string $table): string {
        $data = $this->first(static::getAllTableNameSql($this->geTableName($table)));
        return (!empty($table = ($data['Create Table'] ?? "")) ? ($table . ";") : "");
    }

    /**
     * 获取表单字段信息
     * @param string $table 表单名
     * @param bool   $base  是否添加数据库名
     * @param array  $data
     * @return array
     */
    public function getTableField(string $table, bool $base = false, array $data = []): array {
        $sql = static::getTableFieldSql($this->geTableName($table), "*", (!empty($base) ? $this->config('database') : ""));
        $array = $this->select($sql);
        foreach ($array as $val) {
            $data[$val['COLUMN_NAME']] = $val;
        }
        return $data;
    }

    /**
     * 获取表单数据
     * @param string $table 表单名
     * @param string $columns
     * @param bool   $base  是否添加数据库名
     * @return bool|array
     */
    public function getTableData(string $table, string $columns = "*", bool $base = false): bool|array {
        return $this->select("SELECT {$columns} FROM " . $this->geTableName($table, $base));
    }

    /**
     * 获取数据库全部表单信息
     * @param string|bool $base 数据库名称,true=全部,false=当前,string=指定
     * @param array       $data
     * @return array
     */
    public function getBaseTable(string|bool $base = false, array $data = []): array {
        $tab = $this->select($this->getBaseTableSql((($base === false) ? ($this->config('database')) : (($base === true) ? "" : $base))));
        foreach ($tab as $val) {
            $data[$val['TABLE_SCHEMA']][$val['TABLE_NAME']] = $val;
        }
        return $data;
    }

    /**
     * =======================================================
     * 静态方法
     * =======================================================
     */
    /**
     * 注释
     * @param string|array $data
     * @return string
     */
    public static function gloss(string|array $data): string {
        $gloss = "-- ----------------------------" . PHP_EOL;
        if (is_array($data)) {
            foreach ($data as $v) {
                $gloss .= "-- {$v}" . PHP_EOL;
            }
        } else {
            $gloss .= "-- {$data}" . PHP_EOL;
        }
        $gloss .= "-- ----------------------------" . PHP_EOL;
        return $gloss;
    }

    /**
     * 创造文件夹
     * @param string $filePath 文件名
     * @return string
     */
    public static function createFilePath(string $filePath): string {
        $path = dirname($filePath);
        if (empty(is_dir($path))) {
            mkdir($path, 0777, true);
        }
        return $filePath;
    }

    /**
     * 转换回来第三个为空
     * @param        $int
     * @param int    $decimals
     * @param string $separator
     * @param string $thousands
     * @return string
     */
    public static function decimal($int, int $decimals = 2, string $thousands = '', string $separator = '.'): string {
        return number_format($int, $decimals, $separator, $thousands);
    }

    /**
     * 数组转Json
     * @param      $data
     * @param bool $type
     * @return false|string
     */
    public static function json($data, bool $type = true): bool|string {
        return $type ? \json_encode($data, JSON_NUMERIC_CHECK + JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES) : \json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
    }

    /**
     * 判断字符串是否json,返回array
     * @param mixed $data
     * @param bool  $type
     * @return mixed
     */
    public static function isJson(mixed $data, bool $type = true): mixed {
        $data = \json_decode((is_string($data) ? ($data ?: '') : ''), $type);
        return (($data && \is_object($data)) || (\is_array($data) && $data)) ? $data : [];
    }

    /**
     * 把json压成一行
     * @param array $array
     * @return string
     */
    public static function jsonCompress(array $array): string {
        $json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $json = preg_replace('/^\s+|\s+$/m', '', $json);
        return preg_replace('/\s+/', '', $json);
    }

    /**
     * 添加记录SQL转换成array
     * @param $sql
     * @return array
     */
    public static function insertSqlToArray($sql): array {
        preg_match_all("/INSERT INTO ((`([^`]+)`)|(\b[^`]+\b)) \((.*)\) VALUES \((.*)\)/", $sql, $matches);
        $tables = $matches[1] ?? [];
        $columns = $matches[5] ?? [];
        $values = $matches[6] ?? [];
        $dataArray = [];
        foreach ($tables as $key => $table) {
            $columnArray = explode(",", $columns[$key]);
            $columnFormattedArray = [];
            foreach ($columnArray as $column) {
                $column = trim(trim($column), "`");
                $columnFormattedArray[] = $column;
            }
            preg_match_all("/'([^']+)'|([^,]+)/", $values[$key], $matches);
            $valueFormattedArray = [];
            foreach ($matches[0] as $match) {
                $value = trim(trim($match), "'");
                $valueFormattedArray[] = $value;
            }
            $dataArray[trim(trim($table), "`")] = array_combine($columnFormattedArray, $valueFormattedArray);
        }
        return $dataArray;
    }

    /**
     * 修改创造表单SQL
     * @param string      $sql
     * @param string      $charset    字符集(空值不修改,自定)
     * @param string|null $collate    排序规则(空值不修改,自定,null=删除)
     * @param string      $engine     引擎(空值不修改,自定)
     * @param string      $row_format 行格式(空值不修改,自定)
     * @return string
     */
    public static function handleCreAteSql(
        string      $sql,
        string      $charset = "",
        string|null $collate = null,
        string      $engine = "",
        string      $row_format = ""
    ): string {
        return preg_replace_callback("/(CHARSET(\s|=)|COLLATE(\s|=)|ENGINE=|ROW_FORMAT=)([^ ]+)/", function($arr) use (
            $charset,
            $collate,
            $engine,
            $row_format
        ) {
            $key = trim(($arr[1] ?? ''));
            $list = ['charset' => $charset, 'collate' => $collate, 'engine' => $engine, 'row_format' => $row_format];
            foreach ($list as $k => $v) {
                if (str_starts_with(strtolower($key), $k)) {
                    if (!empty($v)) {
                        return (str_ends_with($key, "=") ? ($key . $v) : ($key . " " . $v));
                    } elseif ($v === null) {
                        return '';
                    }
                }
            }
            return ($arr[0] ?? "");
        }, $sql);
    }

    /**
     * 获取sql表单名
     * @param $sql
     * @return string
     */
    public static function getSqlTableName($sql): string {
        preg_match("/(DROP TABLE IF EXISTS|CREATE TABLE|INSERT INTO) ((`([^`]+)`)|(\b[^`]+\b))/", $sql, $matches);
        return trim(($matches[2] ?? ''), "`");
    }

    /**
     * array生成字符串
     * @param array $array //要转换的array
     * @param bool  $type  //是否使用var_export, array()
     * @return string
     */
    public static function arrToStr(array $array, bool $type = false): string {
        return static::arrayToString($array, $type) . ";" . PHP_EOL;
    }

    /**
     * array生成字符串array
     * @param array $array //要转换的array
     * @param bool  $type  //是否使用var_export, array()
     * @param int   $i
     * @return string
     */
    public static function arrayToString(array $array, bool $type = false, int $i = 0): string {
        if (empty($type)) {
            ++$i;
            $branch = PHP_EOL;
            $symbol = str_repeat('  ', $i);
            $string = ((($i == 1) ? "  " : "") . ("[" . $branch));
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $string .= $symbol . "\"" . $key . "\" => " . static::arrayToString($value, $type, $i) . "," . $branch;
                } else {
                    if (is_numeric($value)) {
                        $string .= $symbol . "\"" . $key . "\" => " . $value . "," . $branch;
                    } else {
                        $string .= $symbol . "\"" . $key . "\" => \"" . addslashes($value) . "\"," . $branch;
                    }
                }
            }
            $value = rtrim($string, "," . $branch) . $branch . $symbol . ']';
        } else {
            $value = var_export($array, true);
        }
        return $value;
    }

    /**
     * =======================================================
     * SQL方法
     * =======================================================
     */
    /**
     * 获取MySQL版本号SQL
     * @return string
     */
    public static function getMysqlVer(): string {
        return "SELECT VERSION() as version;";
    }

    /**
     * 获取全部数据库全部表单信息
     * @param string $base 数据库名称,空获取全部数据库
     * @return string
     */
    public static function getBaseTableSql(string $base = ""): string {
        return "SELECT * FROM information_schema.TABLES" . (!empty($base) ? (" WHERE table_schema='" . $base . "'") : "") . ";";
    }

    /**
     * 获取数据库单个表单字段信息SQL
     * @param string $table 表单
     * @param string $columns
     * @param string $base  数据库名
     * @return string
     */
    public static function getTableFieldSql(string $table, string $columns = "*", string $base = ""): string {
        $sql = "SELECT {$columns} FROM INFORMATION_SCHEMA.COLUMNS";
        if (!empty($base)) {
            $sql .= " WHERE table_schema='" . $base . "' AND table_name='" . $table . "'";
        } else {
            $sql .= " WHERE table_name='" . $table . "'";
        }
        return trim($sql) . ";";
    }

    /**
     * 删除表单SQL
     * @param string $table 表单名
     * @return string
     */
    public static function deleteTableSql(string $table): string {
        return "DROP TABLE IF EXISTS `" . $table . "`;";
    }

    /**
     * 删除表单字段SQL
     * @param string       $table 表单名
     * @param array|string $field ['field1','field2'],field1
     * @return string
     */
    public static function deleteFieldSql(string $table, array|string $field): string {
        $sql = "ALTER TABLE `" . $table . "`";
        $array = is_array($field) ? $field : explode(',', $field);
        foreach ($array as $v) {
            $sql .= " DROP COLUMN `" . $v . "`,";
        }
        return trim(trim($sql), ",") . ";";
    }

    /**
     * 重设AUTO_INCREMENT=1
     * @param array|string $table
     * @param int          $auto
     * @return string
     */
    public static function setTableAutoSql(array|string $table, int $auto = 1): string {
        return "ALTER TABLE `" . $table . "` AUTO_INCREMENT = " . $auto . ";";
    }

    /**
     * 获取所有表名SQL
     * @return string
     */
    public static function getTableNameSql(): string {
        return "SHOW TABLES;";
    }

    /**
     * 获取表名SQL
     * @param $table
     * @return string
     */
    public static function getAllTableNameSql($table): string {
        return "SHOW CREATE TABLE `" . $table . "`;";
    }

    /**
     * where 多条件统计
     * @param string|array $field
     * @return string
     */
    public static function sumSql(string|array $field): string {
        $sumWay = function($field, $alias = "", $where = "") {
            $alias = (!empty($alias) ? $alias : $field);
            if (!empty($where)) {
                $sum = "SUM(CASE WHEN " . $where . " THEN " . $field . " ELSE 0 END) AS " . $alias . ",";
            } else {
                $sum = "SUM(" . $field . ") AS " . $alias . ",";
            }
            return $sum;
        };
        if (is_array($field)) {
            $sum = "";
            foreach ($field as $v) {
                $sum .= $sumWay(($v['name'] ?? ""), ($v['as'] ?? ""), ($v['where'] ?? ""));
            }
        } else {
            $sum = $sumWay($field);
        }
        return trim($sum, ',');
    }

    /**
     * 修改单个字段
     * @param string       $table 表单名
     * @param array|string $array string=字段,array=['旧名字'=>'新的名字']
     * @param array|string $type  string=字段类型,array=['type' => 'int(11)','default' => '默认值','comment' => '备注']
     * @return string
     */
    public static function reviseFieldSql(string $table, array|string $array, array|string $type): string {
        $sql = !empty($table) ? "ALTER TABLE `" . $table . "`" : "";
        if (is_array($array)) {
            $field = key($array);
            $new_field = $array[$field];
        } else {
            $field = $array;
        }
        if (!empty($new_field)) {
            $sql .= " CHANGE COLUMN `" . $field . "` `" . $new_field . "`";
        } else {
            $sql .= " MODIFY COLUMN `" . $field . "`";
        }
        if (is_array($type)) {
            $default = $type['default'] ?? "";
            if ($default == null || strtolower($default) == 'null') {
                $default = "NULL";
            } elseif (!empty($default)) {
                $default = "'" . $default . "'";
            }
            $comment = $type['comment'] ?? "";
            $type = $type['type'] ?? "";
            $sql .= " " . $type . "";
            $sql .= !empty($default) ? " DEFAULT " . $default . "" : "";  //默认值
            $sql .= !empty($comment) ? " COMMENT '" . $comment . "'" : "";//备注
        } else {
            $sql .= " " . $type;
        }
        return $sql . ";";
    }

    /**
     * 修改字段
     * @param string $table 表单名
     * @param array  $array
     * @return string
     */
    public static function editFieldSql(string $table, array $array): string {
        $sql = "ALTER TABLE `" . $table . "`";
        foreach ($array as $v) {
            $fieldSql = static::reviseFieldSql("",
                [
                    ($v['name'] ?? "") => ($v['as'] ?? "")
                ],
                [
                    'type'    => ($v['type'] ?? ""),
                    'default' => ($v['default'] ?? ""),
                    'comment' => ($v['comment'] ?? "")
                ]);
            $sql .= trim($fieldSql, ";") . ",";
        }
        return trim($sql, ",") . ";";
    }

    /**
     * 添加字段
     * @param string $table 表单名
     * @param array  $array
     * @return string
     */
    public static function addFieldSql(string $table, array $array): string {
        $sql = "ALTER TABLE `" . $table . "`";
        foreach ($array as $k => $v) {
            $default = $v['default'];
            if ($default !== 0 && $default == null || strtolower($default) == 'null') {
                $default = "NULL";
            } else {
                $default = "'" . $default . "'";
            }
            $sql .= " ADD `" . $k . "`";
            $sql .= (!empty($type = ($v['type'] ?? '')) ? (" " . $type) : "");                        //类型
            $sql .= (!empty(($v['null'] ?? '')) ? (" NOT NULL") : "");                                //是否能为空
            $sql .= (!empty($default) ? (" DEFAULT " . $default) : "");                               //默认值
            $sql .= !empty(($v['time'] ?? '')) ? " ON UPDATE CURRENT_TIMESTAMP" : "";                 //根据当前时间戳更新
            $sql .= (!empty($comment = ($v['comment'] ?? '')) ? (" COMMENT '" . $comment . "'") : "");//备注
            $sql .= ",";
        }
        return trim(trim($sql), ",") . ";";
    }

    /**
     * 创造表单SQL
     * @param string $table 表单名
     * @param array  $array
     * @return string
     */
    public static function createTableSql(string $table, array $array): string {
        $tables = $table;
        $sql = static::deleteTableSql($tables) . PHP_EOL;
        $sql .= "CREATE TABLE `{$tables}` (" . PHP_EOL;
        $sql .= "  `" . ($array['id']['name'] ?? 'id') . "` " . ($array['id']['type'] ?? 'int(11)') . " NOT NULL AUTO_INCREMENT," . PHP_EOL;
        $primary = "`" . ($array['id']['name'] ?? 'id') . "`";
        foreach ($array['list'] as $k => $v) {
            $null = $v['null'] ?? false;
            if (!empty($key = ($v['key'] ?? ""))) {
                $null = true;
                $primary .= ",`" . $key . "`";
            }
            $default = $v['default'];
            if ($default !== 0 && $default == null || strtolower($default) == 'null') {
                $default = "NULL";
            } else {
                $default = "'" . $default . "'";
            }
            $sql .= "  `" . $k . "`";
            $sql .= (" " . ($v['type'] ?? ""));                                                       //类型
            $sql .= (!empty($null) ? (" NOT NULL") : "");                                             //是否能为空
            $sql .= (!empty($default) ? (" DEFAULT " . $default) : "");                               //默认值
            $sql .= !empty(($v['time'] ?? "")) ? " ON UPDATE CURRENT_TIMESTAMP" : "";                 //根据当前时间戳更新
            $sql .= (!empty($comment = ($v['comment'] ?? "")) ? (" COMMENT '" . $comment . "'") : "");//备注
            $sql .= "," . PHP_EOL;
        }
        $sql .= "  PRIMARY KEY (" . trim($primary, ",") . ") USING BTREE" . PHP_EOL;
        $sql .= ")";
        $sql .= " ENGINE=" . ($array['engine'] ?? 'InnoDB');
        $sql .= " AUTO_INCREMENT=" . ($array['auto'] ?? 1);
        $sql .= " DEFAULT";
        $sql .= " CHARSET=" . ($array['charset'] ?? "");
        $sql .= " COLLATE=" . ($array['collation'] ?? "");
        $sql .= " ROW_FORMAT=" . ($array['row_format'] ?? "");
        $sql .= " COMMENT='" . ($array['comment'] ?? "") . "'";
        return $sql . ";";
    }

    /**
     * 添加记录SQL
     * @param string $table
     * @param array  $data
     * @return string
     */
    public static function insertSql(string $table, array $data): string {
        if (!is_array(reset($data))) {
            $data = [$data];
        }
        $values = [];
        $columns = implode(",", array_keys($data[0]));
        foreach ($data as $record) {
            $recordValues = [];
            foreach ($record as $value) {
                if (is_numeric($value)) {
                    $recordValues[] = $value;
                } else {
                    $recordValues[] = "'" . $value . "'";
                }
            }
            $values[] = '(' . implode(",", $recordValues) . ')';
        }
        $values = implode(",", $values);
        return "INSERT INTO `" . $table . "` (" . $columns . ") VALUES " . $values . ";";
    }

    /**
     * 修改记录SQL
     * @param string $table
     * @param string $where
     * @param array  $data
     * @return string
     */
    public static function updateSql(string $table, string $where, array $data): string {
        $values = [];
        foreach ($data as $column => $value) {
            if (is_numeric($value)) {
                $values[] = $column . '=' . $value;
            } else {
                $values[] = $column . "='" . $value . "'";
            }
        }
        $values = implode(",", $values);
        return "UPDATE `" . $table . "` SET " . $values . " WHERE " . $where . ";";
    }

    /**
     * 创建mysql数据库SQL
     * @param string $dbname    名称
     * @param string $charset   字符集
     * @param string $collation 排序规则
     * @return string
     */
    public static function addBaseSql(string $dbname, string $charset = 'utf8mb4', string $collation = 'utf8mb4_general_ci'): string {
        return "CREATE DATABASE `{$dbname}` CHARACTER SET {$charset} COLLATE {$collation};";
    }

    /**
     * 删除mysql数据库SQL
     * @param string $dbname 名称
     * @return string
     */
    public static function delBaseSql(string $dbname): string {
        return "DROP DATABASE `{$dbname}`;";
    }

    /**
     * 判断mysql数据库是否存在SQL
     * @param string $dbname 名称
     * @return string
     */
    public static function isBaseSql(string $dbname): string {
        return "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname';";
    }
}