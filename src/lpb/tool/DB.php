<?php
/**
 * Created by PhpStorm
 * Func:
 * User: lpb
 * Date: 2023/2/17
 * Time: 17:23
 */

namespace Linpeibing\LpbTool;

use Exception;
use \Illuminate\Database\Capsule\Manager;

class DB
{
    /**
     * @throws Exception
     */
    public function get($conn = "default"): Manager
    {
        $db_conf = require "config/database.php";

        if ( !isset($db_conf[$conn]) ) {
            throw new Exception("mysql conn config not exists.");
        }

        $db = new Manager;
        // 创建链接
        $db->addConnection($db_conf[$conn]);
        // 设置全局静态可访问DB
        $db->setAsGlobal();
        // 启动Eloquent （如果只使用查询构造器，这个可以注释）
        $db->bootEloquent();
        return $db;
    }
}