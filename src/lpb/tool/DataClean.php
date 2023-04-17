<?php
/**
 * Created by PhpStorm
 * Func: 过滤数据
 * User: lpb
 * Date: 2023/4/17
 * Time: 16:37
 */

namespace Linpeibing\LpbTool;

class DataClean
{
    private $target_file = "storage/";

    public function parseDataFromFile(string $base_dir, string $file_name) :void
    {
        $file_info = trim($base_dir .'/'. $this->target_file . $file_name);
        $rows = file_get_contents($file_info);
        $rows = explode("\r\n", $rows);

        $rows_arr = array_chunk($rows,100000,true);
        foreach ($rows_arr as $arr) {
            $file = fopen($base_dir .'/'. $this->target_file . "/data_filter.txt","w");
            foreach ($arr as $item) {
                if (empty($item)) continue;
                list($uid, $mobile, ) = explode("\t", $item);
                if ($uid === "user_id") {
                    continue;
                } else {
                    $lev = (new DB)->get()::table("game_role")->where("acct_name", $uid)->max("lev");
                    var_dump($lev);
                    if ($lev) {
                        fwrite($file,"$uid\t$mobile\t$lev\n");
                    }
                }
            }
            fclose($file);
        }
    }
}