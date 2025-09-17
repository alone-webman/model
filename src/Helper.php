<?php

namespace AloneWebMan\Model;

use support\Db;

class Helper {
    /**
     * 生成model文件
     * @param array $list   config('database.connections', [])
     * @param array $config [namespace=相对路径,prefix=文件前缀,suffix=文件后缀,extends=继承类名,trait=引入类]
     * @param bool  $update 是否更新已存在model文件头property
     * @return array|string
     */
    public static function model(array $list, array $config, bool $update = true): array|string {
        if (count($list) == 0) {
            return 'No database';
        }
        $aloneName = 'alone';
        //model命名空间
        $namespace = $config['namespace'] ?? 'app\model';
        //命名空间
        $namespace = str_replace('/', '\\', trim(trim($namespace, '\\'), '/'));
        //保存路径
        $savePath = rtrim(rtrim(base_path(str_replace('\\', '/', $namespace)), '/'), '\\');
        //扩展Model类名
        $extends = $config['extends'] ?? 'support\Model';
        $extends = "\\" . str_replace('/', '\\', trim(trim($extends, '\\'), '/'));
        $prefix = $config['prefix'] ?? '';
        $suffix = $config['suffix'] ?? '';
        $fileList = [];
        $intList = [
            'tinyint',
            'smallint',
            'mediumint',
            'int',
            'bigint',
            'float',
            'double',
            'decimal'
        ];
        //引入类
        $trait = $config['trait'] ?? [];
        $trait = is_array($trait) ? $trait : [$trait];
        $useTrait = "";
        foreach ($trait as $use) {
            $useTrait .= "    use \\" . str_replace('/', '\\', trim(trim($use, '\\'), '/')) . ";\r\n";
        }
        $useTrait = !empty($useTrait) ? ($useTrait . "\r\n") : "";
        foreach ($list as $key => $val) {
            //获取名称
            $arr = explode(".", $key);
            //获取数据库名称
            $keyName = count($arr) == 1 ? $key : join('', array_slice($arr, -1));
            //Model目录
            $modelDir = static::dirPath($savePath, trim(trim($keyName, '/'), '\\'));
            //更新目录名
            $aloneDir = static::dirPath($modelDir, trim(trim($aloneName, '\\'), '/'));
            //删除目录
            static::deleteDir($aloneDir);
            //创造目录
            static::mkDir($aloneDir);
            $commonCode = "<?php\r\n\r\n";
            $commonCode .= "namespace $namespace\\$keyName\\$aloneName;\r\n\r\n";
            $commonCode .= "//此文件每次都会更新\r\n";
            $commonCode .= "class Common extends $extends {\r\n";
            $commonCode .= $useTrait;
            $commonCode .= "    public               \$connection    = \"$key\";\r\n";
            $commonCode .= "    public static string \$aloneConnName = \"$key\";\r\n\r\n";
            $commonCode .= "}";
            @file_put_contents(static::dirPath($aloneDir, "Common.php"), $commonCode);
            //获取表单列表
            $tableList = Db::connection($key)->select('SELECT * FROM information_schema.TABLES WHERE table_schema="' . $val['database'] . '"');
            $tableNameList = [];
            foreach ($tableList as $v) {
                //获取字段详细信息
                $fieldSql = 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema="' . $v->TABLE_SCHEMA . '" AND table_name="' . $v->TABLE_NAME . '" ORDER BY ORDINAL_POSITION';
                $fieldArr = Db::connection($key)->select($fieldSql);
                $property = "/**\r\n";
                $proTitle = trim($v->TABLE_COMMENT);
                $property .= " * $proTitle\r\n";
                $timestamps = false;
                $casts = '';
                $getArr = '';
                foreach ($fieldArr as $obj) {
                    if ($obj->COLUMN_NAME == 'created_at' || $obj->COLUMN_NAME == 'updated_at') {
                        $timestamps = true;
                    }
                    $type = strtolower($obj->DATA_TYPE);
                    if ($type == 'json') {
                        $casts .= "        \"" . $obj->COLUMN_NAME . "\" => \"array\",\r\n";
                    } elseif ($type == 'decimal') {
                        $casts .= "        \"" . $obj->COLUMN_NAME . "\" => \"float\",\r\n";
                    }
                    $property .= " * @property \$" . $obj->COLUMN_NAME . " " . $obj->COLUMN_TYPE . " " . trim(trim(trim($obj->COLUMN_COMMENT ?? ''), "\n"), "\r") . "\r\n";
                    if (strtolower($obj->EXTRA) != 'auto_increment') {
                        $value = $obj->COLUMN_DEFAULT;
                        $value = is_numeric($value) ? ($value == 0 ? 0 : $value) : (is_string($value) ? ("\"$value\"") : ((($value === null ? 'null' : ($value === false ? 'false' : ($value === true ? 'true' : $value))))));
                        $help = "   //" . ($obj->COLUMN_TYPE . "  " . $obj->COLUMN_COMMENT);
                        $getArr .= "  " . $help . "\r\n";
                        $values = (in_array($type, $intList) ? (is_numeric($value) ? $value : 0) : $value);
                        $getArr .= "    \"" . $obj->COLUMN_NAME . "\" => $values,\r\n\r\n";
                    }
                }
                $property .= " */\r\n";
                $getData = "\r\n/*\r\n";
                $getData .= "[\r\n";
                $getData .= trim(trim($getArr, "\r\n"), ",") . "\r\n";
                $getData .= "];\r\n";
                $getData .= "*/";
                //获取表单名,不带前缀
                $table = str_starts_with($v->TABLE_NAME, $val['prefix']) ? substr($v->TABLE_NAME, strlen($val['prefix'])) : $v->TABLE_NAME;
                $aloneTab = static::modeTable($table);
                $aloneCode = "<?php\r\n\r\n";
                $aloneCode .= "namespace $namespace\\$keyName\\$aloneName;\r\n\r\n";
                $aloneCode .= "//此文件每次都会更新\r\n";
                $aloneCode .= "class $aloneTab extends Common {\r\n";
                $aloneCode .= "    public              \$table       = \"" . $table . "\";\r\n";
                if (!empty($casts)) {
                    $aloneCode .= "    protected \$casts = [\r\n";
                    $aloneCode .= trim(trim($casts, "\r\n"), ",") . "\r\n";
                    $aloneCode .= "    ];\r\n";
                    $aloneCode .= "    public static array \$aloneArrayList = [\r\n";
                    $aloneCode .= trim(trim($casts, "\r\n"), ",") . "\r\n";
                    $aloneCode .= "    ];\r\n";
                }
                $aloneCode .= "    public static string \$aloneTableName = \"" . $table . "\";\r\n";
                $aloneCode .= "    public static string \$aloneTableTitle = \"" . trim($v->TABLE_COMMENT) . "\";\r\n";
                $aloneCode .= "}";
                $aloneCode .= $getData;
                @file_put_contents(static::dirPath($aloneDir, $aloneTab . '.php'), $aloneCode);
                $tableTab = $prefix . static::modeTable($table) . ucfirst($suffix);
                $classCode = "<?php\r\n\r\n";
                $classCode .= "namespace $namespace\\$keyName;\r\n\r\n";
                $classCode .= $property;
                $classCode .= "class $tableTab extends $aloneName\\$aloneTab {\r\n";
                $classCode .= "    protected           \$guarded  = [];\r\n";
                $classCode .= "    public              \$primaryKey  = \"id\";\r\n";
                $classCode .= "    public              \$timestamps  = " . ($timestamps ? "true" : "false") . ";\r\n";
                $classCode .= "    public              \$dateFormat  = \"Y-m-d H:i:s\";\r\n";
                $classCode .= "}";
                $classFile = static::dirPath($modelDir, $tableTab . '.php');
                $tableNameList[$modelDir][] = $tableTab . '.php';
                if (is_file($classFile)) {
                    if ($update === true) {
                        $body = static::strRep($classFile, $property);
                        @file_put_contents($classFile, $body);
                    }
                } else {
                    @file_put_contents($classFile, $classCode);
                }
                $fileList[$key][] = trim(trim(substr($classFile, strlen(base_path())), '/'), '\\');
            }
            //删除不存在Model
            if (!empty($config['delete'] ?? '') && !empty($tableNameList)) {
                foreach ($tableNameList as $path => $list) {
                    if (is_dir($path)) {
                        $files = scandir($path);
                        foreach ($files as $file) {
                            if ($file != '.' && $file != '..') {
                                if (str_ends_with($file, '.php') && is_file($path . '/' . $file)) {
                                    if (!in_array($file, $list)) {
                                        @unlink(static::dirPath($path, $file));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $fileList;
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
     * 替换 property 内容
     * @param string $file     要替换的文件
     * @param string $property 更新的内容
     * @return array|bool|string
     */
    protected static function strRep(string $file, string $property): array|bool|string {
        $body = @file_get_contents($file);
        preg_match('/\/\*\*(.*?)\*\//s', $body, $matches);
        if (str_contains($body, "@property") && !empty($content = ($matches[0] ?? ''))) {
            $body = str_replace(trim($content), trim($property, "\r\n"), $body);
        }
        return $body;
    }

    /**
     * 路径拼接,后面 不带 /
     * @param string $dir  绝对路径
     * @param string $path 相对路径
     * @return string
     */
    public static function dirPath(string $dir, string $path = ''): string {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $path = $path ? (($path == '/') ? $path : (DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) : DIRECTORY_SEPARATOR;
        return rtrim(rtrim($dir . $path, DIRECTORY_SEPARATOR), '/');
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
}