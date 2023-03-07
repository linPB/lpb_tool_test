<?php
/**
 * Created by PhpStorm
 * Func:
 * User: lpb
 * Date: 2023/2/17
 * Time: 19:09
 */

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Linpeibing\LpbTool\DB;

const LocalFilePath = "storage/";

/**
 * 方法1：  打开一次文件,以追加的方式写入数据，会一直占用该文件资源，直到文件被关闭，资源被释放
 * @param array $arr
 * @param array $data
 * @param string $name
 * @return void
 */
function exportCsv1(array $arr, array $data, string $name=""){
    $file = empty($name) ? date('YmdHis').'_export.csv' : $name."_".date('YmdHis').'_export.csv';
    $fp = fopen(LocalFilePath.$file,'w+'); //w+以追加的方式写入文件
    if(flock($fp,LOCK_EX)){
        //文件加独占锁，是否加锁看实际需求 共享锁LOCK_SH 独占锁LOCK_EX
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($fp,$arr);
        foreach($data as $key => $val){
            fputcsv($fp,$val); # fputcsv需要使用一维数组做为参数
        }
    }
    fclose($fp);
}


/**
 * 方法2：  创建文件后以追加的方式写入数据，每次都会打开并关闭文件，会增加I/O操作，但是会减少文件的占用
 * @param array $arr
 * @param array $data
 * @param string $name
 * @return void
 */
function exportCsv2(array $arr, array $data, string $name = ""){
    $name = empty($name) ? date('YmdHis').'_export.csv' : $name."_".date('YmdHis').'_export.csv';
    $file = LocalFilePath.$name;
    file_put_contents($file,chr(0xEF).chr(0xBB).chr(0xBF));//添加BOM头，解决乱码问题
    file_put_contents($file,implode(',',$arr).PHP_EOL,FILE_APPEND);//以追加方式写入
    foreach($data as $key => $val){
        file_put_contents($file,implode(',',$val).PHP_EOL,FILE_APPEND);
    }
}

/**
 * 方法2 的 改良版 分段到数据
 * @param array $arr
 * @param string $str
 * @param string $name
 * @return void
 * @throws Exception
 */
function parseExportCsv2(array $arr, string $str, string $name = ""){
    $name = empty($name) ? date('YmdHis').'_export.csv' : $name."_".date('YmdHis').'_export.csv';
    $file = LocalFilePath.$name.date('YmdHis').'_export.csv';
    file_put_contents($file,chr(0xEF).chr(0xBB).chr(0xBF));//添加BOM头，解决乱码问题
    file_put_contents($file,implode(',',$arr).PHP_EOL,FILE_APPEND);//以追加方式写入

    $db = (new DB)->get();
    $count_obj = $db::select("select count(*) as count from ($str) as a");
    $count = $count_obj[0]->count;
    $nums = 10000;
    $step = $count/$nums;
    for($i = 0; $i < $step; $i++) {
        $start = $i * 10000;
        $rows = $db::select( "$str LIMIT $start,$nums");
        $rows = json_decode(json_encode($rows), true);
        var_dump("起始:$start 数量段：". count($rows));
        foreach($rows as $row){
            file_put_contents($file,implode(',',$row).PHP_EOL,FILE_APPEND);
        }
    }
}

/**
 * 对Spreadsheet方法封装
 * @param array $arr 该数组必须为键值对，键是表格单元，值是单元格的值
 * @param array $data 该数组如果为一维数组，则填写一行，如果为二维数组，则多行数据
 * @param string $name 下载Excel的文件名，可忽略
 * @param string $type 选择文件类型，如果不填写，默认下载为xlsx格式，如果任意填写数值为xls格式
 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
 */
