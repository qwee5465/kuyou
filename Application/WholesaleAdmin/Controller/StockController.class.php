<?php
// +----------------------------------------------------------------------
// | 仓库管理模块
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class StockController extends BaseController
{

    /**
     * 查询库存详情
     */
    public function selStockDetail(){ 
        $this->display();
    }

    public function stockDefault()
    {    
    	$m = M("stock");
    	$wid =getWid();
        $where =""; 
        $gtid = I("gtid");$gtid1 = I("gtid1");$gtid2 = I("gtid2");$gname = I("gname");$numzf=I("numzf");
        $this->assign("gname",$gname);$this->assign("gtid",$gtid);
        $this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);
        $this->assign("numzf",$numzf);
        if($gtid){
            $where .=" and bb.gtid = $gtid";
        }
        if($gtid1){
            $where .=" and bb.gtid1 = $gtid1";
        }
        if($gtid2){
            $where .=" and bb.gtid2 = $gtid2";
        }
        if($gname){
            $where .=" and b.name like '%".$gname."%'";
        } 
        if($numzf=="0"){
            $where .=" and a.num1 = 0";
        }else if($numzf=="1"){
            $where .=" and a.num1 > 0";
        }else if($numzf=="2"){
            $where .=" and a.num1 >= 0";
        }else if($numzf=="3"){
            $where .=" and a.num1 < 0"; 
        }else if($numzf=="4"){
            $where .=" and a.num1 <= 0"; 
        }

    	$sql ="select f.brand_name,b.`name`,c.unit_name unit_name1,a.*, a.num1 * a.price total,g.type_name FROM db_stock a left join db_wholesale_goods bb on a.wgid = bb.wgid LEFT JOIN db_goods b ON bb.gid = b.gid LEFT JOIN db_unit c ON a.unit_id1 = c.unit_id left join db_goods_type g on bb.gtid =g.gtid left join db_brand f on b.brand_id =f.brand_id where a.wid = $wid ";
        //查询商品类型
        $p = I("p"); 
        $page_size = 100;
        $s_num =0;
        if($p){ 
            $s_num=($p-1)*$page_size+1;
            $p=($p-1)*$page_size;
        }else{
            $p = 0;
            $s_num = 1;
        }   
        $sql .=$where;
        $order =" order by bb.gtid2,bb.gtid1,bb.gtid,b.name ";
        $sql .=$order;
        $page =getPageSql($sql,$page_size);
        $this->assign('page',$page->show());
        $limit = " limit $p,$page_size";  
        $this->assign("s_num",$s_num);
        $sql .=$limit; 
        $list = $m->query($sql);
        $system_param=M("system_param")->where("wid=$wid")->find();
        if(empty($system_param)){
            $system_param =getSystemParam();
        } 
        //处理字段  
        foreach ($list as $k => $v) {
            //处理数量 
            $list[$k]['price'] = customDecimal('price',$v['price']);   
        } 
        $goods_type =M("goods_type");
        $typeList = $goods_type->where("series=1 and wid = $wid")->select();
        $this->assign("typeList",$typeList);
        // dump($sql);
        foreach ($list as $k => $v) {
            $sql ="select a.unit_id1,a.num,b.unit_name from db_spec a left join db_unit b on a.unit_id1 = b.unit_id where a.wgid =".$v['wgid'];
            $list[$k]['usub'] = M()->query($sql);
        }   
    	$this->assign("list",$list); 
        /*----------------获取商品过期提醒是否已提醒-------------------*/
        $nowDate = strtotime(date('Y-m-d'));
        $owList = M("overdue_warn")->where("wid = $wid and create_date = $nowDate")->find(); 
        $warn =0; //未提醒
        if($owList){
            $warn = 1;//已提醒
        } 
        $this->assign("warn",$warn); 
        $this->display();
    }

    //库存打印
    public function stockPrint(){
        $wid =getWid();
        $where="where b.wid = $wid";
        $gtid = I("gtid");$gtid1 = I("gtid1");$gtid2 = I("gtid2");
        $gname = I("gname");$numzf=I("numzf");
        if(!empty($gtid)){
            $where .=" and b.gtid=$gtid";
        }
        if(!empty($gtid1)){
            $where .=" and b.gtid1=$gtid1";
        }
        if(!empty($gtid2)){
            $where .=" and b.gtid2=$gtid2";
        }
        if(!empty($gname)){
            $where .=" and c.`name` like '%".$gname."%'";
        }
        if($numzf=="0"){
            $where .=" and a.num1 = 0";
        }else if($numzf=="1"){
            $where .=" and a.num1 > 0";
        }else if($numzf=="2"){
            $where .=" and a.num1 >= 0";
        }else if($numzf=="3"){
            $where .=" and a.num1 < 0"; 
        }else if($numzf=="4"){
            $where .=" and a.num1 <= 0"; 
        }
        $sql="select c.`name` gname,a.num1 num,d.unit_name uname,a.price from db_stock a
            left join db_wholesale_goods b on a.wgid=b.wgid
            left join db_goods c on b.gid=c.gid
            left join db_unit d on a.unit_id1 = d.unit_id "; 
        $sql.=$where." order by b.gtid2,b.gtid1,b.gtid,gname asc";
        $list = M()->query($sql);
        if(!empty($list)){
            $system_param=M("system_param")->where("wid=$wid")->find();
            if(empty($system_param)){
                $system_param =getSystemParam();
            } 
            //处理字段 
            foreach ($list as $k => $v) {
                //处理数量 
                $list[$k]['num'] = customDecimal('num',$v['num']); 
                // //处理单位转换 
                // switch($system_param['param_is_convert_unit']){
                //     case "0":
                //         $list[$k]['num']= $list[$k]['num'].$v['uname']; 
                //         break;
                //     case "1":
                //         if($v['uname']=="公斤"){
                //             $list[$k]['num'] = ($list[$k]['num']*2)."斤"; 
                //         }else{
                //             $list[$k]['num']= $list[$k]['num'].$v['uname'];
                //         } 
                //         break;
                //     case "2":
                //         if($v['uname']=="斤"){
                //             $list[$k]['num'] = ($list[$k]['num']/2)."公斤"; 
                //         }else{
                //             $list[$k]['num']= $list[$k]['num'].$v['uname'];
                //         } 
                //         break;
                // } 
            }
        }
        $this->assign("list",$list);
        $this->display();
    }

    //过期提醒已提醒 添加到数据库中
    public function overdueWarnAdd(){
        if(IS_POST){
            $wid =getWid();
            $nowDate = strtotime(date('Y-m-d'));
            $data=array(
                "wid" => $wid,
                "create_date" => $nowDate
            );
            $result = M("overdue_warn")->add($data);
            if($result){
                $this->ajaxReturn(0);//添加成功
            }else{
                $this->ajaxReturn(1);//添加失败
            }
        }
    }

    //将过期产品列表
    public function overdueWarnList(){
        $wid = getWid();
        $m = M("overdue_set");
        $result = $m->where("wid = $wid")->find();
        $overdue_day= C("OVERDUE_DAY");
        if($result){
            $overdue_day= $result['overdue_day'];
        } 
        $sql ="select * FROM (SELECT a.*,ceil((a.warn_time - UNIX_TIMESTAMP(NOW()))/(24*60*60)) overplus_day 
            FROM (SELECT b.jsid,a.wgid,a.num,a.stock_state,(b.shelf_life)*24*60*60+b.create_time warn_time,
            b.price,d.`name` gname,f.brand_name bname,g.unit_name uname,b.create_time
            FROM db_stock_detail a
            LEFT JOIN db_join_stock_detail b on a.join_id = b.join_id
            LEFT JOIN db_join_stock bb on b.jsid = bb.jsid
            LEFT JOIN db_wholesale_goods c ON a.wgid = c.wgid
            LEFT JOIN db_goods d ON c.gid = d.gid
            LEFT JOIN db_brand f ON d.brand_id = f.brand_id 
            LEFT JOIN db_unit g ON b.unit_id1 = g.unit_id
            WHERE c.wid = $wid AND a.stock_state = 0 AND b.shelf_life IS NOT NULL) a) a
            WHERE a.overplus_day <= $overdue_day";

        $list = M('')->query($sql);
        // dump($list);
        $this->assign("list",$list);
        $this->display();
    }

    //过期提醒
    public function overdueWarn(){
        $wid = getWid();
        $m = M("overdue_set");
        $result = $m->where("wid = $wid")->find();
        $overdue_day= C("OVERDUE_DAY");
        if($result){
            $overdue_day= $result['overdue_day'];
        } 
        $sql ="select * FROM (SELECT a.*,ceil((a.warn_time - UNIX_TIMESTAMP(NOW()))/(24*60*60)) overplus_day 
            FROM (SELECT b.jsid,a.wgid,a.num,a.stock_state,(b.shelf_life)*24*60*60+b.create_time warn_time,
            b.price,d.`name` gname,f.brand_name bname,g.unit_name uname,b.create_time
            FROM db_stock_detail a
            LEFT JOIN db_join_stock_detail b on a.join_id = b.join_id
            LEFT JOIN db_join_stock bb on b.jsid = bb.jsid
            LEFT JOIN db_wholesale_goods c ON a.wgid = c.wgid
            LEFT JOIN db_goods d ON c.gid = d.gid
            LEFT JOIN db_brand f ON d.brand_id = f.brand_id 
            LEFT JOIN db_unit g ON b.unit_id1 = g.unit_id
            WHERE c.wid = $wid AND a.stock_state = 0 AND b.shelf_life IS NOT NULL) a) a
            WHERE a.overplus_day = $overdue_day";
        $list = M('')->query($sql);
        // dump($list);
        $this->assign("list",$list);
        $this->display();
    }

    //过期提醒需不需要提醒
    public function isOverdueWarn(){
        if(IS_POST){
            $wid = getWid();
            $m = M("overdue_set");
            $result = $m->where("wid = $wid")->find();
            $overdue_day= C("OVERDUE_DAY");
            if($result){
                $overdue_day= $result['overdue_day'];
            } 
            $sql ="select * FROM (SELECT a.*,ceil((a.warn_time - UNIX_TIMESTAMP(NOW()))/(24*60*60)) overplus_day 
                FROM (SELECT b.jsid,a.wgid,a.num,a.stock_state,(b.shelf_life)*24*60*60+b.create_time warn_time,
                b.price,d.`name` gname,f.brand_name bname,g.unit_name uname,b.create_time
                FROM db_stock_detail a
                LEFT JOIN db_join_stock_detail b on a.join_id = b.join_id
                LEFT JOIN db_join_stock bb on b.jsid = bb.jsid
                LEFT JOIN db_wholesale_goods c ON a.wgid = c.wgid
                LEFT JOIN db_goods d ON c.gid = d.gid
                LEFT JOIN db_brand f ON d.brand_id = f.brand_id 
                LEFT JOIN db_unit g ON b.unit_id1 = g.unit_id
                WHERE c.wid = $wid AND a.stock_state = 0 AND b.shelf_life IS NOT NULL) a) a
                WHERE a.overplus_day = $overdue_day";
            // $this->ajaxReturn($sql);
            $list = M('')->query($sql);
            if($list){
                $this->ajaxReturn(0);//需要提醒
            }else{
                $this->ajaxReturn(1);//不需要提醒
            }
        } 
    }

    public function stockDetail()
    {
        $wgid = I("wgid");  
        //查询 入库详情
        $m =M("");
        $sql ="select a.*,c.`name` gname,d.unit_name unit_name1 from db_join_stock_detail a left join db_wholesale_goods b on a.wgid = b.wgid left join db_goods c on b.gid = c.gid left join db_unit d on a.unit_id1 = d.unit_id where a.wgid = $wgid";
        $list = $m->query($sql); 
        $this->display();
    }

    public function stockEdit(){
        $wgid = I("wgid");  
        $title = I("title");
        $list = M("stock")->where("wgid = $wgid")->find();
        $this->assign("title",$title);
        $this->assign("list",$list); 
        $this->display();
    }

    //库存更改 入库操作
    public function upStockJoin(){
        if(IS_POST){
            $wgid =I("wgid");
            $num1 = I("num1");
            $num = I("num");
            $unit_id = I("unit_id");
            $price = I("price");
            $wid = getWid();
            $jsid ="PR".$wid."_".time(); 
            $result = M()->query("CALL upStockJoin('".$jsid."',$wgid,$num1,$num,$wid,$unit_id,$price)");
            $arr =array(
                "resultcode"=>0,
                "msg" =>"成功"
            );
            if($result[0]['resultcode']!=0){
               $arr['resultcode'] = 1;
               $arr['msg'] = $result[0]['msg']; 
            }
            $this->ajaxReturn($arr); 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //库存更改 出库操作
    public function upStockOut(){
        if(IS_POST){
            $wgid =I("wgid");
            $num1 = I("num1");
            $num = I("num");
            $unit_id = I("unit_id");
            $price = I("price");
            $wid = getWid(); 
            $osid ="PC".$wid."_".time(); 
            $result = M()->query("CALL upStockOut('".$osid."',$wgid,$num1,$num,$wid,$unit_id,$price)");
            $arr =array(
                "resultcode"=>0,
                "msg" =>"成功"
            );
            if($result[0]['resultcode']!=0){
               $arr['resultcode'] = 1;
               $arr['msg'] = $result[0]['msg']; 
            }
            $this->ajaxReturn($arr); 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    public function stockDamage()
    {
        if(IS_POST){
            $wid = getWid();
            // $where =" WHERE a.create_id = $wid  AND a.`status` = 1 AND b.cd_num > 0 AND h.join_id IS NOT NULL ";
            $where =" where 1=1 ";
            $stime = I("stime");$etime = I("etime");$gname = I("gname");
            $this->assign("stime",$stime);$this->assign("etime",$etime);$this->assign("gname",$gname);
            $stime =strtotime($stime);$etime =strtotime($etime);
            $where1 ="";
            if($stime){
                $where1 .=" and a.create_time>=$stime";
            }
            if($etime){
                $where1 .=" and a.create_time<=$etime+86399";
            }
            if($gname){
                $where .=" and gname like '%".$gname."%'";
            }
            $sql ="select a.gname,a.bname,a.uname,SUM(cd_num) cd_num,SUM(total) total,wgid 
                from (select b.`name` gname,c.brand_name bname,d.unit_name uname,a.*,e.type_name tname,f.type_name tname1,h.type_name tname2
                from (select b.out_id,b.wgid,b.cd_num,e.price,b.cd_num*e.price total,f.gid,f.gtid,f.gtid1,f.gtid2,a.create_time,b.unit_id1 unit_id
                from db_out_stock a 
                left join db_out_stock_detail b on a.osid = b.osid
                left join db_owe_sales c on b.out_id = c.out_id
                left join db_owe_sales_detail d on c.owe_id = d.owe_id
                left join db_join_stock_detail e on d.join_id=e.join_id
                left join db_wholesale_goods f on f.wgid = b.wgid 
                where a.create_id = $wid and a.`status` = 1 and b.cd_num>0 $where1
                group by b.out_id,b.wgid,b.cd_num,e.price,f.wid,a.create_time,f.gtid,f.gtid1,f.gtid2) a
                left join db_goods b on a.gid = b.gid 
                left join db_brand c on b.brand_id = c.brand_id
                left join db_unit d on a.unit_id =d.unit_id
                left join db_goods_type e on a.gtid=e.gtid
                left join db_goods_type f on a.gtid1=f.gtid
                left join db_goods_type h on a.gtid2=h.gtid) a";
            $sql =$sql.$where;   
            $sql .="group by gname,bname,uname,wgid";   
            // dump($sql);
            $list = M()->query($sql);
            $this->assign("list",$list);
        }
        $this->display();
    }

    //商品货损详情
    public function cdnumDetail(){
        $wgid = I("wgid");
        $wid = getWid();
        $sql ="select a.osid,a.gname,a.bname,a.uname,SUM(cd_num) cd_num,SUM(total) total,wgid,create_time
                from (select b.`name` gname,c.brand_name bname,d.unit_name uname,a.*,e.type_name tname,f.type_name tname1,h.type_name tname2
                from (select a.osid,b.out_id,b.wgid,b.cd_num,e.price,b.cd_num*e.price total,f.gid,f.gtid,f.gtid1,f.gtid2,a.create_time,b.unit_id1 unit_id
                from db_out_stock a 
                left join db_out_stock_detail b on a.osid = b.osid
                left join db_owe_sales c on b.out_id = c.out_id
                left join db_owe_sales_detail d on c.owe_id = d.owe_id
                left join db_join_stock_detail e on d.join_id=e.join_id
                left join db_wholesale_goods f on f.wgid = b.wgid 
                where a.create_id = $wid and a.`status` = 1 and b.cd_num>0
                group by a.osid,b.out_id,b.wgid,b.cd_num,e.price,f.wid,a.create_time,f.gtid,f.gtid1,f.gtid2) a
                left join db_goods b on a.gid = b.gid 
                left join db_brand c on b.brand_id = c.brand_id
                left join db_unit d on a.unit_id =d.unit_id
                left join db_goods_type e on a.gtid=e.gtid
                left join db_goods_type f on a.gtid1=f.gtid
                left join db_goods_type h on a.gtid2=h.gtid) a
                where wgid = $wgid 
                group by a.osid,gname,bname,uname,wgid,create_time";
        $list = M()->query($sql);
        $this->assign("list",$list);
        $this->display();
    }


    //清空仓库
    public function clearStock(){
        if(IS_POST){
            $wid =getWid();
            //判断用户是否有权限清空库存
            $pcodes = session("p_codes"); // 当前用户权限 
            $arr_result =array();
            if($pcodes){     
                $a=0; //0.无权限 1.有权限
                foreach ($pcodes as $k => $v) {
                    if($v["p_code"]=="P89"){
                        $a=1;
                        break;
                    }
                }
                if($a==1){   
                    //库存详情 出售状态 更改为1 
                    //销售归还 归还状态 更改为1
                    $out = $this->batchOutStock();//批量出库 
                    $join= $this->batchJoinStock();//批量入库  
                    $sql ="update db_owe_sales set hq_status = 1  where hq_status = 0 and wgid in(select wgid from db_wholesale_goods where wid =$wid)";
                    $sql1 ="update db_stock_detail set stock_state = 1 where stock_state = 0 and wgid in(select wgid from db_wholesale_goods where wid =$wid)"; 
                    M()->execute($sql);
                    M()->execute($sql1);
                    if($join['resultcode']!=10){
                        $arr_result['resultcode']=1;
                        $arr_result['msg']="入库成功了";  
                    }else{
                        $arr_result =$join;
                    } 
                    if($out['resultcode']!=10){
                        $arr_result['resultcode']=1;
                        $arr_result['msg']="出库成功了";  
                    }else{
                        $arr_result =$out;
                    } 
                    $this->ajaxReturn($arr_result);
                }else{
                    $arr_result['resultcode']=3;
                    $arr_result['msg']= '您没有权限清空库存，请联系管理员。';
                    $this->ajaxReturn($arr_result);
                }
            }else{
                $arr_result['resultcode']=3;
                $arr_result['msg']= '您没有权限清空库存，请联系管理员。';
                $this->ajaxReturn($arr_result);
            } 
        }
    } 

    //库存归零 批量入库
    public function batchJoinStock(){ 
        $wid=getWid();
        $list = M("stock")->where("wid=$wid and num1<0")->select(); 
        $arr_result=array(
            'resultcode'=>1,
            'msg'=>'成功'
        );
        if(!empty($list)){ 
            $jsid ="PR".$wid."_".date('YmdHis',time()); 
            $data = array(
                'jsid' => $jsid,
                'jsname'=>'库存归零单据',
                'sid' => 0, 
                'total' => 0,
                'remark' => '库存归零单据',
                'create_id' => $wid,
                'create_time' =>time(),
                'update_time' =>time(),
                'update_id' => $wid
            ); 
            M()->startTrans();
            $result = M("join_stock")->add($data); 
            if(empty($result)){
                $arr_result['resultcode']=10;
                $arr_result['msg']="录入入库单失败了!";
            }else{
                $m=M("join_stock_detail");
                foreach ($list as $k => $v) {
                    $data=array(
                        'jsid'=>$jsid,
                        'wgid'=>$v['wgid'],
                        'num1'=>abs($v['num1']),
                        'unit_id1'=>$v['unit_id1'],
                        'price'=>0.0001,
                        'nei_num'=>1,
                        'nei_unit_id'=>$v['unit_id1'],
                        'nei_price'=>0.0001,
                        'total_cost'=>0,
                        'create_time'=>time()
                    );
                    $result = $m->add($data);
                    if(empty($result)){
                        $arr_result['resultcode']=10;
                        $arr_result['msg']="录入入库详情失败了";
                    }
                }
                if($arr_result['resultcode']==1){
                    M()->commit(); //事务提交   
                    return $this->auditingJoinStock($jsid);
                }else{
                    M()->rollback(); //事务回滚 
                    return $arr_result;
                }
            } 
            return $arr_result;
        }else{
            $arr_result['resultcode']=10; 
            $arr_result['msg']="无入库数据";
            return $arr_result; //无入库信息
        }
    }

    //审核入库单 
    public function auditingJoinStock($jsid){ 
        $wid = getWid();
        $mm=M("join_stock_detail"); 
        $data = $mm->where("jsid = '".$jsid."'")->select();
        $errRow ="";
        foreach ($data as $k => $v) {
            //入库单必需填写数量、单价 
            if(!$this->checkNum($v['num1'])){
                $errRow .= ($k+1).',';
                continue;
            }
            if(!$this->checkNum($v['price'])){ 
                $errRow .= ($k+1).',';
                continue;
            }
        }
        $arr_result = array(
            'resultcode' => 1,
            'msg' =>'成功'
        );
        if($errRow){
            $errRow=substr($errRow,0,strlen($errRow)-1);
            $arr_result['resultcode'] = 10;
            $arr_result['msg'] ="在第".$errRow."行数据有误,请检查数量、单价。";
        }else{ //验证通过
            foreach ($data as $k => $v) {
                $sql ="CALL addStockDetail(".$v['join_id'].",".$v['wgid'].",".$v['num1'].",".$v['price'].",".$wid.",".$v['unit_id1'].")"; 
                $result = M("")->query($sql);
                if($result[0]['resultcode']!=0){
                    $arr_result['resultcode'] = $result[0]['resultcode'];
                    $arr_result['msg'] = $result[0]['msg'];
                    break;
                }
            } 
            $m = M("join_stock"); 
            $data =array(
                "status"=> 1,
                "auditing_time"=>time(),
                "auditing_id"=>session("wid")
            );    
            $list = $m->where("jsid = '".$jsid."'")->save($data); 
            if(!$list){
                $arr_result['resultcode'] = 10;
                $arr_result['msg'] = "修改入库单审核状态时失败了"; 
            }   
        }
        return $arr_result;
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

    //批量出库 批量出库并审核
    public function batchOutStock(){
        $wid = getWid();
        $list = M("stock")->where("wid=$wid and num1>0")->select(); 
        $arr_result=array(
            'resultcode'=>1,
            'msg'=>'成功'
        );
        if(!empty($list)){
            $osid ="PC".$wid."_".date('YmdHis',time()); 
            $data = array(
                'osid' => $osid,
                'osname' => '库存归零单据',
                'cid' => 0,
                'total' => 0,
                'remark' => '库存归零单据',
                'create_id' => $wid,
                'create_time' =>time(),
                'update_id'=>$wid,
                'update_time'=>time()
            ); 
            $m = M("out_stock");
            M()->startTrans();
            $arr_result = array(
                'resultcode' => 1,
                'msg' =>'成功'
            );  
            $result = $m->add($data); 
            if($result){  
                $mm = M("out_stock_detail");  
                //详情录入 
                foreach ($list as $k => $v) { 
                    //入库详情新增
                    $sublist = array(
                        'osid'    =>$osid,
                        'wgid'    =>$v['wgid'], 
                        'than'    =>1, 
                        'cd_num'  =>$v['num1'],
                        'num1'    =>0,
                        'price'   =>0.0001,
                        'unit_id1'=>$v['unit_id1'], 
                        'nei_num'=>1,
                        'nei_unit_id'=>$v['unit_id1'], 
                        'nei_price'=>0.0001,
                        'sales_amount' =>0
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
            if($arr_result['resultcode'] == 1){
                M()->commit(); //事务提交  
                return $this->auditingOutStock($osid);
            }else{
                M()->rollback(); //事务回滚
                return $arr_result;
            }  
        }else{
            $arr_result['resultcode']=10; 
            $arr_result['msg']="无入库数据";
            return $arr_result;  
        }
    }

    //审核出库单
    public function auditingOutStock($osid){
        $wid =getWid();
        $arr_result = array(
            'resultcode' => 1,
            'msg' =>'成功'
        ); 
        $mm=M("out_stock_detail");
        $data = $mm->where("osid = '".$osid."'")->select();  
        $errRow ="";
        foreach ($data as $k => $v) {
            //出库单必需填写数量、单价或货损、单价
            $sumNum = $v['num1'] + $v['cd_num'];
            if(!$this->checkNum($sumNum)){
                $errRow .= ($k+1).',';
                continue;
            }
            if(!$this->checkNum($v['price'])){ 
                $errRow .= ($k+1).',';
                continue;
            }
        }
        if($errRow){
            $errRow=substr($errRow,0,strlen($errRow)-1);
            $arr_result['resultcode'] = 10;
            $arr_result['msg'] ="在第".$errRow."行数据有误,请检查数量、单价或货损、单价。";
        }else{
            foreach ($data as $k => $v) {
                $total_num = $v['num1'] + $v['cd_num'];
                $sql ="CALL addOutStockInfo(".$v['out_id'].",".$v['wgid'].",".$total_num.",".$v['price'].",".time().",$wid,".$v['unit_id1'].")";
                $result = M("")->query($sql); 
                if($result[0]['resultcode']!=0){
                    $arr_result['resultcode'] = $result[0]['resultcode'];
                    $arr_result['msg'] = $result[0]['msg'].".在第".($k+1)."行,数量为：".$v['num1']."货损数量为：".$v['cd_num']."!请先填写对应数量再审核。".$sql;
                    break;
                }
            }
            if($arr_result['resultcode']==1){ //详情审核成功
                $m = M("out_stock");
                $data =array(
                    "status"=> 1,
                ); 
                $data['auditing_time'] = time();
                $data['auditing_id'] = session("wid");  
                $list = $m->where("osid = '".$osid."'")->save($data); 
                if(!$list){
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "修改出库单审核状态时失败了";   
                } 
            }
        }
        return $arr_result;
    }
}