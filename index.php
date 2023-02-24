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

use Linpeibing\LpbTool\DB;

// calculateAttendanceForZhiJie("original.xlsx");

parseExportCsv2($smp_lists['smp_item_score']['时装']['column'], $smp_lists['smp_item_score']['时装']['sql'], "smp_item_score");




