<?php
// +----------------------------------------------------------------------
// | 批发商端----客户商品价格管理
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class ContractController extends BaseController
{
    public function contractList()
    {
        $m = M();
        $wid = getWid(); 
        $where ="";  
        $gname=I("gname");
        $stime = I("stime");
        $etime=I("etime");
        $cid = I("cid");
        $cgpid=I("cgpid");
        $status = I("status"); 
        // if($stime==""){
        //     $stime=date('Y-m-d',time());
        // }
        $this->assign("stime",$stime);
        $this->assign("etime",$etime);
        $this->assign("cid",$cid);
        $this->assign("status",$status);
        $this->assign("gname",$gname); 
        $this->assign("cgpid",$cgpid); 
        if($stime){
            $stime =strtotime($stime);
            $where .=" and a.stime>=$stime";
        }
        if($etime){
            $etime =strtotime($etime);
            $where .=" and a.etime<=$etime+86399";
        } 
        if($cgpid){
            $where .=" and a.cgpid like '%".$cgpid."%'";
        }
        if($status!=''){
            $where .=" and a.status = $status";
        } 
        if($cid!=''){
            $where .=" and a.cid = $cid";
        }  
        
        $sql ="select b.`name` cname,  a.* FROM db_contract a LEFT JOIN db_client b on a.cid=b.c_id";
        $sql .= " where a.wid = $wid";
        $sql =$sql.$where; 
        $sql .= " order by a.status,a.update_time desc";    
        $list =$m->query($sql); 
        $listArr =array();
        //合同列表
        if($gname){  
            foreach($list as $k => $v) {
                $sql1 ="select b.wgid from db_contract_detail a left join db_wholesale_goods b on a.wgid=b.wgid left join db_goods c on b.gid = c.gid  where a.cgpid='".$v['cgpid']."' and c.name ='".$gname."'"; 
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

    //编辑加载页
    public function contractEdit(){
        $wid = getWid();
        $cgpid =I("cgpid");
        $sql ="select a.cid,a.cgpname,a.stime,a.etime,a.is_enable,a.status,
            b.*,d.`name` gname,e.`name` cname,e.than,f.unit_name unit_name1,g.unit_name unit_name2 from db_contract a 
            left join db_contract_detail b on a.cgpid = b.cgpid 
            left join db_wholesale_goods c on c.wgid=b.wgid
            left join db_goods d on d.gid= c.gid
            left join db_client e on e.c_id = a.cid
            left join db_unit f on f.unit_id =b.nei_unit_id
            left join db_unit g on g.unit_id =b.unit_id
            where a.cgpid='$cgpid'";  
        $list = M("")->query($sql); 
        // dump($list);
        //判断单据是否已审核  
        if($list[0]['status']==1){
            $this->error("该单据已审核，无法进行编辑!");
            exit();
        }
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
                $ulist1[0]['unit_id'] = $v['nei_unit_id'];  
                $ulist1[0]['uname'] = $v['unit_name2'];
                $ulist1[0]['num'] = "1";
            }
            $list[$k]['usub'] =$ulist1;
        }  
        // dump($list);
        $this->assign("list",$list);
        $this->assign("cgpid",$cgpid);
        $this->display();
    }
    
    //编辑页添加数据
    public function contractAjaxEdit(){
        if(IS_POST){
            //数据验证
            $data = I("post.");  
            $wid = getWid();
            $list =array();
            $list['wgids'] =$this->strToArray(I("wgids"));   $list['unit_ids'] =$this->strToArray(I("unit_ids"));
            $list['prices'] =$this->strToArray(I("prices")); $list['tax_prices'] =$this->strToArray(I("tax_prices"));   
            $list['thans'] =$this->strToArray(I("thans"));   $list['cgnames'] =$this->strToArray(I("cgnames"));
            $list['remarks']=$this->strToArray(I("remarks"));$status = I("status"); 
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids")); 
            $list['nei_nums'] =$this->strToArray(I("nei_nums")); 
            // $this->ajaxReturn($list);exit();
            if($this->checkFrom($list)){ 
                //总表录入 
                $cgpid = I("cgpid");
                $create_id = session("wid");
                $data = array(
                    'cgpname' => I("cgpname"),
                    'cid' => I("cid"),
                    'stime'=> strtotime(I("stime")),
                    'etime'=> strtotime(I("etime")),
                    'is_enable' => I("is_enable"),
                    'wid' => $wid,
                    'create_time'=>time(),
                    'create_id' => $create_id,
                    'update_time'=>time(),
                    'update_id' => $create_id,
                );
                $m = M("contract");
                M()->startTrans();
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功'
                );  
                $result = $m->where("cgpid = '$cgpid'")->save($data); 
                if($result){  
                    $mm = M("contract_detail");   
                    //删除合同详情
                    $result = $mm->where("cgpid='$cgpid'")->delete();
                    if(empty($result)){
                        $arr_result['resultcode'] = 10;
                        $arr_result['msg'] = "删除合同详情时失败了。"; 
                    }else{
                        //详情录入 
                        foreach ($list['wgids'] as $k => $v) {
                            //计算默认单价  
                            $tax_price = floatval($list['tax_prices'][$k])/floatval($list['nei_nums'][$k]); //默认单位税前价
                            $price = $tax_price/floatval($list['thans'][$k]); //默认单位税前价  
                            $unit_id = floatval($list['unit_ids'][$k]);
                            if($unit_id<=0){
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] = "数据异常，第".($k+1)."行,默认单位异常。";  
                                $arr_result['err_row'] =$k+1;
                                break;
                            }
                            //合同详情新增
                            $sublist = array(
                                'cgpid'    =>$cgpid,
                                'wgid'    =>$list['wgids'][$k],
                                'cgname'   =>$list['cgnames'][$k],
                                'unit_id' =>$list['unit_ids'][$k],
                                'price' =>$price,
                                'tax_price' =>$tax_price,
                                'nei_price' =>$list['prices'][$k],
                                'nei_tax_price' =>$list['tax_prices'][$k], 
                                'nei_unit_id'=>$list['nei_unit_ids'][$k],
                                'nei_num'=>$list['nei_nums'][$k],
                                'than'    =>$list['thans'][$k],  
                                'remark'=>$list['remarks'][$k]
                            );  
                            $cgpdid = $mm->add($sublist);
                            if($cgpdid<=0){
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] = "新增合同详情时失败了";  
                            }  
                        } 
                    }   
                }else{
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "新增合同时失败了"; 
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

    //应用至其他客户 
    public function contractApplication(){
        $cgpid=I("cgpid");
        $wid =getWid();
        $client = M("client");
        $clist = $client->where("create_id = $wid")->select();
        $this->assign("cgpid",$cgpid);
        $this->assign("clist",$clist);
        $this->display();
    }

    //应用至其他客户 提交代码
    public function contractAppOtherClient(){
        if(IS_POST){
            $cgpid=I("cgpid");
            $cid =I("cid");
            //查询合同头部信息
            $list = M("contract")->where("cgpid ='$cgpid'")->find(); 
            $arr_result = array(
                'resultcode' => 0,
                'msg' =>'应用成功'
            ); 
            if(!empty($list)){  //存在数据
                $sublist = M("contract_detail")->where("cgpid='$cgpid'")->select();
                if(!empty($sublist)){
                    //开始添加数据
                    //生成新的合同号 
                    $wid =getWid();
                    $create_id = session("wid"); 
                    $cgpid ="HT".$wid."_".date('YmdHis',time()); 
                    $data = array(
                        'cgpid' => $cgpid,
                        'cgpname' => $list['cgpname'],
                        'cid' => $cid,
                        'stime'=> $list['stime'],
                        'etime'=> $list['etime'],
                        'is_enable' => $list['is_enable'],
                        'wid' => $list['wid'],
                        'status' => 1, //已审核 
                        'create_time'=>time(),
                        'create_id' => $create_id,
                        'update_time'=>time(),
                        'update_id' => $create_id,
                        'auditing_time' => time(),
                        'auditing_id' =>$create_id,
                    );
                    $result = M("contract")->add($data);
                    if(empty($result)){
                        $arr_result['resultcode'] = 10;
                        $arr_result['msg'] = "新增合同时失败了";  
                    }else{
                        foreach ($sublist as $k => $vo) {
                            //合同详情新增
                            $subdata = array(
                                'cgpid'    =>$cgpid,
                                'wgid'    =>$vo['wgid'],
                                'cgname'   =>$vo['cgname'],
                                'unit_id' =>$vo['unit_id'],
                                'price' =>$vo['price'],
                                'tax_price' =>$vo['tax_price'],
                                'nei_price' =>$vo['nei_price'],
                                'nei_tax_price' =>$vo['nei_tax_price'],
                                'nei_unit_id'=>$vo['nei_unit_id'],
                                'nei_num'=>$vo['nei_num'],
                                'than'    =>$vo['than'],
                                'remark'=>$vo['remark'],
                            );  
                            $result = M("contract_detail")->add($subdata);
                            if(empty($result)){
                                $arr_result['resultcode'] = 10;
                                $arr_result['msg'] = "新增合同详情时失败了"; 
                            } 
                        }
                    }
                }else{
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "未发现该合同号的详情数据。";  
                }
            }else{
                $arr_result['resultcode'] = 10;
                $arr_result['msg'] = "未发现该合同号。";  
            } 
            if($arr_result['resultcode'] == 0){
                M()->commit(); //事务提交  
                $this->ajaxReturn($arr_result);
            }else{
                M()->rollback(); //事务回滚 
                $this->ajaxReturn($arr_result);
            }

        } 
    }



    //新增合同价
    public function contractForm(){
        //生成合同号 
        $wid =getWid();
        $cgpid ="HT".$wid."_".date('YmdHis',time());
        $this->assign("cgpid",$cgpid);
        $this->display();
    }

    //审核和反审核 合同
    public function upContractStatus(){
        if(IS_POST){
            $cgpid =I("cgpid");
            $status = I("status");
            $wid =session("wid"); //当前登录用户编号
            $data =array(
                "status"=>$status,
                "update_time"=>time(),
                "update_id"=>$wid,
                "auditing_time"=>time(),
                "auditing_id"=>$wid
            );
            $result = M("contract")->where("cgpid='$cgpid'")->save($data);
            if(!empty($result)){
                $this->ajaxReturn(1);//成功
            }else{
                $this->ajaxReturn(0);//失败
            }
        }
    }

    #客户商品列表-----添加商品
    public function contractAjaxAdd(){
        if(IS_POST){
            //数据验证
            $data = I("post.");  
            $wid = getWid();
            $list =array();
            $list['wgids'] =$this->strToArray(I("wgids"));   $list['unit_ids'] =$this->strToArray(I("unit_ids"));
            $list['prices'] =$this->strToArray(I("prices")); $list['tax_prices'] =$this->strToArray(I("tax_prices"));   
            $list['thans'] =$this->strToArray(I("thans"));   $list['cgnames'] =$this->strToArray(I("cgnames"));
            $list['remarks']=$this->strToArray(I("remarks"));$status = I("status"); 
            $list['nei_unit_ids'] =$this->strToArray(I("nei_unit_ids")); 
            $list['nei_nums'] =$this->strToArray(I("nei_nums")); 
            // $this->ajaxReturn($list);exit();
            if($this->checkFrom($list)){ 
                //总表录入 
                $cgpid = I("cgpid");
                $create_id = session("wid");
                $data = array(
                    'cgpid' => $cgpid,
                    'cgpname' => I("cgpname"),
                    'cid' => I("cid"),
                    'stime'=> strtotime(I("stime")),
                    'etime'=> strtotime(I("etime")),
                    'is_enable' => I("is_enable"),
                    'wid' => $wid,
                    'create_time'=>time(),
                    'create_id' => $create_id,
                    'update_time'=>time(),
                    'update_id' => $create_id,
                );
                $m = M("contract");
                M()->startTrans();
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功'
                );  
                $result = $m->add($data); 
                if($result){  
                    $mm = M("contract_detail");  
                    //详情录入 
                    foreach ($list['wgids'] as $k => $v) {
                        //计算默认单价  
                        $tax_price = floatval($list['tax_prices'][$k])/floatval($list['nei_nums'][$k]); //默认单位税前价
                        $price = $tax_price/floatval($list['thans'][$k]); //默认单位税前价  
                        $unit_id = floatval($list['unit_ids'][$k]);
                        if($unit_id<=0){
                            $arr_result['resultcode'] = 10;
                            $arr_result['msg'] = "数据异常，第".($k+1)."行,默认单位异常。";  
                            $arr_result['err_row'] =$k+1;
                            break;
                        }
                        //合同详情新增
                        $sublist = array(
                            'cgpid'    =>$cgpid,
                            'wgid'    =>$list['wgids'][$k],
                            'cgname'   =>$list['cgnames'][$k],
                            'unit_id' =>$list['unit_ids'][$k],
                            'price' =>$price,
                            'tax_price' =>$tax_price,
                            'nei_price' =>$list['prices'][$k],
                            'nei_tax_price' =>$list['tax_prices'][$k], 
                            'nei_unit_id'=>$list['nei_unit_ids'][$k],
                            'nei_num'=>$list['nei_nums'][$k],
                            'than'    =>$list['thans'][$k],  
                            'remark'=>$list['remarks'][$k]
                        );  
                        $cgpdid = $mm->add($sublist);
                        if($cgpdid<=0){
                            $arr_result['resultcode'] = 10;
                            $arr_result['msg'] = "新增合同详情时失败了";  
                        }  
                    }   
                }else{
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "新增合同时失败了"; 
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

    //合同数据验证
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
    
    //合同详情
    public function contractDetail(){
        $cgpid = I("cgpid");
        $sql ="select a.*,b.*,c.`name` cname,c.pid,c.cid cid2,c.did,d.unit_name uname,f.`name` gname from db_contract a 
                left join db_contract_detail b on a.cgpid = b.cgpid
                left join db_client c on a.cid=c.c_id
                left join db_unit d on b.nei_unit_id = d.unit_id
                left join db_wholesale_goods e on e.wgid= b.wgid
                left join db_goods f on f.gid = e.gid
                where a.cgpid = '$cgpid' ORDER BY b.cgpdid asc";
        $list = M('')->query($sql);     
        $this->assign("cgpid",$cgpid);
        $this->assign("list",$list);
        // dump($list);
        $this->assign("status",$list[0]['status']);  
        $this->display();
    }

    //删除合同
    public function delContract()
    {
        if(IS_POST){
            $cgpid = I('cgpid');   
            //判断合同是否未审核
            $result = M("contract")->where("cgpid='$cgpid'")->find();
            $arr_result = array(
                'resultcode' => 0,
                'msg' =>'成功'
            ); 
            if($result["status"]==0){ //未审核状态
                //删除合同信息   
                //开启事务
                M()->startTrans();
                $result = M('contract')->where("cgpid ='$cgpid'")->delete();
                if(empty($result)) {  //删除失败
                    $arr_result['resultcode'] = 10; 
                    $arr_result['msg'] = "删除合同时失败了！"; 
                }else{ //删除成功，开始删除合同详情
                    $result = M('contract_detail')->where("cgpid ='$cgpid'")->delete();
                    if(empty($result)){ //详情删除失败
                        $arr_result['resultcode'] = 10; 
                        $arr_result['msg'] = "删除合同详情时失败了！";  
                    }  
                }
            }else{ 
                $arr_result['resultcode'] = 10; 
                $arr_result['msg'] = "已审核状态无法删除！";  
            }
            if($arr_result['resultcode'] == 0){
                M()->commit(); //事务提交  
                $this->ajaxReturn($arr_result);
            }else{
                M()->rollback(); //事务回滚 
                $this->ajaxReturn($arr_result);
            }  
        }
    }

    //修改合同供应商(打印抬头)
    public function upCgpname(){
        if(IS_POST){
            $cgpid =I("cgpid");
            $cgpname =I("cgpname");
            $data =array(
                "cgpname"=>$cgpname,
                "update_time" => time(),
                "update_id" => session("wid")
            );
            $result = M("contract")->where("cgpid ='$cgpid'")->save($data);
            if(empty($result)){
                $this->ajaxReturn(0);//修改失败
            }else{
                $this->ajaxReturn(1);//修改成功
            }
        }
    }
}