<?php

namespace AloneWebMan\Model\Command;

use AloneWebMan\Model\SqlHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SqlExec extends Command {
    protected static $defaultName        = 'alone:sql';
    protected static $defaultDescription = 'backup or recover sql';

    public function execute(InputInterface $input, OutputInterface $output): int {
        static::execCommand();
        return self::SUCCESS;
    }

    public static function execCommand(mixed $int = null): void {
        $list = alone_get_data_base(config('plugin.alone.model.app.driver', ''));
        echo "--------------------------------------------------------\r\n";
        if (count($list) == 0) {
            echo "No database\r\n";
            echo "--------------------------------------------------------\r\n";
            return;
        }
        $backPath = config('plugin.alone.model.app.sql', base_path('alone'));
        echo "1:Backup database\r\n";
        echo "2:Recover database\r\n";
        echo "--------------------------------------------------------\r\n";
        echo "Enter the opt number: ";
        $opt = $int ?? trim(fgets(STDIN));
        echo "--------------------------------------------------------\r\n";
        $echoList = function($echo = '', $show = true) use ($list) {
            $i = 0;
            $database = [];
            if (!empty($echo)) {
                echo $echo;
            }
            foreach ($list as $key => $val) {
                ++$i;
                if (!empty($show)) {
                    echo "$i:$key\r\n";
                }
                $arr = explode(".", $key);
                $name = count($arr) == 1 ? $key : join('', array_slice($arr, -1));
                $database[$i] = ['key' => $key, 'val' => $val, 'name' => $name];
            }
            return $database;
        };
        if ($opt == 1) {
            $backKey = 1;
            $database = $echoList("0:Backup all\r\n");
            if (count($database) > 1) {
                echo "Enter backup the number: ";
                $backKey = trim(fgets(STDIN));
            }
            $backup = function($val) use ($backPath) {
                $dir = rtrim($backPath, '/') . '/' . $val['name'];
                (!empty(is_dir($dir)) || @mkdir($dir, 0777, true));
                $mysql = new SqlHelper($val['val']);
                $file = $dir . '/' . time() . '.sql';
                $res = $mysql->exportCallable()->export($file);
                print_r($res);
                print_r("\r\n" . $file . "\r\n");
            };
            if (isset($database[$backKey])) {
                $val = $database[$backKey];
                $backup($val);
            } elseif ($backKey == 0) {
                foreach ($database as $val) {
                    $backup($val);
                }
            } else {
                static::execCommand(1);
                return;
            }
        } elseif ($opt == 2) {
            $recKey = 1;
            $database = $echoList('', count($list) > 1);
            if (count($list) > 1) {
                echo "Enter recover the number: ";
                $recKey = trim(fgets(STDIN));
            }
            if (isset($database[$recKey])) {
                $val = $database[$recKey];
                $dir = rtrim($backPath, '/') . '/' . $val['name'];
                $fileList = alone_get_dir_file($dir);
                if (count($fileList) > 0) {
                    $i = 0;
                    $fileArr = [];
                    foreach ($fileList as $k => $v) {
                        ++$i;
                        echo "$i:$k\r\n";
                        $fileArr[$i] = $v;
                    }
                    echo "Enter recover the file: ";
                    $recSql = trim(fgets(STDIN));
                    if (isset($fileArr[$recSql])) {
                        $file = $fileArr[$recSql];
                        $mysql = new SqlHelper($val['val']);
                        $res = $mysql->importCallable()->import($file);
                        print_r($res);
                        print_r("\r\n" . $file . "\r\n");
                    } else {
                        static::execCommand(2);
                        return;
                    }
                } else {
                    echo "No recover sql file\r\n";
                    echo "--------------------------------------------------------\r\n";
                    return;
                }
            } else {
                static::execCommand(2);
                return;
            }
        } else {
            static::execCommand();
            return;
        }
        echo "\r\n--------------------------------------------------------\r\n";
    }
}