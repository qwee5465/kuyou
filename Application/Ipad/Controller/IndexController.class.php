<?php
// +----------------------------------------------------------------------
// | Ipad主页
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace Ipad\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){ 
        $this->display();
    } 

    //注销
    public function logout(){
        if(IS_POST){
            session(null);
            $this->ajaxReturn(1);
        }
    }

    public function main(){ 
    	$this->assign("contacts",session('contacts'));
    	$this->display();
    }

    //采购单
    public function purchaseReport(){   
    	/*-----------获取供应商-----------*/
    	// $wid = getWid();
		
    	/*-----------商品分类一级分类-----------*/
    	// $gtlist = M("goods_type")->where("wid=$wid and series = 1")->select();
    	// $this->assign("gtlist",$gtlist); 
    	$this->assign("create_time",date('Y-m-d',time()));
    	$this->display();
    }

    //获取供应商类型信息
    public function getSupplierTypeInfo(){
        if(IS_POST){
            $wid = I("wid");
            $stlist = M("supplier_type")->field('stid,type_name')->where("wid=$wid")->select();
            if(!empty($stlist)){
                $this->ajaxReturn(ReturnJSON(0,$stlist));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //获取供应商信息 
    public function getSupplierAjaxInfo(){
        if(IS_POST){
            $wid =I("wid");
            $stid =I("stid");
            $slist = M("supplier")->field('sid,name')->where("create_id=$wid and stid = $stid")->select();
            $this->ajaxReturn(ReturnJSON(0,$slist));
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        } 
    }

    //获取采购单信息
    public function getPurchaseInfo(){ 
    	if(IS_POST){
    		$wid =I("wid");
    		$where ="where c.wid = $wid and b.j_status =0"; 
            $create_time =I("create_time");
            $gtid=I("gtid");$gtid1=I("gtid1");$gtid2=I("gtid2"); 
            $sid=I("sid");$p_status =I("p_status"); $stid =I("stid");
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
            if($stid!=""){
                $where .=" and w.stid = $stid";
            }
            if($sid!=""){
                $where .=" and w.sid = $sid";
            }
            if($p_status!=""){
            	$where .=" and b.p_status = $p_status";
            }
    		$sql ="select b.p_sid,b.out_id,w.`name` sname,k.type_name tname3,j.type_name tname2,h.type_name tname1,b.wgid,d.`name` gname,b.num1 num,b.num1 / b.nei_num num1,g.`code`,b.p_remark,b.remark,ee.unit_name jcuname,e.unit_name uname,t.num1 stock_num,b.p_nei_price p_price,b.p_num/b.p_nei_num p_num,b.p_nei_unit_id p_unit_id,b.p_status  
			FROM db_purchase a 
			LEFT JOIN db_purchase_detail b ON a.osid = b.osid 
			LEFT JOIN db_wholesale_goods c ON c.wgid = b.wgid 
			LEFT JOIN db_goods d ON d.gid = c.gid 
			LEFT JOIN db_unit e ON e.unit_id = b.nei_unit_id 
			LEFT JOIN db_unit ee ON ee.unit_id = b.unit_id1 
			LEFT JOIN db_client g ON g.c_id = a.cid 
			LEFT JOIN db_goods_type h ON c.gtid = h.gtid 
			LEFT JOIN db_goods_type j ON c.gtid1 = j.gtid 
			LEFT JOIN db_goods_type k ON c.gtid2 = k.gtid  
			LEFT JOIN db_supplier w ON w.sid = c.sid  
			LEFT JOIN db_stock t ON t.wgid = b.wgid ";
			$order =" ORDER BY b.p_status,sname,gname ASC";
			$sql = $sql.$where.$order;
			$list= M("")->query($sql);  
			// $this->ajaxReturn($list);
            if(!empty($list)){
                $list = $this->purchaseReportDataHandle($list,$wid);   
                $slist = M("supplier")->field('sid,name')->where("create_id = $wid")->select();
                //取出单位
                foreach ($list as $k => $vo) {
                	$wgid = $vo['wgid'];
                	$sql ="select a.unit_id,a.unit_id1,a.num,b.unit_name uname,c.unit_name uname1 from db_spec a left join db_unit b on a.unit_id = b.unit_id left join db_unit c on a.unit_id1 = c.unit_id where a.wgid =$wgid";
                	$ulist = M('')->query($sql);
                	$ulist1 =array();
                	if(!empty($ulist)){
                		foreach ($ulist as $kk => $vo) {
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
                		$sql1="select a.unit_id,b.unit_name uname from db_wholesale_goods a left join db_unit b on a.unit_id=b.unit_id where a.wgid=$wgid";
			            $udata = M()->query($sql1);
			            $ulist1[0] =array();
			            $ulist1[0]['unit_id'] =$udata[0]['unit_id'];
			            $ulist1[0]['uname'] = $udata[0]['uname'];
			            $ulist1[0]['num'] = 1;
                	}
                	
		            $list[$k]['ulist'] = $ulist1;
                }
                $result=array(
                	'resultcode'=>1,
                	"data"=>$list,
                	'slist'=>$slist
                );   
                $this->ajaxReturn($result);
            }else{
                $this->ajaxReturn(ReturnJSON(2));
            }  
    	}
    }

    //采购单数据处理 
    public function purchaseReportDataHandle($data,$wid){
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
                    $clist[$i]['p_price']=$v['p_price'];
                    $clist[$i]['p_num']=$v['p_num'];
                    $clist[$i]['p_unit_id']=$v['p_unit_id'];
                    $clist[$i]['p_status']=$v['p_status'];
                    $clist[$i]['p_sid']=$v['p_sid'];
                    $clist[$i]['clist']=array(); 
                    $clist[$i]['clist'][$n]=array();
                    $clist[$i]['clist'][$n]['code']=$v['code'];
                    $clist[$i]['clist'][$n]['num']=$v['num'];
                    $clist[$i]['clist'][$n]['num1']=$v['num1'];
                    $clist[$i]['clist'][$n]['uname']=$v['uname'];
                    $clist[$i]['clist'][$n]['remark']=$v['remark'];
                    $clist[$i]['clist'][$n]['p_remark']=$v['p_remark'];
                    $clist[$i]['clist'][$n]['out_id']=$v['out_id'];  
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
                    $clist[$i]['clist'][$n]['out_id']=$v['out_id'];   
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
                    $clist[$i]['p_price']=$v['p_price'];
                    $clist[$i]['p_num']=$v['p_num'];
                    $clist[$i]['p_unit_id']=$v['p_unit_id'];
                    $clist[$i]['p_status']=$v['p_status'];
                    $clist[$i]['p_sid']=$v['p_sid'];
                    $clist[$i]['clist']=array(); 
                    $clist[$i]['clist'][$n]=array();
                    $clist[$i]['clist'][$n]['code']=$v['code'];
                    $clist[$i]['clist'][$n]['num']=$v['num'];
                    $clist[$i]['clist'][$n]['num1']=$v['num1'];
                    $clist[$i]['clist'][$n]['uname']=$v['uname'];
                    $clist[$i]['clist'][$n]['remark']=$v['remark'];
                    $clist[$i]['clist'][$n]['p_remark']=$v['p_remark'];
                    $clist[$i]['clist'][$n]['out_id']=$v['out_id'];  
                    $n++;
                } 
                $temp=$v['gname'];
            }
            // $this->ajaxReturn($clist);
            foreach ($clist as $k => $v) {
                $sum_num = 0;
                foreach ($clist[$k]['clist'] as $kk => $vo) {
                    $sum_num += floatval($vo['num']);
                }
                $clist[$k]['sum_num'] =$sum_num;
                $clist[$k]['sg']=$clist[$k]['sum_num'] - $v['stock_num']; 
            }   
            $system_param=M("system_param")->where("wid=$wid")->find();
            if(empty($system_param)){
                $system_param =getSystemParam();
            }   
            // $this->ajaxReturn(C("SYSTEM_PARAM"));
            //处理字段 
            foreach ($clist as $k => $v) {
                //处理数量 
                $clist[$k]['stock_num'] = unitConvert($system_param,customDecimal1($system_param,'num',$v['stock_num']),$v['jcuname']); 
                $clist[$k]['p_price'] = customDecimal1($system_param,'price',$v['p_price']); 
                $clist[$k]['sum_num'] = unitConvert($system_param,customDecimal1($system_param,'num',$v['sum_num']),$v['jcuname']);
                if(empty($v['p_num'])){
                    $clist[$k]['sg_num'] = customDecimal1($system_param,'num',$v['sg']);
                }else{
                    $clist[$k]['sg_num'] = customDecimal1($system_param,'num',$v['p_num']); 
                }  
                
                $clist[$k]['sg'] = unitConvert($system_param,customDecimal1($system_param,'num',$v['sg']),$v['jcuname']); 
                //处理单位转换   
                foreach ($v['clist'] as $kk => $vo) {  
                    $num1 =unitConvert($system_param,customDecimal1($system_param,'num',$vo['num1']),$vo['uname']);  
                    $clist[$k]['clist'][$kk]['num1'] = $num1;
                }
            } 
            $result = $clist; 
        } 
        return $result;
    } 

    //自动化入库
    public function joinReport(){
        $this->assign("create_time",date('Y-m-d',time()));
        $this->display();
    }

    //获取自动化入库信息
    public function getjoinReportInfo(){
        if(IS_POST){ 
            $wid =I("wid");
            $where ="where b.wid = $wid and a.j_status = 0 and a.p_status = 1"; 
            $create_time =I("create_time"); 
            if($create_time){
                $create_time =strtotime($create_time); 
                $where .=" and aa.create_time >= ".$create_time." and aa.create_time<=".($create_time+86399);
            }else{ //只能查询一天的数据
                $where .=" and aa.create_time >= '".time()."' and aa.create_time<=".(time()+86399);
            }  
            $sql ="select a.pid,a.p_sid,d.`name` sname,a.wgid,c.`name` gname,a.p_unit_id unit_id,e.unit_name uname,f.unit_name nei_uname,a.p_price, a.p_num,a.p_unit_id,a.p_nei_num,a.p_nei_unit_id,a.p_nei_price,a.p_price*a.p_num total
                 from db_purchase_detail a
                left join db_purchase aa on aa.osid= a.osid
                left join db_wholesale_goods b on a.wgid = b.wgid
                left join db_goods c on c.gid = b.gid 
                left join db_supplier d on a.p_sid = d.sid 
                left join db_unit e on e.unit_id = a.p_unit_id 
                left join db_unit f on f.unit_id = a.p_nei_unit_id ";
            $order =" ORDER BY sname,gname ASC";
            $sql = $sql.$where.$order;
            $list= M("")->query($sql);  
            // $this->ajaxReturn($sql);
            if(!empty($list)){
                $list = $this->joinReportDataHandle($list,$wid);  
                $result=array(
                    'resultcode'=>1,
                    "data"=>$list
                );   
                $this->ajaxReturn($result);
            }else{
                $this->ajaxReturn(ReturnJSON(2));
            }  
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //采购单数据处理 
    public function joinReportDataHandle($data,$wid){
        //获取系统参数 单位换算字段  
        $result=array();
        if(!empty($data)){ 
            $clist =array();
            $i=0;$temp="";  
            //数据处理 合并相同名称商品  
            foreach ($data as $k => $v) {  
                if($k==0){ //第一次  
                    $clist[$i]=array();
                    $clist[$i]['pid']=$v['pid'];
                    $clist[$i]['p_sid']=$v['p_sid'];
                    $clist[$i]['sname']=$v['sname']; 
                    $clist[$i]['wgid']=$v['wgid'];     
                    $clist[$i]['gname']=$v['gname'];  
                    $clist[$i]['unit_id']=$v['unit_id'];   
                    $clist[$i]['uname']=$v['uname'];
                    $clist[$i]['nei_uname']=$v['nei_uname'];
                    $clist[$i]['num']=$v['p_num'];
                    $clist[$i]['price']=$v['p_price'];
                    $clist[$i]['nei_num']=$v['p_nei_num'];
                    $clist[$i]['nei_price']=$v['p_nei_price'];
                    $clist[$i]['nei_unit_id']=$v['p_nei_unit_id'];
                    $clist[$i]['total']=$v['total'];  
                }  
                if($temp!=$v['gname'] && $k!=0){
                    $i++; 
                    $clist[$i]=array();
                    $clist[$i]['pid']=$v['pid'];
                    $clist[$i]['p_sid']=$v['p_sid'];
                    $clist[$i]['sname']=$v['sname']; 
                    $clist[$i]['wgid']=$v['wgid'];     
                    $clist[$i]['gname']=$v['gname'];  
                    $clist[$i]['unit_id']=$v['unit_id'];   
                    $clist[$i]['uname']=$v['uname'];
                    $clist[$i]['nei_uname']=$v['nei_uname'];
                    $clist[$i]['num']=$v['p_num'];
                    $clist[$i]['price']=$v['p_price'];
                    $clist[$i]['nei_num']=$v['p_nei_num'];
                    $clist[$i]['nei_price']=$v['p_nei_price'];
                    $clist[$i]['nei_unit_id']=$v['p_nei_unit_id'];
                    $clist[$i]['total']=$v['total'];  
                } 
                $temp=$v['gname'];
            }
            $system_param=M("system_param")->where("wid=$wid")->find();
            if(empty($system_param)){
                $system_param =getSystemParam();
            }  
            //处理字段 
            foreach ($clist as $k => $v) { 
                //处理数量 
                $clist[$k]['num'] = customDecimal1($system_param,'num',$v['num']); 
                $clist[$k]['price'] = customDecimal1($system_param,'price',$v['price']); 
                $clist[$k]['total'] = customDecimal1($system_param,'sum',$v['total']);  
                // $clist[$k]['nei_num'] = customDecimal1($system_param,'num',$v['nei_num']);    
                $clist[$k]['nei_price'] = customDecimal1($system_param,'price',$v['nei_price']);  
            }
            $result = $clist; 
        } 
        return $result;
    } 

    //根据类型id获取一级类型信息
    public function getOneTypeInfo(){
        if(IS_POST){
            $wid = I("wid");
            $list = M("goods_type")->where("series = 1 and fid = 0 and wid = $wid")->select();
            if($list){
                $this->ajaxReturn(ReturnJSON(0,$list));
            }else{
                $this->ajaxReturn(ReturnJSON(1)); 
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //根据类型id获取二级类型信息
    public function getTwoTypeInfo(){
        if(IS_POST){
            $fid = I("fid");
            $wid = I("wid");
            $list = M("goods_type")->where("series = 2 and fid = $fid and wid = $wid")->select();
            if($list){
                $this->ajaxReturn(ReturnJSON(0,$list));
            }else{
                $this->ajaxReturn(ReturnJSON(1)); 
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //采购按钮
    public function alreadyPurchase(){
    	if(IS_POST){
    		$out_ids = I("out_ids");
    		$arr_result =array(
    			'resultcode'=>1,
    			'msg'=>'成功'
    		);
    		if(!empty($out_ids)){
    			$p_sid = I("p_sid"); //供应商
	    		$p_unit_id = I("unit_id"); //基础单位
                $p_nei_num =I('p_nei_num');//内装数
                $p_num = floatval(I("p_num"))*floatval($p_nei_num); //实购数
                $p_nei_unit_id =I('p_unit_id');//内装单位
                $p_nei_price =I("p_price");//购买单价 
                $p_price =floatval($p_nei_price)/floatval($p_nei_num); //基础单位价格
	    		$wid =getWid();  
	    		M()->startTrans(); //开启事务 
	    		$list = explode(',',$out_ids); 
				//生成pid 采购编号
				$pid ="CG".$wid."_".microtime_float();
	    		foreach ($list as $k => $vo) {  
	    			$data =array(
	    				'pid'=>$pid,
	    				'p_status'=>1, //设置采购状态为采购
	    				'p_sid'=>$p_sid,
	    				'p_num'=>$p_num,
	    				'p_price'=>$p_price,
	    				'p_unit_id'=>$p_unit_id,
                        'p_nei_num'=>$p_nei_num,
                        'p_nei_unit_id'=>$p_nei_unit_id,
                        'p_nei_price'=>$p_nei_price
	    			);
	    			$result = M("purchase_detail")->where("out_id = $vo")->save($data);
	    			if(empty($result)){
		    			$arr_result['resultcode'] = 10;
		    			$arr_result['msg']="修改".$vo."时失败了.";
		    		}
	    		}
	    		if($arr_result['resultcode'] == 1){
	                M()->commit(); //事务提交  
	                $this->ajaxReturn($arr_result);
	            }else{
	                M()->rollback(); //事务回滚 
	                $this->ajaxReturn($arr_result);
	            }
	        }else{
	        	$arr_result['resultcode']=8;
	        	$arr_result['msg']='out_ids为空';
				$this->ajaxReturn($arr_result);
	        } 
    	}else{
    		$this->ajaxReturn(ReturnJSON(7));
    	}
    }

    //取消采购  
    public function notPurchase(){
    	if(IS_POST){
    		$out_ids = I("out_ids");
    		$arr_result =array(
    			'resultcode'=>1,
    			'msg'=>'成功'
    		);
    		if(!empty($out_ids)){
	    		$wid =getWid();
	    		M()->startTrans(); //开启事务 
	    		$list = explode(',',$out_ids); 
				//生成pid 采购编号
	    		foreach ($list as $k => $vo) {  
	    			$data =array(
	    				'p_status'=>0 //设置采购状态为未采购
	    			);
	    			$result = M("purchase_detail")->where("out_id = $vo")->save($data);
	    			if(empty($result)){
		    			$arr_result['resultcode'] = 10;
		    			$arr_result['msg']="修改".$vo."时失败了.";
		    		}
	    		}
	    		if($arr_result['resultcode'] == 1){
	                M()->commit(); //事务提交  
	                $this->ajaxReturn($arr_result);
	            }else{
	                M()->rollback(); //事务回滚 
	                $this->ajaxReturn($arr_result);
	            }
	        }else{
	        	$arr_result['resultcode']=8;
	        	$arr_result['msg']='out_ids为空';
				$this->ajaxReturn($arr_result);
	        } 
    	}else{
    		$this->ajaxReturn(ReturnJSON(7));
    	}
    }

    //根据类型id获取三级类型信息
    public function getThreeTypeInfo(){
        if(IS_POST){
            $fid = I("fid");
            $wid = I("wid");
            $list = M("goods_type")->where("series = 3 and fid = $fid and wid = $wid")->select();
            if($list){
                $this->ajaxReturn(ReturnJSON(0,$list));
            }else{
                $this->ajaxReturn(ReturnJSON(1)); 
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //获取供应商
    public function getSupplierInfo(){
    	// if(IS_POST){
    	// 	$wid = getWid();
    	// 	M("supplier")->where("create_id=$wid")->select();
    	// }
    }

    //全部入库
    public function allJoinStock(){
        if(IS_POST){
            $data = I("post.");  
            $wid =$data['wid'];
            $info_id = $data['info_id'];
            $data = $this->allJoinStockDataHandle($data['data'],$wid,$info_id);//数据处理   
            // $this->ajaxReturn($data); 
            M()->startTrans(); //开启事务 
            $arr_result = array(
                'resultcode' => 0,
                'msg' =>'成功',
                'jsids'=>array()
            );  
            $updatePid="";
            foreach ($data as $k => $v) {
                //总表录入 
                $m=M("join_stock");
                $data = array(
                    'jsid' => $v['jsid'],
                    'jsname'=>$v['jsname'],
                    'sid' => $v['sid'], 
                    'total' => $v['total'],
                    'carry_fee' => 0,
                    'unloading_fee' => 0,
                    'agency_fee' => 0,
                    'remark' => '自动化入库',
                    'create_id' => $wid,
                    'create_time'=>strtotime(date('Y-m-d'))
                );
                $result = $m->add($data);
                if(!empty($result)){
                    $arr_result['jsids'][$k]=$v['jsid'];
                    //详情录入
                    foreach ($v['sublist'] as $kk => $vo) { 
                        $mm=M("join_stock_detail");
                        //入库详情新增
                        $sublist = array(
                            'jsid'    =>$v['jsid'],
                            'wgid'    =>$vo['wgid'],
                            'num1'    =>$vo['num1'],
                            'unit_id1'=>$vo['unit_id1'],
                            'price'=>$vo['price'],
                            'nei_num'=>$vo['nei_num'],
                            'nei_unit_id'=>$vo['nei_unit_id'],
                            'nei_price'=>$vo['nei_price'],
                            'total_cost'=>$vo['total_cost'],
                            'shelf_life'=>$vo['shelf_life'],
                            'create_time'=>time()
                        );  
                        $join_id = $mm->add($sublist);
                        //保存到库存中
                        if($join_id<=0){
                            $arr_result['resultcode'] = 10;
                            $arr_result['msg'] = "新增入库详情时失败了";  
                        }else{
                            if($updatePid==""){
                                $updatePid .="'".$vo['pid']."'";
                            }else{
                                $updatePid .=",'".$vo['pid']."'";
                            }   
                        } 
                    }  
                }else{
                    $arr_result['resultcode']=10;
                    $arr_result['msg']="新增入库单时失败了";
                }
            } 
            if($arr_result['resultcode'] == 0){ 
                // M()->rollback(); //事务回滚   
                
                $mmm = M('purchase_detail');
                $pdata =array(
                    'j_status'=>1
                );   
                $presult =$mmm->where("pid in (".$updatePid.")")->save($pdata); 
                // $this->ajaxReturn($mmm->_sql());
                if(empty($presult)){
                    $arr_result['resultcode']=10;
                    $arr_result['msg']="修改订单入库状态时失败了:".$vo['pid']; 
                    M()->rollback(); //事务回滚  
                    $this->ajaxReturn($arr_result);
                    exit();
                }
                M()->commit(); //事务提交   
                $this->ajaxReturn($arr_result);
            }else{
                M()->rollback(); //事务回滚 
                $this->ajaxReturn($arr_result);
            }   
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }  
    //全部入库数据处理 
    public function allJoinStockDataHandle($data,$wid,$info_id){ 
        $result=array();
        if(!empty($data)){ 
            $clist =array();
            $i=0; $n=0;$temp="";  
            //数据处理 合并相同供应商编号   
            foreach ($data as $k => $v) {  
                if($k==0){ //第一次  
                    $clist[$i]=array(); 
                    $clist[$i]['sid']=$v['p_sid'];  
                    $clist[$i]['jsid']=$jsid ="PR".$wid."_".(time()+$i);  
                    $clist[$i]['jsname']=getUnitName1($info_id);   
                    $clist[$i]['sublist']=array(); 
                    $clist[$i]['sublist'][$n]=array();
                    $clist[$i]['sublist'][$n]['pid']=$v['pid']; 
                    $clist[$i]['sublist'][$n]['wgid']=$v['wgid']; 
                    $clist[$i]['sublist'][$n]['unit_id1']=$v['unit_id']; 
                    $clist[$i]['sublist'][$n]['num1']=$v['num'];
                    $clist[$i]['sublist'][$n]['price']=$v['price']; 
                    $clist[$i]['sublist'][$n]['nei_num']=$v['nei_num']; 
                    $clist[$i]['sublist'][$n]['nei_unit_id']=$v['nei_unit_id'];
                    $clist[$i]['sublist'][$n]['nei_price']=$v['nei_price']; 
                    $clist[$i]['sublist'][$n]['total_cost']=$v['total'];
                    $clist[$i]['sublist'][$n]['shelf_life']=$v['shelf_life']; 
                    $n++;
                } 
                if($temp==$v['p_sid']){
                    $clist[$i]['sublist'][$n]=array();
                    $clist[$i]['sublist'][$n]['pid']=$v['pid'];
                    $clist[$i]['sublist'][$n]['wgid']=$v['wgid']; 
                    $clist[$i]['sublist'][$n]['unit_id1']=$v['unit_id']; 
                    $clist[$i]['sublist'][$n]['num1']=$v['num'];
                    $clist[$i]['sublist'][$n]['price']=$v['price']; 
                    $clist[$i]['sublist'][$n]['nei_num']=$v['nei_num']; 
                    $clist[$i]['sublist'][$n]['nei_unit_id']=$v['nei_unit_id'];
                    $clist[$i]['sublist'][$n]['nei_price']=$v['nei_price']; 
                    $clist[$i]['sublist'][$n]['total_cost']=$v['total'];
                    $clist[$i]['sublist'][$n]['shelf_life']=$v['shelf_life'];   
                    $n++;
                }else if($temp!=$v['p_sid'] && $k!=0){
                    $i++;
                    $n=0;
                    $clist[$i]=array(); 
                    $clist[$i]['sid']=$v['p_sid'];  
                    $clist[$i]['jsid']=$jsid ="PR".$wid."_".(time()+$i); 
                    $clist[$i]['jsname']=getUnitName1($info_id);   
                    $clist[$i]['sublist']=array(); 
                    $clist[$i]['sublist'][$n]=array();
                    $clist[$i]['sublist'][$n]['pid']=$v['pid'];
                    $clist[$i]['sublist'][$n]['wgid']=$v['wgid']; 
                    $clist[$i]['sublist'][$n]['unit_id1']=$v['unit_id']; 
                    $clist[$i]['sublist'][$n]['num1']=$v['num'];
                    $clist[$i]['sublist'][$n]['price']=$v['price']; 
                    $clist[$i]['sublist'][$n]['nei_num']=$v['nei_num']; 
                    $clist[$i]['sublist'][$n]['nei_unit_id']=$v['nei_unit_id'];
                    $clist[$i]['sublist'][$n]['nei_price']=$v['nei_price']; 
                    $clist[$i]['sublist'][$n]['total_cost']=$v['total'];
                    $clist[$i]['sublist'][$n]['shelf_life']=$v['shelf_life'];  
                    $n++;
                } 
                $temp=$v['p_sid'];
            } 
            //计算单据金额
            foreach ($clist as $k => $v) {
                $total =0;
                foreach ($v['sublist'] as $kk => $vo) {
                    $total+=floatval($vo['total_cost']);
                }
                $clist[$k]['total']=$total;
            }
            $result = $clist; 
        } 
        return $result;
    } 

    //修改入库单审核状态
    // public function allAuditing(){
    //     if(IS_POST){
    //         $jsids = I('jsids');
    //         $data=explode(",",$jsids);
    //         foreach ($data as $k => $v) { 
    //             $jsid = $v; 
    //             $wid = getWid();
    //             $mm=M("join_stock_detail"); 
    //             $data = $mm->where("jsid = '".$jsid."'")->select();
    //             $errRow ="";
    //             foreach ($data as $k => $v) {
    //                 //入库单必需填写数量、单价 
    //                 if(!$this->checkNum($v['num1'])){
    //                     $errRow .= ($k+1).',';
    //                     continue;
    //                 }
    //                 if(!$this->checkNum($v['price'])){ 
    //                     $errRow .= ($k+1).',';
    //                     continue;
    //                 }
    //             }
    //             if($errRow){
    //                 $errRow=substr($errRow,0,strlen($errRow)-1);
    //                 $arr_result['resultcode'] = 10;
    //                 $arr_result['msg'] ="在第".$errRow."行数据有误,请检查数量、单价。";
    //             }else{ //验证通过
    //                 foreach ($data as $k => $v) {
    //                     $sql ="CALL addStockDetail(".$v['join_id'].",".$v['wgid'].",".$v['num1'].",".$v['price'].",".$wid.",".$v['unit_id1'].")"; 
    //                     $result = M("")->query($sql);
    //                     if($result[0]['resultcode']!=0){
    //                         $arr_result['resultcode'] = $result[0]['resultcode'];
    //                         $arr_result['msg'] = $result[0]['msg'];
    //                         break;
    //                     }
    //                 } 
    //                 $m = M("join_stock"); 
    //                 $data =array(
    //                     "status"=> 1,
    //                     "auditing_time"=>time(),
    //                     "auditing_id"=>session("wid")
    //                 );  
    //                 $arr_result = array(
    //                     'resultcode' => 0,
    //                     'msg' =>'成功'
    //                 ); 
    //                 $list = $m->where("jsid = '".$jsid."'")->save($data); 
    //                 if(!empty($list)){
    //                     $arr_result['resultcode'] = 10;
    //                     $arr_result['msg'] = "审核入库单".$jsid."时失败了"; 
    //                     $this->ajaxReturn($arr_result);
    //                 }   
    //             }  
    //         }
            
    //         $this->ajaxReturn($arr_result);
    //     }else{
    //         $this->ajaxReturn(ReturnJSON(7));
    //     }
    // }
}