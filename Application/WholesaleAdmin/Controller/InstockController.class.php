<?php
// +----------------------------------------------------------------------
// | 入库管理
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class InstockController extends BaseController
{   
    /**
     * 记忆价查询
     */
    public function instockMemory(){
        $m = M("supplier");
        $slist = $m->field("sid,name")->select();
        $this->assign("slist",json_encode($slist));
        $this->display();
    }

    /**
     * 获取入库记忆价
     */
    public function getJoinStockPriceByText(){
        if(IS_GET){
            $stime = I("stime");
            $etime = I("etime");
            $sid = I("sid");
            $gname = I("gname");
            $offset = I("offset");
            $limit = I("limit");
            $sql = "select b.wgid from db_goods a inner join db_wholesale_goods b on a.gid = b.gid where a.`name` = '$gname' limit 0,1";
            $wgids = M()->query($sql);
            $wgid = 0;
            if(count($wgids)>0){
                $wgid = $wgids[0]['wgid'];
                $sql ="select a.jsid,b.wgid,c.`name` sname,d.unit_name,b.price,a.auditing_time  from db_join_stock a
                    inner join db_join_stock_detail b on a.jsid = b.jsid 
                    inner join db_supplier c on a.sid = c.sid 
                    inner join db_unit d on b.unit_id1 = d.unit_id ";
                $where = " where a.`status` = 1 and b.wgid = $wgid ";
                if($stime){
                    $where .=" and a.auditing_time >=" . strtotime($stime);
                }
                if($etime){
                    $where .=" and a.auditing_time <=" . strtotime($etime);
                }
                if($sid){
                    $where .=" and c.sid = $sid ";
                }
                $order = " order by c.sid,a.auditing_time desc ";
                $limit = " limit $offset,$limit";
                $sql = $sql.$where.$group.$order.$limit;
                $list = M()->query($sql);
                $this->ajaxReturn(buildData(0,$list));
            }else{
                $this->ajaxReturn(buildData(1));
            }
            
        }else{
            $this->ajaxReturn(buildData(7));
        }
    }

    public function instockForm()
    {     
        $wid = getWid();
        $jsid ="PR".$wid."_".time(); 
        $this->assign("time",time());
        $this->assign("jsid",$jsid);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $jsList = M("jsdc")->where("wid =$wid")->find();
        if(!$jsList){
            $jsList = getJoinStockColumnT();
        }   
        $titles = $this->getJoinStockTitleInfoT($wid);  
        $this->assign("titles",$titles);
        $this->assign("jsList",$jsList);
        $jsname = getUnitName();
        $this->assign("jsname",$jsname);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $jsbList = M("jsdcb")->where("wid =$wid")->find();
        if(!$jsbList){
            $jsbList = getJoinStockColumn();
        }   
        $t_list = $this->getJoinStockTitleInfo($wid); 
        $this->assign("t_list",$t_list);
        $this->assign("jsbList",$jsbList);
        $this->display();
    }

    //获取商品表格头部标题
    public function getJoinStockTitleInfoT($wid){
        $columns = getJoinStockColumnNameT(); 
        $titles = getJoinStockColumnTitleT();
        $jsdc = M("jsdc")->where("wid=$wid")->find();
        $arr =array();
        foreach ($columns as $k => $v) {
            if(!empty($jsdc)){
                if(empty($jsdc[$v])){
                    $arr[$v]=$titles[$v]; //显示名称 
                }
                else{
                    $arr[$v]=$jsdc[$v]; //显示名称
                } 
            }else{
                $arr[$v]=$titles[$v];
            } 
        }  
        return $arr;
    }

    //获取商品表格标题
    public function getJoinStockTitleInfo($wid){
        $columns = getJoinStockColumnName(); 
        $titles = getJoinStockColumnTitle();
        $jsdcb = M("jsdcb")->where("wid=$wid")->find();
        $arr =array();
        foreach ($columns as $k => $v) {
            if(!empty($jsdcb)){
                if(empty($jsdcb[$v])){
                    $arr[$v]=$titles[$v]; //显示名称 
                }
                else{
                    $arr[$v]=$jsdcb[$v]; //显示名称
                } 
            }else{
                $arr[$v]=$titles[$v];
            } 
        }  
        return $arr;
    }


    //入库数据验证
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

    /**
     * 新增商品库存明细数据并清除亏欠
     * @param int $join_id 入库明细编号
     * @param int 入库数量
     */
    public function addStockDetail($join_id,$wgid,$num){
        $m = M("stock_detail");
        $data=array(
            "join_id" => $join_id,
            "$wgid" => $wgid,
            "num" => $num
        );
        $owe_sales = M("owe_sales");
        $list = $this->getOweSalesInfo($owe_sales,$wgid);
        $stock_state = 0;
        if($list){ //有亏欠 
            foreach ($list as $k => $v) {//批量修改 
                $num = $num - $v['num'];  //剩余库存
                if($num>0){ //入库数正好或者多于当前亏欠数 
                    $owe_sales->where("out_id =".$v['out_id'])->delete();//移除亏欠表数据
                }else if($num==0){
                    $stock_state = 1; // 售空了
                    $owe_sales->where("out_id =".$v['out_id'])->delete();//移除亏欠表数据
                    break;
                }else{ //入库数少于当前亏欠数
                    $kqlist = array(
                        "num"=>$num
                    );
                    $owe_sales->where("out_id =".$v['out_id'])->save($kqlist);
                    $stock_state = 1; // 售空了
                    break;
                }
            } 
        }  
        if($stock_state==1){ 
            $data['stock_state'] = 1; 
            $data["sold_out_time"] =time(); 
        }
        $m->add($data);
    }

    /**
     * 获取批发商商品库存数量
     * @param  int $wgid [批发商商品编号]
     * @return int  剩余数量   
     */
    public function getStockNum($wgid){
        $m = M("stock");
        $data = $m->field("num1")->where("wgid = $wgid")->order("ktime asc")->find();
        if($data){
            return $data['num1'];
        }else{
            return 0;
        }
    } 

    /**
     * 获取商品亏欠信息
     * @param  [int] $wgid [批发商商品编号]
     * @return [array]       亏欠列表
     */
    public function getOweSalesInfo($m,$wgid){ 
        return $m->where("wgid=$wgid")->select(); 
    }

    // 新增库存数
    public function stockAdd($v){ 
        //判断库存中是否存在该商品  
        $m=M("stock"); 
        $list = $m->field('sid')->where("wgid = ".$v['wgid'])->find();
        $wid = getWid();
        if($list){  
            $sql ="update db_stock set price=".$v['price'].",num1=num1+".$v['num1']; 
            $sql .=",new_time=".time()." where sid = ".$list['sid']." and wid =$wid";
            $ll = $m->execute($sql);
        }else{
            $data = array( 
                'wgid'    =>$v['wgid'],
                'num1'    =>$v['num1'],
                'unit_id1'=>$v['unit_id1'],
                'price'   =>$v['price'],
                'wid'   =>$wid,
                "new_time"=> time()
            ); 
            $ll = $m->add($data);
        } 
    }

    //标记重点，取消重点
    public function setKeyDocuments(){
        if(IS_POST){
            $jsid = I("jsid");
            $num = I("num");
            $data=array(
                "key_documents" => $num
            );
            $result = M("join_stock")->where("jsid = '$jsid'")->save($data);
            if($result){
                $this->ajaxReturn(0);//修改成功
            }else{
                $this->ajaxReturn(1);//修改失败
            }
        }
    }

    //库存退货
    public function upStockInfo($jsid){ 
        //无：可以反审核 根据join_id删除库存详情信息
        $sql ="select c.join_id FROM db_join_stock a LEFT JOIN db_join_stock_detail b ON a.jsid = b.jsid LEFT JOIN db_owe_sales_detail c ON b.join_id = c.join_id WHERE a.jsid = '".$jsid."' and a.status = 1";
        $result = M("")->query($sql);
        if($result[0]['join_id']){//有：无法反审核
            $this->ajaxReturn(ReturnJSON(11)); 
            exit();
        }else{  
            M()->startTrans();
            $m = M("join_stock"); 
            $data =array(
                "status"=> 0,
                "counter_audit_time"=>time(),
                "counter_audit_id"=>session("wid")
            );  
            $arr_result = array(
                'resultcode' => 0,
                'msg' =>'成功'
            ); 
            $list = $m->where("jsid = '".$jsid."'")->save($data);  //入库单状态修改成功
            if($list>0){
                //修改库存数
                $m=M("join_stock_detail"); 
                $list = $m->where("jsid = '".$jsid."'")->select();
                if($list){
                    $mm=M("");
                    foreach ($list as $k => $v) { 
                        $sql ="select b.price from db_join_stock a left join db_join_stock_detail b on a.jsid=b.jsid";
                        $sql .=" where a.status=1 and b.wgid =".$v["wgid"];
                        $sql .=" order by  a.create_time desc limit 0,1";
                        $price_list = $mm->query($sql); 
                        // $this->ajaxReturn($price_list);exit();
                        if(count($price_list)>0){
                            $sql ="update db_stock set price=".$price_list[0]['price'].",num1=num1-". $v['num1'];  
                        }else{
                            $sql ="update db_stock set num1=num1-". $v['num1'];  
                        }  
                        $sql .=",new_time=".time();
                        $sql .=" where wgid =".$v["wgid"];
                        $ll = $mm->execute($sql);
                        if($ll<=0){ 
                            $arr_result['resultcode'] = 1;
                            $arr_result['msg'] = "修改库存信息时失败了";
                            break;
                        }
                    }
                } 
                //移除库存详情信息
                $sql ="select b.join_id from db_join_stock a left join db_join_stock_detail b on a.jsid = b.jsid where a.jsid = '".$jsid."'"; 
                $data = M("")->query($sql); 
                if($data){
                    foreach ($data as $k => $v) {
                        $mmm= M("stock_detail");
                        $rownum =  $mmm->where("join_id = ".$v['join_id'])->delete();
                        if($rownum<=0){
                            $arr_result['resultcode'] = 1;
                            $arr_result['msg'] = "移除库存详情信息时失败了";
                            break; 
                        }
                    }
                }  
                if($arr_result['resultcode']==0){
                    M()->commit();//事务提交
                }else{
                    M()->rollback(); //事务回滚
                } 
                $this->ajaxReturn($arr_result); 
            }else{
                M()->rollback(); //事务回滚
                $this->ajaxReturn(ReturnJSON(12)); 
            } 
        } 
    }

    //退货
    public function backMark(){
        if(IS_POST){
            $jsid = I("jsid"); 
            $m = M("join_stock");
            $data =array(
                "back_mark"=> 1,
                'return_time'=>time(),
                'return_id'=>session("wid")
            );
            $list = $m->where("jsid='".$jsid."'")->save($data); 
            if($list){ 
                $this->ajaxReturn(0);
            }else {
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //删除入库单 
    public function delJoinStock(){
        if(IS_POST){
            $jsid = I("jsid");   
            //判断入库单是否被审核
            $m = M("join_stock");
            $result = $m->where("status = 0 and jsid='".$jsid."'")->find();
            if($result){
                M()->startTrans();
                $list =  $m->where("jsid='".$jsid."'")->delete(); 
                if($list){ 
                    $result = M("join_stock_detail")->where("jsid='".$jsid."'")->delete();
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

    //提交入库单
    public function instockAdd(){
        if(IS_POST){
            //数据验证
            $data = I("post.");  
            $wid = getWid();
            $list =array(); 
            $list['wgids'] =$this->strToArray(I("wgids"));   
            $list['unit_id1s'] =$this->strToArray(I("unit_ids"));
            $list['num1s'] =$this->strToArray(I("num1s")); 
            $list['prices'] =$this->strToArray(I("prices"));  
            $list['sums'] =$this->strToArray(I("sums"));  
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids"));  
            $list['nei_nums'] =$this->strToArray(I("nei_nums"));  
            // $this->ajaxReturn($list);exit();
            $status =I("status");
            if($this->checkFrom($list)){
                //总表录入 
                $jsid = I("jsid");
                $data = array(
                    'jsid' => $jsid,
                    'jsname'=>I("jsname"),
                    'sid' => I("sid"), 
                    'total' => I("total"),
                    'carry_fee' => I("carry_fee"),
                    'unloading_fee' => I("unloading_fee"),
                    'agency_fee' => I("agency_fee"),
                    'remark' => I("remark"),
                    'settlement' => I("settlement"),
                    'create_id' => $wid
                );
                if($status == 1){
                    $data['auditing_time'] = time();
                    $data['auditing_id'] = session("wid");
                }
                $create_time = I("create_time");
                if($create_time){
                    $data['create_time'] = strtotime($create_time);
                }else{
                    $data['create_time'] = time();
                }
                $m = M("join_stock");
                M()->startTrans();
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功'
                );  
                $result = $m->add($data); 
                if($result){  
                    $mm = M("join_stock_detail");  
                    //详情录入
                    foreach ($list['wgids'] as $k => $v) {
                        //计算默认单价
                        $price1 = floatval($list['prices'][$k])/floatval($list['nei_nums'][$k]);
                        $num1 =floatval($list['num1s'][$k])*floatval($list['nei_nums'][$k]);
                        $unit_id = floatval($list['unit_id1s'][$k]);
                        if($unit_id<=0){
                            $arr_result['resultcode'] = 10;
                            $arr_result['msg'] = "数据异常，第".($k+1)."行,默认单位异常。"; 
                            $arr_result['err_row'] =$k+1;
                            break;
                        }
                        //入库详情新增
                        $sublist = array(
                            'jsid'    =>$jsid,
                            'wgid'    =>$list['wgids'][$k],
                            'num1'    =>$num1,
                            'unit_id1'=>$list['unit_id1s'][$k],
                            'price'=>$price1,
                            'nei_num'=>$list['nei_nums'][$k],
                            'nei_unit_id'=>$list['nei_unit_ids'][$k],
                            'nei_price'=>$list['prices'][$k],
                            'total_cost'=>$list['sums'][$k],
                            'create_time'=>time()
                        ); 
                        $join_id = $mm->add($sublist);
                        //保存到库存中
                        if($join_id<=0){
                            $arr_result['resultcode'] = 10;
                            $arr_result['msg'] = "新增入库详情时失败了";  
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

    //修改入库名称
    public function upJsname(){
        if(IS_POST){
            $jsid =I("jsid");
            $data =array(
                'jsname'=>I("jsname")
            );
            $result = M("join_stock")->where("jsid='$jsid'")->save($data); 
            if($result){
                $this->ajaxReturn(1); //修改成功
            }else{
                $this->ajaxReturn(0); //修改失败
            }
        }
    }

    public function instockAjaxEdit(){
         if(IS_POST){
            //数据验证
            $data = I("post.");  
            $wid = getWid();
            $jsid = I("jsid"); 
            $list =array();
            //wgids,unit_id1s,num1s,prices,unit_id2s,num2s,unit_id3s,num3s,unit_id4s,num4s
            $list['wgids'] =$this->strToArray(I("wgids"));   
            $list['unit_id1s'] =$this->strToArray(I("unit_ids"));
            $list['num1s'] =$this->strToArray(I("num1s")); 
            $list['prices'] =$this->strToArray(I("prices")); 
            $list['sums'] =$this->strToArray(I("sums"));
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids"));  
            $list['nei_nums'] =$this->strToArray(I("nei_nums"));  
            // $this->ajaxReturn($list);exit();
            $status =I("status");
            if($this->checkFrom($list)){
                //总表录入  
                $data = array(
                    'sid' => I("sid"),
                    'jsname' => I("jsname"), 
                    'total' => I("total"),
                    'carry_fee' => I("carry_fee"),
                    'unloading_fee' => I("unloading_fee"),
                    'agency_fee' => I("agency_fee"),
                    'remark' => I("remark"),
                    'update_time'=>time(),
                    'update_id' => $wid
                );
                $create_time = I("create_time");
                if($create_time){
                    $data['create_time'] = strtotime($create_time);
                }
                if($status == 1){
                    $data['auditing_time'] = time();
                    $data['auditing_id'] = session("wid");
                }
                $m = M("join_stock"); 
                M()->startTrans();
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功'
                );   
                $result = $m->where("jsid='".$jsid."'")->save($data); 
                if($result){  
                     //删除入库名称信息 
                    $mm = M("join_stock_detail");  
                    $del = $mm->where("jsid='".$jsid."'")->delete();
                    if($del==0){
                        $arr_result['resultcode'] = 10;
                        $arr_result['msg'] = "数据异常，删除入库单时。"; 
                    }else{
                        //详情录入
                        foreach ($list['wgids'] as $k => $v) {
                            //计算默认单价
                            $price1 = floatval($list['prices'][$k])/floatval($list['nei_nums'][$k]);
                            $num1 =floatval($list['num1s'][$k])*floatval($list['nei_nums'][$k]);
                            $unit_id = floatval($list['unit_id1s'][$k]);
                            if($unit_id<=0){
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] = "数据异常，第".($k+1)."行,默认单位异常。";  
                                $arr_result['err_row'] =$k+1;
                                break;
                            }
                            //入库详情新增
                            $sublist = array(
                                'jsid'    =>$jsid,
                                'wgid'    =>$list['wgids'][$k],
                                'num1'    =>$num1,
                                'unit_id1'=>$list['unit_id1s'][$k],
                                'price'=>$price1,
                                'nei_num'=>$list['nei_nums'][$k],
                                'nei_unit_id'=>$list['nei_unit_ids'][$k],
                                'nei_price'=>$list['prices'][$k],
                                'total_cost'=>$list['sums'][$k],
                                'create_time'=>time()
                            );  
                            $join_id = $mm->add($sublist);
                            if($join_id<=0){
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

    //入库单编辑
    public function instockEdit(){
        $wid = getWid();
        $jsid =I("jsid");
        $sql ="select  aa.jsname,f.brand_name,aa.agency_fee,aa.carry_fee,aa.remark,aa.create_time,aa.unloading_fee,aa.sid,bb.`name` sname,b.`name` gname,aa.total,d.unit_name unit_name2,g.unit_name unit_name1,a.num1*a.price totalx,a.*,g.unit_name uname FROM db_join_stock aa left join db_supplier bb on aa.sid = bb.sid left join db_join_stock_detail a on a.jsid = aa.jsid LEFT JOIN db_wholesale_goods bbb ON bbb.wgid = a.wgid LEFT JOIN db_goods b ON bbb.gid = b.gid Left JOIN db_brand f ON b.brand_id =f.brand_id LEFT JOIN db_unit d ON a.unit_id1 = d.unit_id LEFT JOIN db_unit g ON g.unit_id = a.nei_unit_id WHERE a.jsid = '".$jsid."' order by a.join_id asc"; 
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
        $this->assign("jsid",$jsid);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $jsList = M("jsdc")->where("wid =$wid")->find();
        if(!$jsList){
            $jsList = getJoinStockColumnT();
        }
        $titles = $this->getJoinStockTitleInfoT($wid);
        $this->assign("titles",$titles);
        $this->assign("jsList",$jsList);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息
        $jsbList = M("jsdcb")->where("wid =$wid")->find();
        if(!$jsbList){
            $jsbList = getJoinStockColumn();
        }
        $t_list = $this->getJoinStockTitleInfo($wid);
        $this->assign('list',$list);
        $this->assign("t_list",$t_list);
        $this->assign("jsbList",$jsbList);
        // dump($jsList);
//         dump($jsbList);
        $this->display();
    }

    public function instockRecord()
    {
        $m = M();
        $wid = getWid(); 
        $where ="";  
        $gname =I("gname");
        $stime = I("stime");$etime=I("etime");$sname=I("sname");
        $sid = I("sid");$jsid=I("jsid");$status = I("status");
        $key_documents = I("key_documents");
        $settlement = I("settlement");
        $this->assign("stime",$stime);
        $this->assign("etime",$etime);
        $this->assign("sname",$sname);
        $this->assign("sid",$sid); 
        $this->assign("status",$status);
        $this->assign("key_documents",$key_documents);
        $this->assign("gname",$gname);
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
        if($jsid){
            $where .=" and a.jsid like '%".$jsid."%'";
        }
        if($status!=''){
            $where .=" and a.status = $status";
        } 
        if($sid!=''){
            $where .=" and a.sid = $sid";
        }  
        if($key_documents!=''){
            $where .=" and a.key_documents = $key_documents";
        }  
        if($settlement!=''){
            $where .=" and a.settlement = $settlement";
        }  
        
        $this->assign("jsid",$jsid); 
        $sql ="select b.`name` s_name,a.* from db_join_stock a left join db_supplier b on a.sid = b.sid";
        if($gname){ 
            $sql .=" inner join(select jsid from db_join_stock_detail a inner join db_wholesale_goods b ON a.wgid=b.wgid inner join db_goods c ON b.gid=c.gid WHERE b.wid=$wid AND c.`name`='".$gname."' group by a.jsid) c on a.jsid = c.jsid";
        }
        $sql .= " where a.create_id = $wid and a.back_mark = 0";
        $sql =$sql.$where; 
        $sql .= " order by a.status,create_time desc";   
        $list =$m->query($sql); 
        $slist = M("supplier")->where("create_id = $wid")->select();
        $this->assign("slist",$slist);
        $this->assign("list",$list);  
        $this->display();
    }

    //修改结算状态 
    public function upSettlementInfo(){
        if(IS_POST){
            $jsid=I("jsid");
            $settlement =I("settlement");
            $data=array(
                "settlement" => $settlement,
                "update_time" =>time(),
                "update_id"=>getWid()
            );
            $result = M("join_stock")->where("jsid='".$jsid."'")->save($data);
            if(!empty($result)){
                $this->ajaxReturn(1); //修改成功
            }else{ 
                $this->ajaxReturn(0); //修改失败
            }
        }else{
            $this->ajaxReturn(0); //请求类型错误
        }
    }

    public function instockExchange(){
        $m = M();
        $wid = getWid(); 
        $where ="";
        if(IS_POST){ 
            $stime = I("stime");$etime=I("etime");$sname=I("sname");
            $sid = I("sid");$jsid=I("jsid"); 
            $this->assign("stime",$stime);
            $this->assign("etime",$etime);
            $this->assign("sname",$sname);
            $this->assign("sid",$sid); 
            if($stime){
                $stime =strtotime($stime);
                $where .=" and a.create_time>=$stime";
            }
            if($etime){
                $etime =strtotime($etime);
                $where .=" and a.create_time<=$etime";
            } 
            if($jsid){
                $where .=" and a.jsid like '%".$jsid."%'";
            }  
            if($sid!=''){
                $where .=" and a.sid = $sid";
            } 
            $this->assign("jsid",$jsid);
        }
        $sql ="select b.`name` s_name,a.* from db_join_stock a left join db_supplier b on a.sid = b.sid";
        $sql .= " where a.create_id = $wid and a.back_mark = 1";
        $sql =$sql.$where; 
        $sql .= " order by a.status,create_time desc";    
        $list =$m->query($sql);
        $this->assign("list",$list); 
        $this->display();
    }

    //修改入库单审核状态
    public function upInstockStatus(){
        if(IS_POST){
            $jsid = I("jsid");
            $status = I("status");  
            $wid = getWid();
            $sql ="CALL joinAuditing('".$jsid."',".$wid.")"; 
            $result = M("")->query($sql);
            $arr_result = array(
                'resultcode' => 0,
                'msg' =>'成功'
            ); 
            if($result[0]['resultcode']!=0){
                $arr_result['resultcode'] = $result[0]['resultcode'];
                $arr_result['msg'] = $result[0]['msg'];
            }
            $this->ajaxReturn($arr_result);
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }


    public function instockPrint(){
        $jsid = I("jsid");
        $sql ="select aa.jsname,b.`name` gname,c.brand_name bname,d.unit_name uname,a.*, f.*,g.`name` sname,g.pid,g.cid,g.did,g.street,phone FROM
               db_join_stock_detail a LEFT JOIN db_join_stock aa on a.jsid=aa.jsid LEFT JOIN db_wholesale_goods bb ON a.wgid = bb.wgid LEFT JOIN db_goods b ON bb.gid = b.gid LEFT JOIN db_brand c ON b.brand_id = c.brand_id LEFT JOIN db_unit d ON a.nei_unit_id = d.unit_id LEFT JOIN db_join_stock f ON a.jsid = f.jsid LEFT JOIN db_supplier g ON f.sid=g.sid WHERE a.jsid ='".$jsid."' order by a.join_id asc"; 
        $list = M('')->query($sql);  
        $sum = $list[0]['num1'] * $list[0]['price'];  //金额
        $sid = $list[0]['sid'];
        $this->assign("jsid",$jsid);   //入库单据号
        $this->assign("sum",$sum);
        $this->assign("sid",$sid);
        $this->assign("list",$list);
        $this->assign("status",$list[0]['status']); 
        $audit = session("contacts"); 
        $unit_name = getUnitName();
        $this->assign("unit_name",$unit_name);
        $this->assign("audit",$audit);
        // dump($list);
        $wid = getWid();
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $jsList = M("jsdc")->where("wid =$wid")->find();
        if(!$jsList){
            $jsList = getJoinStockColumnT();
        }   
        $titles = $this->getJoinStockTitleInfoT($wid);
        // dump($titles);
        $this->assign("titles",$titles);
        $this->assign("jsList",$jsList);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $jsbList = M("jsdcb")->where("wid =$wid")->find();
        if(!$jsbList){
            $jsbList = getJoinStockColumn();
        }   
        $t_list = $this->getJoinStockTitleInfo($wid); 
        $this->assign("t_list",$t_list);
        $this->assign("jsbList",$jsbList);
        /*----------获取公司信息-----------*/
        $info_id=session("info_id");
        $info_list = M("wholesale_info")->where("info_id=$info_id")->find();
        $this->assign("info_list",$info_list);
        $this->assign("company",$info_list['unit_name']);
        $this->assign("company_phone",$info_list['tel']);
        $heje =num2rmb($list[0]['total']); 
        $this->assign("heje",$heje);
        $this->display();
    }

    //根据jsid 获取供应商地址
    public function getSaddress(){
        if(IS_POST){
            $jsid =I("jsid");
            $sql ="select b.pid,b.cid,b.did,b.street from db_join_stock a left join db_supplier b on a.sid=b.sid where a.jsid ='$jsid'";
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

    public function instockDetail(){
        $jsid = I("jsid");
        $sql ="select aa.jsname,b.`name` gname,c.brand_name bname,d.unit_name uname,a.*, f.*,g.`name` sname,g.pid,g.cid,g.did,g.street,phone FROM
	           db_join_stock_detail a LEFT JOIN db_join_stock aa on a.jsid=aa.jsid LEFT JOIN db_wholesale_goods bb ON a.wgid = bb.wgid LEFT JOIN db_goods b ON bb.gid = b.gid LEFT JOIN db_brand c ON b.brand_id = c.brand_id LEFT JOIN db_unit d ON a.nei_unit_id = d.unit_id LEFT JOIN db_join_stock f ON a.jsid = f.jsid LEFT JOIN db_supplier g ON f.sid=g.sid WHERE a.jsid ='".$jsid."' order by a.join_id asc"; 
        $list = M('')->query($sql);   
        $sum = $list[0]['num1'] * $list[0]['price'];  //金额
        $sid = $list[0]['sid'];
        $this->assign("jsid",$jsid);   //入库单据号
        $this->assign("hide_title",I("hide_title"));
        $this->assign("sum",$sum);
        $this->assign("sid",$sid);
        $this->assign("list",$list);
        $this->assign("status",$list[0]['status']); 
        $audit = session("contacts"); 
        $this->assign("unit_name",$unit_name);
        $this->assign("audit",$audit); 
        $wid = getWid();
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $jsList = M("jsdc")->where("wid =$wid")->find();
        if(!$jsList){
            $jsList = getJoinStockColumnT();
        }   
        $titles = $this->getJoinStockTitleInfoT($wid);
//        dump($list);
        $this->assign("titles",$titles);
        $this->assign("jsList",$jsList);
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $jsbList = M("jsdcb")->where("wid =$wid")->find();
        if(!$jsbList){
            $jsbList = getJoinStockColumn();
        }   
        $t_list = $this->getJoinStockTitleInfo($wid); 
        $this->assign("t_list",$t_list);
        $this->assign("jsbList",$jsbList);

        $this->display();
    } 

    //导出入门单 excel
    public function exportJoinStock(){
        $jsid = I("jsid");
        $sql ="select b.`name` gname,c.brand_name bname,d.unit_name uname,a.*, f.*,g.`name` sname,g.pid,g.cid,g.did,g.street,phone FROM
               db_join_stock_detail a LEFT JOIN db_wholesale_goods bb ON a.wgid = bb.wgid LEFT JOIN db_goods b ON bb.gid = b.gid LEFT JOIN db_brand c ON b.brand_id = c.brand_id LEFT JOIN db_unit d ON a.nei_unit_id = d.unit_id LEFT JOIN db_join_stock f ON a.jsid = f.jsid LEFT JOIN db_supplier g ON f.sid=g.sid WHERE a.jsid ='".$jsid."' order by a.wgid asc"; 
        $list = M('')->query($sql);   
        $sum = $list[0]['num1'] * $list[0]['price'];  //金额
        $sid = $list[0]['sid']; 
        $audit = session("contacts");  //审核人
        $unit_name = getUnitName(); //单位名称
        $address =I("address");
       
        
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
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(40);//设置行高
         
        $objSheet->setCellValue("A1", "入库单据号")
                    ->setCellValue("B1", "日期")
                    ->setCellValue("C1", "供应商")
                    ->setCellValue("D1", "供应商地址")
                    ->setCellValue("E1", "单据金额(元)")
                    ->setCellValue("F1", "审核人")
                    ->setCellValue("G1", "供应商联系方式")
                    ->setCellValue("H1", "备注");
                    // ->mergeCells('D1:F1');  //合并单元格 
        $arr =array("A1","B1","C1","D1","E1","F1","G1","H1","A2","B2","C2","D2","E2","F2","G2","H2","A4","B4","C4","D4","E4","F4"); 
        //头部信息 入库单据号 日期 供应商 供应商地址 单据金额(元) 审核人 供应商联系方式 备注 
        $objSheet->setCellValue("A2", $jsid)
                ->setCellValue("B2", date('Y-m-d H:i:s',$list[0]['create_time']))
                ->setCellValue("C2", $list[0]['sname'])
                ->setCellValue("D2", $address)
                ->setCellValue("E2", $list[0]['total'])
                ->setCellValue("F2", $audit)
                ->setCellValue("G2", $list[0]['phone'])
                ->setCellValue("H2", $list[0]['remark']);
         /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $wid = getWid();
        $jsbList = M("jsdcb")->where("wid =$wid")->find();
        if(!$jsbList){
            $jsbList = getJoinStockColumn();
        }   
        $t_list = $this->getJoinStockTitleInfo($wid); 

        $objSheet->setCellValue("A4", $t_list['gname'])
                    ->setCellValue("B4", $t_list['bname'])
                    ->setCellValue("C4", $t_list['uname'])
                    ->setCellValue("D4", $t_list['num'])
                    ->setCellValue("E4", $t_list['price']) 
                    ->setCellValue("F4", $t_list['totalb']);
        //内容部分
        $n = 5;
        foreach ($list as $k => $vo) {
            $objSheet->setCellValue("A".$n, $vo['gname'])
                    ->setCellValue("B".$n, $vo['bname'])
                    ->setCellValue("C".$n, $vo['uname'])
                    ->setCellValue("D".$n, $vo['num1']/$vo['nei_num'])
                    ->setCellValue("E".$n, $vo['price']) 
                    ->setCellValue("F".$n, $vo['total_cost']);
            array_push($arr,"A".$n,"B".$n,"C".$n,"D".$n,"E".$n,"F".$n);
            $n++;
        }
        $this->setExcelBorder($objPHPExcel,$arr);
                    
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");  //Excel2007生成后缀.xlsx 
        $filename ="入库单".$jsid;
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


    public function backMarkDetail(){
        $jsid = I("jsid");  
        $sql ="select b.`name` gname,c.brand_name,d.unit_name unit_name1,a.* from db_join_stock_detail a left join db_wholesale_goods bb on a.wgid = bb.wgid LEFT JOIN db_goods b ON bb.gid = b.gid left join db_brand c on b.brand_id = c.brand_id left join db_unit d on a.unit_id1 = d.unit_id  where jsid ='".$jsid."'";
        $list = M('')->query($sql); 
        // dump($list);
        $this->assign("jsid",$jsid);
        $this->assign("list",$list);
        $this->display();
    }

    public function instockDataSet()
    {
        $wid =getWid(); 
        $js_list = $this->getDataColumnInfoT($wid);  
        $this->assign("js_list",$js_list);
        $jsb_list = $this->getDataColumnInfo($wid);
        $this->assign("jsb_list",$jsb_list);
        // dump($jsb_list);
        $this->display();
    }

    //商品数据列表头设置数据提交
    public function dataColumnSubmitT(){
        if(IS_POST){
            $wid =getWid();  
            $m = M("jsdc");
            $list = $m->field('jsdc_id')->where("wid =$wid")->find();
            $data =array(
                "wid"=>$wid,
                "jsid"=>I("jsid"),
                "jsid_s"=>I("jsid_s"),
                "jsid_p"=>I("jsid_p"),
                "jsname"=>I("jsname"),
                "jsname_s"=>I("jsname_s"),
                "jsname_p"=>I("jsname_p"),
                "sname"=>I("sname"),
                "sname_s"=>I("sname_s"),
                "sname_p"=>I("sname_p"),
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
                "cfee"=>I("cfee"),
                "cfee_s"=>I("cfee_s"),
                "cfee_p"=>I("cfee_p"),
                "ufee"=>I("ufee"),
                "ufee_s"=>I("ufee_s"),
                "ufee_p"=>I("ufee_p"),
                "afee"=>I("afee"),
                "afee_s"=>I("afee_s"),
                "afee_p"=>I("afee_p"),
                "remark"=>I("remark"),
                "remark_s"=>I("remark_s"),
                "remark_p"=>I("remark_p"),
                "total"=>I("total"),
                "total_s"=>I("total_s"),
                "total_p"=>I("total_p"), 
                "saddress"=>I("saddress"),
                "saddress_s"=>I("saddress_s"),
                "saddress_p"=>I("saddress_p"),
                "stel"=>I("stel"),
                "stel_s"=>I("stel_s"),
                "stel_p"=>I("stel_p"),
                "audit"=>I("audit"),
                "audit_s"=>I("audit_s"),
                "audit_p"=>I("audit_p"),
                "settlement"=>I("settlement"),
                "settlement_s"=>I("settlement_s"),
                "settlement_p"=>I("settlement_p"),
            );
            if($list){ //修改
                $jsdc_id = $list['jsdc_id'];
                $result = $m->where("jsdc_id = $jsdc_id")->save($data);
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
            $m = M("jsdcb");
            $list = $m->field('jsdcb_id')->where("wid =$wid")->find();
            $data =array(
                "wid"=>$wid,
                "gname"=>I("gname"),
                "gname_s"=>I("gname_s"),
                "gname_p"=>I("gname_p"),
                "bname"=>I("bname"),
                "bname_s"=>I("bname_s"),
                "bname_p"=>I("bname_p"),
                "uname"=>I("uname"),
                "uname_s"=>I("uname_s"),
                "uname_p"=>I("uname_p"),
                "num"=>I("num"),
                "num_s"=>I("num_s"),
                "num_p"=>I("num_p"),
                "price"=>I("price"),
                "price_s"=>I("price_s"),
                "price_p"=>I("price_p"),
                "shelf"=>I("shelf"),
                "shelf_s"=>I("shelf_s"),
                "shelf_p"=>I("shelf_p"),
                "totalb"=>I("totalb"),
                "totalb_s"=>I("totalb_s"),
                "totalb_p"=>I("totalb_p")
            );
            if($list){ //修改
                $jsdcb_id = $list['jsdcb_id'];
                $result = $m->where("jsdcb_id = $jsdcb_id")->save($data);
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
        $columns = getJoinStockColumnNameT();
        $mrgsList = getJoinStockColumnT();
        $needs = getJoinStockColumnNeedT();
        $titles = getJoinStockColumnTitleT();
        $jsdc = M("jsdc")->where("wid=$wid")->find();
        $gs_list =array();
        foreach ($columns as $k => $v) {
            if(!empty($jsdc)){
                if(empty($jsdc[$v])){
                    $gs_list[$k]['show_name']=$titles[$v]; //显示名称 
                }
                else{
                    $gs_list[$k]['show_name']=$jsdc[$v]; //显示名称
                } 
                $gs_list[$k]['isshow']= $jsdc[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= $jsdc[$v.'_p']; //是否打印 0.打印 1.不打印
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
        $columns = getJoinStockColumnName();
        $mrgsList = getJoinStockColumn();
        $needs = getJoinStockColumnNeed();
        $titles = getJoinStockColumnTitle();
        $jsdcb = M("jsdcb")->where("wid=$wid")->find();
        $gs_list =array();
        foreach ($columns as $k => $v) {
            if(!empty($jsdcb)){
                if(empty($jsdcb[$v])){
                    $gs_list[$k]['show_name']=$titles[$v]; //显示名称 
                }
                else{
                    $gs_list[$k]['show_name']=$jsdcb[$v]; //显示名称
                } 
                $gs_list[$k]['isshow']= $jsdcb[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= $jsdcb[$v.'_p']; //是否打印 0.打印 1.不打印
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