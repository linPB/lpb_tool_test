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

/**
 * 对Spreadsheet方法封装
 * @param array $arr 该数组必须为键值对，键是表格单元，值是单元格的值
 * @param array $data 该数组如果为一维数组，则填写一行，如果为二维数组，则多行数据
 * @param string $name 下载Excel的文件名，可忽略
 * @param string $type 选择文件类型，如果不填写，默认下载为xlsx格式，如果任意填写数值为xls格式
 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
 */
function downloadExcel(array $arr, array $data, string $name="", string $type="Xlsx"){
    //文件名处置
    if(empty($name)){
        $name=time();
    }else{
        $name = $name."_".time();
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
    $writer->save("php://output");//数据流
}

function saveLocalExcel(array $arr, array $data, string $name="", string $type="Xlsx")
{
    //文件名处置
    if(empty($name)){
        $name=time();
    }else{
        $name = $name."_".time();
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
    $writer->save("storage/$name.$type");
}

function readLocalExcel($name): array
{
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($name);
    return $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
}

function calculateAttendanceForZhiJie($filename)
{
    $rows = readLocalExcel($filename);

    $staffs = [];
    foreach ($rows as $k => $row) {

        if ($k < 5) {
            continue;
        }

        # 初始化员工
        if (!isset($staffs[$row["B"]])) {
            $staffs[$row["B"]] = [];
        }

        # 初始化员工下的日期
        if (!isset($staffs[$row["B"]][$row["A"]])) {
            $staffs[$row["B"]][$row["A"]] = [];
        }

        if (false !== strstr($row["I"], "未打卡")) {
            continue;
        }

        if ( count($staffs[$row["B"]][$row["A"]]) == 2 ) {
            $staffs[$row["B"]][$row["A"]][1] = $row["I"];
        } else {
            $staffs[$row["B"]][$row["A"]][] = $row["I"];
        }
    }

    foreach ( $staffs as $staff_name => $date_nums ) {

        foreach ($date_nums as $date => $clock_list) {

            if (empty($clock_list)) {
                $staffs[$staff_name][$date]["memo"] = "一次打卡都没";
                $staffs[$staff_name][$date]["hours"] = 0;
                continue;
            }

            # 只有一次打卡
            if (count($clock_list) == 1) {
                $staffs[$staff_name][$date]["memo"] = "一次打卡";
                $staffs[$staff_name][$date]["hours"] = 0;
                continue;
            }

            # 两次都是次日打卡
            if (false !== strstr($clock_list[0], "次日") && false !== strstr($clock_list[1], "次日")) {
                $staffs[$staff_name][$date]["memo"] = "两个次日";
                $staffs[$staff_name][$date]["hours"] = 0;
                continue;
            }

            $first = explode(":", $clock_list[0]);#上班打卡
            list($first_h, $first_m) = $first;
            if ($first_h < 9) {
                $st = 9 * 60;
            } else {
                $st = $first_h * 60 + $first_m;
            }

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
                    $invalid_time = 0;
                } elseif ( 12*60 <= $et && $et <= 14*60 ) {
                    $invalid_time = round(($et-12*60)/60, 4);
                } elseif ( 14*60 <= $et && $et <= 18*60 ) {
                    $invalid_time = 2;
                } elseif ( 18*60 <= $et && $et <= 19*60 ) {
                    $invalid_time = round(($et-18*60)/60, 4) - 2;
                } else {
                    $invalid_time = 3;
                }
            } elseif ( 12*60 <= $st && $st <= 14*60 ) {
                if ( 12*60 <= $et && $et <= 14*60 ) {
                    $invalid_time = round(($et-$st)/60, 4);
                } elseif ( 14*60 <= $et && $et <= 18*60 ) {
                    $invalid_time = round(($st-12*60)/60, 4);
                } elseif ( 18*60 <= $et && $et <= 19*60 ) {
                    $invalid_time = round(($st-12*60)/60, 4) + round(($et-18*60)/60, 4);
                } else {
                    $invalid_time = round(($st-12*60)/60, 4) + 1;
                }
            } elseif ( 14*60 <= $st && $st <= 18*60 ) {
                if ( 14*60 <= $et && $et <= 18*60 ) {
                    $invalid_time = 0;
                } elseif ( 18*60 <= $et && $et <= 19*60 ) {
                    $invalid_time = round(($et-18*60)/60, 4);
                } else {
                    $invalid_time = 1;
                }
            } elseif ( 18*60 <= $st && $st <= 19*60 ) {
                if ( 18*60 <= $et && $et <= 19*60 ) {
                    $invalid_time = round(($et - $st)/60, 4);
                } else {
                    $invalid_time = round(($et - 18*60)/60, 4);
                }
            } else {
                $invalid_time = 0;
            }

            $staffs[$staff_name][$date]["memo"] = "正常";
            $staffs[$staff_name][$date]["hours"] = round(($et-$st)/60, 4) - $invalid_time;
        }
    }
}