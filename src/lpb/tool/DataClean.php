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

    public function parseDataFromFile(string $file_name) :void
    {
        $rows = file_get_contents($this->target_file . $file_name);
        $rows = explode("\r\n", $rows);

        $rows_arr = array_chunk($rows,100000,true);
        foreach ($rows_arr as $arr) {
            $file = fopen($this->target_file . "/data_filter.txt","w");
            foreach ($arr as $item) {
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