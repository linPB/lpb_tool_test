<?php
/**
 * Created by PhpStorm
 * Func:
 * User: lpb
 * Date: 2023/2/17
 * Time: 16:08
 */

require "vendor/autoload.php";
$smp_lists = require "data_list/smp.php";
$log_lists = require "data_list/log.php";

# php index.php -p 1
$do_args = getopt('p:');

var_dump($do_args);

if (!isset($do_args["p"])) {
    echo "参数缺失".PHP_EOL;
    die();
}

switch ($do_args["p"]){
    case "export_calc" :
        calculateAttendanceForZhiJie("original.xlsx");
        break;
    case "export_smp":
        /**
         *@ 标准输入
         *@ php://stdin & STDIN
         *@ STDIN是一个文件句柄，等同于fopen("php://stdin", 'r')
         */
        $fh = fopen('php://stdin', 'r');
        echo "请输入smp表名：";
        $table = fread($fh, 1000);
        echo "你输入的是：$table";
        fclose($fh);
        echo "请输入导出类型：";
        $type = fread(STDIN, 1000);
        echo "你输入的是：$type";

        if (!isset($smp_lists[$table])) {
            echo "$table 表未配置，操作拒绝".PHP_EOL;
            die();
        }
        if (!isset($smp_lists[$table][$type])) {
            echo "$table 表对应的 $type 未配置，操作拒绝".PHP_EOL;
            die();
        }

        try {
            parseExportCsv2($smp_lists[$table][$type]['column'], $smp_lists[$table][$type]['sql'], $table);
        } catch (Exception $e) {
            var_dump($e);
        }

        break;
    case "export_log":
        $fh = fopen('php://stdin', 'r');
        echo "请输入log表名：";
        $table = fread($fh, 1000);
        echo "你输入的是：$table";
        fclose($fh);
        echo "请输入导出类型：";
        $type = fread(STDIN, 1000);
        echo "你输入的是：$type";
        break;
    case "test":
        try {
            $rows = (new Linpeibing\LpbTool\DB)->get()::table("test")->get();
            var_dump($rows);
        } catch (Exception $e) {
            var_dump($e);
        }
        break;
    default:
        echo "操作不允许".PHP_EOL;
        die();
}



