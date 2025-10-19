<?php

namespace AloneWebMan\Model;

use PDO;

class Helper {
    // 数据库配置
    protected array $mysqlConfig = [
        //数据库类型
        'driver'      => "mysql",
        //服务器地址
        'host'        => "127.0.0.1",
        //服务器端口
        'port'        => 3306,
        //用户名
        'username'    => "root",
        //密码
        'password'    => "",
        //数据库名
        'database'    => "",
        //表前缀
        'prefix'      => "",
        //字符集
        'charset'     => "utf8mb4",
        //Unix域套
        'unix_socket' => null
    ];

    // 生成配置
    protected array $buildConfig = [
        //根目录(绝对路径)
        "rootBase"       => __DIR__,
        //保存目录(相对路径)
        "savePath"       => "",
        //连接名称
        "connectName"    => "",
        //model前缀
        "prefix"         => "",
        //model后缀
        "suffix"         => "",
        //model主类存在时是否更新
        "updateModel"    => false,
        //是否主库 (别库时生成database.table)
        "main"           => true,
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
        ]
    ];
    // 数据库连接
    protected PDO|null $pdo = null;
    // 命名空间
    protected string $_namespace = "";
    // 相对路径
    protected string $_basePath = "";
    // 绝对路径
    protected string $_savePath = "";
    // 更新目录名
    protected string $_updateName = "";
    // 更新目录命名空间
    protected string $_updateNamespace = "";
    // 更新目录绝对路径
    protected string $_updatePath = "";

    /**
     * 生成model信息
     * @return array
     */
    public function workerManLaravelModel(): array {
        $build = ["success" => [], 'error' => []];
        $this->modelConfig();
        $database = $this->mysqlConfig('database');
        $tableItem = $this->tableList($database);
        if (count($tableItem) > 0) {
            //删除目录
            static::deleteDir($this->_updatePath);
            //创造目录
            static::mkDir($this->_updatePath);
            //生成Common.php
            $build[($this->workerManCommon() ? 'success' : 'error')][] = $this->_basePath . "/Common.php";
            $dataBasePrefix = $this->mysqlConfig('prefix', '');
            $modelFiles = [];
            foreach ($tableItem as $table => $note) {
                $tableName = ($dataBasePrefix && str_starts_with($table, $dataBasePrefix)) ? substr($table, strlen($dataBasePrefix)) : $table;
                //不是主库时加database.table
                $tableTitle = empty($this->buildConfig['main'] ?? null) ? ($database . "." . $table) : $tableName;
                $modelName = static::modeTable($tableName);
                $property = "/**\r\n";
                $property .= " * " . trim($note) . "\r\n";
                $casts = "";
                $json = "";
                $help = "";
                $fieldItem = $this->fieldList($database, $table);
                foreach ($fieldItem as $fieldName => $field) {
                    $property .= " * @property \$" . $fieldName . " " . $field['types'] . " " . preg_replace('/\R/u', ' ', $field['note']) . "\r\n";
                    if ($field['type'] == 'json') {
                        $casts .= "        \"" . $fieldName . "\" => \"array\",\r\n";
                        $json .= "        \"" . $fieldName . "\",\r\n";
                    } elseif ($field['type'] == 'decimal') {
                        $casts .= "        \"" . $fieldName . "\" => \"float\",\r\n";
                    }
                    if (!($field['id'])) {
                        $value = $field['default'];
                        $value = is_numeric($value) ? ($value == 0 ? 0 : $value) : (is_string($value) ? ("\"$value\"") : ((($value === null ? 'null' : ($value === false ? 'false' : ($value === true ? 'true' : $value))))));
                        $help .= "   //" . ($field['nullable'] ? "必填" : "可选") . " - " . ($field['types'] . "  " . $field['note']) . "\r\n";
                        $values = (in_array($field['types'], ['tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal']) ? (is_numeric($value) ? $value : 0) : $value);
                        $help .= "    \"" . $fieldName . "\" => $values,\r\n\r\n";
                    }
                }
                $property .= " */\r\n";
                /*
                 * =============生在信息文件=============
                 */
                $updateCode = "<?php\r\n\r\n";
                $updateCode .= "namespace $this->_updateNamespace;\r\n\r\n";
                $updateCode .= $property;
                $updateCode .= "class $modelName extends \\" . $this->_namespace . "\\CommandModel {\r\n";
                $updateCode .= "    public              \$table       = \"" . $tableTitle . "\";\r\n";
                $updateCode .= "    public static string \$aloneTableName = \"" . $tableTitle . "\";\r\n";
                $updateCode .= "    public static string \$aloneTableTitle = \"" . $note . "\";\r\n";
                if (!empty($casts)) {
                    $updateCode .= "    protected \$casts = [\r\n";
                    $updateCode .= trim(trim($casts, "\r\n"), ",") . "\r\n";
                    $updateCode .= "    ];\r\n";
                }
                if (!empty($json)) {
                    $updateCode .= "    public static array \$aloneArrayList = [\r\n";
                    $updateCode .= trim(trim($json, "\r\n"), ",") . "\r\n";
                    $updateCode .= "    ];\r\n";
                }
                $updateCode .= "}";
                $updateCode .= "\r\n/*\r\n";
                $updateCode .= "[\r\n";
                $updateCode .= trim(trim($help, "\r\n"), ",") . "\r\n";
                $updateCode .= "];\r\n";
                $updateCode .= "*/";
                $isUpdate = @file_put_contents($this->_updatePath . "/$modelName.php", $updateCode);
                $build[($isUpdate ? 'success' : 'error')][] = $this->_updatePath . "/$modelName.php";
                /*
                 * =============生在model文件=============
                 */
                $modelTableName = !empty($prefix = ($this->buildConfig['prefix'] ?? '')) ? $prefix : "";
                $modelTableName .= $modelName;
                $modelTableName .= !empty($suffix = ($this->buildConfig['suffix'] ?? '')) ? ucfirst($suffix) : "";
                $modelCode = "<?php\r\n\r\n";
                $modelCode .= "namespace " . $this->_namespace . ";\r\n\r\n";
                $modelCode .= "/**\r\n";
                $modelCode .= " * " . trim($note) . "\r\n";
                $modelCode .= " */\r\n";
                $modelCode .= "class $modelTableName extends " . $this->_updateName . "\\$modelName {\r\n";
                $modelCode .= $this->getModelParam();
                $modelCode .= "}";
                $updateModel = $this->buildConfig['updateModel'] ?? null;
                $modelFileName = $this->_savePath . "/$modelTableName.php";
                if (!empty($updateModel) || empty(is_file($modelFileName))) {
                    $isModel = @file_put_contents($this->_savePath . "/$modelTableName.php", $modelCode);
                    $build[($isModel ? 'success' : 'error')][] = $this->_basePath . "/$modelTableName.php";
                }
                $modelFiles[$this->_savePath][] = $modelTableName . '.php';
                $modelFiles[$this->_savePath][] = 'CommandModel.php';
                $commonFileName = $this->_savePath . "/CommandModel.php";
                if (empty(is_file($commonFileName))) {
                    $commonCode = "<?php\r\n\r\n";
                    $commonCode .= "namespace $this->_namespace;\r\n\r\n";
                    $commonCode .= "/**\r\n";
                    $commonCode .= " * 公共Model,不会更新\r\n";
                    $commonCode .= " */\r\n";
                    $commonCode .= "class CommandModel extends " . $this->_updateName . "\\Common {\r\n";
                    $commonCode .= "}";
                    $isCommon = @file_put_contents($this->_savePath . "/CommandModel.php", $commonCode);
                    $build[($isCommon ? 'success' : 'error')][] = $this->_basePath . "/CommandModel.php";
                }
            }
        }
        //删除不存在Model
        if (!empty($this->buildConfig['deleteNotModel'] ?? null) && !empty($modelFiles)) {
            foreach ($modelFiles as $path => $list) {
                if (is_dir($path)) {
                    $files = scandir($path);
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..') {
                            if (str_ends_with($file, '.php') && is_file($path . '/' . $file)) {
                                if (!in_array($file, $list)) {
                                    @unlink($path . "/" . $file);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $build;
    }

    /**
     * @param array $mysqlConfig 数据库配置
     * @param array $buildConfig 生成配置
     */
    public function __construct(array $mysqlConfig, array $buildConfig) {
        $this->buildConfig = array_merge($this->buildConfig, $buildConfig);
        $this->mysqlConfig = array_merge($this->mysqlConfig, $mysqlConfig);
    }

    /**
     * 获取表单名称=>表单说明
     * @param string $database 数据库名称
     * @return array
     */
    public function tableList(string $database): array {
        $items = $this->tableInfo($database);
        $array = [];
        foreach ($items as $item) {
            $array[$item['TABLE_NAME']] = $item["TABLE_COMMENT"];
        }
        return $array;
    }

    /**
     * 原始数据库表单信息
     * @param string $database 数据库名称
     * @return array
     */
    public function tableInfo(string $database): array {
        $sql = 'SELECT * FROM information_schema.TABLES WHERE table_schema="' . $database . '"';
        return $this->connect()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取字段信息 名称=>[]
     * @param string $database 数据库名称
     * @param string $table    表单名称
     * @return mixed
     */
    public function fieldList(string $database, string $table): mixed {
        $items = $this->fieldInfo($database, $table);
        $array = [];
        foreach ($items as $item) {
            $array[$item['COLUMN_NAME']] = [
                // 是否id
                'id'       => strtolower($item['EXTRA']) == 'auto_increment',
                // 是否允许 NULL,true=表示可为空，false=表示不可为空
                "nullable" => $item["IS_NULLABLE"] == "YES",
                // 默认值
                "default"  => $item["COLUMN_DEFAULT"],
                //说明
                'note'     => $item['COLUMN_COMMENT'],
                // 类型(不含长度、符号）
                'type'     => strtolower($item['DATA_TYPE']),
                // 类型
                'types'    => strtolower($item['COLUMN_TYPE'])
            ];
        }
        return $array;
    }

    /**
     * 原始表单字段信息
     * @param string $database 数据库名称
     * @param string $table    表单名称
     * @return mixed
     */
    public function fieldInfo(string $database, string $table): mixed {
        $sql = 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema="' . $database . '" AND table_name="' . $table . '" ORDER BY ORDINAL_POSITION';
        return $this->connect()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 数据库连接
     * @param bool $type 是否重连
     * @return PDO|null
     */
    public function connect(bool $type = false): PDO|null {
        if (empty($this->pdo) || !empty($type)) {
            $dsn = $this->mysqlConfig('driver', 'mysql') . ":";
            if (!empty($socket = $this->mysqlConfig('unix_socket'))) {
                $dsn .= "unix_socket=" . $socket . ";";
            } else {
                $dsn .= "host=" . $this->mysqlConfig('host', '127.0.0.1') . ";";
                $dsn .= "port=" . $this->mysqlConfig('port', 3306) . ";";
            }
            $dsn .= "dbname=" . $this->mysqlConfig('database') . ";";
            $dsn .= "charset=" . $this->mysqlConfig('charset') . ";";
            $this->pdo = new PDO($dsn, $this->mysqlConfig('username'), $this->mysqlConfig('password'));
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->pdo;
    }

    /**
     * 获取配置
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function mysqlConfig(string $key, mixed $default = ""): mixed {
        return (isset($this->mysqlConfig[$key]) ? ($this->mysqlConfig[$key] ?? $default) : $default);
    }

    /**
     * 文件夹不存在创建文件夹(无限级)
     * @param $dir
     * @return bool
     */
    public static function mkDir($dir): bool {
        return (!empty(is_dir($dir)) || @mkdir($dir, 0777, true));
    }

    /**
     * 删除目录
     * @param string $path
     * @param array  $exclude
     * @return bool
     */
    public static function deleteDir(string $path, array $exclude = []): bool {
        if (is_link($path) || is_file($path)) {
            return unlink($path);
        } elseif (is_dir($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            $del = true;
            foreach ($files as $file) {
                if (empty($exclude) || !in_array(trim($file, '/'), $exclude)) {
                    (is_dir("$path/$file") && !is_link($path)) ? static::deleteDir("$path/$file") : unlink("$path/$file");
                } else {
                    $del = false;
                }
            }
            return $del && rmdir($path);
        }
        return false;
    }

    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param string $name    字符串
     * @param bool   $type    转换类型 true不使用_,false=使用_
     * @param bool   $ucFirst 首字母是否大写（驼峰规则）
     * @return string
     */
    public static function modeTable(string $name, bool $type = true, bool $ucFirst = true): string {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucFirst ? ucfirst($name) : lcfirst($name);
        }
        return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $name), '_'));
    }

    /**
     * @return $this
     */
    protected function modelConfig(): static {
        //命名空间
        $this->_namespace = str_replace('/', '\\', trim(trim($this->buildConfig['savePath'], '\\'), '/'));
        //相对路径
        $this->_basePath = str_replace('\\', DIRECTORY_SEPARATOR, $this->_namespace);
        //绝对路径
        $this->_savePath = rtrim(rtrim($this->buildConfig['rootBase'], '\\'), '/') . DIRECTORY_SEPARATOR . $this->_basePath;
        //更新目录名
        $this->_updateName = trim(trim($this->buildConfig['updateName'], '\\'), '/');
        //更新目录命名空间
        $this->_updateNamespace = $this->_namespace . "\\" . $this->_updateName;
        //更新目录绝对路径
        $this->_updatePath = $this->_savePath . DIRECTORY_SEPARATOR . $this->_updateName;
        return $this;
    }

    /**
     * @return string
     */
    protected function getModelParam(): string {
        $list = $this->buildConfig['args'] ?? "";
        if (is_array($list)) {
            $param = "";
            foreach ($list as $val) {
                $param .= "    " . trim(trim($val), ';') . ";\r\n";
            }
            return $param;

        }
        return $list;
    }

    /**
     * @return bool|int
     */
    protected function workerManCommon(): bool|int {
        $name = $this->buildConfig["connectName"];
        $commonCode = "<?php\r\n\r\n";
        $commonCode .= "namespace $this->_updateNamespace;\r\n\r\n";
        $commonCode .= "/**\r\n";
        $commonCode .= " * 此目录的文件每次都会更新\r\n";
        $commonCode .= " */\r\n";
        $commonCode .= "class Common" . $this->getCommonExtends() . " {\r\n";
        $commonCode .= $this->getCommonTrait();
        $commonCode .= "    public               \$connection    = \"$name\";\r\n";
        $commonCode .= "    public static string \$aloneConnName = \"$name\";\r\n";
        $commonCode .= "    // 是否主库\r\n";
        $commonCode .= "    public static bool   \$aloneMain = " . ($this->buildConfig["main"] ? "true" : "false") . ";\r\n\r\n";
        $commonCode .= "}";
        return @file_put_contents($this->_updatePath . "/Common.php", $commonCode);
    }

    /**
     * @return string
     */
    protected function getCommonExtends(): string {
        $extends = $this->buildConfig["extends"] ?? "";
        return !empty($extends) ? " extends $extends" : "";
    }

    /**
     * @return string
     */
    protected function getCommonTrait(): string {
        $trait = $this->buildConfig["trait"] ?? "";
        if (is_array($trait)) {
            $use = "";
            foreach ($trait as $val) {
                $value = str_replace('/', '\\', trim(trim($val), ';'));
                if (str_starts_with($value, 'use')) {
                    $value = substr($value, strlen("use"));
                }
                $use .= "    use " . $value . ";\r\n";
            }
            return $use;
        }
        return $trait;
    }
}