function downloadExcel(array $arr, array $data, string $name="", string $type="Xlsx"){
    # 文件名处置
    $name = empty($name) ? date('YmdHis') : $name."_".date('YmdHis');

    # 内容设置
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    foreach($arr as $k=>$v){
        $sheet->setCellValue($k,$v);
    }
    $sheet->fromArray($data,null,"A2");

    # 样式设置
    $sheet->getDefaultColumnDimension()->setWidth(12);

    # 设置下载与后缀
    if($type=="Xlsx"){
        header("Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        $suffix = "xlsx";
    }else{
        header("Content-Type:application/vnd.ms-excel");
        $type = "Xls";
        $suffix = "xls";
    }
    header("Content-Disposition:attachment;filename=$name.$suffix");
    header("Cache-Control:max-age=0"); # 缓存控制
    $writer = IOFactory::createWriter($spreadsheet,$type);
    $writer->save("php://output"); # 数据流
}

function saveLocalExcel(array $arr, array $data, string $name = "", string $type = "Xlsx")
{
    //    $arr = ["A1"=>"创建时间","B1"=>"姓名","C1"=>"手机号","D1"=>"来源","E1"=>"身份",
    //        "F1"=>"付费状态","G1"=>"幼儿园","H1"=>"用户","I1"=>"地区","J1"=>"渠道","K1"=>"销售","L1"=>"运营"
    //    ];

    //    $data = [
    //        [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
    //        [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2],
    //        [3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3],
    //    ];

    //文件名处置
    if(empty($name)){
        $name = date('YmdHis');
    }else{
        $name = $name."_".date('YmdHis');
    }

    //内容设置
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    foreach($arr as $k=>$v){
        $sheet->setCellValue($k,$v);
    }
    $sheet->fromArray($data,null,"A2");

    //样式设置
    $sheet->getDefaultColumnDimension()->setWidth(12);

    //设置下载与后缀
    if($type=="Xlsx"){
        header("Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        $suffix = "xlsx";
    }else{
        header("Content-Type:application/vnd.ms-excel");
        $type = "Xls";
        $suffix = "xls";
    }
    header("Content-Disposition:attachment;filename=$name.$suffix");
    header("Cache-Control:max-age=0");//缓存控制
    $writer = IOFactory::createWriter($spreadsheet,$type);
    $writer->save(LocalFilePath."$name.$type");
}

/**
 * 读取excel文件内容
 * @param $name
 * @return array
 */
function readLocalExcel($name): array
{
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(LocalFilePath.$name);
    return $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
}

/**
 * 工时计算。。
 * @param $filename
 * @return void
 */
function calculateAttendanceForZhiJie($filename): void
{
    $rows = readLocalExcel($filename);

    $staffs = [];
    foreach ($rows as $k => $row) {

        # 过滤表头信息
        if ($k < 5) {
            continue;
        }

        # 初始化员工集合
        if (!isset($staffs[$row["B"]])) {
            $staffs[$row["B"]] = [];
        }

        # 初始化员工下的日期集合
        if (!isset($staffs[$row["B"]][$row["A"]])) {
            $staffs[$row["B"]][$row["A"]] = [];
        }

        # 过滤 未打卡 类型
        if (false !== strstr($row["I"], "未打卡")) {
            continue;
        }

        if ( count($staffs[$row["B"]][$row["A"]]) == 2 ) {
            # 只保留至多两个打卡记录，数量为2时，新出现的打卡记录覆盖旧地打卡记录
            $staffs[$row["B"]][$row["A"]][1] = $row["I"];
        } else {
            $staffs[$row["B"]][$row["A"]][] = $row["I"];
        }
    }

    foreach ( $staffs as $staff_name => $date_nums ) {

        foreach ($date_nums as $date => $clock_list) {
            # $clock_list 打卡集合
            if (empty($clock_list)) {
                # 补充回去使excel绘制数据完整
                $staffs[$staff_name][$date]["memo"] = "一次打卡都没";
                $staffs[$staff_name][$date]["hours"] = 0;
                $staffs[$staff_name][$date]["st"] = "";
                $staffs[$staff_name][$date]["et"] = "";
                $staffs[$staff_name][$date][0] = 0;
                $staffs[$staff_name][$date][1] = 0;
                continue;
            }

            # 只有一次打卡
            if (count($clock_list) == 1) {
                # 补充回去使excel绘制数据完整
                $staffs[$staff_name][$date]["memo"] = "一次打卡";
                $staffs[$staff_name][$date]["hours"] = 0;
                $staffs[$staff_name][$date]["st"] = $clock_list[0];
                $staffs[$staff_name][$date]["et"] = "";
                $staffs[$staff_name][$date][0] = 0;
                $staffs[$staff_name][$date][1] = 0;
                continue;
            }

            # 两次都是次日打卡
            if (false !== strstr($clock_list[0], "次日") && false !== strstr($clock_list[1], "次日")) {
                $staffs[$staff_name][$date]["memo"] = "两个次日";
                $staffs[$staff_name][$date]["hours"] = 0;
                $staffs[$staff_name][$date]["st"] = $clock_list[0];
                $staffs[$staff_name][$date]["et"] = $clock_list[1];
                $staffs[$staff_name][$date][0] = 0;
                $staffs[$staff_name][$date][1] = 0;
                continue;
            }

            $first = explode(":", $clock_list[0]);#上班打卡
            list($first_h, $first_m) = $first;
            if ($first_h < 9) {
                $st = 9 * 60;
            } else {
                $st = $first_h * 60 + $first_m;
            }

            $origin_st = $clock_list[0];
            $origin_et = $clock_list[1];
            #下班打卡
            if (false !== strstr($clock_list[1], "次日")) {
                $second = str_replace("次日", "", $clock_list[1]);
                $second = explode(":", $second);
                list($second_h, $second_m) = $second;
                $et = ($second_h+24) * 60 + $second_m;#小时+分钟
            } else {
                $second = explode(":", $clock_list[1]);
                list($second_h, $second_m) = $second;
                $et = $second_h * 60 + $second_m;#小时+分钟
            }

            $staffs[$staff_name][$date][0] = $st;
            $staffs[$staff_name][$date][1] = $et;

            if ( 9*60 <= $st && $st <= 12*60 ) {
                if ( 9*60 <= $et && $et <= 12*60 ) {
                    # ----9:30--🐕-🐕-12:00----14:00----18:00----19:00----
                    $invalid_time = 0;
                } elseif ( 12*60 <= $et && $et <= 14*60 ) {
                    # ----9:30--🐕--12:00--🐕--14:00----18:00----19:00----
                    $invalid_time = round(($et-12*60)/60, 4);
                } elseif ( 14*60 <= $et && $et <= 18*60 ) {
                    # ----9:30--🐕--12:00----14:00--🐕--18:00----19:00----
                    $invalid_time = 2;
                } elseif ( 18*60 <= $et && $et <= 19*60 ) {
                    # ----9:30--🐕--12:00----14:00----18:00--🐕--19:00----
                    $invalid_time = 2 + round(($et-18*60)/60, 4);
                } else {
                    # ----9:30--🐕--12:00----14:00----18:00----19:00--🐕--
                    $invalid_time = 3;
                }
            } elseif ( 12*60 <= $st && $st <= 14*60 ) {
                if ( 12*60 <= $et && $et <= 14*60 ) {
                    # ----9:30----12:00--🐕-🐕-14:00----18:00----19:00----
                    $invalid_time = round(($et-$st)/60, 4);
                } elseif ( 14*60 <= $et && $et <= 18*60 ) {
                    # ----9:30----12:00--🐕--14:00--🐕--18:00----19:00----
                    $invalid_time = round((14*60-$st)/60, 4);
                } elseif ( 18*60 <= $et && $et <= 19*60 ) {
                    # ----9:30----12:00--🐕--14:00----18:00--🐕--19:00----
                    $invalid_time = round((14*60-$st)/60, 4) + round(($et-18*60)/60, 4);
                } else {
                    # ----9:30----12:00--🐕--14:00----18:00----19:00--🐕--
                    $invalid_time = round((14*60-$st)/60, 4) + 1;
                }
            } elseif ( 14*60 <= $st && $st <= 18*60 ) {
                if ( 14*60 <= $et && $et <= 18*60 ) {
                    # ----9:30----12:00----14:00--🐕-🐕-18:00----19:00----
                    $invalid_time = 0;
                } elseif ( 18*60 <= $et && $et <= 19*60 ) {
                    # ----9:30----12:00----14:00--🐕--18:00--🐕--19:00----
                    $invalid_time = round(($et-18*60)/60, 4);
                } else {
                    # ----9:30----12:00----14:00--🐕--18:00----19:00--🐕--
                    $invalid_time = 1;
                }
            } elseif ( 18*60 <= $st && $st <= 19*60 ) {
                if ( 18*60 <= $et && $et <= 19*60 ) {
                    # ----9:30----12:00----14:00----18:00--🐕-🐕-19:00----
                    $invalid_time = round(($et - $st)/60, 4);
                } else {
                    # ----9:30----12:00----14:00----18:00--🐕--19:00--🐕--
                    $invalid_time = round((19*60 - $et)/60, 4);
                }
            } else {
                # ----9:30----12:00----14:00----18:00----19:00----🐕🐕 狗die了
                $invalid_time = 0;
            }

            $staffs[$staff_name][$date]["memo"] = "正常";
            $staffs[$staff_name][$date]["hours"] = round(($et-$st)/60, 4) - $invalid_time;
            $staffs[$staff_name][$date]["st"] = $origin_st;
            $staffs[$staff_name][$date]["et"] = $origin_et;
        }
    }

    $arr_title = ["A1"=>"日期","B1"=>"员工","C1"=>"上班时间","D1"=>"下班时间","E1"=>"上班时间[分钟]", "F1"=>"下班时间[分钟]","G1"=>"工时","H1"=>"备注"];
    $excel_data = [];
    foreach ($staffs as $staff_k => $staff_v) {
        foreach ($staff_v as $clock_date => $clock_v) {
            $excel_data[] = [$clock_date, $staff_k, $clock_v["st"], $clock_v["et"], $clock_v[0], $clock_v[1], $clock_v["hours"], $clock_v["memo"]];
        }
        $excel_data[] = ["汇总", $staff_k, "--", "--", "--", "--", array_sum(array_column($staff_v, "hours")), ""];
    }
    saveLocalExcel($arr_title, $excel_data, "$filename-工时计算");

}