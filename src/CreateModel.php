<?php

namespace AloneWebMan\Model;


class CreateModel {

    public static function webMan(): void {
        $mysqlList = alone_get_data_base('mysql');
        if (count($mysqlList) == 0) {
            print_r("No database");
            return;
        }
        $appConfig = config('plugin.alone.model.app.model', []);
        if (!empty($appConfig["switch"] ?? null) && !empty($database = $appConfig["database"] ?? [])) {
            $dataList = $database;

        } else {
            $dataList = [];
        }


        foreach ($mysqlList as $key) {
            //获取名称
            $arr = explode(".", $key);
            //获取数据库名称
            $name = count($arr) == 1 ? $key : join('', array_slice($arr, -1));

        }
    }
}