<?php
// +----------------------------------------------------------------------
// | 出库模块
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class PurchaseController extends BaseController
{
    //采购单
    public function purchaseForm()
    {    
        $wid = getWid();
        $osid ="PC".$wid."_".time();
        $this->assign("time",time());
        $this->assign("osid",$osid);  
        $osname = getUnitName()."配送单";
        $this->assign("osname",$osname);
        // dump($t_list);dump($osbList);
        $this->display();
    }


    //标记重点，取消重点
    public function setKeyDocuments(){
        if(IS_POST){
            $osid = I("osid");
            $num = I("num");
            $data=array(
                "key_documents" => $num
            );
            $m =M("purchase");
            $result = $m->where("osid = '$osid'")->save($data);  
            if($result){
                $this->ajaxReturn(0);//修改成功
            }else{
                $this->ajaxReturn(1);//修改失败
            }
        }
    }

    //出库数据验证
    public function checkFrom($list){
        if($list){
            $a=0;$b=0;
            foreach ($list as $k => $v) {
                if($a==0){
                    $a = count($v); 
                }else{
                    $b = count($v);
                }
                if($a!=0 and $b!=0 and $a!=$b){
                    return false;
                } 
            }
            return true;
        }else{
            return false;
        }
    }

    //字符串处理返回数组
    public function strToArray($str){
        return explode(",",$str);
    }

   


    //删除采购单单 
    public function delPurchase(){
        if(IS_POST){
            $osid = I("osid");   
            //判断采购单是否被审核
            $m = M("purchase"); 
            M()->startTrans();
            $list =  $m->where("osid='".$osid."'")->delete(); 
            if($list){ 
                $result = M("purchase_detail")->where("osid='".$osid."'")->delete();
                if($result){
                    M()->commit();
                    $this->ajaxReturn(0);
                }else{
                    M()->rollback();
                    $this->ajaxReturn(1);
                } 
            }else {
                M()->rollback();
                $this->ajaxReturn(1);
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }
 

      //提交采购单
    public function purchaseAdd(){
        if(IS_POST){
            //数据验证
            $data = I("post.");  
            $wid = getWid();
            $list =array();
            //gids,unit_id1s,num1s,prices,unit_id2s,num2s,unit_id3s,num3s,unit_id4s,num4s
            $list['wgids'] =$this->strToArray(I("wgids"));   $list['unit_id1s'] =$this->strToArray(I("unit_ids"));
            $list['num1s'] =$this->strToArray(I("num1s")); $list['prices'] =$this->strToArray(I("prices"));   
            $list['sums'] =$this->strToArray(I("sums")); 
            $list['remarks']=$this->strToArray(I("remarks")); 
            $status = I("status"); 
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids")); 
            $list['nei_nums'] =$this->strToArray(I("nei_nums")); 
            $list['alias'] =$this->strToArray(I("alias"));
            $list['machinings'] =$this->strToArray(I("machinings")); 
            // $this->ajaxReturn($list);exit();
            if($this->checkFrom($list)){
                //总表录入 
                $osid = I("osid");
                $data = array(
                    'osid' => $osid,
                    'osname' => I("osname"),
                    'cid' => I("cid"),
                    'total' => I("total"),
                    'remark' => I("remark"),
                    'create_id' => $wid
                );
                $create_time = I("create_time"); 
                if($create_time){
                    $create_time = strtotime($create_time); 
                    $t=time(); 
                    $t=strtotime(date("Y-m-d",$t));
                    if($create_time==$t){
                        $create_time = time(); 
                    }
                }else{
                    $create_time = time(); 
                }
                $data['create_time'] = $create_time;
                $m = M("purchase");
                M()->startTrans();
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功'
                );  
                $result = $m->add($data); 
                if($result){  
                    $mm = M("purchase_detail");  
                    //详情录入 
                    foreach ($list['wgids'] as $k => $v) {
                        //计算默认单价
                        $price1 = floatval($list['prices'][$k])/floatval($list['nei_nums'][$k]);
                        $num1 =floatval($list['num1s'][$k])*floatval($list['nei_nums'][$k]);
                        $cd_num =floatval($list['cdnums'][$k])*floatval($list['nei_nums'][$k]);
                        //采购详情新增
                        $sublist = array(
                            'osid'    =>$osid,
                            'wgid'    =>$list['wgids'][$k],
                            'alias'   =>$list['alias'][$k], 
                            'num1'    =>$num1,
                            'price'   =>$price1,
                            'unit_id1'=>$list['unit_id1s'][$k],
                            'remark'=>$list['remarks'][$k],
                            'nei_num'=>$list['nei_nums'][$k],
                            'nei_unit_id'=>$list['nei_unit_ids'][$k],
                            'nei_price'=>$list['prices'][$k],
                            'sales_amount' => $list['sums'][$k], 
                            'machining'=>$list['machinings'][$k],
                        ); 
                        //计算成本价格 强转换为数字 如果此处去问题 则前端有问题
                        
                        $sublist["cost"] = $this->getCost($list['wgids'][$k],$list['num1s'][$k]);
                        $out_id = $mm->add($sublist);
                        if($out_id<=0){
                            $arr_result['resultcode'] = 10;
                            $arr_result['msg'] = "新增出库详情时失败了";  
                        }  
                    }   
                }else{
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "新增出库单时失败了"; 
                }

                //判断status == 1 生成出库单 
                if($arr_result['resultcode'] == 0){
                    M()->commit(); //事务提交  
                    $this->ajaxReturn($arr_result);
                }else{
                    M()->rollback(); //事务回滚 
                    $this->ajaxReturn($arr_result);
                }  
            }else{
                //数据错误 验证未通过
                $this->ajaxReturn(ReturnJSON(8));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }


    //出库单编辑 Ajax表单提交
    public function PurchaseAjaxEdit(){
         if(IS_POST){
            //数据验证
            $data = I("post.");  
            $wid = getWid(); 
            $osid = I("osid"); 
            $list =array(); 
            $mm = M("purchase_detail");  
            $list['wgids'] =$this->strToArray(I("wgids"));   $list['unit_id1s'] =$this->strToArray(I("unit_ids"));
            $list['num1s'] =$this->strToArray(I("num1s")); $list['prices'] =$this->strToArray(I("prices"));   
            $list['sums'] =$this->strToArray(I("sums")); $list['remarks']=$this->strToArray(I("remarks"));
            $status = I("status"); $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids")); 
            $list['nei_nums'] =$this->strToArray(I("nei_nums")); $list['alias'] =$this->strToArray(I("alias"));  
            $list['machinings'] =$this->strToArray(I("machinings"));  
            // $this->ajaxReturn($list);exit();
            if($this->checkFrom($list)){
                //总表录入  
                $data = array(
                    'cid' => I("cid"),
                    'osname' => I("osname"), 
                    'total' => I("total"), 
                    'remark' => I("remark"),
                    'update_time'=>time(),
                    'update_id' => $wid
                );
                $create_time = I("create_time");
                if($create_time){
                    $create_time = strtotime($create_time); 
                    $t=time(); 
                    $t=strtotime(date("Y-m-d",$t));
                    if($create_time==$t){
                        $create_time = time(); 
                    }
                }else{
                    $create_time = time();
                }
                $data['create_time'] =  $create_time;
               
                $m = M("purchase");
                M()->startTrans();
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功'
                );  
                $result = $m->where("osid='".$osid."'")->save($data); 
                if($result){ 
                    //添加出库单日志  
                    //删除采购名称信息  
                    $del = $mm->where("osid='".$osid."'")->delete();
                    if($del<=0){
                      $arr_result['resultcode'] = 10;
                      $arr_result['msg'] = "删除采购详情时失败了";  
                    }else{
                        //详情录入
                        foreach ($list['wgids'] as $k => $v) {
                            //计算默认单价
                            $price1 = floatval($list['prices'][$k])/floatval($list['nei_nums'][$k]);
                            $num1 =floatval($list['num1s'][$k])*floatval($list['nei_nums'][$k]); 
                            //采购详情新增
                            $sublist = array(
                                'osid'    =>$osid,
                                'wgid'    =>$list['wgids'][$k],
                                'alias'    =>$list['alias'][$k],  
                                'num1'    =>$num1,
                                'price'   =>$price1,
                                'unit_id1'=>$list['unit_id1s'][$k],
                                'remark'=>$list['remarks'][$k],
                                'nei_num'=>$list['nei_nums'][$k],
                                'nei_unit_id'=>$list['nei_unit_ids'][$k],
                                'nei_price'=>$list['prices'][$k],
                                'sales_amount' => $list['sums'][$k], 
                                'machining'=>$list['machinings'][$k],
                            );  
                            //出库时设置成本金额以供 财务分析报表 查询 和其他作用
                            $sublist["cost"] = $this->getCost($list['wgids'][$k],$list['num1s'][$k],$list['cdnums'][$k]);
                            $out_id = $mm->add($sublist);  
                            if($out_id<=0){
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] = "新增出库详情时失败了";  
                            }  
                        }  
                    }
                }else{ 
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "新增出库单时失败了"; 
                }
                if($arr_result['resultcode'] == 0){
                    M()->commit(); //事务提交  
                    $this->ajaxReturn($arr_result);
                }else{
                    M()->rollback(); //事务回滚 
                    $this->ajaxReturn($arr_result);
                }  
            }else{ 
                //数据错误 验证未通过
                $this->ajaxReturn(ReturnJSON(8));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //生成出库单
    public function buildOutStock(){ 
        if(IS_POST){
            $osid = I("osid"); 
            $purchase_id = $osid;
            $status = I("status"); 
            $wid =getWid();
            $list = M("purchase")->where("osid = '$purchase_id'")->find();  
            if($list){
                M()->startTrans();  //开启事务
                if($list['status']==1){
                    $osid = "PC".$wid."_".time();//新出库单据号
                }
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'生成成功,出库单据号为：'.$osid,
                );  
                $data = array(
                    'osid' => $osid, //新出库单据号
                    'osname' => $list['osname'],
                    'cid' => $list['cid'], 
                    'total' => $list['total'],
                    'remark' => $list['remark'],
                    'create_id' => $wid,
                    'create_time' => time(),
                    'source'=>1,
                    'purchase_id'=>$purchase_id, //采购单据号
                );  
                $m = M("out_stock"); 
                $result = $m->add($data);
                if($result){  
                    $dlist = M("purchase_detail")->where("osid = '$purchase_id'")->select();
                    if($dlist){
                        $mm = M("out_stock_detail");  
                        foreach ($dlist as $k => $v) {
                            $sublist = array(
                                'osid'    =>$osid,
                                'wgid'    =>$v['wgid'],
                                'alias'   =>$v['alias'],
                                'than'    =>1, 
                                'cd_num'  =>0,
                                'num1'    =>$v['num1'],
                                'price'   =>$v['price'],
                                'unit_id1'=>$v['unit_id1'],
                                'remark'=>$v['remark'],
                                'nei_num'=>$v['nei_num'],
                                'nei_unit_id'=>$v['nei_unit_id'],
                                'nei_price'=>$v['nei_price'],
                                'sales_amount' => $v['sales_amount'], 
                                'cost'=>$v['cost'],
                                'machining'=>$v['machining'],
                            ); 
                            $out_id = $mm->add($sublist);
                            if($out_id<=0){
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] = "新增出库详情时失败了";  
                            }  
                        }
                    }else{
                        $arr_result['resultcode'] = 10;
                        $arr_result['msg'] = "未发现该采购单详情";
                    }
                }else{
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "新增出库单时失败了"; 
                }
                //修改出库状态为1
                $data=array(
                    'status'=>1,
                    'update_time'=>time(),
                    'update_id'=> $wid
                ); 
                $result = M("purchase")->where("osid ='$purchase_id'")->save($data); 
                if($arr_result['resultcode'] == 0){
                    M()->commit(); //事务提交  
                    $this->ajaxReturn($arr_result); 
                }else{
                    M()->rollback(); //事务回滚 
                    $this->ajaxReturn($arr_result);
                }  
            }else{
                $arr_result['resultcode']=10;
                $arr_result['msg']="未发现该采购单";
            }  
            $this->ajaxReturn($arr_result); 
        } 
    }

   


    //采购单编辑加载页
    public function purchaseEdit(){
        $wid = getWid();
        $osid =I("osid");
        $sql ="select g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,aa.osname,bb.`name` cname,b.`name` gname,aa.total,d.unit_name unit_name1,e.unit_name unit_name2,a.num1 * a.price totalx,a.* FROM   db_purchase aa LEFT JOIN db_client bb ON aa.cid = bb.c_id LEFT JOIN db_purchase_detail a ON a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid LEFT JOIN db_brand f ON b.brand_id = f.brand_id LEFT JOIN db_unit d ON a.nei_unit_id = d.unit_id LEFT JOIN db_unit e ON a.unit_id1 = e.unit_id LEFT JOIN db_stock g ON a.wgid=g.wgid WHERE a.osid = '$osid' ORDER BY a.out_id ASC";
        $list = M("")->query($sql);  
        foreach ($list as $k => $v) {  
            $sql ="select a.*,b.unit_name uname1,c.unit_name uname FROM  db_spec a LEFT JOIN db_unit b ON a.unit_id1 = b.unit_id LEFT JOIN db_unit c ON a.unit_id = c.unit_id WHERE a.wgid = ".$v['wgid'];
            $ulist = M()->query($sql); 
            $ulist1 =array();
            if($ulist){
                foreach ($ulist as $kk => $vo) {
                    if($list[$k]['nei_unit_id'] == $vo['unit_id1']){
                        $list[$k]['stock_num'] = floatval($v['stock_num'])/floatval($vo['num']); 
                    }
                    if($kk==0){
                        $ulist1[$kk] =array();
                        $ulist1[$kk]['unit_id'] = $vo['unit_id'];  
                        $ulist1[$kk]['uname'] = $vo['uname'];
                        $ulist1[$kk]['num'] = "1";
                    }
                    $ulist1[$kk+1] =array();
                    $ulist1[$kk+1]['unit_id'] = $vo['unit_id1'];
                    $ulist1[$kk+1]['uname'] = $vo['uname1'];
                    $ulist1[$kk+1]['num'] = $vo['num'];
                } 
            }else{
                $ulist1[0] =array();
                $ulist1[0]['unit_id'] = $v['unit_id1'];  
                $ulist1[0]['uname'] = $v['unit_name2'];
                $ulist1[0]['num'] = "1";
            }
            $list[$k]['usub'] =$ulist1;
        }  

        $this->assign("list",$list);
        $this->assign("time",time());
        $this->assign("osid",$osid); 
        $this->display();
    }  
   
    /**
     * 获取成本价格 强转换为数字 如果此处去问题 则前端提交数据有问题
     * @param string $wgid 批发商商品编号
     * @param string $num1 商品出库数量
     * @param string $cdnum 商品出库货损数
     * @return  int  成本价 如果是0，表示无法计算商品成本 
     */
    public function getCost($wgid,$num1){
        $num = floatval($num1); 
        $result = M()->query("CALL getCost($wgid,$num)");  
        if($result[0]['d_cost']){
           return $result[0]['d_cost'];
        } 
        return 0;
    }

    //修改采购单据名称
    public function upOsname(){
        if(IS_POST){
            $osid =I("osid");
            $data =array(
                'osname'=>I("osname")
            );
            $result = M("purchase")->where("osid='$osid'")->save($data); 
            if($result){
                $this->ajaxReturn(1); //修改成功
            }else{
                $this->ajaxReturn(0); //修改失败
            }
        }
    }

    public function purchaseDetail(){
        $osid = I("osid");
        $sql ="select aa.osname,g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,bb.`name` cname,bb.phone,bb.pid,bb.cid,bb.did,bb.street,b.`name` gname,aa.total,h.unit_name uname,a.num1*a.price totalx,a.*,aa.`status`,jj.contacts FROM db_purchase aa left join db_client bb on aa.cid = bb.c_id left join db_purchase_detail a on a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid Left JOIN db_brand f ON b.brand_id =f.brand_id LEFT JOIN db_unit d ON a.unit_id1 = d.unit_id left join db_stock g on g.wgid = a.wgid LEFT JOIN db_unit h ON h.unit_id = a.nei_unit_id LEFT JOIN db_wholesale_user jj on aa.create_time = jj.wid WHERE a.osid = '".$osid."' order by a.out_id asc";
        $list = M('')->query($sql);  
        // dump($list);
        $this->assign("osid",$osid);
        $this->assign("list",$list);
        $this->assign("status",$list[0]['status']); 
        $this->display();
    } 

    //根据jsid 获取供应商地址
    public function getSaddress(){
        if(IS_POST){
            $osid =I("osid");
            $sql ="select b.pid,b.cid,b.did,b.street from db_purchase a left join db_client b on a.cid=b.c_id where a.osid ='$osid'";
            $result = M()->query($sql);
            if($result){
                $this->ajaxReturn(ReturnJSON(0,$result));
            }else{ 
                $this->ajaxReturn(ReturnJSON(1));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(1));
        }
    }

    //导出采购单 excel
    public function exportJoinStock(){
        $osid = I("osid"); 
        $sql ="select g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,bb.`name` cname,bb.phone,bb.pid,bb.cid,bb.did,bb.street,b.`name` gname,aa.total,h.unit_name uname,a.num1*a.price*a.than totalx,a.*,aa.`status`,jj.contacts FROM db_purchase aa left join db_client bb on aa.cid = bb.c_id left join db_purchase_detail a on a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid Left JOIN db_brand f ON b.brand_id =f.brand_id LEFT JOIN db_unit d ON a.unit_id1 = d.unit_id left join db_stock g on g.wgid = a.wgid LEFT JOIN db_unit h ON h.unit_id = a.nei_unit_id LEFT JOIN db_wholesale_user jj on aa.auditing_id = jj.wid WHERE a.osid = '".$osid."' order by a.wgid asc";
        $list = M('')->query($sql); 
        $address =I("address");    
        $wid = getWid();
       
        
        Vendor('PHPExcel.PHPExcel');    //引入phpexcel类
        $objPHPExcel = new \PHPExcel();   //实例化phpexcel类
        $objSheet = $objPHPExcel->getActiveSheet(); //获得当前sheet 
        //设置当前的sheet
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(27);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(27); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(19); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(19); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(27); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(19); 
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(40);//设置行高
        
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $wid = getWid();
        $osList = M("oscd")->where("wid =$wid")->find();
        if(!$osList){
            $osList = getOutStockColumnT();
        }
        $titles = $this->getOutStockTitleInfoT($wid);
        $objSheet->setCellValue("A1",$titles['osid'])
                    ->setCellValue("B1",$titles['ctime'])
                    ->setCellValue("C1",$titles['cname'])
                    ->setCellValue("D1",$titles['cphone'])
                    ->setCellValue("E1",$titles['caddress'])
                    ->setCellValue("F1",$titles['audit'])
                    ->setCellValue("G1",$titles['total'])
                    ->setCellValue("H1",$titles['remark']);
                    // ->mergeCells('D1:F1');  //合并单元格 
        $arr =array("A1","B1","C1","D1","E1","F1","G1","H1","A2","B2","C2","D2","E2","F2","G2","H2","A4","B4","C4","D4","E4","F4","G4","H4","I4","J4","K4","L4"); 
        //头部信息 出库单据号 日期 客户 客户地址 客户电话 单据金额(元) 审核人  备注 
        $objSheet->setCellValue("A2", $osid)
                ->setCellValue("B2", date('Y-m-d H:i:s',$list[0]['create_time']))
                ->setCellValue("C2", $list[0]['cname'])
                ->setCellValue("D2", $list[0]['phone'])
                ->setCellValue("E2", $address)
                ->setCellValue("F2", $list[0]['contacts'])
                ->setCellValue("G2", $list[0]['total'])
                ->setCellValue("H2", $list[0]['remarkf']);
         /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息   
        $osbList = M("oscdb")->where("wid =$wid")->find();
        if(!$osbList){
            $osbList = getOutStockColumn();
        }
        $t_list = $this->getOutStockTitleInfo($wid);

        $objSheet->setCellValue("A4", $t_list['gname'])
                    ->setCellValue("B4", $t_list['bname'])
                    ->setCellValue("C4", $t_list['uname'])
                    ->setCellValue("D4", $t_list['machining'])
                    ->setCellValue("E4", $t_list['num'])
                    ->setCellValue("F4", $t_list['cd_num'])
                    ->setCellValue("G4", $t_list['than'])
                    ->setCellValue("H4", $t_list['price'])
                    ->setCellValue("I4", $t_list['tax_price'])
                    ->setCellValue("J4", $t_list['rweight'])
                    ->setCellValue("K4", $t_list['remarkb'])
                    ->setCellValue("L4", $t_list['totalb']);
        //内容部分
        $n = 5;
        foreach ($list as $k => $vo) {
            $machining = $vo['machining'];
            if($machining==0){
                $machining ='不加工';
            }else{
                $machining ='加工';
            }
            $objSheet->setCellValue("A".$n, $vo['gname'])
                    ->setCellValue("B".$n, $vo['brand_name'])
                    ->setCellValue("C".$n, $vo['uname'])
                    ->setCellValue("D".$n, $machining) 
                    ->setCellValue("E".$n, $vo['num1'])
                    ->setCellValue("F".$n, $vo['cd_num'])
                    ->setCellValue("G".$n, $vo['than'])
                    ->setCellValue("H".$n, $vo['nei_price'])
                    ->setCellValue("I".$n, round($vo['than']*$vo['nei_price'],2))
                    ->setCellValue("J".$n, round(($vo['num']+$vo['cd_num'])/$vo['nei_num'],2))
                    ->setCellValue("K".$n, $vo['remark'])
                    ->setCellValue("L".$n, $vo['sales_amount']);
 
            array_push($arr,"A".$n,"B".$n,"C".$n,"D".$n,"E".$n,"F".$n,"G".$n,"H".$n,"I".$n,"J".$n,"K".$n,"L".$n);
            $n++;
        }
        $this->setExcelBorder($objPHPExcel,$arr);
                    
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");  //Excel2007生成后缀.xlsx 
        $filename="出库单".$osid;
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

    //采购记录
    public function purchaseRecord()
    {
        $m = M();
        $wid = getWid(); 
        $where ="";  $gname=I("gname");
        $stime = I("stime");$etime=I("etime");$sname=I("sname");
        $cid = I("cid");$osid=I("osid"); 
        $status =I("status");
        $this->assign("status",$status);
        $key_documents = I("key_documents");  
        $this->assign("stime",$stime);
        $this->assign("etime",$etime);
        $this->assign("sname",$sname);
        $this->assign("cid",$cid); 
        $this->assign("key_documents",$key_documents);
        $this->assign("gname",$gname);
        if($stime==""){
            $stime=date('Y-m-d',time());
            $this->assign("stime",$stime);
        }
        if($stime){
            $stime =strtotime($stime);
            $where .=" and a.create_time>=$stime";
        }
        if($etime){
            $etime =strtotime($etime);
            $where .=" and a.create_time<=$etime+86399";
        } 
        if($osid){
            $where .=" and a.osid like '%".$osid."%'";
        } 
        if($cid!=''){
            $where .=" and a.cid = $cid";
        } 
        if($status!=''){
            $where .=" and a.status = $status";
        } 
        if($key_documents!=''){
            $where .=" and a.key_documents = $key_documents";
        } 
        $this->assign("osid",$osid);
        
        $sql ="select b.`name` cname,  a.* FROM db_purchase a LEFT JOIN db_client b on a.cid=b.c_id";
        $sql .= " where a.create_id = $wid";
        $sql =$sql.$where; 
        $sql .= " order by a.create_time desc";    
        $list =$m->query($sql); 
        $listArr =array();
        //出库记录
        if($gname){  
            foreach($list as $k => $v) {
                $sql1 ="select b.wgid from db_purchase_detail a left join db_wholesale_goods b on a.wgid=b.wgid left join db_goods c on b.gid = c.gid  where a.osid='".$v['osid']."' and c.name like '%".$gname."%'"; 
                $result = M("")->query($sql1); 
                if($result){
                    array_push($listArr,$v);
                }
            }
            $list = $listArr;
        } 
        $client = M("client");
        $clist = $client->where("create_id = $wid")->select();
        $this->assign("clist",$clist);
        $this->assign("list",$list); 
        $this->display();
    } 

    public function purchaseReport()
    {    
        $wid = getWid();
        if(IS_POST){
           $where =" where c.wid =$wid ";
           $create_time =I("create_time");$brand_name =I("brand_name");
           $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id");
           $gname=I("gname");$ctid=I("ctid");$machining=I("machining");$gtid2=I("gtid2");
           $this->assign("create_time",$create_time); $this->assign("brand_name",$brand_name);
           $this->assign("ctid",$ctid);$this->assign("c_id",$c_id);
           $this->assign("gtid",$gtid);$this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);
           $this->assign("gname",$gname);$this->assign("c_id",$c_id); $this->assign("machining",$machining); 
           
           if($create_time){
                $create_time =strtotime($create_time); 
                $where .=" and a.create_time >= '".$create_time."' and a.create_time<=".($create_time+86399);
           }else{ //只能查询一天的数据
                $where .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
           }
           if($brand_name){
                $where .=" and l.brand_name = '".$brand_name."'";
           }
           if($gtid){
                $where .=" and h.gtid = $gtid";
           }
           if($gtid1){
                $where .=" and j.gtid = $gtid1";
           }
           if($gtid2){
                $where .=" and k.gtid = $gtid2";
           }
           if($c_id){
                $where .=" and g.c_id = $c_id";
           }
           if($ctid){
                $where .=" and n.ctid = $ctid";
           }
           if($gname){
                $where .=" and d.name like '%".$gname."%'";
           } 
           if($machining!=""){
                $where .=" and b.machining = $machining";
           }
           $sql ="select k.type_name tname3,j.type_name tname2,h.type_name tname1,
                    b.wgid,d.`name` gname,SUM(b.num1) num,e.unit_name uname
                    from db_purchase a 
                    left join db_purchase_detail b on a.osid = b.osid
                    left join db_wholesale_goods c on c.wgid = b.wgid
                    left join db_goods d on d.gid = c.gid
                    left join db_unit e on e.unit_id = b.unit_id1
                    left join db_client g on g.c_id = a.cid 
                    left join db_goods_type h on c.gtid = h.gtid
                    left join db_goods_type j on c.gtid1 = j.gtid
                    left join db_goods_type k on c.gtid2 = k.gtid
                    left join db_brand l on d.brand_id = l.brand_id
                    left join db_client_type n on g.ctid = n.ctid  ";
            $group=" group by tname3,tname2,tname1,b.wgid,gname,uname ";
            $order =" order by tname3,tname2,tname1 asc";
            $sql = $sql.$where.$group.$order; 
            $list= M("")->query($sql); 
            // dump($sql);
            //数据处理 
            foreach ($list as $k => $v) {
               $wgid = $v['wgid'];
               //根据供应商商品编号获取客户量
               $sql ="select d.`code`,d.`name` cname,SUM(b.num1) num from db_purchase a 
                    left join db_purchase_detail b on a.osid = b.osid
                    left join db_wholesale_goods c on c.wgid = b.wgid
                    left join db_client d on a.cid=d.c_id
                    where b.wgid = $wgid and c.wid = $wid ";
               if($create_time){
                    $sql .=" and a.create_time >= '".$create_time."' and a.create_time<=".($create_time+86399);
               }else{ //只能查询一天的数据
                    $sql .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
               } 
               $sql .=" group by d.`code`,cname order by d.`code` asc"; 
               // dump($sql);
               $code = M("")->query($sql);
               $str =""; 
               $str_code =""; //客户简码
               $str_num =""; //客户数量
               $code_num=0; //客户简码列数
               $count = count($code);
               foreach ($code as $kk => $vo) { 
                    if($kk==0){
                        //第一行
                        $str_code ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td>";
                        $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                        $str_num ="<tr><td style='border-left:none;width:50px;'>数量</td>";
                        $str_num .="<td>".keep2($vo['num'])."</td>";
                        $code_num ++;  
                    }else if($kk%9==0 && $kk!=0){
                        //结束上一行，并生成新的一行 
                        $code_num = 0; //新的一行变为0
                        $str .=$str_code.$str_num."</tr>";
                        $str_code ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td>";
                        $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                        $str_num ="<tr><td style='border-left:none;width:50px;'>数量</td>";
                        $str_num .="<td>".keep2($vo['num'])."</td>";
                        $code_num ++; 
                    }else{
                        //不是第一行也不是新一行 拼接 td
                        $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                        $str_num .="<td>".keep2($vo['num'])."</td>"; 
                        $code_num ++; 
                    }
                    if($kk+1==$count){
                        //最后一个 判断当前客户简码行数是否等于10 结束上一行 
                        if($code_num==10){ 
                           $str .=$str_code.$str_num."</tr>"; 
                        }else{
                           $a = 10-$code_num;
                            for($n=1;$n<=$a;$n++){
                                if($n==$a){ //结束循环 
                                    $str .=$str_code.$str_num."</tr>";
                                }else{
                                    $str_code .="<td>&nbsp;</td>";
                                    $str_num .="<td>&nbsp;</td>";
                                }
                            } 
                        }
                    }
               }
               if($str==""){
                    $str ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
               }
               $list[$k]["code"] =$str; 
            }  
            // dump($list);
            $this->assign("list",$list);  
        }else{
            $this->assign("create_time",date('Y-m-d'));
        } 
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid =$wid")->select();
        $this->assign("tlist",$tlist);
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist); 
        $this->display();
    }

    public function purchasePrint(){
       $wid = getWid(); 
       $where =" where c.wid =$wid ";
       $create_time =I("create_time");$brand_name =I("brand_name");
       $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id");
       $gname=I("gname");$ctid=I("ctid");$machining=I("machining");
       $gtid2=I("gtid2"); $tcaption=I("tcaption");
       $this->assign("tcaption",$tcaption);
       if($create_time){
            $create_time =strtotime($create_time); 
            $where .=" and a.create_time >= '".$create_time."' and a.create_time<=".($create_time+86399);
       }else{ //只能查询一天的数据
            $where .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
       }
       if($brand_name){
            $where .=" and l.brand_name = '".$brand_name."'";
       }
       if($gtid){
            $where .=" and h.gtid = $gtid";
       }
       if($gtid1){
            $where .=" and j.gtid = $gtid1";
       }
       if($gtid2){
            $where .=" and k.gtid = $gtid2";
       }
       if($c_id){
            $where .=" and g.c_id = $c_id";
       }
       if($ctid){
            $where .=" and n.ctid = $ctid";
       }
       if($gname){
            $where .=" and d.name like '%".$gname."%'";
       } 
       if($machining!=""){
            $where .=" and b.machining = $machining";
       }
       $sql ="select k.type_name tname3,j.type_name tname2,h.type_name tname1,
                b.wgid,d.`name` gname,SUM(b.num1) num,e.unit_name uname
                from db_purchase a 
                left join db_purchase_detail b on a.osid = b.osid
                left join db_wholesale_goods c on c.wgid = b.wgid
                left join db_goods d on d.gid = c.gid
                left join db_unit e on e.unit_id = b.unit_id1
                left join db_client g on g.c_id = a.cid 
                left join db_goods_type h on c.gtid = h.gtid
                left join db_goods_type j on c.gtid1 = j.gtid
                left join db_goods_type k on c.gtid2 = k.gtid
                left join db_brand l on d.brand_id = l.brand_id
                left join db_client_type n on g.ctid = n.ctid  ";
        $group=" group by tname3,tname2,tname1,b.wgid,gname,uname ";
        $order =" order by tname3,tname2,tname1 asc";
        $sql = $sql.$where.$group.$order; 
        $list= M("")->query($sql); 
        //数据处理 
        foreach ($list as $k => $v) {
           $wgid = $v['wgid'];
           //根据供应商商品编号获取客户量
           $sql ="select d.`code`,d.`name` cname,SUM(b.num1) num from db_purchase a 
                left join db_purchase_detail b on a.osid = b.osid
                left join db_wholesale_goods c on c.wgid = b.wgid
                left join db_client d on a.cid=d.c_id
                where b.wgid = $wgid and c.wid = $wid ";
           if($create_time){
                $sql .=" and a.create_time >= '".$create_time."' and a.create_time<=".($create_time+86399);
           }else{ //只能查询一天的数据
                $sql .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
           } 
           $sql .=" group by d.`code`,cname order by d.`code` asc"; 
           // dump($sql);
           $code = M("")->query($sql);
           $str =""; 
           $str_code =""; //客户简码
           $str_num =""; //客户数量
           $code_num=0; //客户简码列数
           $count = count($code);
           foreach ($code as $kk => $vo) { 
                if($kk==0){
                    //第一行
                    $str_code ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td>";
                    $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                    $str_num ="<tr class='num-tr'><td style='border-left:none;width:50px;'>数量</td>";
                    $str_num .="<td>".keep2($vo['num'])."</td>";
                    $code_num ++;  
                }else if($kk%9==0 && $kk!=0){
                    //结束上一行，并生成新的一行 
                    $code_num = 0; //新的一行变为0
                    $str .=$str_code.$str_num."</tr>";
                    $str_code ="<tr><td style='border-left:none;width:50px;'>客户</td>";
                    $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                    $str_num ="<tr class='num-tr'><td style='border-left:none;width:50px;'>数量</td>";
                    $str_num .="<td>".keep2($vo['num'])."</td>";
                    $code_num ++; 
                }else{
                    //不是第一行也不是新一行 拼接 td
                    $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                    $str_num .="<td>".keep2($vo['num'])."</td>"; 
                    $code_num ++; 
                }
                if($kk+1==$count){
                    //最后一个 判断当前客户简码行数是否等于10 结束上一行 
                    if($code_num==10){ 
                       $str .=$str_code.$str_num."</tr>"; 
                    }else{
                       $a = 10-$code_num;
                        for($n=1;$n<=$a;$n++){
                            if($n==$a){ //结束循环 
                                $str .=$str_code.$str_num."</tr>";
                            }else{
                                $str_code .="<td>&nbsp;</td>";
                                $str_num .="<td>&nbsp;</td>";
                            }
                        } 
                    }
                }
           }
           if($str==""){
                $str ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
           }
           $list[$k]["code"] =$str; 
        }  
        $nlist = array();   
        $i=0;
        foreach ($list as $k => $v) { 
            if($k%15==0 && $k>0){
                $i++;
            }
            $nlist[$i]['list'][$k]=$v; 
        }   
        $this->assign("nlist",$nlist);
        // dump($list);
        
        $this->assign("list",$list);   
        $this->display();
    }
    //分拣单
    public function sortingReport(){
        $wid = getWid();
        if(IS_POST){
           $where =" where c.wid =$wid ";
           $create_time =I("create_time");$brand_name =I("brand_name");
           $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id");
           $gname=I("gname");$ctid=I("ctid");$machining=I("machining");$gtid2=I("gtid2");
           $this->assign("create_time",$create_time); $this->assign("brand_name",$brand_name);
           $this->assign("ctid",$ctid);$this->assign("c_id",$c_id);
           $this->assign("gtid",$gtid);$this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);
           $this->assign("gname",$gname);$this->assign("c_id",$c_id); $this->assign("machining",$machining); 
           
           if($create_time){
                $create_time =strtotime($create_time); 
                $where .=" and a.create_time >= '".$create_time."' and a.create_time<=".($create_time+86399);
           }else{ //只能查询一天的数据
                $where .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
           }
           if($brand_name){
                $where .=" and l.brand_name = '".$brand_name."'";
           }
           if($gtid){
                $where .=" and h.gtid = $gtid";
           }
           if($gtid1){
                $where .=" and j.gtid = $gtid1";
           }
           if($gtid2){
                $where .=" and k.gtid = $gtid2";
           }
           if($c_id){
                $where .=" and g.c_id = $c_id";
           }
           if($ctid){
                $where .=" and n.ctid = $ctid";
           }
           if($gname){
                $where .=" and d.name like '%".$gname."%'";
           } 
           if($machining!=""){
                $where .=" and b.machining = $machining";
           }
           $sql ="select k.type_name tname3,j.type_name tname2,h.type_name tname1,
                    b.wgid,d.`name` gname,SUM(b.num1) num,e.unit_name uname
                    from db_purchase a 
                    left join db_purchase_detail b on a.osid = b.osid
                    left join db_wholesale_goods c on c.wgid = b.wgid
                    left join db_goods d on d.gid = c.gid
                    left join db_unit e on e.unit_id = b.unit_id1
                    left join db_client g on g.c_id = a.cid 
                    left join db_goods_type h on c.gtid = h.gtid
                    left join db_goods_type j on c.gtid1 = j.gtid
                    left join db_goods_type k on c.gtid2 = k.gtid
                    left join db_brand l on d.brand_id = l.brand_id
                    left join db_client_type n on g.ctid = n.ctid  ";
            $group=" group by tname3,tname2,tname1,b.wgid,gname,uname ";
            $order =" order by tname3,tname2,tname1 asc";
            $sql = $sql.$where.$group.$order; 
            $list= M("")->query($sql); 
            // dump($sql);
            //数据处理 
            foreach ($list as $k => $v) {
               $wgid = $v['wgid'];
               //根据供应商商品编号获取客户量
               $sql ="select d.`code`,d.`name` cname,SUM(b.num1) num from db_purchase a 
                    left join db_purchase_detail b on a.osid = b.osid
                    left join db_wholesale_goods c on c.wgid = b.wgid
                    left join db_client d on a.cid=d.c_id
                    where b.wgid = $wgid and c.wid = $wid ";
               if($create_time){
                    $sql .=" and a.create_time >= '".$create_time."' and a.create_time<=".($create_time+86399);
               }else{ //只能查询一天的数据
                    $sql .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
               } 
               $sql .=" group by d.`code`,cname order by d.`code` asc"; 
               // dump($sql);
               $code = M("")->query($sql);
               $str =""; 
               $str_code =""; //客户简码
               $str_num =""; //客户数量
               $code_num=0; //客户简码列数
               $count = count($code);
               foreach ($code as $kk => $vo) { 
                    if($kk==0){
                        //第一行
                        $str_code ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td>";
                        $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                        $str_num ="<tr><td style='border-left:none;width:50px;'>数量</td>";
                        $str_num .="<td>".keep2($vo['num'])."</td>";
                        $code_num ++;  
                    }else if($kk%16==0 && $kk!=0){
                        //结束上一行，并生成新的一行 
                        $code_num = 0; //新的一行变为0
                        $str .=$str_code.$str_num."</tr>";
                        $str_code ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td>";
                        $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                        $str_num ="<tr><td style='border-left:none;width:50px;'>数量</td>";
                        $str_num .="<td>".keep2($vo['num'])."</td>";
                        $code_num ++; 
                    }else{
                        //不是第一行也不是新一行 拼接 td
                        $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                        $str_num .="<td>".keep2($vo['num'])."</td>"; 
                        $code_num ++; 
                    }
                    if($kk+1==$count){
                        //最后一个 判断当前客户简码行数是否等于18 结束上一行 
                        if($code_num==17){ 
                           $str .=$str_code.$str_num."</tr>"; 
                        }else{
                           $a = 17-$code_num;
                            for($n=1;$n<=$a;$n++){
                                if($n==$a){ //结束循环 
                                    $str .=$str_code.$str_num."</tr>";
                                }else{
                                    $str_code .="<td>&nbsp;</td>";
                                    $str_num .="<td>&nbsp;</td>";
                                }
                            } 
                        }
                    }
               }
               if($str==""){
                    $str ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
               }
               $list[$k]["code"] =$str; 
            }  
            // dump($list);
            $this->assign("list",$list);  
        }else{
            $this->assign("create_time",date('Y-m-d'));
        } 
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid =$wid")->select();
        $this->assign("tlist",$tlist);
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist); 
        $this->display();
    }

    //分拣单打印
    public function sortingPrint(){
       $wid = getWid(); 
       $where =" where c.wid =$wid ";
       $create_time =I("create_time");$brand_name =I("brand_name");
       $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id");
       $gname=I("gname");$ctid=I("ctid");$machining=I("machining");
       $gtid2=I("gtid2"); $tcaption=I("tcaption");
       $this->assign("tcaption",$tcaption);
       if($create_time){
            $create_time =strtotime($create_time); 
            $where .=" and a.create_time >= '".$create_time."' and a.create_time<=".($create_time+86399);
       }else{ //只能查询一天的数据
            $where .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
       }
       if($brand_name){
            $where .=" and l.brand_name = '".$brand_name."'";
       }
       if($gtid){
            $where .=" and h.gtid = $gtid";
       }
       if($gtid1){
            $where .=" and j.gtid = $gtid1";
       }
       if($gtid2){
            $where .=" and k.gtid = $gtid2";
       }
       if($c_id){
            $where .=" and g.c_id = $c_id";
       }
       if($ctid){
            $where .=" and n.ctid = $ctid";
       }
       if($gname){
            $where .=" and d.name like '%".$gname."%'";
       } 
       if($machining!=""){
            $where .=" and b.machining = $machining";
       }
       $sql ="select k.type_name tname3,j.type_name tname2,h.type_name tname1,
                b.wgid,d.`name` gname,SUM(b.num1) num,e.unit_name uname
                from db_purchase a 
                left join db_purchase_detail b on a.osid = b.osid
                left join db_wholesale_goods c on c.wgid = b.wgid
                left join db_goods d on d.gid = c.gid
                left join db_unit e on e.unit_id = b.unit_id1
                left join db_client g on g.c_id = a.cid 
                left join db_goods_type h on c.gtid = h.gtid
                left join db_goods_type j on c.gtid1 = j.gtid
                left join db_goods_type k on c.gtid2 = k.gtid
                left join db_brand l on d.brand_id = l.brand_id
                left join db_client_type n on g.ctid = n.ctid  ";
        $group=" group by tname3,tname2,tname1,b.wgid,gname,uname ";
        $order =" order by tname3,tname2,tname1 asc";
        $sql = $sql.$where.$group.$order; 
        $list= M("")->query($sql); 
        //数据处理 
        foreach ($list as $k => $v) {
           $wgid = $v['wgid'];
           //根据供应商商品编号获取客户量
           $sql ="select d.`code`,d.`name` cname,SUM(b.num1) num from db_purchase a 
                left join db_purchase_detail b on a.osid = b.osid
                left join db_wholesale_goods c on c.wgid = b.wgid
                left join db_client d on a.cid=d.c_id
                where b.wgid = $wgid and c.wid = $wid ";
           if($create_time){
                $sql .=" and a.create_time >= '".$create_time."' and a.create_time<=".($create_time+86399);
           }else{ //只能查询一天的数据
                $sql .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
           } 
           $sql .=" group by d.`code`,cname order by d.`code` asc"; 
           // dump($sql);
           $code = M("")->query($sql);
           $str =""; 
           $str_code =""; //客户简码
           $str_num =""; //客户数量
           $code_num=0; //客户简码列数
           $count = count($code);
           foreach ($code as $kk => $vo) { 
                if($kk==0){
                    //第一行
                    $str_code ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td>";
                    $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                    $str_num ="<tr class='num-tr'><td style='border-left:none;width:50px;'>数量</td>";
                    $str_num .="<td>".keep2($vo['num'])."</td>";
                    $code_num ++;  
                }else if($kk%16==0 && $kk!=0){
                    //结束上一行，并生成新的一行 
                    $code_num = 0; //新的一行变为0
                    $str .=$str_code.$str_num."</tr>";
                    $str_code ="<tr><td style='border-left:none;width:50px;'>客户</td>";
                    $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                    $str_num ="<tr class='num-tr'><td style='border-left:none;width:50px;'>数量</td>";
                    $str_num .="<td>".keep2($vo['num'])."</td>";
                    $code_num ++; 
                }else{
                    //不是第一行也不是新一行 拼接 td
                    $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                    $str_num .="<td>".keep2($vo['num'])."</td>"; 
                    $code_num ++; 
                }
                if($kk+1==$count){
                    //最后一个 判断当前客户简码行数是否等于17 结束上一行 
                    if($code_num==17){ 
                       $str .=$str_code.$str_num."</tr>"; 
                    }else{
                       $a = 17-$code_num;
                        for($n=1;$n<=$a;$n++){
                            if($n==$a){ //结束循环 
                                $str .=$str_code.$str_num."</tr>";
                            }else{
                                $str_code .="<td>&nbsp;</td>";
                                $str_num .="<td>&nbsp;</td>";
                            }
                        } 
                    }
                }
           }
           if($str==""){
                $str ="<tr class='code-tr'><td style='border-left:none;width:50px;'>客户</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
           }
           $list[$k]["code"] =$str; 
        }  
        $nlist = array();   
        $i=0;
        foreach ($list as $k => $v) { 
            if($k%16==0 && $k>0){
                $i++;
            }
            $nlist[$i]['list'][$k]=$v; 
        }   
        $this->assign("nlist",$nlist);
        // dump($list);
        
        $this->assign("list",$list);   
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
 
}