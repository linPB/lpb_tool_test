<?php
/**
 * Created by PhpStorm
 * Func:
 * User: lpb
 * Date: 2023/2/17
 * Time: 16:08
 */

require "vendor/autoload.php";

use Linpeibing\LpbTool\DB;

//    $db = (new DB)->get();
//    $rows = $db::table('test_tb')->get()->toArray();

//    $arr = ["A1"=>"创建时间","B1"=>"姓名","C1"=>"手机号","D1"=>"来源","E1"=>"身份",
//        "F1"=>"付费状态","G1"=>"幼儿园","H1"=>"用户","I1"=>"地区","J1"=>"渠道","K1"=>"销售","L1"=>"运营"
//    ];
//    saveLocalExcel($arr, [
//        [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
//        [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2],
//        [3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3],
//    ],"全部用户","xls");

calculateAttendanceForZhiJie("original.xlsx");





