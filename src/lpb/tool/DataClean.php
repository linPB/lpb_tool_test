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
        $file_info = trim($base_dir .'/'. $this->target_file . "$file_name");
        $rows = file_get_contents($file_info);
        $rows = explode("\n", $rows);

        $file = fopen($base_dir .'/'. $this->target_file . "/data_filter.txt","w");

        $rows_arr = array_chunk($rows, 1000,true);
        foreach ($rows_arr as $items) {

            // 每一千个处理一次
            $handle_arr= [];
            foreach ($items as $cun_num => $item) {
                if (empty($item)) continue;
                list($uid, $mobile, ) = explode("\t", $item);
                $handle_arr[$uid] = $mobile;

                var_dump($cun_num);
            }
            $handle_str = implode("','", array_keys($handle_arr));
            $lists = (new DB)->get()::select("select 
    max(lev) as lev,
    acct_name from game_role 
              where acct_name in ('$handle_str') group by acct_name");

            $stat = json_decode( json_encode($lists), true );
            $stat = array_column($stat, 'lev', 'acct_name');

            //写入回文件
            foreach ($handle_arr as $uid_v => $mobile_v) {
                $temp = $stat[$uid_v] ?? 0;
                fwrite($file,"$uid_v\t$mobile_v\t$temp\n");
            }
        }
        fclose($file);
    }
}