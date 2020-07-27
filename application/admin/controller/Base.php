<?php

namespace app\admin\controller;

use think\Config;
use think\Controller;
use think\Session;

/**
 * Class Base
 * @package app\admin\controller
 * 基类控制器
 */
class Base extends Controller
{
    protected $pageSize;

    public function _initialize()
    {
        // 分页
        $configPageSize = Config::get("paginate.list_rows");
        $this->pageSize = $configPageSize;

        // 系统名称
        $siteName = getSettings("site", "siteName");
        $this->assign("sysName", $siteName);
    }

    /**
     * 验证是否登录
     * @return bool
     */
    protected function isLogin()
    {
        $uid = Session::get("uid", "admin");
        if (!$uid) {
            return false;
        }
        return true;
    }

    /**
     * 根据地址ID获取省、市、区三级联动
     */
    protected function regionId($regionId, &$list, &$data)
    {
        // 下拉框
        $province = getRegionList(0);
        $city = array("" => "");
        $area = array("" => "");
        $provinceId = "";
        $cityId = "";
        $areaId = "";
        // 获取省市区下拉框
        if (!empty($regionId)) {
            $address = db('region')
                ->field("id_path")
                ->where(array("id" => $regionId))
                ->find();

            // 如果有
            if (!empty($address) && !empty($address['id_path'])) {
                $addresArr = explode(",", $address['id_path']);

                // 如果省有值，获取市下拉框
                if ($addresArr[0]) {
                    $provinceId = $addresArr[0];
                    $city = getRegionList($provinceId);
                }

                // 如果市有值，获取区下拉框
                if ($addresArr[1]) {
                    $cityId = $addresArr[1];
                    $area = getRegionList($cityId);
                }
                if ($addresArr[2]) {
                    $areaId = $addresArr[2];
                }
            }
        }

        // 选中值
        $data['provinceId'] = $provinceId;
        $data['cityId'] = $cityId;
        $data['areaId'] = $areaId;

        // 省市区下拉框
        $list['provinceList'] = $province;
        $list['cityList'] = $city;
        $list['areaList'] = $area;
    }
    public function exportExcel($expTitle, $expCellName, $expTableData,$excelName)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); //文件名称
        $fileName = $excelName . date('_YmdHis'); //or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new\PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O');
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(22);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);
//        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');
//合并单元格
//        $objPHPExcel->getActiveSheet()->setCellValue('A1', $excelName)->getStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 2), " " . $expTableData[$i][$expCellName[$j][0]]);
            }
        }
        ob_end_clean(); //清除缓冲区,避免乱码
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}
