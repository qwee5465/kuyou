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
    //订单
    public function purchaseForm()
    {     
        $wid = getWid();
        $osid ="PC".$wid."_".time();
        $this->assign("time",time());
        $this->assign("osid",$osid);  
        $osname = getUnitName()."订单";
        $this->assign("osname",$osname); 

        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $osList = M("oscd")->where("wid =$wid")->find();
        if(!$osList){
            $osList = getOutStockColumnT();
        }   
        $titles = $this->getOutStockTitleInfoT($wid);  
        $this->assign("titles",$titles);
        $this->assign("osList",$osList);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $osbList = M("oscdb")->where("wid =$wid")->find();
        if(!$osbList){
            $osbList = getOutStockColumn();
        }   
        $t_list = $this->getOutStockTitleInfo($wid); 
        $this->assign("t_list",$t_list);
        $this->assign("osbList",$osbList);   
        // dump($t_list);dump($osbList);
        $this->display();
    } 

     //获取商品表格头部标题
    public function getOutStockTitleInfoT($wid){
        $columns = getOutStockColumnNameT(); 
        $titles = getOutStockColumnTitleT();
        $oscd = M("oscd")->where("wid=$wid")->find();
        $arr =array();
        foreach ($columns as $k => $v) {
            if(!empty($oscd)){
                if(empty($oscd[$v])){
                    $arr[$v]=$titles[$v]; //显示名称 
                }
                else{
                    $arr[$v]=$oscd[$v]; //显示名称
                } 
            }else{
                $arr[$v]=$titles[$v];
            } 
        }  
        return $arr;
    }

    //获取商品表格标题
    public function getOutStockTitleInfo($wid){
        $columns = getOutStockColumnName(); 
        $titles = getOutStockColumnTitle();
        $oscdb = M("oscdb")->where("wid=$wid")->find();
        $arr =array();
        foreach ($columns as $k => $v) {
            if(!empty($oscdb)){
                if(empty($oscdb[$v])){
                    $arr[$v]=$titles[$v]; //显示名称 
                }
                else{
                    $arr[$v]=$oscdb[$v]; //显示名称
                } 
            }else{
                $arr[$v]=$titles[$v];
            } 
        }  
        return $arr;
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

    //订单数据验证
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
            if(empty($list['num1s'])){ //数量字符串是空  
                return false;
            }else{
               $nums = $list['num1s']; 
               foreach ($nums as $key => $vo) { 
                    if(!is_numeric($vo)){//判断是否为数字 整数或浮点数
                        return false;
                    }
               }
            }
            return true;
        }else{
            return false;
        }
    }

    //货损提交验证
    public function cdCheckFrom($list){
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

   


    //删除订单 
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
 

    //提交订单
    public function purchaseAdd(){
        if(IS_POST){
            //数据验证
            $data = I("post.");  
            $wid = getWid();
            $list =array();
            //gids,unit_id1s,num1s,prices,unit_id2s,num2s,unit_id3s,num3s,unit_id4s,num4s
            $list['wgids'] =$this->strToArray(I("wgids"));   
            $list['unit_id1s'] =$this->strToArray(I("unit_ids"));
            $list['num1s'] =$this->strToArray(I("num1s")); 
            $list['num2s'] =$this->strToArray(I("num2s")); 
            $list['prices'] =$this->strToArray(I("prices"));   
            $list['thans'] =$this->strToArray(I("thans"));  
            $list['sums'] =$this->strToArray(I("sums"));  
            $list['cdnums'] =$this->strToArray(I("cdnums"));
            $list['remarks']=$this->strToArray(I("remarks"));
            $status = I("status"); 
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids")); 
            $list['nei_nums'] =$this->strToArray(I("nei_nums")); 
            $list['machinings'] =$this->strToArray(I("machinings"));
            $list['alias'] =$this->strToArray(I("alias")); 
            $list['p_remarks'] =$this->strToArray(I("p_remarks"));
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
                        $price1 = floatval($list['prices'][$k])/floatval($list['nei_nums'][$k]);
                        $num1 =floatval($list['num1s'][$k])*floatval($list['nei_nums'][$k]);
                        $num2 =floatval($list['num2s'][$k])*floatval($list['nei_nums'][$k]);
                        $cd_num =floatval($list['cdnums'][$k])*floatval($list['nei_nums'][$k]);
                        $unit_id = floatval($list['unit_id1s'][$k]);
                        if($unit_id<=0){
                            $arr_result['resultcode'] = 10;
                            $arr_result['msg'] = "数据异常，第".($k+1)."行,默认单位异常。";  
                            $arr_result['err_row'] =$k+1;
                            break;
                        }
                        $slist = M("wholesale_goods")->field('sid')->where("wgid=".$list['wgids'][$k])->find();
                        $p_sid = $slist['sid'];
                        //订单详情新增
                        $sublist = array(
                            'osid'    =>$osid,
                            'wgid'    =>$list['wgids'][$k],
                            'alias'   =>$list['alias'][$k],
                            'than'    =>$list['thans'][$k], 
                            'cd_num'  =>$cd_num,
                            'num1'    =>$num1,
                            'num2'    =>$num2,
                            'price'   =>$price1,
                            'unit_id1'=>$list['unit_id1s'][$k],
                            'remark'=>$list['remarks'][$k],
                            'nei_num'=>$list['nei_nums'][$k],
                            'nei_unit_id'=>$list['nei_unit_ids'][$k],
                            'nei_price'=>$list['prices'][$k],
                            'sales_amount' => $list['sums'][$k],
                            'machining'=>$list['machinings'][$k],
                            'p_remark'=>$list['p_remarks'][$k],
                            'p_sid'=>$p_sid
                        ); 
                        
                        $out_id = $mm->add($sublist);
                        if($out_id<=0){
                            $arr_result['resultcode'] = 10;
                            $arr_result['msg'] = "新增采购详情时失败了";  
                        }  
                    }   
                }else{
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "新增采购单时失败了"; 
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
            $list['wgids'] =$this->strToArray(I("wgids"));
            $list['unit_id1s'] =$this->strToArray(I("unit_ids"));
            $list['num1s'] =$this->strToArray(I("num1s")); 
            $list['num2s'] =$this->strToArray(I("num2s")); 
            $list['prices'] =$this->strToArray(I("prices"));   
            $list['thans'] =$this->strToArray(I("thans"));  
            $list['sums'] =$this->strToArray(I("sums"));  
            $list['cdnums'] =$this->strToArray(I("cdnums"));
            $list['remarks']=$this->strToArray(I("remarks"));
            $status = I("status"); 
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids")); 
            $list['nei_nums'] =$this->strToArray(I("nei_nums")); 
            $list['machinings'] =$this->strToArray(I("machinings"));
            $list['alias'] =$this->strToArray(I("alias"));
            $list['p_remarks'] =$this->strToArray(I("p_remarks"));
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
                      $arr_result['msg'] = "删除订单详情时失败了";  
                    }else{
                        //详情录入
                        foreach ($list['wgids'] as $k => $v) {
                           //计算默认单价
                            $price1 = floatval($list['prices'][$k])/floatval($list['nei_nums'][$k]);
                            $num1 =floatval($list['num1s'][$k])*floatval($list['nei_nums'][$k]);
                            $num2 =floatval($list['num2s'][$k])*floatval($list['nei_nums'][$k]);
                            $cd_num =floatval($list['cdnums'][$k])*floatval($list['nei_nums'][$k]);
                            $unit_id = floatval($list['unit_id1s'][$k]);
                            if($unit_id<=0){
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] = "数据异常，第".($k+1)."行,默认单位异常。";  
                                $arr_result['err_row'] =$k+1;
                                break;
                            }
                            $slist = M("wholesale_goods")->field('sid')->where("wgid=".$list['wgids'][$k])->find();
                            $p_sid = $slist['sid'];
                            //详情新增
                            $sublist = array(
                                'osid'    =>$osid,
                                'wgid'    =>$list['wgids'][$k],
                                'alias'    =>$list['alias'][$k],
                                'than'    =>$list['thans'][$k], 
                                'cd_num'    =>$cd_num,
                                'num1'    =>$num1,
                                'num2'    =>$num2,
                                'price'   =>$price1,
                                'unit_id1'=>$list['unit_id1s'][$k],
                                'remark'=>$list['remarks'][$k],
                                'nei_num'=>$list['nei_nums'][$k],
                                'nei_unit_id'=>$list['nei_unit_ids'][$k],
                                'nei_price'=>$list['prices'][$k],
                                'sales_amount' => $list['sums'][$k],
                                'machining'=>$list['machinings'][$k],
                                'p_remark'=>$list['p_remarks'][$k],
                                'p_sid'=>$p_sid
                            );  
                            //出库时设置成本金额以供 财务分析报表 查询 和其他作用
                            $sublist["cost"] = $this->getCost($list['wgids'][$k],$list['num1s'][$k],$list['cdnums'][$k]);
                            $out_id = $mm->add($sublist);  
                            if($out_id<=0){
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] = "新增订单详情时失败了";  
                            }  
                        }  
                    }
                }else{ 
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "新增订单时失败了"; 
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
                $m = M("out_stock"); 
                $llist = $m->where("osid = '$purchase_id'")->find(); 
                if($list['status']==1 || !empty($llist)){
                    $osid = "PC".$wid."_".time();//新出库单据号
                }
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'生成成功,出库单据号为：'.$osid,
                );  
                $data = array(
                    'osid' => $osid, //新出库单据号
                    'osname' => getUnitName()."配送单",
                    'cid' => $list['cid'], 
                    'total' => $list['total'],
                    'remark' => $list['remark'],
                    'create_id' => $wid,
                    'create_time' => $list['create_time'],
                    'update_time' => time(),
                    'update_id' => $wid,
                    'source'=>1,
                    'purchase_id'=>$purchase_id, //订单据号
                );  
                $result = $m->add($data);
                if($result){  
                    $sql ="select a.*,b.price*a.nei_num j_price from db_purchase_detail a left join db_stock b on a.wgid = b.wgid where a.osid ='$purchase_id' order by a.out_id asc";
                    $dlist = M()->query($sql);
                    // $dlist = M("purchase_detail")->where("osid = '$purchase_id'")->select();
                    if($dlist){
                        $mm = M("out_stock_detail");  
                        foreach ($dlist as $k => $v) {
                            $sublist = array(
                                'osid'    =>$osid,
                                'wgid'    =>$v['wgid'],
                                'alias'   =>$v['alias'],
                                'than'    =>$v['than'], 
                                'cd_num'  =>$v['cd_num'],
                                'num1'    =>$v['num1'],
                                'num2'    =>$v['num2'],
                                'price'   =>$v['price'],
                                'j_price' =>$v['j_price'],
                                'unit_id1'=>$v['unit_id1'],
                                // 'remark'=>$v['remark'],   统一先不生成，做数据列设置
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
                        $arr_result['msg'] = "未发现该订单详情";
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
                $arr_result['msg']="未发现该订单";
            }  
            $this->ajaxReturn($arr_result); 
        } 
    }

   


    //订单编辑加载页
    public function purchaseEdit(){
        $wid = getWid();
        $osid =I("osid");
        $sql ="select g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,aa.osname,bb.`name` cname,bb.than hid_than,b.`name` gname,aa.total,d.unit_name unit_name1,e.unit_name unit_name2,a.sales_amount totalx,a.* FROM   db_purchase aa LEFT JOIN db_client bb ON aa.cid = bb.c_id LEFT JOIN db_purchase_detail a ON a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid LEFT JOIN db_brand f ON b.brand_id = f.brand_id LEFT JOIN db_unit d ON a.nei_unit_id = d.unit_id LEFT JOIN db_unit e ON a.unit_id1 = e.unit_id LEFT JOIN db_stock g ON a.wgid=g.wgid WHERE a.osid = '$osid' ORDER BY a.out_id ASC";
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

        // dump($list);
        $this->assign("time",time());
        $this->assign("osid",$osid);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $osList = M("oscd")->where("wid =$wid")->find();
        if(!$osList){
            $osList = getOutStockColumnT();
        }
        $titles = $this->getOutStockTitleInfoT($wid);
        $this->assign("titles",$titles);
        $this->assign("osList",$osList);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $osbList = M("oscdb")->where("wid =$wid")->find();
        if(!$osbList){
            $osbList = getOutStockColumn();
        }
        $t_list = $this->getOutStockTitleInfo($wid);
        $this->assign('list',$list);
//        dump($list);
        $this->assign("t_list",$t_list);
        $this->assign("osbList",$osbList);
        // dump($t_list);dump($osbList);
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

    //修改订单据名称
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
        $this->assign("osid",$osid);
        $this->assign("list",$list);
        $this->assign("status",$list[0]['status']); 
        $this->display();
    } 

    public function orderPrint(){ 
        $osid = I("osid");
        $sql ="select aa.osname,g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,bb.`name` cname,bb.phone,bb.pid pid2,bb.cid cid1,bb.did,bb.street,b.`name` gname,aa.total,h.unit_name uname,a.num1*a.price*a.than totalx,a.*,aa.`status`,jj.contacts FROM db_purchase aa left join db_client bb on aa.cid = bb.c_id left join db_purchase_detail a on a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid Left JOIN db_brand f ON b.brand_id =f.brand_id LEFT JOIN db_unit d ON a.unit_id1 = d.unit_id left join db_stock g on g.wgid = a.wgid LEFT JOIN db_unit h ON h.unit_id = a.nei_unit_id LEFT JOIN db_wholesale_user jj on aa.create_id = jj.wid WHERE a.osid = '".$osid."' order by a.out_id asc";
        $list = M('')->query($sql);
        $nlist = array();   
        $i=0;
        foreach ($list as $k => $v) { 
            if($k%30==0 && $k>0){
                $i++;
            }
            $nlist[$i]['list'][$k]=$v; 
        }     

        $this->assign("osid",$osid);
        $this->assign("list",$list);
        // dump($list);
        $this->assign("nlist",$nlist);
        $this->assign("nlist_length",count($nlist));
       
        $this->assign("status",$list[0]['status']); 
        $unit_name = getUnitName();
        $wid = getWid();
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $osList = M("oscd")->where("wid =$wid")->find();
        if(!$osList){
            $osList = getOutStockColumnT();
        }
        $titles = $this->getOutStockTitleInfoT($wid);
        $this->assign("titles",$titles);
        $this->assign("osList",$osList);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $osbList = M("oscdb")->where("wid =$wid")->find();
        if(!$osbList){
            $osbList = getOutStockColumn();
        }
        $t_list = $this->getOutStockTitleInfo($wid);
        $this->assign("t_list",$t_list);
        $this->assign("osbList",$osbList);
       // dump($osbList);
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

    //导出订单
    public function exportOrder(){
        $osid = I("osid"); 
        $sql ="select g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,bb.`name` cname,bb.phone,bb.pid,bb.cid,bb.did,bb.street,b.`name` gname,aa.total,h.unit_name uname,a.num1*a.price*a.than totalx,a.*,aa.`status`,jj.contacts FROM db_purchase aa left join db_client bb on aa.cid = bb.c_id left join db_purchase_detail a on a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid Left JOIN db_brand f ON b.brand_id =f.brand_id LEFT JOIN db_unit d ON a.unit_id1 = d.unit_id left join db_stock g on g.wgid = a.wgid LEFT JOIN db_unit h ON h.unit_id = a.nei_unit_id LEFT JOIN db_wholesale_user jj on aa.create_id = jj.wid WHERE a.osid = '".$osid."' order by a.wgid asc";
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
        $filename="订单".$osid;
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
        
        $sql ="select b.`name` cname,  a.* FROM db_purchase a left join db_client b on a.cid=b.c_id";
        if($gname){ 
            $sql .=" inner join(SELECT osid FROM db_purchase_detail a inner JOIN db_wholesale_goods b ON a.wgid=b.wgid inner JOIN db_goods c ON b.gid=c.gid WHERE b.wid=$wid AND c.`name`='".$gname."' GROUP BY a.osid) c on a.osid = c.osid";
        }
        $sql .= " where a.create_id = $wid";
        $sql =$sql.$where; 
        $sql .= " order by a.create_time desc";    
        $list =$m->query($sql);  
        $client = M("client");
        $clist = $client->where("create_id = $wid")->select();
        $this->assign("clist",$clist);
        $this->assign("list",$list); 
        $this->display();
    } 

    //按商品采购单
    public function purchaseReport()
    {     
        $wid = getWid(); 
        $this->assign("create_time",date('Y-m-d')); 
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid =$wid")->select();
        $this->assign("tlist",$tlist);
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist); 
        //供应商类型
        $stlist = M("supplier_type")->where("wid =$wid")->select();
        $this->assign("stlist",$stlist);
        $this->display();
    }

    public function getPurchaseReportInfo(){
        if(IS_POST){
            $wid = getWid(); 
            $where =" where c.wid =$wid ";
            $create_time =I("create_time");
            $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id");
            $gname=I("gname");$ctid=I("ctid");$gtid2=I("gtid2");
            $stid =I("stid"); $sid=I("sid");$hid_order =I("hid_order"); 
            if($create_time){
                $create_time =strtotime($create_time); 
                $where .=" and a.create_time >= '".$create_time."' and a.create_time<=".($create_time+86399);
            }else{ //只能查询一天的数据
                $where .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
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
            if($stid!=""){
                $where .=" and r.stid = $stid";
            }
            if($sid!=""){
                $where .=" and w.sid = $sid";
            }
            $sql ="select w.name sname,k.type_name tname3,j.type_name tname2,h.type_name tname1,
                    b.wgid,d.`name` gname,b.num1 num,b.num1/b.nei_num num1,g.`code`,b.p_remark,b.remark,ee.unit_name jcuname,e.unit_name uname,t.num1 stock_num
                    from db_purchase a 
                    left join db_purchase_detail b on a.osid = b.osid
                    left join db_wholesale_goods c on c.wgid = b.wgid
                    left join db_goods d on d.gid = c.gid
                    left join db_unit e on e.unit_id = b.nei_unit_id
                    left join db_unit ee on ee.unit_id = b.unit_id1
                    left join db_client g on g.c_id = a.cid 
                    left join db_goods_type h on c.gtid = h.gtid
                    left join db_goods_type j on c.gtid1 = j.gtid
                    left join db_goods_type k on c.gtid2 = k.gtid
                    left join db_brand l on d.brand_id = l.brand_id
                    left join db_client_type n on g.ctid = n.ctid  
                    LEFT JOIN db_supplier w on w.sid= c.sid
                    LEFT JOIN db_supplier_type r ON r.stid =w.stid 
                    LEFT JOIN db_stock t ON t.wgid = b.wgid";
            $order =" order by sname,gname asc";
            $orderArr =array();
            if($hid_order)
               $orderArr = explode("|",$hid_order);
            if($orderArr[0]!=""){  
                if($orderArr[0]=="gname"){
                    $order=" order by gname ".$orderArr[1]; 
                }
                if($orderArr[0]=="sname")
                    $order=" order by w.sid,gname ".$orderArr[1]; 
                if($orderArr[0]=="gtname")
                    $order=" order by tname3,tname2,tname1,gname ".$orderArr[1];
            }  
            $sql = $sql.$where.$group.$order; 
            $list= M("")->query($sql); 
            // $this->ajaxReturn($list);
            if(!empty($list)){
                $list = $this->purchaseReportDataHandle($list);     
                $this->ajaxReturn(ReturnJSON(0,$list));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7)); 
        }
    }

     //采购单数据处理 
    public function purchaseReportDataHandle($data){
        //获取系统参数 单位换算字段  
        $result=array();
        if(!empty($data)){ 
            $clist =array();
            $i=0; $n=0;$temp="";  
            //数据处理 合并相同名称商品  
            foreach ($data as $k => $v) {  
                if($k==0){ //第一次  
                    $clist[$i]=array();
                    $clist[$i]['sname']=$v['sname'];
                    $clist[$i]['tname1']=$v['tname1'];  
                    $clist[$i]['tname2']=$v['tname2'];  
                    $clist[$i]['tname3']=$v['tname3'];   
                    $clist[$i]['gname']=$v['gname'];  
                    $clist[$i]['wgid']=$v['wgid']; 
                    $clist[$i]['jcuname']=$v['jcuname']; 
                    $clist[$i]['stock_num']=$v['stock_num'];
                    $clist[$i]['clist']=array(); 
                    $clist[$i]['clist'][$n]=array();
                    $clist[$i]['clist'][$n]['code']=$v['code'];
                    $clist[$i]['clist'][$n]['num']=$v['num'];
                    $clist[$i]['clist'][$n]['num1']=$v['num1'];
                    $clist[$i]['clist'][$n]['uname']=$v['uname'];
                    $clist[$i]['clist'][$n]['remark']=$v['remark'];
                    $clist[$i]['clist'][$n]['p_remark']=$v['p_remark'];  
                    $n++;
                } 
                if($temp==$v['gname']){
                    $clist[$i]['clist'][$n]=array();
                    $clist[$i]['clist'][$n]['code']=$v['code'];
                    $clist[$i]['clist'][$n]['num']=$v['num'];
                    $clist[$i]['clist'][$n]['num1']=$v['num1'];
                    $clist[$i]['clist'][$n]['uname']=$v['uname'];
                    $clist[$i]['clist'][$n]['remark']=$v['remark'];
                    $clist[$i]['clist'][$n]['p_remark']=$v['p_remark'];  
                    $n++;
                }else if($temp!=$v['gname'] && $k!=0){
                    $i++;
                    $n=0;
                    $clist[$i]=array();
                    $clist[$i]['sname']=$v['sname'];
                    $clist[$i]['tname1']=$v['tname1'];  
                    $clist[$i]['tname2']=$v['tname2'];  
                    $clist[$i]['tname3']=$v['tname3']; 
                    $clist[$i]['gname']=$v['gname'];  
                    $clist[$i]['wgid']=$v['wgid'];  
                    $clist[$i]['jcuname']=$v['jcuname']; 
                    $clist[$i]['stock_num']=$v['stock_num'];
                    $clist[$i]['clist']=array(); 
                    $clist[$i]['clist'][$n]=array();
                    $clist[$i]['clist'][$n]['code']=$v['code'];
                    $clist[$i]['clist'][$n]['num']=$v['num'];
                    $clist[$i]['clist'][$n]['num1']=$v['num1'];
                    $clist[$i]['clist'][$n]['uname']=$v['uname'];
                    $clist[$i]['clist'][$n]['remark']=$v['remark'];
                    $clist[$i]['clist'][$n]['p_remark']=$v['p_remark'];
                    $n++;
                } 
                $temp=$v['gname'];
            }
            foreach ($clist as $k => $v) {
                $sum_num = 0;
                foreach ($clist[$k]['clist'] as $kk => $vo) { 
                    $sum_num += floatval($vo['num']);
                }
                $clist[$k]['sum_num'] =$sum_num;
                $clist[$k]['sg']=$clist[$k]['sum_num'] - $v['stock_num'];
            } 
            
            $wid = getWid();
            $system_param=M("system_param")->where("wid=$wid")->find();
            if(empty($system_param)){
                $system_param =getSystemParam();
            }    
            //处理字段 
            foreach ($clist as $k => $v) {
                //处理数量 
                $clist[$k]['stock_num'] = unitConvert($system_param,customDecimal('num',$v['stock_num']),$v['jcuname']);   
                $clist[$k]['sum_num'] = unitConvert($system_param,customDecimal('num',$v['sum_num']),$v['jcuname']); 
                $clist[$k]['sg'] = unitConvert($system_param,customDecimal('num',$v['sg']),$v['jcuname']); 
                //处理单位转换   
                foreach ($v['clist'] as $kk => $vo) {
                    $num1 =unitConvert($system_param,customDecimal('num',$vo['num1']),$vo['uname']);  
                    $clist[$k]['clist'][$kk]['num1'] = $num1;
                }
            }
            $result = $clist; 
        } 
        return $result;
    } 

    //按客户采购单
    public function purchaseReport1(){
        $wid = getWid();
        if(IS_POST){
           $where =" where c.wid =$wid ";
           $create_time =I("create_time");$brand_name =I("brand_name");
           $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id");
           $gname=I("gname");$ctid=I("ctid");$machining=I("machining");$gtid2=I("gtid2");
           $stid =I("stid"); $sid=I("sid");$hid_order =I("hid_order");
           $this->assign("create_time",$create_time); $this->assign("brand_name",$brand_name);
           $this->assign("ctid",$ctid);$this->assign("c_id",$c_id);
           $this->assign("gtid",$gtid);$this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);
           $this->assign("gname",$gname);$this->assign("c_id",$c_id); $this->assign("machining",$machining); 
           $this->assign("stid",$stid);$this->assign("sid",$sid);$this->assign("hid_order",$hid_order);
           if($create_time){
                $create_time =strtotime($create_time); 
                $where .=" and a.create_time >= ".$create_time." and a.create_time<=".($create_time+86399);
           }else{ //只能查询一天的数据
                $where .=" and a.create_time >= ".time()." and a.create_time<=".(time()+86399);
           }
           if($brand_name){
                $where .=" and m.brand_name = '".$brand_name."'";
           }
           if($gtid){
                $where .=" and e.gtid = $gtid";
           }
           if($gtid1){
                $where .=" and f.gtid = $gtid1";
           }
           if($gtid2){
                $where .=" and g.gtid = $gtid2";
           }
           if($c_id){
                $where .=" and h.c_id = $c_id";
           }
           if($ctid){
                $where .=" and i.ctid = $ctid";
           }
           if($gname){
                $where .=" and d.name like '%".$gname."%'";
           } 
           if($machining!=""){
                $where .=" and b.machining = $machining";
           }
           if($stid!=""){
                $where .=" and k.stid = $stid";
           }
           if($sid!=""){
                $where .=" and j.sid = $sid";
           }
           $sql ="select i.type_name ctname,h.`name` cname,h.`code`,
                d.`name` gname,b.p_remark,b.num1,l.unit_name uname,b.remark,a.create_time
                from db_purchase a
                left join db_purchase_detail b on b.osid= a.osid
                left join db_wholesale_goods c on c.wgid= b.wgid
                left join db_goods d on d.gid= c.gid
                left join db_goods_type e on e.gtid = c.gtid
                left join db_goods_type f on f.gtid = c.gtid1
                left join db_goods_type g on g.gtid = c.gtid2 
                left join db_client h on h.c_id = a.cid 
                left join db_client_type i on i.ctid = h.ctid
                left join db_supplier j on j.sid = c.sid 
                left join db_supplier_type k on k.stid = j.stid 
                left join db_unit l on l.unit_id = b.unit_id1 
                left join db_brand m on m.brand_id = d.brand_id "; 
            $order =" order by ctname,gname asc"; 
            $sql = $sql.$where.$order; 
            $list= M("")->query($sql);    
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
        //供应商类型
        $stlist = M("supplier_type")->where("wid =$wid")->select();
        $this->assign("stlist",$stlist);
        $this->display();
    }

    //根据供应商类型获取供应商信息
    public function getSupplierInfo(){
        if(IS_POST){
            $stid =I("stid");
            $result = M("supplier")->where("stid =$stid")->select();
            if($result){
                $this->ajaxReturn(ReturnJSON(0,$result));
            }else{
                $this->ajaxReturn(ReturnJSON(1)); 
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }


    
    public function purchasePrint(){
       $wid = getWid(); 
       $where =" where c.wid =$wid ";
       $create_time =I("create_time");$brand_name =I("brand_name");
       $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id");
       $gname=I("gname");$ctid=I("ctid"); 
       $gtid2=I("gtid2"); $tcaption=I("tcaption");
       $this->assign("tcaption",$tcaption);
       $stid =I("stid"); $sid=I("sid");$hid_order =I("hid_order"); 
       $this->assign("create_time",$create_time);
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
       if($stid!=""){
            $where .=" and r.stid = $stid";
       }
       if($sid!=""){
            $where .=" and w.sid = $sid";
       }
       $sql ="select w.name sname,k.type_name tname3,j.type_name tname2,h.type_name tname1,
                b.wgid,d.`name` gname,SUM(b.num1) num,e.unit_name uname,t.num1 stock_num
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
                left join db_client_type n on g.ctid = n.ctid  
                LEFT JOIN db_supplier w on w.sid= c.sid
                LEFT JOIN db_supplier_type r ON r.stid =w.stid 
                LEFT JOIN db_stock t ON t.wgid = b.wgid";
        $group=" group by sname,tname3,tname2,tname1,b.wgid,gname,uname,stock_num ";
        $order =" order by sname asc";
        $orderArr =array();
        if($hid_order)
           $orderArr = explode("|",$hid_order);
        if($orderArr[0]!=""){  
            if($orderArr[0]=="gname"){
                $order=" order by gname ".$orderArr[1]; 
            }
            if($orderArr[0]=="sname")
                $order=" order by w.sid ".$orderArr[1]; 
            if($orderArr[0]=="gtname")
                    $order=" order by tname3,tname2,tname1 ".$orderArr[1];
        }  
        $sql = $sql.$where.$group.$order; 
        $list= M("")->query($sql);  

        //数据处理 
        foreach ($list as $k => $v) {
           $wgid = $v['wgid'];
           $where1 =$where;
           //根据供应商商品编号获取客户量
           $sql ="select g.`code`,g.`name` cname,b.p_remark num,b.num1 from db_purchase a 
                left join db_purchase_detail b on a.osid = b.osid
                left join db_wholesale_goods c on c.wgid = b.wgid
                left join db_goods d on d.gid = c.gid
                left join db_unit e on e.unit_id = b.unit_id1
                left join db_client g on a.cid=g.c_id
                left join db_goods_type h on c.gtid = h.gtid
                left join db_goods_type j on c.gtid1 = j.gtid
                left join db_goods_type k on c.gtid2 = k.gtid
                left join db_brand l on d.brand_id = l.brand_id
                left join db_client_type n on g.ctid = n.ctid 
                LEFT JOIN db_supplier w on w.sid= c.sid
                LEFT JOIN db_supplier_type r ON r.stid =w.stid";
           $where1 .=" and b.wgid = $wgid ";
           $sql .= $where1;  
           $code = M("")->query($sql);
           // dump($sql);
           $str ="&nbsp;"; 
           $str_code =""; //客户简码
           $str_num =""; //客户数量
           $code_num=0; //客户简码列数
           $count = count($code);
           foreach ($code as $kk => $vo) { 
                if($vo['num']!="")
                    $str .='<span style="float:left;margin-left:2px;">'.$vo['code'].''.$vo['num'].';</span>';
                else
                    $str .='<span style="float:left;margin-left:2px;">'.$vo['code'].''.keep4($vo['num1']).$v['uname'].';</span>';   
           } 
           $list[$k]["code"] =$str; 
        }  
        $nlist = array();   
        $i=0;
        foreach ($list as $k => $v) { 
            if($k%30==0 && $k>0){
                $i++;
            }
            $nlist[$i]['list'][$k]=$v; 
        }   
        $this->assign("nlist",$nlist);
        // dump($list);
        $this->assign("countnlist",count($nlist));
        $this->assign("list",$list);   
        $this->display();
    }

    //被用 去除客户字段
    public function purchasePrint1(){
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
           $where1 =$where;
           //根据供应商商品编号获取客户量
           $sql ="select g.`code`,g.`name` cname,SUM(b.num1) num from db_purchase a 
                left join db_purchase_detail b on a.osid = b.osid
                left join db_wholesale_goods c on c.wgid = b.wgid
                left join db_goods d on d.gid = c.gid
                left join db_unit e on e.unit_id = b.unit_id1
                left join db_client g on a.cid=g.c_id
                left join db_goods_type h on c.gtid = h.gtid
                left join db_goods_type j on c.gtid1 = j.gtid
                left join db_goods_type k on c.gtid2 = k.gtid
                left join db_brand l on d.brand_id = l.brand_id
                left join db_client_type n on g.ctid = n.ctid ";
           $where1 .=" and b.wgid = $wgid ";
           $sql .= $where1." group by g.`code`,cname order by g.`code` asc"; 
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
                    $str_num .="<td>".keep4($vo['num'])."</td>";
                    $code_num ++;  
                }else if($kk%9==0 && $kk!=0){
                    //结束上一行，并生成新的一行 
                    $code_num = 0; //新的一行变为0
                    $str .=$str_code.$str_num."</tr>";
                    $str_code ="<tr><td style='border-left:none;width:50px;'>客户</td>";
                    $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                    $str_num ="<tr class='num-tr'><td style='border-left:none;width:50px;'>数量</td>";
                    $str_num .="<td>".keep4($vo['num'])."</td>";
                    $code_num ++; 
                }else{
                    //不是第一行也不是新一行 拼接 td
                    $str_code .="<td title='".$vo['cname']."'>".$vo['code']."</td>";
                    $str_num .="<td>".keep4($vo['num'])."</td>"; 
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

    //导出采购报表
    public function exportPurchase(){
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
            $where .=" and a.create_time >= ".$create_time." and a.create_time<=".($create_time+86399);
       }else{ //只能查询一天的数据
            $where .=" and a.create_time >= ".time()." and a.create_time<=".(time()+86399);
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
        Vendor('PHPExcel.PHPExcel');    //引入phpexcel类
        $objPHPExcel = new \PHPExcel();   //实例化phpexcel类
        $objSheet = $objPHPExcel->getActiveSheet(); //获得当前sheet 
        //设置当前的sheet
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(19);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(7);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(7);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(7);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(7);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(7);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(7); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(7); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(7); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(7); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(7); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(12); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(12); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(12); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(12); 

        /*----------------------获取数据列设置信息---------------------*/
        
         
        $objSheet->setCellValue("A1",$tcaption)->mergeCells('A1:O1'); 
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(24);//设置行高
        $objSheet->setCellValue("A2","商品名称")
                    ->setCellValue("B2","客户量")
                    ->setCellValue("H2","合计")
                    ->setCellValue("I2","实购数量")
                    ->setCellValue("J2","进价")
                    ->setCellValue("K2","备注")
                    ->mergeCells('B2:K2');  //合并单元格 
        $arr =array("A1","B1","C1","D1","E1","F1","G1","H1","I1","J1","K1","L1","M1","N1","O1","A2","B2","C2","D2","E2","F2","G2","H2","I2","J2","K2","L2","M2","N2","O2");  
        // dump($sql);exit();
        //数据处理 
        $n = 3;
        foreach ($list as $k => $v) {
            //设置A\L\M\N\O列信息 
            $objSheet->setCellValue("A".$n,"商品名称")
                    ->setCellValue("B".$n, "客户量")
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
        $filename="采购单".time();
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

    //分拣单
    public function sortingReport(){
        $wid = getWid(); 
        $this->assign("create_time",date('Y-m-d')); 
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid =$wid")->select();
        $this->assign("tlist",$tlist);
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist); 
        $this->display();
    } 

    //获取分拣单信息
    public function getSortingReportInfo(){ 
        if(IS_POST){
            $wid = getWid(); 
            $where =" where c.wid =$wid ";
            $create_time =I("create_time");$brand_name =I("brand_name");
            $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id");
            $gname=I("gname");$ctid=I("ctid");$machining=I("machining");
            $gtid2=I("gtid2"); 
            if($create_time){
                $create_time =strtotime($create_time); 
                $where .=" and a.create_time >= ".$create_time." and a.create_time<=".($create_time+86399);
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
                    g.c_id,g.`code`,b.wgid,d.`name` gname,(b.num1/nei_num) num,b.p_remark,b.remark,e.unit_name uname
                    from db_purchase a 
                    left join db_purchase_detail b on a.osid = b.osid
                    left join db_wholesale_goods c on c.wgid = b.wgid
                    left join db_goods d on d.gid = c.gid
                    left join db_unit e on e.unit_id = b.nei_unit_id
                    left join db_client g on g.c_id = a.cid 
                    left join db_goods_type h on c.gtid = h.gtid
                    left join db_goods_type j on c.gtid1 = j.gtid
                    left join db_goods_type k on c.gtid2 = k.gtid
                    left join db_brand l on d.brand_id = l.brand_id
                    left join db_client_type n on g.ctid = n.ctid  "; 
            $order =" order by tname3,tname2,tname1,gname,d.py,g.`code` asc";
            $sql = $sql.$where.$order; 
            $list= M("")->query($sql);  
            $result=array();
            if(!empty($list)){
                $result['msg'] ="请求成功";
                $result['resultcode'] =1;
                //处理返回数据 
                $data = $this->sortingReporDataHandle($list);
                $result['data']=$data;
            }else{
                $result['msg'] ="无数据";
                $result['resultcode'] =0;
                $this->ajaxReturn(0); //无数据
            }   
            $this->ajaxReturn($result);
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        } 
    } 

    //单品分拣单数据处理 
    public function sortingReporDataHandle($data){
        //获取系统参数 单位换算字段  
        $result=array();
        if(!empty($data)){
            $wid = getWid();
            $system_param=M("system_param")->where("wid=$wid")->find();
            if(empty($system_param)){
                $system_param =getSystemParam();
            } 
            //处理字段 
            foreach ($data as $k => $v) {
                //处理数量 
                $data[$k]['num'] = customDecimal('num',$v['num']); 
                //处理单位转换 
                switch($system_param['param_is_convert_unit']){
                    case "0":
                        $data[$k]['num']= $data[$k]['num'].$v['uname']; 
                        break;
                    case "1":
                        if($v['uname']=="公斤"){
                            $data[$k]['num'] = ($data[$k]['num']*2)."斤"; 
                        }else{
                            $data[$k]['num']= $data[$k]['num'].$v['uname'];
                        } 
                        break;
                    case "2":
                        if($v['uname']=="斤"){
                            $data[$k]['num'] = ($data[$k]['num']/2)."公斤"; 
                        }else{
                            $data[$k]['num']= $data[$k]['num'].$v['uname'];
                        } 
                        break;
                } 
            }
            $clist =array();
            $i=0; $n=0;$temp="";  
            //数据处理 合并相同名称商品  
            foreach ($data as $k => $v) {  
                if($k==0){ //第一次  
                    $clist[$i]=array();
                    $clist[$i]['tname1']=$v['tname1'];  
                    $clist[$i]['tname2']=$v['tname2'];  
                    $clist[$i]['tname3']=$v['tname3'];   
                    $clist[$i]['gname']=$v['gname'];  
                    $clist[$i]['wgid']=$v['wgid'];  
                    $clist[$i]['clist']=array(); 
                    $clist[$i]['clist'][$n]=array();
                    $clist[$i]['clist'][$n]['code']=$v['code'];
                    $clist[$i]['clist'][$n]['num']=$v['num'];
                    $clist[$i]['clist'][$n]['uname']=$v['uname'];
                    $clist[$i]['clist'][$n]['remark']=$v['remark'];
                    $clist[$i]['clist'][$n]['p_remark']=$v['p_remark'];  
                    $n++;
                } 
                if($temp==$v['gname']){
                    $clist[$i]['clist'][$n]=array();
                    $clist[$i]['clist'][$n]['code']=$v['code'];
                    $clist[$i]['clist'][$n]['num']=$v['num'];
                    $clist[$i]['clist'][$n]['uname']=$v['uname'];
                    $clist[$i]['clist'][$n]['remark']=$v['remark'];
                    $clist[$i]['clist'][$n]['p_remark']=$v['p_remark'];  
                    $n++;
                }else if($temp!=$v['gname'] && $k!=0){
                    $i++;
                    $n=0;
                    $clist[$i]=array();
                    $clist[$i]['tname1']=$v['tname1'];  
                    $clist[$i]['tname2']=$v['tname2'];  
                    $clist[$i]['tname3']=$v['tname3']; 
                    $clist[$i]['gname']=$v['gname'];  
                    $clist[$i]['wgid']=$v['wgid'];  
                    $clist[$i]['clist']=array(); 
                    $clist[$i]['clist'][$n]=array();
                    $clist[$i]['clist'][$n]['code']=$v['code'];
                    $clist[$i]['clist'][$n]['num']=$v['num'];
                    $clist[$i]['clist'][$n]['uname']=$v['uname'];
                    $clist[$i]['clist'][$n]['remark']=$v['remark'];
                    $clist[$i]['clist'][$n]['p_remark']=$v['p_remark'];
                    $n++;
                } 
                $temp=$v['gname'];
            }
            $result = $clist; 
        } 
        return $result;
    }

    //分拣核对单
    public function sortingCheck(){
        $wid = getWid(); 
        $this->assign("create_time",date('Y-m-d')); 
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid =$wid")->select();
        $this->assign("tlist",$tlist);
        //客户类型
        $ctlist = M("client_type")->where("wid = $wid")->select();
        $this->assign("ctlist",$ctlist); 

        $this->display();
    }

    //加工单
    public function machiningReport(){
        $wid = getWid();   
        //默认获取今天
        $this->assign("create_time",date('Y-m-d')); 
        //商品类型
        $tm = M("goods_type");
        $tlist = $tm->where("series=1 and wid =$wid")->select();
        $this->assign("tlist",$tlist);
        $this->display();
    }   

    //获取货损信息
    public function getDamageOfCargoInfo($osid,$wgid){ 
        $spec_sql="select a.*,b.unit_name uname,c.unit_name uname1 from db_spec a 
                    left join db_unit b on a.unit_id=b.unit_id
                    left join db_unit c on a.unit_id1=c.unit_id where a.wgid = $wgid";
        $spec_list = M("")->query($spec_sql);
        $result =array(
            "cd_num"=>"",
            "unit_id"=>"",
            "ulist"=>array()
        );
        $ulist1 =array();
        if(!empty($spec_list)){ 
            //取出基础单位 和 内装单位 
            foreach ($spec_list as $kk => $vo) {
                if($kk==0){
                    $ulist1[$kk] =array();
                    $ulist1[$kk]['unit_id'] = $vo['unit_id'];  
                    $ulist1[$kk]['uname'] = $vo['uname'];
                    $ulist1[$kk]['num'] = 1; 
                }
                $ulist1[$kk+1] =array();
                $ulist1[$kk+1]['unit_id'] = $vo['unit_id1'];
                $ulist1[$kk+1]['uname'] = $vo['uname1'];
                $ulist1[$kk+1]['num'] = $vo['num'];
            } 
        }else{
            $sql1="select a.nei_unit_id,a.nei_num,b.unit_name uname from db_purchase_detail a left join db_unit b on a.nei_unit_id=b.unit_id where a.wgid=$wgid";
            $udata = M()->query($sql1);
            $ulist1[0] =array();
            $ulist1[0]['unit_id'] =$udata[0]['nei_unit_id'];
            $ulist1[0]['uname'] = $udata[0]['uname'];
            $ulist1[0]['num'] = $udata[0]['num'];
        }
        $result['ulist']=$ulist1;  
        //获取选择单位和货损数量
        $sql ="select cd_num/nei_num cd_num,nei_unit_id from db_out_stock_detail where osid ='$osid' and wgid =$wgid";
        // $this->ajaxReturn($sql);
        $list = M()->query($sql);
        if(!empty($list)){
            $result['cd_num'] = customDecimal('num',$list[0]['cd_num']);
            $result['unit_id'] = $list[0]['nei_unit_id']; 
        }else{
            $result['cd_num'] = "";
            $result['unit_id'] = ""; 
        }  
        return $result; 
    } 

    //获取加工信息
    public function getMachiningInfo(){
        if(IS_POST){
            $wid = getWid();
            $where =" where c.wid = $wid and b.machining = 1 ";
            $gtid = I("gtid");$gtid1 = I("gtid1");$gtid2 = I("gtid2");$create_time = I("create_time");
            $result=array();
            if(!empty($create_time)){
                $create_time =strtotime($create_time); 
                $where .=" and a.create_time >= ".$create_time." and a.create_time<=".($create_time+86399);
            }else{ //只能查询一天的数据
                $where .=" and a.create_time >= '".time()."' and a.create_time<=".(time()+86399);
            }
            if(!empty($gtid)){
                $where .=" and c.gtid = $gtid";
            }
            if(!empty($gtid1)){
                $where .=" and c.gtid1 = $gtid1";
            }
            if(!empty($gtid2)){
                $where .=" and c.gtid2 = $gtid2";
            }  

            $sql ="select f.`code`,d.`name` gname,b.wgid,b.num1/b.nei_num num,e.unit_name uname,b.num1,ee.unit_name jcuname,b.remark  
                from db_purchase a
                left join db_purchase_detail b on a.osid = b.osid
                left join db_wholesale_goods c on c.wgid = b.wgid
                left join db_goods d on d.gid =c.gid
                left join db_unit e on e.unit_id = b.nei_unit_id 
                left join db_unit ee on ee.unit_id = b.unit_id1
                left join db_client f on f.c_id = a.cid"; 
            $group =" GROUP BY `code`,gname,wgid,uname,jcuname,remark ";
            $order =" ORDER BY c.gtid2,c.gtid1,c.gtid,d.py asc";
            $sql .=$where.$order;
            // $sql .=$where.$group.$order;
            $list = M()->query($sql);
            // $this->ajaxReturn($sql);
            if(!empty($list)){ 
                $system_param=M("system_param")->where("wid=$wid")->find();
                if(empty($system_param)){
                    $system_param =getSystemParam();
                }
                $result['system_param']=$system_param; 
                $result['data']=$this->machiningDataHandle($system_param,$list,$create_time); 
                $this->ajaxReturn($result);
            }else{
                $this->ajaxreturn(0);//无数据
            }
        } 
    }

    //加工单数据处理 
    public function machiningDataHandle($system_param,$list,$create_time){
        $clist =array();
        $i=0; $n=0;$temp="";  
        $osid ="PC".getWid()."_".date('Ymd',$create_time)."0000";
        //处理字段 
        foreach ($list as $k => $v) {
            //处理数量 
            $list[$k]['num'] = customDecimal('num',$v['num']); 
            //处理单位转换 
            switch($system_param['param_is_convert_unit']){
                case "0":
                    $list[$k]['num']= $list[$k]['num'].$v['uname']; 
                    break;
                case "1":
                    if($v['uname']=="公斤"){
                        $list[$k]['num'] = ($list[$k]['num']*2)."斤"; 
                    }else{
                        $list[$k]['num']= $list[$k]['num'].$v['uname'];
                    }
                    if($v['jcuname']=="公斤"){
                        $list[$k]['num1'] = $list[$k]['num1']*2; 
                        $list[$k]['jcuname']="斤";
                    }
                    break;
                case "2":
                    if($v['uname']=="斤"){
                        $list[$k]['num'] = ($list[$k]['num']/2)."公斤"; 
                    }else{
                        $list[$k]['num']= $list[$k]['num'].$v['uname'];
                    }
                    if($v['jcuname']=="斤"){
                        $list[$k]['num1'] = $list[$k]['num1']/2; 
                        $list[$k]['jcuname']="公斤";
                    }
                    break;
            } 
        }
        foreach ($list as $k => $v) {
            if($k==0){ //第一次  
                $clist[$i]=array();
                $clist[$i]['gname']=$v['gname']; 
                $uulist = $this->getDamageOfCargoInfo($osid,$v['wgid']);
                $clist[$i]['wgid']=$v['wgid'];
                $clist[$i]['cd_num']=$uulist['cd_num'];
                $clist[$i]['unit_id']=$uulist['unit_id'];
                $clist[$i]['ulist']=$uulist['ulist'];
                $clist[$i]['clist']=array(); 
                $clist[$i]['clist'][$n]=array();
                $clist[$i]['clist'][$n]['code']=$v['code'];
                $clist[$i]['clist'][$n]['num']=$v['num'];
                $clist[$i]['clist'][$n]['uname']=$v['uname'];
                $clist[$i]['clist'][$n]['remark']=$v['remark'];
                $clist[$i]['clist'][$n]['num1']=$v['num1'];
                $clist[$i]['clist'][$n]['jcuname']=$v['jcuname']; 
                $n++;
            } 
            if($temp==$v['gname']){
                $clist[$i]['clist'][$n]=array();
                $clist[$i]['clist'][$n]['code']=$v['code'];
                $clist[$i]['clist'][$n]['num']=$v['num'];
                $clist[$i]['clist'][$n]['uname']=$v['uname'];
                $clist[$i]['clist'][$n]['remark']=$v['remark'];
                $clist[$i]['clist'][$n]['num1']=$v['num1'];
                $clist[$i]['clist'][$n]['jcuname']=$v['jcuname'];
                $n++;
            }else if($temp!=$v['gname'] && $k!=0){
                $i++;
                $n=0;
                $clist[$i]=array();
                $clist[$i]['gname']=$v['gname'];
                $uulist = $this->getDamageOfCargoInfo($osid,$v['wgid']);
                $clist[$i]['wgid']=$v['wgid'];
                $clist[$i]['cd_num']=$uulist['cd_num'];
                $clist[$i]['unit_id']=$uulist['unit_id'];
                $clist[$i]['ulist']=$uulist['ulist'];
                $clist[$i]['clist']=array(); 
                $clist[$i]['clist'][$n]=array();
                $clist[$i]['clist'][$n]['code']=$v['code'];
                $clist[$i]['clist'][$n]['num']=$v['num'];
                $clist[$i]['clist'][$n]['uname']=$v['uname'];
                $clist[$i]['clist'][$n]['remark']=$v['remark'];
                $clist[$i]['clist'][$n]['num1']=$v['num1'];
                $clist[$i]['clist'][$n]['jcuname']=$v['jcuname'];
                $n++;
            } 
            $temp=$v['gname'];
        }
        return $clist;
    }

    //生成货损出库单 
    public function buildOutStockInfo(){
        if(IS_POST){
            //单据号 PC+wid+_+201702260000 
            $wid = getWid();
            $create_time = strtotime(I("create_time"));
            $osid="PC".$wid."_".date('Ymd',$create_time)."0000"; 
            $list['unit_id1s'] =$this->strToArray(I("unit_ids"));
            $list['cdnums'] =$this->strToArray(I("cdnums"));
            $list['wgids'] =$this->strToArray(I("wgids"));
            $list['nei_nums'] =$this->strToArray(I("nei_nums")); 
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids"));
            if($this->cdCheckFrom($list)){ 
                //查询 出库单是否已存在 该出库单 
                $m = M("out_stock");
                $mm = M("out_stock_detail"); 
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功',
                    'osid'=>$osid
                );  
                /*--------------------单据存在情况处理开始---------------------*/
                $outlist = $m->where("osid ='".$osid."'")->find(); 
                if(!empty($outlist)){//存在
                    if($outlist['status']==1){//已审核执行反审核操作
                        $sql ="CALL counterAuditOut('".$osid."',$wid)";
                        $result = M("")->query($sql); 
                        if($result[0]['resultcode']!=0){//反审核失败
                            $arr_result['resultcode'] = 11;
                            $arr_result['msg'] = "反审核失败!".$result[0]['msg']; 
                            $this->ajaxReturn($arr_result);
                            exit();//数据有误结束方法
                        }  
                    }
                    $mResult = $m->where("osid='$osid'")->delete();
                    $mmResult= $mm->where("osid='$osid'")->delete();
                }
                /*----------------单据存在情况处理结束-------------------------*/

                M()->startTrans(); 
                //总表录入 
                $data = array(
                    'osid' => $osid,
                    'osname' => '加工货损单',
                    'cid' => 0,
                    'total' => 0,
                    'is_cargo' => 1,
                    'remark' => '加工货损单',
                    'create_id' => $wid,
                    'create_time' => time(),
                    'update_time' => time(),
                    'update_id' =>$wid
                );  
                $result = $m->add($data); 
                if($result){  
                    //详情录入
                    foreach ($list['wgids'] as $k => $v) {
                        //计算默认单价
                        $price1 = 0.0001/floatval($list['nei_nums'][$k]);
                        $cd_num =floatval($list['cdnums'][$k])*floatval($list['nei_nums'][$k]);
                        $unit_id = floatval($list['unit_id1s'][$k]);
                        $sales_amount = $cd_num*$price1;
                        //入库详情新增
                        $sublist = array(
                            'osid'    =>$osid,
                            'wgid'    =>$list['wgids'][$k],
                            'than'    =>1, 
                            'cd_num'  =>$cd_num,
                            'num1'    =>0,
                            'price'   =>$price1,
                            'unit_id1'=>$list['unit_id1s'][$k],
                            'nei_num'=>$list['nei_nums'][$k],
                            'nei_unit_id'=>$list['nei_unit_ids'][$k],
                            'nei_price'=>0.0001,
                            'sales_amount' => $sales_amount,
                            'machining'=>1,
                        ); 
                        //计算成本价格 强转换为数字 如果此处去问题 则前端有问题 
                        $sublist["cost"] = $this->getCost($list['wgids'][$k],0,$cd_num);
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
        } 
    }
  

    public function getSortingCheckInfo(){
        if(IS_POST){
            $wid=getWid();
            $where =" where c.wid =$wid ";
            $create_time =I("create_time");$brand_name =I("brand_name");
            $gtid=I("gtid");$gtid1=I("gtid1");$c_id=I("c_id");
            $gname=I("gname");$ctid=I("ctid");$machining=I("machining");$gtid2=I("gtid2"); 
            $result=array();
            if($create_time){
                $create_time =strtotime($create_time); 
                $where .=" and a.create_time >= ".$create_time." and a.create_time<=".($create_time+86399);
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
            if($ctid){
                $where .=" and n.ctid = $ctid";
                //根据客户类型 查询客户信息 
                $clist = M("client")->field("c_id,code")->where("ctid = $ctid")->select();
                $result['clist']=$clist;
            }
            if($gname){
                $where .=" and d.name like '%".$gname."%'";
            } 
            if($machining!=""){
                $where .=" and b.machining = $machining";
            }
            $sql ="select k.type_name tname3,j.type_name tname2,h.type_name tname1,
                    b.wgid,d.`name` gname,b.num1/b.nei_num num,b.p_remark,b.remark,e.unit_name uname,g.c_id
                    from db_purchase a 
                    left join db_purchase_detail b on a.osid = b.osid
                    left join db_wholesale_goods c on c.wgid = b.wgid
                    left join db_goods d on d.gid = c.gid
                    left join db_unit e on e.unit_id = b.nei_unit_id
                    left join db_client g on g.c_id = a.cid 
                    left join db_goods_type h on c.gtid = h.gtid
                    left join db_goods_type j on c.gtid1 = j.gtid
                    left join db_goods_type k on c.gtid2 = k.gtid
                    left join db_brand l on d.brand_id = l.brand_id
                    left join db_client_type n on g.ctid = n.ctid  ";
            $order =" order by tname3,tname2,tname1,gname asc";
            $sql = $sql.$where.$order; 
            $list= M("")->query($sql);  
            // $this->ajaxReturn($sql);
            if(empty($list)){
                $this->ajaxreturn(0);//无数据
            }else{
                $result['data']=$list;
                $system_param=M("system_param")->where("wid=$wid")->find();
                if(empty($system_param)){
                    $system_param =getSystemParam();
                }
                $result['system_param']=$system_param;
                $this->ajaxReturn($result);
            }
        }
    }

    public function sortingCheckPrint(){ 
        $this->assign("create_time",I("create_time"));
        $this->assign("ctid",I("ctid"));
        $this->assign("machining",I("machining"));
        $this->assign("gtid",I("gtid"));
        $this->assign("gtid1",I("gtid1"));
        $this->assign("gtid2",I("gtid2"));
        $this->assign("tcaption",I("tcaption"));
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
           $where1 =$where;
           //根据供应商商品编号获取客户量
           $sql ="select g.`code`,SUM(b.num1) num,e.unit_name uname,b.remark from db_purchase a 
                left join db_purchase_detail b on a.osid = b.osid
                left join db_wholesale_goods c on c.wgid = b.wgid
                left join db_goods d on d.gid = c.gid
                left join db_unit e on e.unit_id = b.unit_id1
                left join db_client g on a.cid=g.c_id
                left join db_goods_type h on c.gtid = h.gtid
                left join db_goods_type j on c.gtid1 = j.gtid
                left join db_goods_type k on c.gtid2 = k.gtid
                left join db_brand l on d.brand_id = l.brand_id
                left join db_client_type n on g.ctid = n.ctid ";
           $where1 .=" and b.wgid = $wgid ";
           $sql .= $where1." group by g.`code`,e.unit_id,b.remark order by g.`code` asc";  
           // dump($sql);
           $code = M("")->query($sql);
           $str =""; 
           $str_code =""; //客户简码
           $str_num =""; //客户数量
           $code_num=0; //客户简码列数
           $count = count($code);
           foreach ($code as $kk => $vo) { 
                if(trim($vo['uname'])=="公斤")
                    // $str .='<span style="float:left;margin-left:5px;">'.$vo['code'].keep4(floatval($vo['num'])*2).'斤'.'['.$vo['remark'].'];</span>';
                    $str .='<span style="float:left;margin-left:15px;">'.$vo['code'].keep4(floatval($vo['num'])*2).'['.$vo['remark'].'];</span>';
                else
                    $str .='<span style="float:left;margin-left:15px;">'.$vo['code'].keep4($vo['num']).$vo['uname'].'['.$vo['remark'].'];</span>';   
           } 
           $list[$k]["code"] =$str;  
        }  
        $nlist = array();   
        $i=0;
        foreach ($list as $k => $v) { 
            if($k%10==0 && $k>0){
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