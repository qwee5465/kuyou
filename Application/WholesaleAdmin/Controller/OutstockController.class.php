<?php
// +----------------------------------------------------------------------
// | 出库模块
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 田小文 <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class OutstockController extends BaseController
{  
    /*-----------------鲁汉需求开始---------------------*/
    /**
     * 查询所有未审核的出库单
     */
    public function queryOutboundOrder(){
        if(IS_POST){ 
            $wid = getWid();
            $m = M(); 
            $sql ="select osid from db_out_stock where `status`=0 and create_id=$wid order by create_time asc";  
            $data = $m->query($sql); 
            if($data){
                $this->ajaxReturn(ReturnJSON(0,$data));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }  
    }

    /**
     * 批量审核出库单 用于鲁汉计算成本 
     */
    public function auditingOrder(){
        if(IS_POST){
            $osid = I('osid'); 
            //根据osid 查出要出库的商品数量
            $sql ="select a.out_id,a.wgid,a.num1,b.create_time from db_out_stock_detail a inner join db_out_stock b on b.osid = a.osid where a.osid='$osid' and b.`status` = 0";
            $data = M()->query($sql); 
            if($data){
                //事务开启
                M()->startTrans();
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'审核成功'
                );
                foreach($data as $k => $v){
                    //根据先进先出的原则和商品数量 查出入库商品批次
                    $sql1 ="select join_id,surplus,price from db_join_stock_detail where wgid = $v.wgid and surplus > 0  order by join_id ASC";
                    $data1 = M()->query($sql1); 
                    foreach($data1 as $kk => $vv){
                        if($vv['surplus']>=$v['num1']){ //该批次够 修改这些批次的剩余数量
                            $res = M()->execute("update db_join_stock_detail set surplus = surplus - ".$v['num1']." where join_id = ".$vv["join_id"]);
                            $v['num1'] = 0;
                            if($res){//计算该出库商品的成本
                                $cost  = $vv['price'] * $v['num1'];
                                $res = M()->execute("update db_out_stock_detail set cost = cost + ".$cost." where out_id = ".$v['out_id']);
                                if(!$res){
                                    $arr_result['resultcode'] = 10;
                                    $arr_result['msg'] .="批次够,修改出库成本失败。单据号为：$osid-----out_id:$v['out_id']";
                                    break;
                                }
                            }else{
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] .="批次够,修改入库剩余数失败。单据号为：$osid-----join_id:$vv['join_id']";
                                break;
                            }
                            break;
                        }else{ //该批次不够 修改这些批次的剩余数量
                            $res = M()->execute("update db_join_stock_detail set surplus = 0 where join_id = ".$vv["join_id"]);
                            $v['num1'] = $v['num1'] - $vv['surplus'];
                            if($res){ //计算该出库商品的成本
                                $cost  = $vv['price'] * $vv['surplus'];
                                $res = M()->execute("update db_out_stock_detail set cost = cost + ".$cost." where out_id = ".$v['out_id']);
                                if(!$res){
                                    $arr_result['resultcode'] = 10;
                                    $arr_result['msg'] .="批次不够,修改出库成本失败。单据号为：$osid-----out_id:$v['out_id']";
                                    $v['num1'] = 0;
                                    break;
                                }
                            }else{
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] .="批次不够,修改入库剩余数失败。单据号为：$osid-----join_id:$vv['join_id']";
                                $v['num1'] = 0;
                                break;
                            }
                        }
                    }
                    //若库存不足则按最近的成本价计算
                    //若没有最近的成本则按0 计算
                    if($v['num1']>0){
                        $sql1 = "select b.price from db_join_stock a 
                                INNER JOIN db_join_stock_detail b on a.jsid = b.jsid
                                where a.`status` = 1 and b.wgid = ".$v['wgid']." and b.price >0 and a.create_time <= ".$v['create_time']." order by a.create_time asc limit 1";
                        $data1 = $m->query($sql); 
                        if($data1){
                            $cost = $v['num1']*$data1[0]['price'];
                            $res = M()->execute("update db_out_stock_detail set cost = cost + ".$cost." where out_id = ".$v['out_id']);
                            if(!$res){
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] .="库存不足<=0,修改出库成本失败。单据号为：$osid-----out_id:$v['out_id']";
                                break;
                            }
                        }else{
                            $sql1 = "select b.price from db_join_stock a 
                                INNER JOIN db_join_stock_detail b on a.jsid = b.jsid
                                where a.`status` = 1 and b.wgid = ".$v['wgid']." and b.price >0 and a.create_time > ".$v['create_time']." order by a.create_time asc limit 1";
                            $data1 = $m->query($sql); 
                            if($data1){
                                $cost = $v['num1']*$data1[0]['price'];
                                $res = M()->execute("update db_out_stock_detail set cost = cost + ".$cost." where out_id = ".$v['out_id']);
                                if(!$res){
                                    $arr_result['resultcode'] = 10;
                                    $arr_result['msg'] .="库存不足>0,修改出库成本失败。单据号为：$osid-----out_id:$v['out_id']";
                                    break;
                                }
                            }else{
                                //如果还没有成本价格就不对了
                            }

                        }
                    } 
                    if($arr_result['resultcode'] != 0){
                        break;
                    }
                }
                //循环所有商品以上步骤都完成 
                if($arr_result['resultcode'] == 0){
                    //修改当前单据的审核状态
                    $res = M()->execute("update db_out_stock set `status`=1 where osid='".$osid."'");
                    if($res){
                        M()->commit(); //事务提交  
                        $this->ajaxReturn($arr_result);
                    }else{
                        $arr_result['resultcode'] = 10;
                        $arr_result['msg'] .="修改单据状态失败。单据号为：$osid";
                        M()->rollback(); //事务回滚 
                        $this->ajaxReturn($arr_result);
                    }
                }else{
                    M()->rollback(); //事务回滚 
                    $this->ajaxReturn($arr_result);
                } 
            } else{
                $arr_result = array(
                    'resultcode' => 11,
                    'msg' =>'该单据已审核或不存在'
                );
                $this->ajaxReturn($arr_result);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }  
       
    }
    /*-----------------鲁汉需求结束---------------------*/


    public function outstockForm()
    {   
        $wid = getWid();
        $osid ="PC".$wid."_".time();
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
        $this->assign("t_list",$t_list);
        $this->assign("osbList",$osbList); 
        $osname = getUnitName()."配送单";
        $this->assign("osname",$osname);
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
            $result = M("out_stock")->where("osid = '$osid'")->save($data);
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

    //验证表单提交时是否为数字了
    public function checkNum($num){
        if(empty($num)){ //数量字符串是空  
            return false;
        }else{ 
            if(!is_numeric($num))//判断是否为数字 整数或浮点数
                return false; 
            if($num<=0)
                return false;
        }
        return true;
    }   

    //字符串处理返回数组
    public function strToArray($str){
        return explode(",",$str);
    }

    // 新增库存数
    public function stockAdd($v){ 
        //判断库存中是否存在该商品  
        $m=M("stock"); 
        $list = $m->field('sid')->where("wgid = ".$v['wgid'])->find();
        $wid = getWid();
        if($list){  
            $num1 = $v['num1'];
            $cd_num = $v['cd_num']; 
            if($cd_num)
                $num1 = $num1 + $cd_num;
            $sql ="update db_stock set num1=num1-".$num1; 
            $sql .=",new_time=".time()." where sid = ".$list['sid']." and wid =$wid";
            $ll = $m->execute($sql);
        }else{ 
            $num1 = $v['num1'];
            $cd_num = $v['cd_num']; 
            if($cd_num)
                $num1 = $num1 + $cd_num;
            $data = array( 
                'wgid'    =>$v['wgid'],
                'num1'    =>'-'.$num1,
                'unit_id1'=>$v['unit_id1'],
                'price'   =>$v['price'],
                'wid'   =>$wid,
                "new_time"=> time()
            ); 
            $ll = $m->add($data);
        } 
    }

    /**
     * 修改出库单审核状态 
     * @param  $osid 出库单据号 
     * @param  $status 审核状态  1.审核通过 0.审核不通过->反审核
     * @return [type] [description]
     */
    public function upOutstockStatus(){
        if(IS_POST){
            $osid = I("osid");
            $status = I("status");
            $wid =getWid();
            $arr_result = array(
                'resultcode' => 0,
                'msg' =>'成功'
            ); 
            $sql ="CALL outAuditing('".$osid."',$wid)";
            $result = M("")->query($sql); 
            if($result[0]['resultcode']!=0){
                $arr_result['resultcode'] = $result[0]['resultcode'];
                $arr_result['msg'] = $result[0]['msg']; 
            }
            $this->ajaxReturn($arr_result);
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //审核通过
    public function auditPass($osid,$ktime){
        $wid =getWid();
        $m = M("out_stock");
        $mm=M("out_stock_detail");
        $arr_result = array(
            'resultcode' => 0,
            'msg' =>'成功'
        ); 
        $data = $mm->where("osid = '".$osid."'")->select(); 
        if($data){
            $errRow ="";
            foreach ($data as $k => $v) {
                //出库单必需填写数量、单价或货损、单价
                $sumNum = $v['num1'] + $v['cd_num'];
                if(!$this->checkNum($sumNum)){
                    $errRow = $k+1;
                    break;
                }
                if(!$this->checkNum($v['price'])){
                    $errRow = $k+1;
                    break;
                }
            }
            if($errRow){
                $arr_result['resultcode'] = 10;
                $arr_result['msg'] .="单据保存成功,在第".$errRow."行数据有误,请检查数量、单价、总金额或货损、单价、总金额";
            }else{
                foreach ($data as $k => $v) {
                    //总出库数量 = 出库数 + 货损数 
                    $total_num = $v['num1'] + $v['cd_num'];
                    $sql ="CALL addOutStockInfo(".$v['out_id'].",".$v['wgid'].",".$total_num.",".$v['price'].",$ktime,$wid,".$v['unit_id1'].")"; 
                    $result = M("")->query($sql);
                    if($result[0]['resultcode']!=0){
                        $arr_result['resultcode'] = $result[0]['resultcode'];
                        $arr_result['msg'] = $result[0]['msg'].".在第".($k+1)."行,数量为：".$v['num1']."货损数量为：".$v['cd_num']."!请先填写对应数量再审核。";
                        break;
                    }
                } 
            }
           
        } 
        $this->ajaxReturn($arr_result);
        exit();
    }

    //退货
    public function backMark(){
        if(IS_POST){
            $osid = I("osid");  
            $m = M("out_stock");
            $data =array(
                "back_mark"=> 1,
                "return_time"=>time(),
                "return_id"=>session("wid")
            );
            $list = $m->where("osid='".$osid."'")->save($data); 
            if($list){ 
                $this->ajaxReturn(0);
            }else {
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //删除出库单 
    public function delOutStock(){
        if(IS_POST){
            $osid = I("osid");   
            //判断入库单是否被审核
            $m = M("out_stock");
            $result = $m->where("status = 0 and osid='".$osid."'")->find();
            if($result){
                M()->startTrans();
                $list =  $m->where("osid='".$osid."'")->delete(); 
                if($list){ 
                    $result = M("out_stock_detail")->where("osid='".$osid."'")->delete();
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
                $this->ajaxReturn(2);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    public function outstockExchange(){
        $m = M();
        $wid = getWid(); 
        $where ="";
        if(IS_POST){ 
            $stime = I("stime");$etime=I("etime");$sname=I("sname");
            $cid = I("cid");$osid=I("osid");$status = I("status");
            $this->assign("stime",$stime);
            $this->assign("etime",$etime);
            $this->assign("sname",$sname);
            $this->assign("cid",$cid);
            $this->assign("status",$status);
            if($stime){
                $stime =strtotime($stime);
                $where .=" and a.create_time>=$stime";
            }
            if($etime){
                $etime =strtotime($etime);
                $where .=" and a.create_time<=$etime";
            } 
            if($osid){
                $where .=" and a.osid like '%".$osid."%'";
            }
            if($status!=''){
                $where .=" and a.status = $status";
            } 
            if($cid!=''){
                $where .=" and a.cid = $cid";
            } 
            $this->assign("osid",$osid);
        }
        $sql ="select b.`name` cname,  a.* FROM db_out_stock a LEFT JOIN db_client b on a.cid=b.c_id";
        $sql .= " where a.create_id = $wid and a.back_mark = 1";
        $sql =$sql.$where; 
        $sql .= " order by a.status,a.create_time desc";    
        $list =$m->query($sql);
        $client = M("client");
        $clist = $client->where("create_id = $wid")->select();
        $this->assign("clist",$clist);
        $this->assign("list",$list); 
        $this->display();
    } 

    //提交出库单
    public function outStockAdd(){
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
            $list['j_prices'] =$this->strToArray(I("j_prices"));   
            $list['thans'] =$this->strToArray(I("thans"));  
            $list['sums'] =$this->strToArray(I("sums"));  
            $list['cdnums'] =$this->strToArray(I("cdnums"));
            $list['remarks']=$this->strToArray(I("remarks"));
            $status = I("status"); 
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids")); 
            $list['nei_nums'] =$this->strToArray(I("nei_nums")); 
            $list['machinings'] =$this->strToArray(I("machinings"));  
            $list['alias'] =$this->strToArray(I("alias"));
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
                $data['update_time'] =time();
                $data['update_id'] = $wid;
                if($status == 1){
                    $data['auditing_time'] = time();
                    $data['auditing_id'] = session("wid");
                }
                $m = M("out_stock");
                M()->startTrans();
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功'
                );  
                $result = $m->add($data); 
                if($result){  
                    $mm = M("out_stock_detail");  
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
                        //入库详情新增
                        $sublist = array(
                            'osid'    =>$osid,
                            'wgid'    =>$list['wgids'][$k],
                            'alias'   =>$list['alias'][$k],
                            'than'    =>$list['thans'][$k], 
                            'cd_num'  =>$cd_num,
                            'num1'    =>$num1,
                            'num2'    =>$num2,
                            'price'   =>$price1,
                            'j_price'=>$list['j_prices'][$k],
                            'unit_id1'=>$list['unit_id1s'][$k],
                            'remark'=>$list['remarks'][$k],
                            'nei_num'=>$list['nei_nums'][$k],
                            'nei_unit_id'=>$list['nei_unit_ids'][$k],
                            'nei_price'=>$list['prices'][$k],
                            'sales_amount' => $list['sums'][$k],
                            'machining'=>$list['machinings'][$k],
                        ); 
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
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }


    //出库单编辑 Ajax表单提交
    public function outStockAjaxEdit(){
         if(IS_POST){
            //数据验证
            $data = I("post.");  
            $wid = getWid(); 
            $osid = I("osid"); 
            $list =array(); 
            $mm = M("out_stock_detail");  
            $list['wgids'] =$this->strToArray(I("wgids"));   
            $list['unit_id1s'] =$this->strToArray(I("unit_ids"));
            $list['num1s'] =$this->strToArray(I("num1s")); 
            $list['num2s'] =$this->strToArray(I("num2s")); 
            $list['prices'] =$this->strToArray(I("prices"));   
            $list['j_prices'] =$this->strToArray(I("j_prices"));   
            $list['thans'] =$this->strToArray(I("thans"));  
            $list['sums'] =$this->strToArray(I("sums"));  
            $list['cdnums'] =$this->strToArray(I("cdnums"));
            $list['remarks']=$this->strToArray(I("remarks"));
            $status = I("status"); 
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids")); 
            $list['nei_nums'] =$this->strToArray(I("nei_nums")); 
            $list['machinings'] =$this->strToArray(I("machinings"));  
            $list['alias'] =$this->strToArray(I("alias"));  
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
                if($status == 1){
                    $data['auditing_time'] = time();
                    $data['auditing_id'] = session("wid");
                } 
                $m = M("out_stock");
                M()->startTrans();
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功'
                );  
                $result = $m->where("osid='".$osid."'")->save($data); 
                if($result){ 
                    //删除入库名称信息  
                    $del = $mm->where("osid='".$osid."'")->delete();
                    if($del<=0){
                      $arr_result['resultcode'] = 10;
                      $arr_result['msg'] = "删除出库详情时失败了";  
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
                            //入库详情新增
                            $sublist = array(
                                'osid'    =>$osid,
                                'wgid'    =>$list['wgids'][$k],
                                'alias'    =>$list['alias'][$k],
                                'than'    =>$list['thans'][$k], 
                                'cd_num'    =>$cd_num,
                                'num1'    =>$num1,
                                'num2'    =>$num2,
                                'price'   =>$price1,
                                'j_price'=>$list['j_prices'][$k],
                                'unit_id1'=>$list['unit_id1s'][$k],
                                'remark'=>$list['remarks'][$k],
                                'nei_num'=>$list['nei_nums'][$k],
                                'nei_unit_id'=>$list['nei_unit_ids'][$k],
                                'nei_price'=>$list['prices'][$k],
                                'sales_amount' => $list['sums'][$k],
                                'machining'=>$list['machinings'][$k],
                            );  
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


    //出库编辑加载页
    public function outstockEdit(){
        $wid = getWid();
        $osid =I("osid");
        $sql ="select ifnull(h.num1,0) - g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,aa.osname,bb.`name` cname,bb.than hid_than,b.`name` gname,aa.total,d.unit_name unit_name1,e.unit_name unit_name2,a.sales_amount totalx,a.* FROM db_out_stock aa left join db_client bb on aa.cid = bb.c_id left join db_out_stock_detail a on a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid Left JOIN db_brand f ON b.brand_id =f.brand_id LEFT JOIN db_unit d ON a.nei_unit_id = d.unit_id LEFT JOIN db_unit e ON a.unit_id1 = e.unit_id LEFT JOIN (select a.wgid,sum(a.num1)+sum(a.cd_num) as num1 from db_out_stock_detail a LEFT JOIN db_stock b on a.wgid = b.wgid where a.osid ='".$osid."' GROUP BY a.wgid) g ON g.wgid = a.wgid LEFT JOIN db_stock h on a.wgid=h.wgid WHERE a.osid = '".$osid."'  order by a.out_id asc"; 
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

    public function upStockInfo($osid){
        $m=M("out_stock_detail");
        $list = $m->where("osid = '".$osid."'")->select();
        if($list){
            $mm=M("");
            foreach ($list as $k => $v) { 
                $sql ="update db_stock set num1=num1+". $v['num1'];  
                $sql .=",new_time=".time();
                $sql .=" where wgid =".$v["wgid"];
                $ll = $mm->execute($sql);
            }
        }
    } 
    /**
     * 获取成本价格 强转换为数字 如果此处去问题 则前端提交数据有问题
     * @param string $wgid 批发商商品编号
     * @param string $num1 商品出库数量
     * @param string $cdnum 商品出库货损数
     * @return  int  成本价 如果是0，表示无法计算商品成本 
     */
    public function getCost($wgid,$num1,$cdnum){
        $num = floatval($num1);
        if($cdnum>0){
            $num =$num +floatval($cdnum);
        } 
        $result = M()->query("CALL getCost($wgid,$num)");  
        if($result[0]['d_cost']){
           return $result[0]['d_cost'];
        } 
        return 0;
    }

    //修改出库单据名称
    public function upOsname(){
        if(IS_POST){
            $osid =I("osid");
            $data =array(
                'osname'=>I("osname")
            );
            $result = M("out_stock")->where("osid='$osid'")->save($data); 
            if($result){
                $this->ajaxReturn(1); //修改成功
            }else{
                $this->ajaxReturn(0); //修改失败
            }
        }
    }

    public function outStockDetail(){
        $osid = I("osid");
        $sql ="select aa.osname,g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,bb.`name` cname,bb.phone,bb.pid,bb.cid,bb.did,bb.street,b.`name` gname,aa.total,h.unit_name uname,a.num1*a.price*a.than totalx,a.*,aa.`status`,jj.contacts FROM db_out_stock aa left join db_client bb on aa.cid = bb.c_id left join db_out_stock_detail a on a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid Left JOIN db_brand f ON b.brand_id =f.brand_id LEFT JOIN db_unit d ON a.unit_id1 = d.unit_id left join db_stock g on g.wgid = a.wgid LEFT JOIN db_unit h ON h.unit_id = a.nei_unit_id LEFT JOIN db_wholesale_user jj on aa.auditing_id = jj.wid WHERE a.osid = '".$osid."' order by a.out_id asc";
        $list = M('')->query($sql);     
        $this->assign("osid",$osid);
        $this->assign("hide_title",I("hide_title"));
        $this->assign("list",$list);
        $this->assign("status",$list[0]['status']); 
        $wid = getWid();
       // dump($list);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $osList = M("oscd")->where("wid =$wid")->find();
        if(!$osList){
            $osList = getOutStockColumnT();
        }
        $titles = $this->getOutStockTitleInfoT($wid);
//        dump($osList);
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
//        dump($osbList);
        $this->display();
    }

    public function outstockPrint(){ 
        $osid = I("osid");
        $sql ="select aa.osname,g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,bb.`name` cname,bb.phone,bb.pid,bb.cid,bb.did,bb.street,b.`name` gname,aa.total,h.unit_name uname,a.num1*a.price*a.than totalx,a.*,aa.`status`,jj.contacts FROM db_out_stock aa left join db_client bb on aa.cid = bb.c_id left join db_out_stock_detail a on a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid Left JOIN db_brand f ON b.brand_id =f.brand_id LEFT JOIN db_unit d ON a.unit_id1 = d.unit_id left join db_stock g on g.wgid = a.wgid LEFT JOIN db_unit h ON h.unit_id = a.nei_unit_id LEFT JOIN db_wholesale_user jj on aa.auditing_id = jj.wid WHERE a.osid = '".$osid."' order by a.out_id asc";
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
       // dump($osList);
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
        /*----------获取公司信息-----------*/
        $info_id=session("info_id");
        $info_list = M("wholesale_info")->where("info_id=$info_id")->find();
        $this->assign("info_list",$info_list);
        $this->assign("company",$info_list['unit_name']);
        $this->assign("company_phone",$info_list['tel']);
        $heje =num2rmb($list[0]['total']); 
        $this->assign("heje",$heje);
        // dump($info_list);  
        $this->display();
    }

    //根据jsid 获取供应商地址
    public function getSaddress(){
        if(IS_POST){
            $osid =I("osid");
            $sql ="select b.pid,b.cid,b.did,b.street from db_out_stock a left join db_client b on a.cid=b.c_id where a.osid ='$osid'";
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

    //导出出库单 excel
    public function exportJoinStock(){
        $osid = I("osid"); 
        $sql ="select g.num1 stock_num,f.brand_name,aa.remark remarkf,aa.total totalf,aa.create_time,aa.cid,bb.`name` cname,bb.phone,bb.pid,bb.cid,bb.did,bb.street,b.`name` gname,aa.total,h.unit_name uname,a.num1*a.price*a.than totalx,a.*,aa.`status`,jj.contacts FROM db_out_stock aa left join db_client bb on aa.cid = bb.c_id left join db_out_stock_detail a on a.osid = aa.osid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid Left JOIN db_brand f ON b.brand_id =f.brand_id LEFT JOIN db_unit d ON a.unit_id1 = d.unit_id left join db_stock g on g.wgid = a.wgid LEFT JOIN db_unit h ON h.unit_id = a.nei_unit_id LEFT JOIN db_wholesale_user jj on aa.auditing_id = jj.wid WHERE a.osid = '".$osid."' order by a.wgid asc";
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

    //出库记录
    public function outstockRecord()
    {
        $m = M();
        $wid = getWid(); 
        $where ="";  $gname=I("gname");
        $stime = I("stime");$etime=I("etime");
        $astime = I("astime");$aetime=I("aetime");$sname=I("sname");
        $cid = I("cid");$osid=I("osid");$status = I("status");
        $ctid =I("ctid");$hid_order=I("hid_order");
        $key_documents = I("key_documents");  
        $settlement = I("settlement");
        $this->assign("stime",$stime);
        $this->assign("etime",$etime);
        $this->assign("astime",$astime);
        $this->assign("aetime",$aetime);
        $this->assign("sname",$sname);
        $this->assign("cid",$cid);
        $this->assign("status",$status);
        $this->assign("key_documents",$key_documents);
        $this->assign("gname",$gname);
        $this->assign("ctid",$ctid);
        $this->assign("settlement",$settlement);
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
        if($astime){
            $astime =strtotime($astime);
            $where .=" and a.auditing_time>=$astime";
        }
        if($aetime){
            $aetime =strtotime($aetime);
            $where .=" and a.auditing_time<=$aetime+86399";
        } 
        if($osid){
            $where .=" and a.osid like '%".$osid."%'";
        }
        if($status!=''){
            $where .=" and a.status = $status";
        } 
        if($cid!=''){
            $where .=" and a.cid = $cid";
        } 
        if($ctid!=''){
            $where .=" and b.ctid = $ctid";
        } 
        if($key_documents!=''){
            $where .=" and a.key_documents = $key_documents";
        }  
        if($settlement!=''){
            $where .=" and a.settlement = $settlement";
        }  
        $this->assign("osid",$osid);
        
        $sql ="select b.`name` cname,  a.* FROM db_out_stock a left join db_client b on a.cid=b.c_id";
        if($gname){ 
            $sql .=" inner join(SELECT osid FROM db_out_stock_detail a inner JOIN db_wholesale_goods b ON a.wgid=b.wgid inner JOIN db_goods c ON b.gid=c.gid WHERE b.wid=$wid  AND c.`name`='".$gname."'  GROUP BY a.osid) c on a.osid = c.osid";
        }
        $sql .= " where a.create_id = $wid  and a.back_mark = 0";
        $sql =$sql.$where; 
        $order =  " order by a.status,a.update_time desc"; 
        if($astime || $aetime){
            $order =  " order by a.status,a.auditing_time desc"; 
        }    
        $orderArr =array();
        if($hid_order)
           $orderArr = explode("|",$hid_order);
        if($orderArr[0]!=""){  
            if($orderArr[0]=="create_time"){
                $order=" order by a.create_time ".$orderArr[1]; 
            } 
        } 
        $sql .=$order; 
        $list =$m->query($sql);  
        // dump($list);
        /*客户类型*/
        $ctlist = M("client_type")->where("wid=$wid")->select();
        $this->assign("ctlist",$ctlist); 
        foreach($list as $key=>$val){
            if($val['auditing_time']){
                $list[$key]['auditing_time'] = date("Y-m-d H:i:s",$val['auditing_time']);
            }else{
                $list[$key]['auditing_time'] = '';
            }
        }
        $this->assign("list",$list); 
        $this->display();
    }

    //修改结算状态 
    public function upSettlementInfo(){
        if(IS_POST){
            $osid=I("osid");
            $settlement =I("settlement");
            $data=array(
                "settlement" => $settlement,
                "update_time" =>time(),
                "update_id"=>getWid()
            );
            $result = M("out_stock")->where("osid='".$osid."'")->save($data);
            if(!empty($result)){
                $this->ajaxReturn(1); //修改成功
            }else{ 
                $this->ajaxReturn(0); //修改失败
            }
        }else{
            $this->ajaxReturn(0); //请求类型错误
        }
    }

    public function getClientInfo(){
        if(IS_POST){
            $ctid=I("ctid");
            $result = M("client")->field("c_id,name")->where("ctid =$ctid")->select(); 
            if(!empty($result)){
                $this->ajaxReturn(ReturnJSON(0,$result));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }
     
    public function outstockDataSet()
    {
        $wid =getWid(); 
        $os_list = $this->getDataColumnInfoT($wid);   
        $this->assign("os_list",$os_list); 
        $osb_list = $this->getDataColumnInfo($wid);
        $this->assign("osb_list",$osb_list);
        $this->display();
    }

     public function dataColumnSubmitT(){
        if(IS_POST){
            $wid =getWid();   
            $m = M("oscd");
            $list = $m->field('oscd_id')->where("wid =$wid")->find(); 
            $data =array(
                "wid"=>$wid,
                "osid"=>I("osid"),
                "osid_s"=>I("osid_s"),
                "osid_p"=>I("osid_p"),
                "osname"=>I("osname"),
                "osname_s"=>I("osname_s"),
                "osname_p"=>I("osname_p"),
                "cname"=>I("cname"),
                "cname_s"=>I("cname_s"),
                "cname_p"=>I("cname_p"),
                "cphone"=>I("cphone"),
                "cphone_s"=>I("cphone_s"),
                "cphone_p"=>I("cphone_p"),
                "caddress"=>I("caddress"),
                "caddress_s"=>I("caddress_s"),
                "caddress_p"=>I("caddress_p"),
                "company"=>I("company"),
                "company_s"=>I("company_s"),
                "company_p"=>I("company_p"),
                "company_address"=>I("company_address"),
                "company_address_s"=>I("company_address_s"),
                "company_address_p"=>I("company_address_p"),
                "company_phone"=>I("company_phone"),
                "company_phone_s"=>I("company_phone_s"),
                "company_phone_p"=>I("company_phone_p"),
                "ctime"=>I("ctime"),
                "ctime_s"=>I("ctime_s"),
                "ctime_p"=>I("ctime_p"),
                "remark"=>I("remark"),
                "remark_s"=>I("remark_s"),
                "remark_p"=>I("remark_p"),
                "total"=>I("total"),
                "total_s"=>I("total_s"),
                "total_p"=>I("total_p"),
                "audit"=>I("audit"),
                "audit_s"=>I("audit_s"),
                "audit_p"=>I("audit_p"),
                "check"=>I("check"),
                "check_s"=>I("check_s"),
                "check_p"=>I("check_p"),
                "delivery"=>I("delivery"),
                "delivery_s"=>I("delivery_s"),
                "delivery_p"=>I("delivery_p"),
                "checkp"=>I("checkp"),
                "checkp_s"=>I("checkp_s"),
                "checkp_p"=>I("checkp_p"),
                "take_over"=>I("take_over"),
                "take_over_s"=>I("take_over_s"),
                "take_over_p"=>I("take_over_p"),
            );

            if($list){ //修改
                $oscd_id = $list['oscd_id'];
                $result = $m->where("oscd_id = $oscd_id")->save($data); 
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
    public function dataColumnSubmit(){
        if(IS_POST){
            $wid =getWid();  
            $m = M("oscdb");
            $list = $m->field('oscdb_id')->where("wid =$wid")->find();
            $data =array(
                "wid"=>$wid,
                "gname"=>I("gname"),
                "gname_s"=>I("gname_s"),
                "gname_p"=>I("gname_p"),
                "alias"=>I("alias"),
                "alias_s"=>I("alias_s"),
                "alias_p"=>I("alias_p"),
                "bname"=>I("bname"),
                "bname_s"=>I("bname_s"),
                "bname_p"=>I("bname_p"),
                "uname"=>I("uname"),
                "uname_s"=>I("uname_s"),
                "uname_p"=>I("uname_p"),
                "num"=>I("num"),
                "num_s"=>I("num_s"),
                "num_p"=>I("num_p"),
                "p_remark"=>I("p_remark"),
                "p_remark_s"=>I("p_remark_s"),
                "p_remark_p"=>I("p_remark_p"),
                "num1"=>I("num1"),
                "num1_s"=>I("num1_s"),
                "num1_p"=>I("num1_p"),
                "than"=>I("than"),
                "than_s"=>I("than_s"),
                "than_p"=>I("than_p"),
                "price"=>I("price"),
                "price_s"=>I("price_s"),
                "price_p"=>I("price_p"),
                "tax_price"=>I("tax_price"),
                "tax_price_s"=>I("tax_price_s"),
                "tax_price_p"=>I("tax_price_p"),
                "price1"=>I("price1"),
                "price1_s"=>I("price1_s"),
                "price1_p"=>I("price1_p"),
                "j_price"=>I("j_price"),
                "j_price_s"=>I("j_price_s"),
                "j_price_p"=>I("j_price_p"),
                "cd_num"=>I("cd_num"),
                "cd_num_s"=>I("cd_num_s"),
                "cd_num_p"=>I("cd_num_p"),
                "stock"=>I("stock"),
                "stock_s"=>I("stock_s"),
                "stock_p"=>I("stock_p"),
                "remarkb"=>I("remarkb"),
                "remarkb_s"=>I("remarkb_s"),
                "remarkb_p"=>I("remarkb_p"),
                "totalb"=>I("totalb"),
                "totalb_s"=>I("totalb_s"),
                "totalb_p"=>I("totalb_p"),
                "rweight"=>I("rweight"),
                "rweight_s"=>I("rweight_s"),
                "rweight_p"=>I("rweight_p"),
                "machining"=>I("machining"),
                "machining_s"=>I("machining_s"),
                "machining_p"=>I("machining_p"),
                "check_num"=>I("check_num"),
                "check_num_s"=>I("check_num_s"),
                "check_num_p"=>I("check_num_p"),
                "totalb1"=>I("totalb1"),
                "totalb1_s"=>I("totalb1_s"),
                "totalb1_p"=>I("totalb1_p"),
            );
            if($list){ //修改
                $oscdb_id = $list['oscdb_id'];
                $result = $m->where("oscdb_id = $oscdb_id")->save($data);
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

    public function getDataColumnInfoT($wid){ 
        $columns = getOutStockColumnNameT(); 
        $mrgsList = getOutStockColumnT(); 
        $needs = getOutStockColumnNeedT();
        $titles = getOutStockColumnTitleT();
        $oscd = M("oscd")->where("wid=$wid")->find();
        $gs_list =array();
        foreach ($columns as $k => $v) {
            if(!empty($oscd)){
                if(empty($oscd[$v])){
                    $gs_list[$k]['show_name']=$titles[$v]; //显示名称 
                }
                else{
                    $gs_list[$k]['show_name']=$oscd[$v]; //显示名称
                } 
                $gs_list[$k]['isshow']= $oscd[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= $oscd[$v.'_p']; //是否打印 0.打印 1.不打印
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


    public function getDataColumnInfo($wid){ 
        $columns = getOutStockColumnName();
        $mrgsList = getOutStockColumn();
        $needs = getOutStockColumnNeed();
        $titles = getOutStockColumnTitle();
        $oscdb = M("oscdb")->where("wid=$wid")->find();
        $gs_list =array();
        foreach ($columns as $k => $v) {
            if(!empty($oscdb)){
                if(empty($oscdb[$v])){
                    $gs_list[$k]['show_name']=$titles[$v]; //显示名称 
                }
                else{
                    $gs_list[$k]['show_name']=$oscdb[$v]; //显示名称
                } 
                $gs_list[$k]['isshow']= $oscdb[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= $oscdb[$v.'_p']; //是否打印 0.打印 1.不打印
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

    //记忆价查询
    public function outstockMemory(){
        if(IS_POST){ 
            $sql ="select a.osid,a.auditing_time,e.`name` gname,b.unit_id1,f.unit_name uname,b.price,h.`name` cname FROM db_out_stock a LEFT JOIN db_out_stock_detail b ON a.osid = b.osid LEFT JOIN db_client c ON a.cid = c.c_id left join db_wholesale_goods d on b.wgid = d.wgid left join db_goods e on d.gid = e.gid  left join db_unit f on f.unit_id = b.unit_id1 left join db_client h on a.cid=h.c_id ";
            $where =" WHERE  a.`status` = 1 AND b.price > 0";
            $order=" ORDER BY a.auditing_time DESC";
            $gname = I("gname");$cid=I("cid");$stime =I("stime");$etime=I("etime"); 
            $this->assign("etime",$etime);
            $this->assign("stime",$stime);
            $this->assign("cid",$cid);
            $this->assign("gname",$gname); 
            if($gname){
                $where .=" and e.`name` ='$gname'";
            }
            if($cid){
                $where .=" and a.cid =$cid";
            }
            if($stime){
                $stime =strtotime($stime);
                $where .=" and a.auditing_time>=$stime";
            }
            if($etime){
                $etime =strtotime($etime);
                $where .=" and a.auditing_time<=$etime+86399";
            } 
            $sql.=$where.$order;
            $list =M("")->query($sql); 
            $this->assign("list",$list);
        }
        //获取客户信息
        $wid = getWid();
        $clist=M("client")->field("c_id,name")->where("create_id=$wid")->select();
        $this->assign("clist",$clist);
        // dump($clist);
        $this->display();
    }
 
}