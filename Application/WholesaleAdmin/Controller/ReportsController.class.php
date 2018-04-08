<?php
// +----------------------------------------------------------------------
// | 报表
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class ReportsController extends BaseController
{
    public function daySaleDetails()
    {
        $wid = getWid();
        if(IS_POST){
           $where =" where 1=1 and a.`status` = 1 and d.wid =$wid";
           $create_time =I("create_time");$brand_name =I("brand_name");
           $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id"); $hid_order=I("hid_order");
           $gname=I("gname");$ctid=I("ctid");$machining=I("machining");$gtid2=I("gtid2");
           $this->assign("create_time",$create_time); $this->assign("brand_name",$brand_name);
           $this->assign("hid_order",$hid_order);$this->assign("ctid",$ctid);$this->assign("c_id",$c_id);
           $this->assign("gtid",$gtid);$this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);
           $this->assign("gname",$gname);$this->assign("c_id",$c_id); $this->assign("machining",$machining); 
           $orderArr =array();
           if($hid_order)
               $orderArr = explode("|",$hid_order);
           // dump($orderArr);
           if($create_time){
                $where .=" and from_unixtime(a.create_time,'%Y-%m-%d') = '".$create_time."'";
           }else{
                $this->display();
                exit();
           }
           if($brand_name){
                $where .=" and g.brand_name = '".$brand_name."'";
           }
           if($gtid){
                $where .=" and h.gtid = $gtid";
           }
           if($gtid1){
                $where .=" and hh.gtid = $gtid1";
           }
           if($gtid2){
                $where .=" and hhh.gtid = $gtid2";
           }
           if($c_id){
                $where .=" and c.c_id = $c_id";
           }
           if($ctid){
                $where .=" and c.ctid = $ctid";
           }
           if($gname){
                $where .=" and e.name like '%".$gname."%'";
           } 
           if($machining!=""){
                $where .=" and b.machining = $machining";
           }
           $sql ="select * from (select g.brand_name,f.unit_name,e.`name` gname,cc.type_name ctname,c.`name` cname,b.*,a.create_time,h.type_name,hh.type_name type_name1,hhh.type_name type_name2
                from db_out_stock a 
                left join db_out_stock_detail b on a.osid = b.osid
                left join db_client c on a.cid=c.c_id
                left join db_client_type cc on c.ctid = cc.ctid
                left join db_wholesale_goods d on b.wgid = d.wgid
                left join db_goods e on d.gid = e.gid
                left join db_unit f on b.unit_id1 = f.unit_id
                left join db_brand g on e.brand_id = g.brand_id 
                left join db_goods_type h on d.gtid =h.gtid
                left join db_goods_type hh on d.gtid1 =hh.gtid
                left join db_goods_type hhh on d.gtid2 = hhh.gtid"; 
            $sql = $sql.$where." ) a ";
            $order =" order by a.gname asc";  
            if($orderArr[0]!=""){  
                if($orderArr[0]=="gname"){
                    $order=" order by a.gname ".$orderArr[1]; 
                }
                if($orderArr[0]=="bname")
                    $order=" order by a.brand_name ".$orderArr[1];
                if($orderArr[0]=="gtname")
                    $order=" order by a.type_name2,a.type_name1,a.type_name ".$orderArr[1];
                if($orderArr[0]=="cname")
                    $order=" order by a.cname ".$orderArr[1];
            } 
            $sql = $sql.$order;   
            $list= M("")->query($sql);
            // dump($list);
            $this->assign("list",$list); 
            
        }else{
            $this->assign("create_time",date('Y-m-d'));
        }
        // $cm = M("client"); 
        // $clist = $cm->field("c_id,`name` cname")->where("create_id = $wid")->select();
        // $this->assign("clist",$clist);
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid =$wid")->select();
        $this->assign("tlist",$tlist);
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $dsdcList = M("dsdc")->where("wid = $wid")->find();
        if(!$dsdcList){
            $dsdcList = getDaySaleColumn();
        }
        $titles = $this->getDaySaleTitleInfo($wid);
        $this->assign("titles",$titles);
        $this->assign("dsdcList",$dsdcList);
        // dump($titles);dump($dsdcList);
        $this->display();
    }

    //根据客户类型获取客户信息
    public function getCilentInfo(){
        if(IS_POST){
            $ctid = I("ctid");
            $result = M("client")->field("c_id,`name` cname")->where("ctid=$ctid")->select();
            if($result){
                $this->ajaxReturn(ReturnJSON(0,$result));
            }else{
                $this->ajaxReturn(ReturnJSON(1,$result));
            }
        }else{
           $this->ajaxReturn(ReturnJSON(7,$result)); 
        }
    }

    //导出销售汇总
    public function exportTotalSale(){ 
        $stime =I("stime");$etime=I("etime");$gtid=I("gtid");$gtid1=I("gtid1");$gtid2=I("gtid2");$c_id=I("c_id");
        $gname = I("gname");$brand_name = I("brand_name"); $machining = I("machining");$wid = getWid();
        $where =" where aa.create_id= $wid and aa.`status` = 1 ";
        if($stime){
            $stime =strtotime($stime);
            $where .=" and aa.create_time>=$stime";
        }
        if($etime){
            $etime =strtotime($etime);
            $where .=" and aa.create_time<=$etime";
        } 
        if($gname){
            $where .=" and c.`name` like '%".$gname."%'";
        }
        if($brand_name!=''){
            $where .=" and e.brand_name like '%".$brand_name."%'";
        } 
        if($gtid!=''){
            $where .=" and f.gtid = $gtid";
        } 
        if($gtid1!=''){
            $where .=" and ff.gtid = $gtid1";
        } 
        if($gtid2!=''){
            $where .=" and fff.gtid = $gtid2";
        } 
        if($c_id!=''){
            $where .=" and g.c_id = $c_id";
        } 
        if($machining!=''){
            $where .=" and a.machining = $machining";
        }
        $sql ="select a.alias,c.`name` gname,d.unit_name,e.brand_name,f.type_name,ff.type_name type_name1,fff.type_name type_name2,SUM(a.num1) as num,SUM(a.cd_num) as cd_num
            from db_out_stock_detail a 
            left join db_out_stock aa on a.osid = aa.osid
            left join db_wholesale_goods b on a.wgid = b.wgid
            left join db_goods c on b.gid=c.gid
            left join db_unit d on d.unit_id = a.unit_id1
            left join db_brand e on c.brand_id = e.brand_id
            left join db_goods_type f on b.gtid = f.gtid
            left join db_goods_type ff on b.gtid1 = ff.gtid
            left join db_goods_type fff on b.gtid2 = fff.gtid
            left join db_client g on g.c_id = aa.cid ";
        $group = " group by c.`name`,d.unit_name,e.brand_name,f.type_name,ff.type_name,fff.type_name";
        $order = " order by num desc";
        $sql = $sql.$where.$group.$order;  
        // dump($sql);
        $list =M("")->query($sql);

        Vendor('PHPExcel.PHPExcel');    //引入phpexcel类
        $objPHPExcel = new \PHPExcel();   //实例化phpexcel类
        $objSheet = $objPHPExcel->getActiveSheet(); //获得当前sheet 
        //设置当前的sheet
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10); 
        
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $wid = getWid(); 
        $rdcList = M("rdc")->where("wid = $wid")->find();
        if(!$rdcList){
            $rdcList = getReportsColumn();
        }   
        $titles = $this->getReportsTitleInfo($wid); 
        $tcaption=I("tcaption");
        $objSheet->setCellValue("A1",$tcaption)->mergeCells('A1:H1'); 
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);//设置行高
        $objSheet->setCellValue("A2",$titles['gtname'])
                    ->setCellValue("B2",$titles['gname'])
                    ->setCellValue("C2",$titles['alias'])
                    ->setCellValue("D2",$titles['bname'])
                    ->setCellValue("E2",$titles['uname'])
                    ->setCellValue("F2",$titles['num'])
                    ->setCellValue("G2",$titles['cd_num'])
                    ->setCellValue("H2",$titles['snum']);
                    // ->mergeCells('D1:F1');  //合并单元格 
        $arr =array("A1","B1","C1","D1","E1","F1","G1","H1","A2","B2","C2","D2","E2","F2","G2","H2");  
        //内容部分
        $n = 3;
        foreach ($list as $k => $vo) { 
            $tname =$vo['type_name'];
            if($vo['type_name1']){
                $tname.=">".$vo['type_name1'];
            }
            if($vo['type_name2']){
                $tname.=">".$vo['type_name2'];
            }
            $objSheet->setCellValue("A".$n,$tname)
                    ->setCellValue("B".$n, $vo['gname'])
                    ->setCellValue("C".$n, $vo['alias'])
                    ->setCellValue("D".$n, $vo['brand_name'])
                    ->setCellValue("E".$n, $vo['unit_name']) 
                    ->setCellValue("F".$n, $vo['num'])
                    ->setCellValue("G".$n, $vo['cd_num'])
                    ->setCellValue("H".$n, $vo['num']+$vo['cd_num']);
 
            array_push($arr,"A".$n,"B".$n,"C".$n,"D".$n,"E".$n,"F".$n,"G".$n,"H".$n);
            $n++;
        }
        $this->setExcelBorder($objPHPExcel,$arr);
                    
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");  //Excel2007生成后缀.xlsx 
        $filename="销售汇总".time();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header("Content-Disposition:attachment;filename='$filename.xlsx'");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
        
    }
    //导出日销售报表
    public function exportDaySaleDetails(){
        $wid = getWid(); 
        $where =" where 1=1 and a.`status` = 1 and d.wid =$wid";
       $create_time =I("create_time");$brand_name =I("brand_name");
       $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id"); $hid_order=I("hid_order");
       $gname=I("gname");$ctid=I("ctid");$machining=I("machining");$gtid2=I("gtid2");
       $this->assign("create_time",$create_time); $this->assign("brand_name",$brand_name);
       $this->assign("hid_order",$hid_order);$this->assign("ctid",$ctid);$this->assign("c_id",$c_id);
       $this->assign("gtid",$gtid);$this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);
       $this->assign("gname",$gname);$this->assign("c_id",$c_id); $this->assign("machining",$machining); 
       $orderArr =array();
       if($hid_order)
           $orderArr = explode("|",$hid_order);
       // dump($orderArr);
       if($create_time){
            $where .=" and from_unixtime(a.create_time,'%Y-%m-%d') = '".$create_time."'";
       }else{
            $this->display();
            exit();
       }
       if($brand_name){
            $where .=" and g.brand_name = '".$brand_name."'";
       }
       if($gtid){
            $where .=" and h.gtid = $gtid";
       }
       if($gtid1){
            $where .=" and hh.gtid = $gtid1";
       }
       if($gtid2){
            $where .=" and hhh.gtid = $gtid2";
       }
       if($c_id){
            $where .=" and c.c_id = $c_id";
       }
       if($ctid){
            $where .=" and c.ctid = $ctid";
       }
       if($gname){
            $where .=" and e.name like '%".$gname."%'";
       } 
       if($machining!=""){
            $where .=" and b.machining = $machining";
       }
       $sql ="select * from (select g.brand_name,f.unit_name,e.`name` gname,cc.type_name ctname,c.`name` cname,b.*,a.create_time,h.type_name,hh.type_name type_name1,hhh.type_name type_name2
            from db_out_stock a 
            left join db_out_stock_detail b on a.osid = b.osid
            left join db_client c on a.cid=c.c_id
            left join db_client_type cc on c.ctid = cc.ctid
            left join db_wholesale_goods d on b.wgid = d.wgid
            left join db_goods e on d.gid = e.gid
            left join db_unit f on b.unit_id1 = f.unit_id
            left join db_brand g on e.brand_id = g.brand_id 
            left join db_goods_type h on d.gtid =h.gtid
            left join db_goods_type hh on d.gtid1 =hh.gtid
            left join db_goods_type hhh on d.gtid2 = hhh.gtid"; 
        $sql = $sql.$where." ) a ";
        $order =" order by a.gname asc";  
        if($orderArr[0]!=""){  
            if($orderArr[0]=="gname"){
                $order=" order by a.gname ".$orderArr[1]; 
            }
            if($orderArr[0]=="bname")
                $order=" order by a.brand_name ".$orderArr[1];
            if($orderArr[0]=="gtname")
                $order=" order by a.type_name2,a.type_name1,a.type_name ".$orderArr[1];
            if($orderArr[0]=="cname")
                $order=" order by a.cname ".$orderArr[1];
        } 
        $sql = $sql.$order;   
        $list= M("")->query($sql);
        // dump($list);
        $this->assign("list",$list);

        Vendor('PHPExcel.PHPExcel');    //引入phpexcel类
        $objPHPExcel = new \PHPExcel();   //实例化phpexcel类
        $objSheet = $objPHPExcel->getActiveSheet(); //获得当前sheet 
        //设置当前的sheet
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(19); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20); 
        
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $wid = getWid(); 
        $dsdcList = M("dsdc")->where("wid = $wid")->find();
        if(!$dsdcList){
            $dsdcList = getDaySaleColumn();
        }
        $tcaption = I("tcaption");
        $titles = $this->getDaySaleTitleInfo($wid);
        $objSheet->setCellValue("A1",$tcaption)->mergeCells('A1:K1'); 
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);//设置行高
        $objSheet->setCellValue("A2",$titles['gname'])
                    ->setCellValue("B2",$titles['alias'])
                    ->setCellValue("C2",$titles['bname'])
                    ->setCellValue("D2",$titles['uname'])
                    ->setCellValue("E2",$titles['num'])
                    ->setCellValue("F2",$titles['than'])
                    ->setCellValue("G2",$titles['price'])
                    ->setCellValue("H2",$titles['cd_num'])
                    ->setCellValue("I2",$titles['total'])
                    ->setCellValue("J2",$titles['cname'])
                    ->setCellValue("K2",$titles['remark']);
                    // ->mergeCells('D1:F1');  //合并单元格 
        $arr =array("A1","B1","C1","D1","E1","F1","G1","H1","I1","J1","K1","A2","B2","C2","D2","E2","F2","G2","H2","I2","J2","K2");  
        //内容部分
        $n = 3;
        foreach ($list as $k => $vo) {   
            $objSheet->setCellValue("A".$n,$vo['gname'])
                    ->setCellValue("B".$n, $vo['alias'])
                    ->setCellValue("C".$n, $vo['brand_name'])
                    ->setCellValue("D".$n, $vo['unit_name'])
                    ->setCellValue("E".$n, $vo['num1']) 
                    ->setCellValue("F".$n, $vo['than'])
                    ->setCellValue("G".$n, $vo['price'])
                    ->setCellValue("H".$n, $vo['cd_num'])
                    ->setCellValue("I".$n, $vo['sales_amount'])
                    ->setCellValue("J".$n, $vo['cname'])
                    ->setCellValue("K".$n, $vo['remark']); 
            array_push($arr,"A".$n,"B".$n,"C".$n,"D".$n,"E".$n,"F".$n,"G".$n,"H".$n,"I".$n,"J".$n,"K".$n);
            $n++;
        }
        $this->setExcelBorder($objPHPExcel,$arr);
                    
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");  //Excel2007生成后缀.xlsx 
        $filename="日销售明细".time();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header("Content-Disposition:attachment;filename='$filename.xlsx'");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit(); 
    }

    //财务分析
    public function exportFinanceAnaly(){ 
        $stime =I("stime");$etime=I("etime");$c_id=I("c_id");$tcaption = I("tcaption");
        $gtid=I("gtid");$gtid1=I("gtid1");$gtid2=I("gtid2"); $ctid = I("ctid");
        $gname = I("gname");$brand_name = I("brand_name");
        $wid =getWid();
        $where ="";
        if($stime){
            $stime =strtotime($stime);
            $where .=" and b.ktime>=$stime";
        }
        if($etime){
            $etime =strtotime($etime); 
            $where .=" and b.ktime<=$etime";
        } 
        if($gname){
            $where .=" and c.`name` like '%".$gname."%'";
        }
        if($brand_name!=''){
            $where .=" and e.brand_name like '%".$brand_name."%'";
        } 
        if($gtid!=''){
            $where .=" and a.gtid = $gtid";
        } 
        if($gtid1!=''){
            $where .=" and a.gtid1 = $gtid1";
        } 
        if($gtid2!=''){
            $where .=" and a.gtid2 = $gtid2";
        } 
        if($c_id!=''){
            $where .=" and f.c_id = $c_id";
        } 
        if($ctid!=''){
            $where .=" and f.ctid = $ctid";
        }   
        $sql ="select a.gname,a.unit_name,SUM(a.num) num,SUM(a.cd_num) cd_num,SUM(a.total_amount) total_amount,SUM(cost) cost
                                 FROM (SELECT b.owe_id,b.wgid,b.num,bb.cd_num,b.price,b.num*b.price total_amount,b.ktime,
                                c.`name` gname,d.unit_name,e.brand_name,cc.type_name,bbb.cid c_id,IFNULL(g.cost,0) AS cost
                                FROM db_wholesale_goods a 
                                LEFT JOIN db_owe_sales b ON a.wgid = b.wgid
                                LEFT JOIN db_out_stock_detail bb ON b.out_id = bb.out_id
                                LEFT JOIN db_out_stock bbb ON bbb.osid = bb.osid
                                LEFT JOIN db_goods c ON a.gid = c.gid
                                LEFT JOIN db_goods_type cc ON a.gtid = cc.gtid
                                LEFT JOIN db_unit d ON a.unit_id = d.unit_id
                                LEFT JOIN db_brand e ON e.brand_id = c.brand_id 
                                LEFT JOIN db_client f ON f.c_id = bbb.cid  
                                LEFT JOIN (SELECT a.owe_id,SUM(a.num*b.price) cost FROM db_owe_sales_detail a 
                                LEFT JOIN db_join_stock_detail b ON a.join_id = b.join_id
                                GROUP BY a.owe_id) g ON b.owe_id = g.owe_id
                                WHERE a.wid = $wid and ktime IS NOT NULL ".$where.") a GROUP BY a.gname,a.unit_name;";
        $list =M()->query($sql);   

        Vendor('PHPExcel.PHPExcel');    //引入phpexcel类
        $objPHPExcel = new \PHPExcel();   //实例化phpexcel类
        $objSheet = $objPHPExcel->getActiveSheet(); //获得当前sheet 
        //设置当前的sheet
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息 
        $finList = M("fin")->where("wid = $wid")->find();
        if(!$finList){
            $finList = getFinanceColumn();
        }
        $titles = $this->getFinanceTitleInfo($wid); 
        $objSheet->setCellValue("A1",$tcaption)->mergeCells('A1:H1'); 
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);//设置行高
        $objSheet->setCellValue("A2",$titles['gname'])
                    ->setCellValue("B2",$titles['uname'])
                    ->setCellValue("C2",$titles['num'])
                    ->setCellValue("D2",$titles['cd_num'])
                    ->setCellValue("E2",$titles['cd_cost'])
                    ->setCellValue("F2",$titles['profit'])
                    ->setCellValue("G2",$titles['cost'])
                    ->setCellValue("H2",$titles['rate']);
                    // ->mergeCells('D1:F1');  //合并单元格 
        $arr =array("A1","B1","C1","D1","E1","F1","G1","H1","A2","B2","C2","D2","E2","F2","G2","H2");  
        //内容部分 小计毛利 小计销售成本 小计销售毛利率
        $n = 3; $ml=0;$xscb=0;$xsmll;$xscdnum=0;$xscdcost=0;
        foreach ($list as $k => $vo) {  
            $objSheet->setCellValue("A".$n,$vo['gname'])
                    ->setCellValue("B".$n, $vo['unit_name'])
                    ->setCellValue("C".$n, $vo['num'])
                    ->setCellValue("D".$n, $vo['cd_num'])
                    ->setCellValue("E".$n, keep4(($vo['cost']/$vo['num'])*$vo['cd_num']))
                    ->setCellValue("F".$n, keep4($vo['total_amount'])) 
                    ->setCellValue("G".$n, keep4($vo['cost']))
                    ->setCellValue("H".$n, keep4((($vo['total_amount']-$vo['cost'])/$vo['total_amount'])*100).'%');
            $ml=$ml+ $vo['total_amount'];
            $xscb = $xscb + $vo['cost'];
            $xscdnum = $xscdnum + $vo['cd_num'];
            $xscdcost = $xscdcost + ($vo['cost']/$vo['num'])*$vo['cd_num'];
            array_push($arr,"A".$n,"B".$n,"C".$n,"D".$n,"E".$n,"F".$n,"G".$n,"H".$n);
            $n++;
        }
        $objSheet->setCellValue("A".$n,"小计")->mergeCells('A'.$n.':C'.$n);
        $objSheet->setCellValue("D".$n,$ml)
                ->setCellValue("E".$n,$xscdnum)
                ->setCellValue("F".$n,keep4($xscdcost))
                ->setCellValue("G".$n,$xscb)
                ->setCellValue("H".$n,keep4((($ml-$xscb)/$ml)*100).'%');
        array_push($arr,"A".$n,"B".$n,"C".$n,"D".$n,"E".$n,"F".$n,"G".$n,"H".$n);
        $this->setExcelBorder($objPHPExcel,$arr);
                    
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");  //Excel2007生成后缀.xlsx 
        $filename="财务分析".time();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header("Content-Disposition:attachment;filename='$filename.xlsx'");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit(); 
    }

    //设置边框和对齐方式 
    private function setExcelBorder($objPHPExcel,$arr=array()){
        foreach ($arr as $k => $vo) {
            $objPHPExcel->getActiveSheet()->getStyle("$vo")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER); 
            $objPHPExcel->getActiveSheet()->getStyle("$vo")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle("$vo")->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle("$vo")->getBorders()->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getStyle("$vo")->getBorders()->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getStyle("$vo")->getBorders()->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getStyle("$vo")->getBorders()->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        }
    } 

    public function totalSalePrint(){
        $stime =I("stime");$etime=I("etime");$gtid=I("gtid");$gtid1=I("gtid1");$gtid2=I("gtid2");$c_id=I("c_id");
        $gname = I("gname");$brand_name = I("brand_name"); $machining = I("machining"); $tcaption =I("tcaption");
        $this->assign("stime",$stime); $this->assign("etime",$etime); $this->assign("gtid",$gtid);
        $this->assign("c_id",$c_id);$this->assign("gname",$gname);$this->assign("brand_name",$brand_name);
        $this->assign("machining",$machining); $this->assign("gtid1",$gtid1); $this->assign("gtid2",$gtid2);
        $wid = getWid();$this->assign("tcaption",$tcaption);
        $where =" where aa.create_id= $wid and aa.`status` = 1 ";
        if($stime){
            $stime =strtotime($stime);
            $where .=" and aa.create_time>=$stime";
        }
        if($etime){
            $etime =strtotime($etime);
            $where .=" and aa.create_time<=$etime+86399";
        } 
        if($gname){
            $where .=" and c.`name` like '%".$gname."%'";
        }
        if($brand_name!=''){
            $where .=" and e.brand_name like '%".$brand_name."%'";
        } 
        if($gtid!=''){
            $where .=" and f.gtid = $gtid";
        } 
        if($gtid1!=''){
            $where .=" and ff.gtid = $gtid1";
        } 
        if($gtid2!=''){
            $where .=" and fff.gtid = $gtid2";
        } 
        if($c_id!=''){
            $where .=" and g.c_id = $c_id";
        } 
        if($machining!=''){
            $where .=" and a.machining = $machining";
        }
        $sql ="select a.alias,c.`name` gname,d.unit_name,e.brand_name,f.type_name,ff.type_name type_name1,fff.type_name type_name2,SUM(a.num1) as num,SUM(a.cd_num) as cd_num
            from db_out_stock_detail a 
            left join db_out_stock aa on a.osid = aa.osid
            left join db_wholesale_goods b on a.wgid = b.wgid
            left join db_goods c on b.gid=c.gid
            left join db_unit d on d.unit_id = a.unit_id1
            left join db_brand e on c.brand_id = e.brand_id
            left join db_goods_type f on b.gtid  = f.gtid
            left join db_goods_type ff on b.gtid1 = ff.gtid
            left join db_goods_type fff on b.gtid2 = fff.gtid
            left join db_client g on g.c_id = aa.cid ";
        $group = " group by c.`name`,d.unit_name,e.brand_name,f.type_name,ff.type_name,fff.type_name";
        $order = " order by num desc";
        $sql = $sql.$where.$group.$order;   
        $list =M("")->query($sql); 
        $this->assign("list",$list); 
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $rdcList = M("rdc")->where("wid = $wid")->find();
        if(!$rdcList){
            $rdcList = getReportsColumn();
        }   
        $titles = $this->getReportsTitleInfo($wid); 
        $this->assign("titles",$titles);
        $this->assign("rdcList",$rdcList); 
        $this->display();
    }

    public function daySalePrint(){
       $wid = getWid(); 
       $where =" where 1=1 and a.`status` = 1 and d.wid =$wid";
       $create_time =I("create_time");$brand_name =I("brand_name");
       $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id"); $hid_order=I("hid_order");
       $gname=I("gname");$ctid=I("ctid");$machining=I("machining");$gtid2=I("gtid2");$tcaption=I("tcaption");
       $this->assign("create_time",$create_time); $this->assign("brand_name",$brand_name);
       $this->assign("tcaption",$tcaption);$this->assign("c_id",$c_id);
       $this->assign("hid_order",$hid_order);$this->assign("ctid",$ctid);
       $this->assign("gtid",$gtid);$this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);
       $this->assign("gname",$gname);$this->assign("c_id",$c_id); $this->assign("machining",$machining); 
       $orderArr =array();
       if($hid_order)
           $orderArr = explode("|",$hid_order);
       // dump($orderArr);
       if($create_time){
            $where .=" and from_unixtime(a.create_time,'%Y-%m-%d') = '".$create_time."'";
       }else{
            $this->display();
            exit();
       }
       if($brand_name){
            $where .=" and g.brand_name = '".$brand_name."'";
       }
       if($gtid){
            $where .=" and h.gtid = $gtid";
       }
       if($gtid1){
            $where .=" and hh.gtid = $gtid1";
       }
       if($gtid2){
            $where .=" and hhh.gtid = $gtid2";
       }
       if($c_id){
            $where .=" and c.c_id = $c_id";
       }
       if($ctid){
            $where .=" and c.ctid = $ctid";
       }
       if($gname){
            $where .=" and e.name like '%".$gname."%'";
       } 
       if($machining!=""){
            $where .=" and b.machining = $machining";
       }
       $sql ="select * from (select g.brand_name,f.unit_name,e.`name` gname,cc.type_name ctname,c.`name` cname,b.*,a.create_time,h.type_name,hh.type_name type_name1,hhh.type_name type_name2
            from db_out_stock a 
            left join db_out_stock_detail b on a.osid = b.osid
            left join db_client c on a.cid=c.c_id
            left join db_client_type cc on c.ctid = cc.ctid
            left join db_wholesale_goods d on b.wgid = d.wgid
            left join db_goods e on d.gid = e.gid
            left join db_unit f on b.unit_id1 = f.unit_id
            left join db_brand g on e.brand_id = g.brand_id 
            left join db_goods_type h on d.gtid =h.gtid
            left join db_goods_type hh on d.gtid1 =hh.gtid
            left join db_goods_type hhh on d.gtid2 = hhh.gtid"; 
        $sql = $sql.$where." ) a ";
        $order =" order by a.gname asc";  
        if($orderArr[0]!=""){  
            if($orderArr[0]=="gname"){
                $order=" order by a.gname ".$orderArr[1]; 
            }
            if($orderArr[0]=="bname")
                $order=" order by a.brand_name ".$orderArr[1];
            if($orderArr[0]=="gtname")
                $order=" order by a.type_name2,a.type_name1,a.type_name ".$orderArr[1];
            if($orderArr[0]=="cname")
                $order=" order by a.cname ".$orderArr[1];
        } 
        $sql = $sql.$order;   
        $list= M("")->query($sql);
        // dump($list);
        $this->assign("list",$list); 
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $dsdcList = M("dsdc")->where("wid = $wid")->find();
        if(!$dsdcList){
            $dsdcList = getDaySaleColumn();
        }
        $titles = $this->getDaySaleTitleInfo($wid);
        $this->assign("titles",$titles);
        $this->assign("dsdcList",$dsdcList); 
        $this->display();
    }

    //客户销售汇总
    public function clientTotalSale(){
        $wid =getWid();
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist);
        $stime=date('Y-m-01', strtotime(date("Y-m-d"))); 
        $etime= date('Y-m-d', strtotime("$stime +1 month -1 day"));  
        $this->assign("stime",$stime);  
        $this->assign("etime",$etime);
        $this->display();
    }

    //获取客户销售汇总信息
    public function getClientTotalSale(){
        if(IS_POST){
            $wid = getWid();
            $stime=I("stime");$etime=I("etime");$ctid=I("ctid");$c_id=I("c_id"); 
            $where =" where c.wid = $wid and a.cid!=0 and a.status = 1";
            if($stime){
                $stime =strtotime($stime);
                $where .=" and a.create_time>=$stime";
            }
            if($etime){
                $etime =strtotime($etime);
                $where .=" and a.create_time<=$etime+86399";
            } 
            if($c_id!=''){
                $where .=" and e.c_id = $c_id";
            } 
            if($ctid!=''){
                $where .=" and f.ctid = $ctid";
            } 
            $sql ="select f.type_name tname,e.`name` cname,SUM(b.price*b.num1) price,SUM(b.price*b.num1 * b.than) tax_price
                from db_out_stock a 
                left join db_out_stock_detail b on a.osid = b.osid 
                left join db_wholesale_goods c on b.wgid = c.wgid
                left join db_client e on a.cid = e.c_id 
                left join db_client_type f on e.ctid = f.ctid";     
            $group =" group by tname,cname ";
            $order =" order by tname,cname asc";
            $sql .=$where.$group.$order;
            // $this->ajaxReturn($sql);
            $result = M()->query($sql); 
            /*-----------系统参数获取-------------*/
            $system_param=M("system_param")->where("wid=$wid")->find();
            if(empty($system_param)){
                $system_param =getSystemParam();
            } 
            //处理字段  
            $sum_price =0;$sum_tax_price=0;$sum_mprice=0;
            foreach ($result as $k => $v) {
                //处理数量 
                $sum_price+=$result[$k]['price'];
                $sum_tax_price+=$result[$k]['tax_price']; 
                $result[$k]['price'] = customDecimal('sum',$v['price']);  
                $result[$k]['tax_price'] = customDecimal('sum',$v['tax_price']);  
                $result[$k]['mprice'] = customDecimal('sum',$result[$k]['tax_price'] - $result[$k]['price']); 
            } 
            if(empty($result)){ 
                $this->ajaxReturn(ReturnJSON(1));
            }else{
                $list =array();
                $list['sum']=array();
                $list['sum']['sum_price']=customDecimal("sum",$sum_price);
                $list['sum']['sum_tax_price']=customDecimal("sum",$sum_tax_price);
                $list['sum']['sum_mprice']=customDecimal("sum",$sum_tax_price-$sum_price);
                $list['result']=$result;
                $this->ajaxReturn(ReturnJSON(0,$list));
            }
        }
    }

    public function clientTotalSaleDetail()
    {
        if(IS_POST){
            $stime =I("stime");$etime=I("etime");$gtid=I("gtid");$gtid1=I("gtid1");$gtid2=I("gtid2");
            $gname = I("gname");$brand_name = I("brand_name"); $machining = I("machining");
            $c_id=I("c_id");$ctid = I("ctid");$cgpid = I("cgpid");
            $this->assign("stime",$stime); $this->assign("etime",$etime); $this->assign("gtid",$gtid);
            $this->assign("c_id",$c_id);$this->assign("ctid",$ctid); $this->assign("cgpid",$cgpid);
            $this->assign("gname",$gname);$this->assign("brand_name",$brand_name);
            $this->assign("machining",$machining); $this->assign("gtid1",$gtid1); $this->assign("gtid2",$gtid2);
            $wid = getWid();
            $where =" where aa.create_id= $wid and aa.`status` = 1 and aa.cid !=0 ";
            $where1 =" where 1=1 "; 
            if($stime){
                $stime =strtotime($stime);
                $where .=" and aa.create_time>=$stime";
                $where1 .=" and a.stime>$stime";
            }
            if($etime){
                $etime =strtotime($etime);
                $where .=" and aa.create_time<=$etime+86399";
                $where1 .=" and a.etime>$etime+86399";
            } 
            if($gname){
                $where .=" and c.`name` like '%".$gname."%'";
            }
            if($brand_name!=''){
                $where .=" and e.brand_name like '%".$brand_name."%'";
            } 
            if($gtid!=''){
                $where .=" and f.gtid = $gtid";
            } 
            if($gtid1!=''){
                $where .=" and ff.gtid = $gtid1";
            } 
            if($gtid2!=''){
                $where .=" and fff.gtid = $gtid2";
            } 
            if($c_id!=''){
                $where .=" and g.c_id = $c_id";
            } 
            if($ctid!=''){
                $where .=" and g.ctid = $ctid";
            } 
            if($machining!=''){
                $where .=" and a.machining = $machining";
            }
            $sql ="select a.wgid,c.`name` gname,d.unit_name,e.brand_name,f.type_name,ff.type_name type_name1,fff.type_name type_name2,SUM(a.num1) as num,SUM(a.cd_num) as cd_num,SUM(a.num1 * a.price * a.than) sales_amount
                from db_out_stock_detail a 
                left join db_out_stock aa on a.osid = aa.osid
                left join db_wholesale_goods b on a.wgid = b.wgid
                left join db_goods c on b.gid=c.gid
                left join db_unit d on d.unit_id = a.unit_id1
                left join db_brand e on c.brand_id = e.brand_id
                left join db_goods_type f on b.gtid  = f.gtid
                left join db_goods_type ff on b.gtid1 = ff.gtid
                left join db_goods_type fff on b.gtid2 = fff.gtid
                left join db_client g on g.c_id = aa.cid ";
            $group = " group by a.wgid,c.`name`,d.unit_name,e.brand_name,f.type_name,ff.type_name,fff.type_name";
            $order = " order by b.gtid2,b.gtid1,b.gtid,gname asc";
            $sql = $sql.$where.$group.$order;  
            // dump($sql); 
            $list =M("")->query($sql); 
            $cgpdlist = M("contract_detail")->field("wgid,tax_price")->where("cgpid='".$cgpid."'")->select(); 
            foreach ($cgpdlist as $k => $v) {
                foreach ($list as $key => $vo) {
                    if($vo['wgid']==$v['wgid']){
                        $list[$key]['cgp_price']=$v['tax_price'];
                    }
                }
            }
            $system_param=M("system_param")->where("wid=$wid")->find();
            if(empty($system_param)){
                $system_param =getSystemParam();
            } 
            $subtotal =0; 
            $subhttotal = 0;
            //处理字段 
            foreach ($list as $k => $v) {
                //处理数量 
                $list[$k]['num'] = customDecimal('num',$v['num']); 
                $list[$k]['cd_num'] = customDecimal('num',$v['cd_num']);   
                $subtotal += floatval($list[$k]['sales_amount']); 
                $list[$k]['sales_amount'] = customDecimal('sum',$v['sales_amount']); 
                if(!empty($v['cgp_price'])){
                    $list[$k]['cgp_price'] = customDecimal('price',$v['cgp_price']); 
                    $subhttotal +=floatval($v['cgp_price']*$v['num']);
                }else{
                    $list[$k]['cgp_price']=0;
                } 
            } 
            $this->assign("subhttotal",customDecimal("sum",$subhttotal));
            $this->assign("subtotal",customDecimal("sum",$subtotal));
            $this->assign("list",$list); 
        }else{
            $stime=date('Y-m-01', strtotime(date("Y-m-d"))); 
            $etime= date('Y-m-d', strtotime("$stime +1 month -1 day"));  
            $this->assign("stime",$stime);  
            $this->assign("etime",$etime);
        } 
        $wid = getWid();
        // $cm = M("client");
        // $clist = $cm->field("c_id,`name` cname")->where("create_id = $wid")->select();
        // $this->assign("clist",$clist);
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid=$wid")->select();
        $this->assign("tlist",$tlist); 
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $rdcList = M("rdc")->where("wid = $wid")->find();
        if(!$rdcList){
            $rdcList = getReportsColumn();
        }   
        $titles = $this->getReportsTitleInfo($wid); 
        $this->assign("titles",$titles);
        $this->assign("rdcList",$rdcList);

        //获取所有合同 
        $cgplist = M("contract")->field('cgpid')->where("wid=$wid  and status = 1 and is_enable = 1")->select();
        $this->assign("cgplist",$cgplist);
//         dump($titles);dump($rdcList);
        $this->display();
    }

    //供应商销售汇总
    public function supplierTotalSale(){
        $wid =getWid();
        $stlist = M("supplier_type")->where("wid = $wid")->select();
        $this->assign("stlist",$stlist);
        $stime=date('Y-m-01', strtotime(date("Y-m-d"))); 
        $etime= date('Y-m-d', strtotime("$stime +1 month -1 day"));  
        $this->assign("stime",$stime);  
        $this->assign("etime",$etime);
        $this->display();
    }

    //根据供应商类型获取 供销商信息
    public function getSupplierInfoByStid(){
        if(IS_POST){
            $stid = I("stid");
            $result = M("supplier")->where("stid = $stid")->select();
            if(!empty($result)){
                $this->ajaxReturn(ReturnJSON(0,$result));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //获取供应商销售汇总信息
    public function getSupplierTotalSale(){
        if(IS_POST){
            $wid = getWid();
            $stime=I("stime");$etime=I("etime");$stid=I("stid");$sid=I("sid"); 
            $where ="  where a.sid != 0 and e.wid = $wid and a.`status` = 1";
            if($stime){
                $stime =strtotime($stime);
                $where .=" and a.create_time>=$stime";
            }
            if($etime){
                $etime =strtotime($etime);
                $where .=" and a.create_time<=$etime+86399";
            } 
            if($stid!=''){
                $where .=" and d.stid = $stid";
            } 
            if($sid!=''){
                $where .=" and c.sid = $sid";
            } 
            $sql ="select d.type_name tname,c.`name` sname,SUM(b.total_cost) total
                from db_join_stock a
                left join db_join_stock_detail b on a.jsid = b.jsid 
                left join db_wholesale_goods e on e.wgid = b.wgid
                left join db_supplier c on a.sid = c.sid 
                left join db_supplier_type d on c.stid = d.stid"; 
            $group =" group by tname,sname ";
            $order =" order by tname,sname asc";
            $sql .=$where.$group.$order;
            $result = M()->query($sql); 
            // $this->ajaxReturn($sql);
            if(!empty($result)){
                $system_param=M("system_param")->where("wid=$wid")->find();
                if(empty($system_param)){
                    $system_param =getSystemParam();
                } 
                //处理字段  
                $sum_total =0;
                foreach ($result as $k => $v) {
                    //处理数量 
                    $result[$k]['total'] = customDecimal('sum',$v['total']);   
                    $sum_total += $result[$k]['total'];
                } 
                $list=array();
                $list['result']=$result;
                $list['sum']=array();
                $list['sum']['sum_total'] =$sum_total;
                $this->ajaxReturn(ReturnJSON(0,$list));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //供应商销售明细
    public function supplierTotalSaleDetail(){
        $wid =getWid();
        $stlist = M("supplier_type")->where("wid = $wid")->select();
        $this->assign("stlist",$stlist);
        $stime=date('Y-m-01', strtotime(date("Y-m-d"))); 
        $etime= date('Y-m-d', strtotime("$stime +1 month -1 day"));
        $this->assign("stime",$stime);  
        $this->assign("etime",$etime);  
        $this->display();
    }

    //获取供应商销售明细信息
    public function getSupplierTotalSaleDetailInfo(){
        if(IS_POST){
            $wid =getWid();
            $stime = I("stime");$etime=I("etime");$gname=I("gname");$stid =I("stid");$sid =I("sid");
            $where =" where a.sid != 0 and c.wid =$wid and a.`status` = 1 ";
            if($stime){
                $stime =strtotime($stime);
                $where .=" and a.create_time>=$stime";
            }
            if($etime){
                $etime =strtotime($etime);
                $where .=" and a.create_time<=$etime+86399";
            } 
            if($stid!=''){
                $where .=" and f.stid = $stid";
            } 
            if($sid!=''){
                $where .=" and e.sid = $sid";
            } 
            if($gname!=''){
                $where .=" and d.`name` like '%".$gname."%'";
            } 
            $sql ="select h.type_name tname1,j.type_name tname2,l.type_name tname3,d.`name` gname,g.unit_name uname,SUM(b.num1) num,SUM(b.total_cost) total
                from db_join_stock a 
                left join db_join_stock_detail b on a.jsid = b.jsid
                left join db_wholesale_goods c on c.wgid= b.wgid
                left join db_goods d on c.gid = d.gid 
                left join db_supplier e on e.sid = a.sid 
                left join db_supplier_type f on f.stid = e.stid
                left join db_unit g on g.unit_id = b.unit_id1
                left join db_goods_type h on h.gtid = c.gtid
                left join db_goods_type j on j.gtid = c.gtid1
                left join db_goods_type l on l.gtid = c.gtid2"; 
            $group =" group by tname3,tname2,tname1,gname,uname ";
            $order =" order by tname3,tname2,tname1,gname asc";
            $sql.=$where.$group.$order;
            $result = M()->query($sql);
            if(!empty($result)){
                //处理字段  
                $sum_total =0;
                foreach ($result as $k => $v) {
                    //处理数量 
                    $result[$k]['total'] = customDecimal('sum',$v['total']);  
                    $result[$k]['num'] = customDecimal('num',$v['num']);   
                    $sum_total += $result[$k]['total'];
                } 
                $list=array();
                $list['result']=$result;
                $list['sum']=array();
                $list['sum']['sum_total'] =$sum_total;
                $this->ajaxReturn(ReturnJSON(0,$list));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }   
    }

    public function totalSale()
    {
        if(IS_POST){
            $stime =I("stime");$etime=I("etime");$gtid=I("gtid");$gtid1=I("gtid1");$gtid2=I("gtid2");
            $gname = I("gname");$brand_name = I("brand_name"); $machining = I("machining");
            $c_id=I("c_id");$ctid = I("ctid");
            $this->assign("stime",$stime); $this->assign("etime",$etime); $this->assign("gtid",$gtid);
            $this->assign("c_id",$c_id);$this->assign("ctid",$ctid);
            $this->assign("gname",$gname);$this->assign("brand_name",$brand_name);
            $this->assign("machining",$machining); $this->assign("gtid1",$gtid1); $this->assign("gtid2",$gtid2);
            $wid = getWid();
            $where =" where aa.create_id= $wid and aa.`status` = 1 ";
            if($stime){
                $stime =strtotime($stime);
                $where .=" and aa.create_time>=$stime";
            }
            if($etime){
                $etime =strtotime($etime);
                $where .=" and aa.create_time<=$etime+86399";
            } 
            if($gname){
                $where .=" and c.`name` like '%".$gname."%'";
            }
            if($brand_name!=''){
                $where .=" and e.brand_name like '%".$brand_name."%'";
            } 
            if($gtid!=''){
                $where .=" and f.gtid = $gtid";
            } 
            if($gtid1!=''){
                $where .=" and ff.gtid = $gtid1";
            } 
            if($gtid2!=''){
                $where .=" and fff.gtid = $gtid2";
            } 
            if($c_id!=''){
                $where .=" and g.c_id = $c_id";
            } 
            if($ctid!=''){
                $where .=" and g.ctid = $ctid";
            } 
            if($machining!=''){
                $where .=" and a.machining = $machining";
            }
            $sql ="select a.alias,c.`name` gname,d.unit_name,e.brand_name,f.type_name,ff.type_name type_name1,fff.type_name type_name2,SUM(a.num1) as num,SUM(a.cd_num) as cd_num
                from db_out_stock_detail a 
                left join db_out_stock aa on a.osid = aa.osid
                left join db_wholesale_goods b on a.wgid = b.wgid
                left join db_goods c on b.gid=c.gid
                left join db_unit d on d.unit_id = a.unit_id1
                left join db_brand e on c.brand_id = e.brand_id
                left join db_goods_type f on b.gtid  = f.gtid
                left join db_goods_type ff on b.gtid1 = ff.gtid
                left join db_goods_type fff on b.gtid2 = fff.gtid
                left join db_client g on g.c_id = aa.cid ";
            $group = " group by c.`name`,d.unit_name,e.brand_name,f.type_name,ff.type_name,fff.type_name";
            $order = " order by num desc";
            $sql = $sql.$where.$group.$order;   
            $list =M("")->query($sql); 
            $this->assign("list",$list); 
        }else{
            $stime=date('Y-m-01', strtotime(date("Y-m-d"))); 
            $etime= date('Y-m-d', strtotime("$stime +1 month -1 day"));  
            $this->assign("stime",$stime);  
            $this->assign("etime",$etime);
        } 
        $wid = getWid();
        // $cm = M("client");
        // $clist = $cm->field("c_id,`name` cname")->where("create_id = $wid")->select();
        // $this->assign("clist",$clist);
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid=$wid")->select();
        $this->assign("tlist",$tlist); 
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $rdcList = M("rdc")->where("wid = $wid")->find();
        if(!$rdcList){
            $rdcList = getReportsColumn();
        }   
        $titles = $this->getReportsTitleInfo($wid); 
        $this->assign("titles",$titles);
        $this->assign("rdcList",$rdcList);
        $this->display();
    }

    //获取商品表格头部标题
    public function getReportsTitleInfo($wid){
        $columns = getReportsColumnName(); 
        $titles = getReportsColumnTitle();
        $rdc = M("rdc")->where("wid=$wid")->find();
        $arr =array();
        foreach ($columns as $k => $v) {
            if(!empty($rdc)){
                if(empty($rdc[$v])){
                    $arr[$v]=$titles[$v]; //显示名称 
                }
                else{
                    $arr[$v]=$rdc[$v]; //显示名称
                } 
            }else{
                $arr[$v]=$titles[$v];
            } 
               
        }  
        return $arr;
    }


    public function financeAnaly()
    {   
        if(IS_POST){
            $stime =I("stime");$etime=I("etime");$c_id=I("c_id");$ctid=I("ctid");$gtid=I("gtid");
            $gname = I("gname");$brand_name = I("brand_name");$gtid1=I("gtid1");$gtid2=I("gtid2");
            $this->assign("stime",$stime); $this->assign("etime",$etime); $this->assign("gtid",$gtid);
            $this->assign("c_id",$c_id);$this->assign("gname",$gname);$this->assign("brand_name",$brand_name);
            $this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);$this->assign("ctid",$ctid);
            $wid = getWid();
            $where ="";
            if($stime){
                $stime =strtotime($stime);
                $where .=" and b.ktime>=$stime";
            }
            if($etime){
                $etime =strtotime($etime);
                $where .=" and b.ktime<=$etime+86399";
            } 
            if($gname){
                $where .=" and c.`name` like '%".$gname."%'";
            }
            if($brand_name!=''){
                $where .=" and e.brand_name like '%".$brand_name."%'";
            } 
            if($gtid!=''){
                $where .=" and a.gtid = $gtid";
            } 
            if($gtid1!=''){
                $where .=" and a.gtid1 = $gtid1";
            } 
            if($gtid2!=''){
                $where .=" and a.gtid2 = $gtid2";
            } 
            if($c_id!=''){
                $where .=" and f.c_id = $c_id";
            }  
            if($ctid!=''){
                $where .=" and f.ctid = $ctid";
            }   
            $sql ="select a.gname,a.unit_name,SUM(a.num) num,SUM(a.cd_num) cd_num,SUM(a.total_amount) total_amount,SUM(cost) cost
                                 FROM (SELECT b.owe_id,b.wgid,b.num,bb.cd_num,b.price,b.num*b.price total_amount,b.ktime,
                                c.`name` gname,d.unit_name,e.brand_name,cc.type_name,bbb.cid c_id,IFNULL(g.cost,0) AS cost
                                FROM db_wholesale_goods a 
                                LEFT JOIN db_owe_sales b ON a.wgid = b.wgid
                                LEFT JOIN db_out_stock_detail bb ON b.out_id = bb.out_id
                                LEFT JOIN db_out_stock bbb ON bbb.osid = bb.osid
                                LEFT JOIN db_goods c ON a.gid = c.gid
                                LEFT JOIN db_goods_type cc ON a.gtid = cc.gtid
                                LEFT JOIN db_unit d ON a.unit_id = d.unit_id
                                LEFT JOIN db_brand e ON e.brand_id = c.brand_id 
                                LEFT JOIN db_client f ON f.c_id = bbb.cid  
                                LEFT JOIN (SELECT a.owe_id,SUM(a.num*b.price) cost FROM db_owe_sales_detail a 
                                LEFT JOIN db_join_stock_detail b ON a.join_id = b.join_id
                                GROUP BY a.owe_id) g ON b.owe_id = g.owe_id
                                WHERE a.wid = $wid and ktime IS NOT NULL ".$where.") a GROUP BY a.gname,a.unit_name;";
            $list =M()->query($sql);
            $this->assign("list",$list); 
            // dump($list);
        }else{
            $stime=date('Y-m-01', strtotime(date("Y-m-d"))); 
            $etime= date('Y-m-d', strtotime("$stime +1 month -1 day")); 
            $this->assign("stime",$stime);  
            $this->assign("etime",$etime);
        }
        $cm = M("client");
        $wid = getWid();
        // $clist = $cm->field("c_id,`name` cname")->where("create_id = $wid")->select();
        // $this->assign("clist",$clist);
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid =$wid")->select();
        $this->assign("tlist",$tlist); 
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $finList = M("fin")->where("wid = $wid")->find();
        if(!$finList){
            $finList = getFinanceColumn();
        }
        $titles = $this->getFinanceTitleInfo($wid);
        $this->assign("titles",$titles);
        $this->assign("finList",$finList); 
        $this->display();
    }

    public function financeAnalyPrint(){ 
        $stime =I("stime");$etime=I("etime");$c_id=I("c_id");$gtid=I("gtid");$ctid=I("ctid");
        $gname = I("gname");$brand_name = I("brand_name");$gtid1=I("gtid1");$gtid2=I("gtid2");
        $this->assign("stime",$stime); $this->assign("etime",$etime); $this->assign("gtid",$gtid);
        $this->assign("c_id",$c_id);$this->assign("gname",$gname);$this->assign("brand_name",$brand_name);
        $this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);$this->assign("tcaption",I("tcaption"));
        $wid = getWid();
        $where ="";
        if($stime){
            $stime =strtotime($stime);
            $where .=" and b.ktime>=$stime";
        }
        if($etime){
            $etime =strtotime($etime);
            $where .=" and b.ktime<=$etime+86399";
        } 
        if($gname){
            $where .=" and c.`name` like '%".$gname."%'";
        }
        if($brand_name!=''){
            $where .=" and e.brand_name like '%".$brand_name."%'";
        } 
        if($gtid!=''){
            $where .=" and a.gtid = $gtid";
        } 
        if($gtid1!=''){
            $where .=" and a.gtid1 = $gtid1";
        } 
        if($gtid2!=''){
            $where .=" and a.gtid2 = $gtid2";
        } 
        if($c_id!=''){
            $where .=" and f.c_id = $c_id";
        } 
        if($ctid!=''){
            $where .=" and f.ctid = $ctid";
        }   
        $sql ="select a.gname,a.unit_name,SUM(a.num) num,SUM(a.cd_num) cd_num,SUM(a.total_amount) total_amount,SUM(cost) cost
                             FROM (SELECT b.owe_id,b.wgid,b.num,bb.cd_num,b.price,b.num*b.price total_amount,b.ktime,
                            c.`name` gname,d.unit_name,e.brand_name,cc.type_name,bbb.cid c_id,IFNULL(g.cost,0) AS cost
                            FROM db_wholesale_goods a 
                            LEFT JOIN db_owe_sales b ON a.wgid = b.wgid
                            LEFT JOIN db_out_stock_detail bb ON b.out_id = bb.out_id
                            LEFT JOIN db_out_stock bbb ON bbb.osid = bb.osid
                            LEFT JOIN db_goods c ON a.gid = c.gid
                            LEFT JOIN db_goods_type cc ON a.gtid = cc.gtid
                            LEFT JOIN db_unit d ON a.unit_id = d.unit_id
                            LEFT JOIN db_brand e ON e.brand_id = c.brand_id 
                            LEFT JOIN db_client f ON f.c_id = bbb.cid  
                            LEFT JOIN (SELECT a.owe_id,SUM(a.num*b.price) cost FROM db_owe_sales_detail a 
                            LEFT JOIN db_join_stock_detail b ON a.join_id = b.join_id
                            GROUP BY a.owe_id) g ON b.owe_id = g.owe_id
                            WHERE a.wid = $wid and ktime IS NOT NULL ".$where.") a GROUP BY a.gname,a.unit_name;";
        $list =M()->query($sql);
        $this->assign("list",$list);  
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $finList = M("fin")->where("wid = $wid")->find();
        if(!$finList){
            $finList = getFinanceColumn();
        }
        $titles = $this->getFinanceTitleInfo($wid);
        $this->assign("titles",$titles);
        $this->assign("finList",$finList); 
        $this->display();
    }

    public function financeAnalySet()
    {
        $wid =getWid();
        $fin_list = $this->getFinanceColumnInfo($wid);
        // dump($rdc_list);
        $this->assign("fin_list",$fin_list);
        $this->display();
    }
    //获取数据列信息
    public function getFinanceColumnInfo($wid){
        $columns = getFinanceColumnName();
        $mrgsList = getFinanceColumn();
        $needs = getFinanceColumnNeed();
        $titles = getFinanceColumnTitle();
        $fin = M("fin")->where("wid=$wid")->find();
        $gs_list =array();
        foreach ($columns as $k => $v) {
            if(!empty($fin)){
                if(empty($fin[$v])){
                    $gs_list[$k]['show_name']=$titles[$v]; //显示名称
                }
                else{
                    $gs_list[$k]['show_name']=$fin[$v]; //显示名称
                }
                $gs_list[$k]['isshow']= $fin[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= $fin[$v.'_p']; //是否打印 0.打印 1.不打印
            }else{
                $gs_list[$k]['show_name']=$titles[$v]; //显示名称
                $gs_list[$k]['isshow']= $mrgsList[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= 0; //是否打印 0.打印 1.不打印
            }
            $gs_list[$k]['title']=$titles[$v]; //标题名称
            $gs_list[$k]['disabled'] = $needs[$v]; //是否为必需 0.必需 1.不必需
            $gs_list[$k]['cname'] = $v;

        }
        return $gs_list;
    }
    //销售汇总数据列设置数据提交
    public function financeColumnSubmit(){
        if(IS_POST){
            $wid =getWid();
            $m = M("fin");
            $list = $m->field('fin_id')->where("wid =$wid")->find();
            $data =array(
                "wid"=>$wid,
                "gname"=>I("gname"),
                "gname_s"=>I("gname_s"),
                "gname_p"=>I("gname_p"),
                "num"=>I("num"),
                "num_s"=>I("num_s"),
                "num_p"=>I("num_p"),
                "cd_num"=>I("cd_num"),
                "cd_num_s"=>I("cd_num_s"),
                "cd_num_p"=>I("cd_num_p"),
                "cd_cost"=>I("cd_cost"),
                "cd_cost_s"=>I("cd_cost_s"),
                "cd_cost_p"=>I("cd_cost_p"),
                "uname"=>I("uname"),
                "uname_s"=>I("uname_s"),
                "uname_p"=>I("uname_p"),
                "profit"=>I("profit"),
                "profit_s"=>I("profit_s"),
                "profit_p"=>I("profit_p"),
                "cost"=>I("cost"),
                "cost_s"=>I("cost_s"),
                "cost_p"=>I("cost_p"),
                "rate"=>I("rate"),
                "rate_s"=>I("rate_s"),
                "rate_p"=>I("rate_p"),
            );
            if($list){ //修改
                $fin_id = $list['fin_id'];
                $result = $m->where("fin_id = $fin_id")->save($data);
            }else{
                $result = $m->add($data);
            }
            if($result){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }
    }


    //获取财务表格头部标题
    public function getFinanceTitleInfo($wid){
        $columns = getFinanceColumnName();
        $titles = getFinanceColumnTitle();
        $fin = M("fin")->where("wid=$wid")->find();
        $arr =array();
        foreach ($columns as $k => $v) {
            if(!empty($fin)){
                if(empty($fin[$v])){
                    $arr[$v]=$titles[$v]; //显示名称
                }
                else{
                    $arr[$v]=$fin[$v]; //显示名称
                }
            }else{
                $arr[$v]=$titles[$v];
            }

        }
        return $arr;
    }
    public function supplierGet()
    {
        $this->display();
    }

    //销售汇总数据列设置列表页
    public function reportsDataSet(){
        $wid =getWid(); 
        $rdc_list = $this->getDataColumnInfo($wid);  
        // dump($rdc_list);
        $this->assign("rdc_list",$rdc_list);
        $this->display();
    }
 
    //销售汇总数据列设置数据提交
    public function dataColumnSubmit(){
        if(IS_POST){
            $wid =getWid();  
            $m = M("rdc");
            $list = $m->field('rdc_id')->where("wid =$wid")->find();
            $data =array(
                "wid"=>$wid,
                "gname"=>I("gname"),
                "gname_s"=>I("gname_s"),
                "gname_p"=>I("gname_p"),
                "alias"=>I("alias"),
                "alias_s"=>I("alias_s"),
                "alias_p"=>I("alias_p"),
                "gtname"=>I("gtname"),
                "gtname_s"=>I("gtname_s"),
                "gtname_p"=>I("gtname_p"), 
                "bname"=>I("bname"),
                "bname_s"=>I("bname_s"),
                "bname_p"=>I("bname_p"), 
                "uname"=>I("uname"),
                "uname_s"=>I("uname_s"),
                "uname_p"=>I("uname_p"), 
                "num"=>I("num"),
                "num_s"=>I("num_s"),
                "num_p"=>I("num_p"),
                "cd_num"=>I("cd_num"),
                "cd_num_s"=>I("cd_num_s"),
                "cd_num_p"=>I("cd_num_p"),
                "snum"=>I("snum"),
                "snum_s"=>I("snum_s"),
                "snum_p"=>I("snum_p"),
            );
            if($list){ //修改
                $rdc_id = $list['rdc_id'];
                $result = $m->where("rdc_id = $rdc_id")->save($data);
            }else{
                $result = $m->add($data);
            }
            if($result){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        } 
    }

    //获取数据列信息
    public function getDataColumnInfo($wid){ 
        $columns = getReportsColumnName();
        $mrgsList = getReportsColumn();
        $needs = getReportsColumnNeed();
        $titles = getReportsColumnTitle();
        $rdc = M("rdc")->where("wid=$wid")->find();
        $gs_list =array();
        foreach ($columns as $k => $v) {
            if(!empty($rdc)){
                if(empty($rdc[$v])){
                    $gs_list[$k]['show_name']=$titles[$v]; //显示名称 
                }
                else{
                    $gs_list[$k]['show_name']=$rdc[$v]; //显示名称
                } 
                $gs_list[$k]['isshow']= $rdc[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= $rdc[$v.'_p']; //是否打印 0.打印 1.不打印
            }else{
                $gs_list[$k]['show_name']=$titles[$v]; //显示名称
                $gs_list[$k]['isshow']= $mrgsList[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= 0; //是否打印 0.打印 1.不打印 
            }
            $gs_list[$k]['title']=$titles[$v]; //标题名称 
            $gs_list[$k]['disabled'] = $needs[$v]; //是否为必需 0.必需 1.不必需
            $gs_list[$k]['cname'] = $v;
           
        } 
        return $gs_list;
    }

    //日销售明细 数据列设置
    public function daySaleDataSet()
    {
        $wid =getWid();
        $dsdc_list = $this->getDaySaleColumnInfo($wid);
//         dump($dsdc_list);
        $this->assign("dsdc_list",$dsdc_list);
        $this->display();
    }

    public function getDaySaleTitleInfo($wid){
        $columns = getDaySaleColumnName();
        $titles = getDaySaleColumnTitle();
        $dsdc = M("dsdc")->where("wid=$wid")->find();
        $arr =array();
        foreach ($columns as $k => $v) {
            if(!empty($dsdc)){
                if(empty($dsdc[$v])){
                    $dsdc[$v]=$titles[$v]; //显示名称
                }
                else{
                    $arr[$v]=$dsdc[$v]; //显示名称
                }
            }else{
                $arr[$v]=$titles[$v];
            }

        }
        return $arr;
    }

    //销售汇总数据列设置数据提交
    public function daySaleColumnSubmit(){
        if(IS_POST){
            $wid =getWid();
            $m = M("dsdc");
            $list = $m->field('dsdc_id')->where("wid =$wid")->find();
            $data =array(
                "wid"=>$wid,
                "gname"=>I("gname"),
                "gname_s"=>I("gname_s"),
                "gname_p"=>I("gname_p"),
                "alias"=>I("alias"),
                "alias_s"=>I("alias_s"),
                "alias_p"=>I("alias_s"), 
                "gtname"=>I("gtname"),
                "gtname_s"=>I("gtname_s"),
                "gtname_p"=>I("gtname_p"),
                "bname"=>I("bname"),
                "bname_s"=>I("bname_s"),
                "bname_p"=>I("bname_p"),
                "uname"=>I("uname"),
                "uname_s"=>I("uname_s"),
                "uname_p"=>I("uname_p"),
                "num"=>I("num"),
                "num_s"=>I("num_s"),
                "num_p"=>I("num_p"),
                "rweight"=>I("rweight"),
                "rweight_s"=>I("rweight_s"),
                "rweight_p"=>I("rweight_p"),
                "than"=>I("than"),
                "than_s"=>I("than_s"),
                "than_p"=>I("than_p"),
                "price"=>I("price"),
                "price_s"=>I("price_s"),
                "price_p"=>I("price_p"),
                "cd_num"=>I("cd_num"),
                "cd_num_s"=>I("cd_num_s"),
                "cd_num_p"=>I("cd_num_p"),
                "total"=>I("total"),
                "total_s"=>I("total_s"),
                "total_p"=>I("total_p"),
                "cname"=>I("cname"),
                "cname_s"=>I("cname_s"),
                "cname_p"=>I("cname_p"),
                "remark"=>I("remark"),
                "remark_s"=>I("remark_s"),
                "remark_p"=>I("remark_p"),
                "check_num"=>I("check_num"),
                "check_num_s"=>I("check_num_s"),
                "check_num_p"=>I("check_num_p"),
            );
            if($list){ //修改
                $dsdc_id = $list['dsdc_id'];
                $result = $m->where("dsdc_id = $dsdc_id")->save($data);
            }else{
                $result = $m->add($data);
            }
            if($result){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }
    }

    //获取数据列信息
    public function getDaySaleColumnInfo($wid){
        $columns = getDaySaleColumnName();
        $mrgsList =getDaySaleColumn();
        $needs = getDaySaleColumnNeed();
        $titles = getDaySaleColumnTitle();
        $dsdc = M("dsdc")->where("wid=$wid")->find();
        $gs_list =array();
        foreach ($columns as $k => $v) {
            if(!empty($dsdc)){
                if(empty($dsdc[$v])){
                    $gs_list[$k]['show_name']=$titles[$v]; //显示名称
                }
                else{
                    $gs_list[$k]['show_name']=$dsdc[$v]; //显示名称
                }
                $gs_list[$k]['isshow']= $dsdc[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= $dsdc[$v.'_p']; //是否打印 0.打印 1.不打印
            }else{
                $gs_list[$k]['show_name']=$titles[$v]; //显示名称
                $gs_list[$k]['isshow']= $mrgsList[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= 0; //是否打印 0.打印 1.不打印
            }
            $gs_list[$k]['title']=$titles[$v]; //标题名称
            $gs_list[$k]['disabled'] = $needs[$v]; //是否为必需 0.必需 1.不必需
            $gs_list[$k]['cname'] = $v;

        }
        return $gs_list;
    }

    
}