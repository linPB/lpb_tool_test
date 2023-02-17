<?php
/**
 * Created by PhpStorm
 * Func:
 * User: lpb
 * Date: 2023/2/17
 * Time: 16:08
 */

require "vendor/autoload.php";
$Test = new Linpeibing\LpbTool\Test();
$Test->test();

$capsule = new \Illuminate\Database\Capsule\Manager;
// 创建链接
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'lpb_test',
    'username' => 'root',
    'password' => '123456',
    'charset' => 'utf8mb4',
    'port' => 3306,
    'collation' => 'utf8mb4_general_ci',
    'prefix' => '',
]);
// 设置全局静态可访问DB
$capsule->setAsGlobal();
// 启动Eloquent （如果只使用查询构造器，这个可以注释）
$capsule->bootEloquent();