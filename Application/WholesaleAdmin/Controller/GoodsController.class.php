<?php
// +----------------------------------------------------------------------
// | 信息管理----商品管理
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class GoodsController extends BaseController
{
    //商品类型
    public function goodsTypeList()
    {
        $where=" 1=1 "; 
        $search = I('search');
        if($search){
            $where .= " and type_name like '%$search%' ";
        } 
        $p = empty(I("p")) ? 1 : I("p"); 
        $page_size = C("PAGE_SIZE"); 
        $s_num =  ($p-1)*$page_size+1;
        $this->assign('s_num',$s_num);
        $wid = getWid();
        $goods_type = M('goods_type');
        $where .=" and series = 1 and wid = $wid"; 
        $list = $goods_type->where($where)->order('gtid asc')->select(); 
        foreach ($list as $k => $vo) {
            $sub_list = $goods_type->where("series = 2 and fid =".$vo['gtid'])->select();
            if($sub_list){
                $list[$k]['sub'] =$sub_list;
                foreach ($list[$k]['sub'] as $kk => $v) {
                    $sub_list1 = $goods_type->where("series = 3 and fid =".$v['gtid'])->select();
                    if($sub_list1)
                        $list[$k]['sub'][$kk]['sub_sub'] = $sub_list1;
                    else 
                        $list[$k]['sub'][$kk]['sub_sub'] = 1;
                } 
            }else{
               $list[$k]['sub'] =1; 
            } 
        } 
        // dump($list);
        $this->assign('search',$search);
        $this->assign('list',$list);
        $this->display();
    }

    //商品类型----新增一级
    public function goodsTypeAdd()
    {
        $goods_type = M('goods_type');
        if(IS_POST){
            $wid = getWid();
            $type_name = I('type_name');
            $goods_type->type_name = $type_name;
            $goods_type->series = 1;
            $goods_type->fid = 0;
            $goods_type->wid = $wid;
            $list = $goods_type->add();
            if($list){
                $this->ajaxReturn(0);
                // $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->ajaxReturn(1);
                // $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }
        $this->display();
    }

    //商品类型----新增二级
    public function goodsTypeAdd2()
    {
        $goods_type = M('goods_type');
        $wid = getWid();
        if(IS_POST){ 
            $type_name = I('type_name');
            $fid = I("fid");
            $goods_type->type_name = $type_name;
            $goods_type->series = 2;
            $goods_type->fid = $fid;
            $goods_type->wid = $wid;
            $list = $goods_type->add();
            if($list){
                $this->ajaxReturn(0);
                // $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->ajaxReturn(1);
                // $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }
        //获取一级类型
        $list = $goods_type->where("series=1 and wid = $wid")->select();
        $this->assign("list",$list);
        $this->display();
    }

    //商品类型----新增三级
    public function goodsTypeAdd3()
    {
        $goods_type = M('goods_type');
        $wid = getWid();
        if(IS_POST){
            $type_name = I('type_name');
            $fid = I("fid1");
            $goods_type->type_name = $type_name;
            $goods_type->series = 3;
            $goods_type->fid = $fid;
            $goods_type->wid = $wid;
            $list = $goods_type->add();
            if($list){
                $this->ajaxReturn(0);
                // $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->ajaxReturn(1);
                // $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }
        //获取一级类型
        $list = $goods_type->where("series=1 and wid = $wid")->select();
        $this->assign("list",$list);
        $this->display();
    }

    //根据类型id获取二级类型信息
    public function getTwoTypeInfo(){
        if(IS_POST){
            $fid = I("fid");
            $wid = getWid();
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
    //根据类型id获取三级类型信息
    public function getThreeTypeInfo(){
        if(IS_POST){
            $fid = I("fid");
            $wid = getWid();
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

    //商品类型---删除
    public function goodsTypeDel()
    {
        if(IS_POST){
            $gtid = I('gtid');
            $wid =getWid();
            //判断商品类型是否已被引用
            $result = M("wholesale_goods")->where("(gtid = $gtid or gtid1 =$gtid or gtid2=$gtid) and wid=$wid")->find();
            if($result){
                $this->ajaxReturn(2); //商品已被引用 
            }else{
                $where = "gtid=$gtid";
                $goods_type = M('goods_type');
                //判断是否存在子级
                $result = $goods_type->where("fid = $gtid")->find();
                if($result){
                    $this->ajaxReturn(3);
                }else{
                    $list = $goods_type->where($where)->delete();
                    if ($list) {
                        $this->ajaxReturn(0); //删除成功
                    }else{
                        $this->ajaxReturn(1); //删除失败
                    }
                }
            } 
        } 
    }

    //商品类型----编辑
    public function goodsTypeEdit()
    { 
        $goods_type = M('goods_type');
        if(IS_POST){
            $gtid =I("gtid");
            $data =array(
                'type_name'=>I('type_name')
            ); 
            $data = $goods_type->where("gtid=$gtid")->save($data);
            // dump($goods_type->_sql());exit();
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }else{
            $gtid = I('get.gtid'); 
            $where = "gtid=$gtid";
            $list = $goods_type->where($where)->find();
            $this->assign('list',$list);
            $this->display();
        }
    }

    //商品管理
    public function goodsList()
    {     
        $wid = getWid();
        $where=" where a.wid = $wid"; 
        $search = I('search');$gtid =I("gtid"); $gtid1=I("gtid1");$gtid2=I("gtid2");
        $stid = I("stid");$sid=I("sid");
        $this->assign('search',$search); $this->assign('gtid',$gtid); 
        $this->assign('gtid1',$gtid1);$this->assign('gtid2',$gtid2);
        $this->assign('stid',$stid);$this->assign("sid",$sid);
        if($search){
            $where .= " and b.name like '%$search%'";
        }
        if($gtid){
            $where .= " and a.gtid = $gtid";
        }
        if($gtid1){
            $where .= " and a.gtid1 = $gtid1";
        }
        if($gtid2){
            $where .= " and a.gtid2 = $gtid2";
        } 
        if($stid){
            $where .= " and k.stid = $stid";
        }
        if($sid){
            $where .= " and k.sid = $sid";
        }
        // 商品类型
        $gtlist = M("goods_type")->where("series=1 and wid = $wid")->select();
        $this->assign("gtlist",$gtlist);
        $page_size = C('PAGE_SIZE'); 
        $p = I("p");  
        $goods = M('Goods');   
        if($p){
            $p=($p-1)*$page_size+1;
        }else{ 
            $p = 1;
        }  
        $s_page=$p-1; //分页开始数
        $e_page=$page_size; //分页结束数

        $limit = " limit $s_page,$e_page";
        $sql = "select a.*,b.name,c.brand_name,d.unit_name,e.type_name,h.type_name type_name1,j.type_name type_name2,b.pic bpic,k.`name` sname
                from db_wholesale_goods a 
                left join db_goods b on a.gid = b.gid 
                left join db_brand c on b.brand_id = c.brand_id
                left join db_unit d on a.unit_id = d.unit_id
                left join db_goods_type e on a.gtid = e.gtid 
                left join db_goods_type h on a.gtid1 = h.gtid
                left join db_goods_type j on a.gtid2 = j.gtid
                LEFT JOIN db_supplier k ON k.sid = a.sid "; 
        $sql .= $where." ORDER BY a.create_time desc ";   
        // dump($sql);
        $page =getPageSql($sql,$page_size);
        $sql .=$limit;  
        $list = $goods->query($sql);  
        foreach ($list as $k => $v) {
            $sql ="select a.unit_id1,a.num,b.unit_name from db_spec a left join db_unit b on a.unit_id1 = b.unit_id where a.wgid =".$v['wgid'];
            $list[$k]['usub'] = M()->query($sql);
        }  
        $this->assign('list',$list);  
        $this->assign('p',$p); 
        $this->assign('page',$page->show());
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $gcList = M("cdc")->where("wid = $wid")->find();
        if(!$gcList){
            $gcList = getGoodsColumn();
        }   
        $titles = $this->getGoodsTitleInfo($wid); 
        $this->assign("titles",$titles); 
        $this->assign("gcList",$gcList);
        /*-----------------供应商类型--------------------*/
        $stList = M("supplier_type")->where("wid = $wid")->select();
        $this->assign("stList",$stList);
        $this->display();
    }

    public function getSupplierInfo(){
        if(IS_POST){
            $stid = I("stid");
            $slist = M("supplier")->field('sid,name')->where("stid = $stid")->select();
            if(!empty($slist)){
                $this->ajaxReturn(ReturnJSON(0,$slist));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //商品管理
    public function goodsList1()
    {     
        $wid = getWid();
        $where=" where a.wid = $wid";
        if(IS_POST){
            $search = I('search');$gtid =I("gtid"); $gtid1=I("gtid1");$gtid2=I("gtid2");
            $this->assign('search',$search); $this->assign('gtid',$gtid); 
            $this->assign('gtid1',$gtid1);$this->assign('gtid2',$gtid2);
            if($search){
                $where .= " and name like '%$search%'";
            }
            if($gtid){
                $where .= " and b.gtid = $gtid";
            }
            if($gtid1){
                $where .= " and b.gtid1 = $gtid1";
            }
            if($gtid2){
                $where .= " and b.gtid2 = $gtid2";
            }
        }
        // 商品类型
        $gtlist = M("goods_type")->where("series=1")->select();
        $this->assign("gtlist",$gtlist);
        $page_size = C('PAGE_SIZE');
        $p = I("p");  
        $goods = M('Goods');   
        if($p){
            $p=($p-1)*$page_size+1;
        }else{ 
            $p = 1;
        }  
        $s_page=$p-1; //分页开始数
        $e_page=$page_size; //分页结束数
        $limit = " limit $s_page,$e_page";
        $sql = "select a.*,b.name,c.brand_name,d.unit_name,e.type_name,h.type_name type_name1,j.type_name type_name2,b.pic bpic 
                from db_wholesale_goods a 
                left join db_goods b on a.gid = b.gid 
                left join db_brand c on b.brand_id = c.brand_id
                left join db_unit d on a.unit_id = d.unit_id
                left join db_goods_type e on b.gtid = e.gtid 
                left join db_goods_type h on b.gtid1 = h.gtid
                left join db_goods_type j on b.gtid2 = j.gtid"; 
        $sql .= $where." ORDER BY a.create_time desc ";   
        $page =getPageSql($sql,$page_size);
        $sql .=$limit;  
        $list = $goods->query($sql);  
        foreach ($list as $k => $v) {
            $sql ="select a.unit_id1,a.num,b.unit_name from db_spec a left join db_unit b on a.unit_id1 = b.unit_id where a.wgid =".$v['wgid'];
            $list[$k]['usub'] = M()->query($sql);
        }  
        $this->assign('list',$list);  
        $this->assign('p',$p); 
        $this->assign('page',$page->show());
        /*----------------------获取数据量设置信息---------------------*/
        //获取默认商品数据列表信息  
        $gcList = M("cdc")->where("wid = $wid")->find();
        if(!$gcList){
            $gcList = getGoodsColumn();
        }   
        $titles = $this->getGoodsTitleInfo($wid); 
        $this->assign("titles",$titles);
        $this->assign("gcList",$gcList);
        $this->display();
    }

    //获取商品表格头部标题
    public function getGoodsTitleInfo($wid){
        $columns = getGoodsColumnName(); 
        $titles = getGoodsColumnTitle();
        $cdc = M("cdc")->where("wid=$wid")->find();
        $arr =array();
        foreach ($columns as $k => $v) {
            if(!empty($cdc)){
                if(empty($cdc[$v])){
                    $arr[$v]=$titles[$v]; //显示名称 
                }
                else{
                    $arr[$v]=$cdc[$v]; //显示名称
                } 
            }else{
                $arr[$v]=$titles[$v];
            } 
               
        }  
        return $arr;
    }

    //商品管理---新增
    public function goodsAdd()
    {
        $wid = getWid();
        $pageState = I("pageState"); //出入库新增商品传入
        $this->assign("pageState",$pageState);
        $unit = M('unit');      //商品单位
        $brand = M('brand');    //品牌
        $goods = M('goods');    //商品
        $goods_type = M('goods_type'); //商品类型表
        $wholesale_goods = M('wholesale_goods');//批发商商品表
        $list_unit = $unit->select();
        $list_brand = $brand->select();
        $list1 = $goods_type->where("series=1 and wid =$wid")->select();
        if(IS_POST){
            $info = uploadimg('Goods');
            $pic ='';
            if($info){ 
                $pic ="/Upload/Images/".$info["pic"]["savepath"].$info["pic"]["savename"];
            }else{
                $pic = I("hidpic");
            }   
            $name = I('name'); 
            $unit_id =I('unit_id');
            $brand_id = I('brand_id');
            $price = I('price'); 
            $wid = getWid(); 
            $gtid = I('gtid');
            $gtid1 = I('gtid1');
            $gtid2 = I('gtid2');  
            $sql = "select a.gid from db_goods a,db_wholesale_goods b
                    where a.gid = b.gid and a.`name`='".$name."' and a.brand_id = $brand_id and b.wid =$wid "; 
            $result = $goods->query($sql); 
            //商品名称 品牌 单位 三种相同则获取商品标准库中的商品插入到批发商商品档案中 否则新增商品标准库商品，审核状态标注为未审核 
            if($result){  
                $this->error('添加失败,商品档案中已存在该商品.');
            }else{
                /*----------------添加商品----------------*/
                $price = I('price');
                $data = array(
                    'name'=>$name,
                    'gtid'=>$gtid,
                    'gtid1'=>$gtid1,
                    'gtid2'=>$gtid2,
                    'pic' =>$pic,
                    'py'=> getFirstPY("$name"),
                    'price' => $price,
                    'unit_id'=>$unit_id,
                    'brand_id' =>$brand_id,
                    'create_time'=>time(),
                    'create_id'=>$wid
                );
                $arr_result = array(
                    'resultcode' => 0,
                    'msg' =>'成功'
                );
                M()->startTrans(); //开启事务 
                $list = $goods->add($data); 
                /*-----------------添加批发商商品------------------*/
                if($list){
                    $data_goods = array(
                        'wid'=>$wid,
                        'gid'=>$list,
                        'gtid'=>$gtid,
                        'gtid1'=>$gtid1,
                        'gtid2'=>$gtid2,
                        'sid'=>I("sid"),
                        'unit_id'=>$unit_id,
                        'pic' => $pic,
                        'price'=>$price,
                        'create_time'=>time(),
                    );
                    $list = $wholesale_goods->add($data_goods);
                    if($list) {
                        //添加库存
                        $stockInfo = array(
                            'wgid'=> $list,
                            'num1'=> 0,
                            'unit_id1'=> $unit_id,
                            'price'=> 0,
                            'wid'=>$wid,
                            'new_time'=>time(),
                        );
                        $stockR = M("stock")->add($stockInfo);
                        if($stockR){
                            $hid_spec = intval(I("hid_spec")); 
                            if($hid_spec>0){
                                //新增商品规格 
                                for($i =0;$i<$hid_spec;$i++){
                                    $num0 =floatval(I("num".$i)); $unit_id0 = I("unit_id".$i);  
                                    if($unit_id!=$unit_id0){ 
                                        if($num0>=0.01 && $unit_id0>0){ 
                                            $spec = M("spec"); 
                                            $udata =array(
                                                "wgid"=>$list,
                                                "unit_id"=>$unit_id,
                                                "num"=>$num0,
                                                "unit_id1"=>$unit_id0,
                                            ); 
                                            $result = $spec->add($udata); 
                                            if(empty($result)){
                                                $arr_result['resultcode'] = 10;
                                                $arr_result['msg'] = "新增商品规格时失败了";
                                            }
                                        } 
                                    }else{
                                        $arr_result['resultcode'] = 10;
                                        $arr_result['msg'] = "新增商品规格时失败了,内装单位和基础单位相同";
                                    }
                                }  
                            } 
                        }else{
                            $arr_result['resultcode'] = 10;
                            $arr_result['msg'] = "新增商品库存时失败了";
                        }
                    }else{
                        $arr_result['resultcode'] = 10;
                        $arr_result['msg'] = "新增商品时失败了";
                    } 
                }else{
                    $arr_result['resultcode'] = 10;
                    $arr_result['msg'] = "新增商品时失败了";  
                }
                /*--------------最后判断---------------*/
                if($arr_result["resultcode"]==0){ 
                    M()->commit(); //事务提交  
                    $this->success('添加成功',U('Goods/goodsList'));
                }else{
                    M()->rollback(); //事务回滚 
                    $this->error('添加失败'.$arr_result['msg']);
                }
            } 
        }else{ 
            $slist =M("supplier")->where("create_id =$wid")->select();
            $this->assign("slist",$slist);
            $this->assign('list_unit',$list_unit);
            $this->assign("json_unit",json_encode($list_unit));
            $this->assign('list_brand',$list_brand);
            $this->assign('list1',$list1);
            $this->display();
        }
    }

    //商品管理---编辑
    public function goodsEdit()
    { 
        $goods = M('goods');    //商品
        $wholesale_goods = M('wholesale_goods');//批发商商品表
        if(IS_POST){
            //表单提交
            $info = uploadimg('Goods');
            $pic ='';
            if($info){ 
                $pic ="/Upload/Images/".$info["pic"]["savepath"].$info["pic"]["savename"];
            }else{
                $pic = I("hidpic");
            }   
            $wgid = I("wgid");
            $gtid = I("gtid");
            $gtid1 = I("gtid1");
            $gtid2 = I("gtid2");
            $name = I('name'); 
            $unit_id =I('unit_id');
            $brand_id = I('brand_id');
            $price = I('price');
            $wid =getWid(); 
            $sql = "select a.gid from db_goods a,db_wholesale_goods b
                    where a.gid = b.gid and a.`name`='".$name."' and a.brand_id = $brand_id and b.wid =$wid and wgid <> $wgid "; 
            $result = $goods->query($sql); 
            //商品名称 品牌 单位 三种相同则获取商品标准库中的商品插入到批发商商品档案中 否则新增商品标准库商品，审核状态标注为未审核 
            if($result){  
                $this->error('修改失败,商品档案中已存在该商品.');
            }else{
                //获取商品编号
                $sql ="select b.gid from db_wholesale_goods a LEFT JOIN db_goods b on a.gid = b.gid where a.wgid = $wgid";
                $glist = M()->query($sql); 
                if($glist){ 
                    $gid = $glist[0]['gid'];
                    $resultArr =array(
                        'data'=>0,
                        'msg'=>"成功"
                    ); 
                    M()->startTrans();//开启事务
                    /*------------------修改商品------------------*/
                    $gdata = array(
                        'name'=>$name,
                        'gtid'=>$gtid,
                        'gtid1'=>$gtid1,
                        'gtid2'=>$gtid2,
                        'pic'=>$pic,
                        'brand_id'=>$brand_id,
                        'price'=>$price,
                        'unit_id'=>$unit_id,
                        'py'=>getFirstPY($name),
                        'create_time'=>time(),
                        'create_id'=>$wid 
                    );
                    $result = $goods->where("gid = $gid")->save($gdata);
                    /*------------------修改批发商商品------------------*/ 
                    if($result){
                        $wgdata = array(
                            'wid'=>$wid,
                            'gid'=>$gid,
                            'gtid'=>$gtid,
                            'gtid1'=>$gtid1,
                            'gtid2'=>$gtid2,
                            'sid'=>I("sid"),
                            'unit_id'=>$unit_id, //单位被引用不可修改 
                            'pic' => $pic,
                            'price'=>$price,
                            'create_time'=>time()
                        );
                        $result = $wholesale_goods->where("wgid = $wgid")->save($wgdata);
                        if($result<=0){ 
                            $resultArr['data']=10;
                            $resultArr['msg']="修改失败,在修改批发商商品信息时失败了."; 
                        } 
                    }else{  
                        $resultArr['data']=10;
                        $resultArr['msg']="修改失败,在修改商品信息时失败了.";
                    } 
                    $hid_spec = intval(I("hid_spec")); 
                    if($hid_spec>0){
                        //新增商品规格  
                        for($i=0;$i<$hid_spec;$i++){
                            //商品编辑规格是否可编辑
                            $isquote=I("isquote".$i);  
                            $num0 =floatval(I("num".$i)); $unit_id0 = I("unit_id".$i); 
                            if($unit_id!=$unit_id0){
                                if($num0>=0.01){
                                    if($isquote!=""){
                                        if($isquote==1){//可编辑   
                                            $spec_id = I("spec_id".$i);
                                            if($num0>0 && $unit_id0>0){ 
                                                $udata =array(
                                                    "wgid"=>$wgid,
                                                    "unit_id"=>$unit_id,
                                                    "num"=>$num0,
                                                    "unit_id1"=>$unit_id0,
                                                );
                                                $result = M("spec")->where("spec_id=$spec_id")->save($udata); 
                                            } 
                                        }
                                    }else{//新增
                                        $udata =array(
                                            "wgid"=>$wgid,
                                            "unit_id"=>$unit_id,
                                            "num"=>$num0,
                                            "unit_id1"=>$unit_id0,
                                        );
                                        $result = M("spec")->add($udata);
                                        if($result<=0){
                                            $resultArr['data']=10;
                                            $resultArr['msg']="添加失败,在添加商品规格$i时失败了.";  
                                        }
                                    }
                                }
                            }else{
                                $resultArr['data']=10;
                                $resultArr['msg']="商品规格$i时失败了，与基础单位相同.";  
                            }
                            
                        }
                    }  
                    //事务提交
                    if($resultArr['data']==10){
                        M()->rollback();
                        $this->error($resultArr['msg']);
                    }else{
                        M()->commit();
                        $this->success("修改成功",U("Goods/goodsList"));
                    } 
                }  
            }
        }else{
            //页面信息
            $wgid = I("wgid");
            $sql ="select a.wgid,b.`name` gname,a.unit_id,a.sid,a.price,a.pic,b.brand_id,a.gtid,a.gtid1,a.gtid2,b.`status` from db_wholesale_goods a LEFT JOIN db_goods b on a.gid = b.gid WHERE a.wgid = $wgid";
            $list = M()->query($sql);
            $unit = M('unit');      //商品单位
            $brand = M('brand');    //品牌 
            $goods_type = M('goods_type'); //商品类型表 
            $list_unit = $unit->select();
            $list_brand = $brand->select();
            $wid =getWid();
            $list1 = $goods_type->where("series=1 and wid = $wid")->select(); 
            $this->assign("list",$list);
            $this->assign('list_unit',$list_unit);
            $this->assign("json_unit",json_encode($list_unit));
            $this->assign('list_brand',$list_brand);
            $this->assign('list1',$list1);
            //商品是否被审核  //商品是否被引用
            $result =M()->query("CALL checkGoodsQuote($wgid)"); 
            if($result[0]['resultcode']==1 && $list[0]['status'] ==1){ 
                $this->assign("edit_status",1);  
            }
            //获取商品规格
            $ulist= M("spec")->where("wgid = $wgid")->select();
            foreach ($ulist as $k => $v) {
                $result = M()->query("CALL checkSpecQuote(".$v['wgid'].",".$v['unit_id1'].")");
                $ulist[$k]['isquote'] =$result[0]['resultcode'];
            }
            // dump($ulist);
            $this->assign("ulist",$ulist); 
            $wid =getWid();
            //供应商列表
            $slist =M("supplier")->where("create_id =$wid")->select();
            $this->assign("slist",$slist);
            $this->display();
        } 
    }

    //商品管理---删除
    public function goodsDel()
    {
        if(IS_POST){
            $wgid = I('wgid');
            //判断商品是否被引用到
            $result =M()->query("CALL checkGoodsQuote($wgid)");
            if($result[0]['resultcode']==1){//未被引用
                //查看该商品在商品标准库中是否被审核
                $wholesale_goods = M('wholesale_goods');
                $wid = getWid();
                $where = "wgid = $wgid and wid =$wid";
                $list =$wholesale_goods->where($where)->delete();
                if($list){ 
                    $this->ajaxReturn(0);
                }else{
                    $this->ajaxReturn(1);
                }
            }else{
                $this->ajaxReturn(2);//已被引用
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //停用启用商品
    public function goodsDisable(){
        if(IS_POST){
            $wgid = I('wgid');
            $disable =I("disable");
            $data=array(
                "wgid"=>$wgid,
                "disable"=>$disable
            );
            $result = M("wholesale_goods")->save($data);
            if($result){ 
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    // 规格设置
    public function goodsSpecList()
    {
        $wgid = I('wgid');
        //根据wgid 获取规格列表
        $m = M();
        $sql = "select a.*,b.unit_name unit_name1,c.unit_name unit_name2,d.unit_name unit_name3,e.unit_name unit_name4 from db_spec a left join db_unit b on a.unit_id1 = b.unit_id
            left join db_unit c on a.unit_id2 = c.unit_id
            left join db_unit d on a.unit_id3 = d.unit_id
            left join db_unit e on a.unit_id4 = e.unit_id where a.wgid = $wgid order by a.inside_type asc";
        $list = $m ->query($sql); 
        if($list){
            foreach ($list as $k => $v) {
                $list[$k]["spec_rule"] = getSpecRule($v);
            }
        } 
        $this->assign("list",$list); 
        $this->assign('wgid',$wgid); 
        $this->display();
    }

    public function goodsSpecAdd(){
        if(IS_POST){
            $inside_type = I("inside_type");
            $wgid = I('wgid');
            $spec = M('Spec'); 
            /**
             * 删除规格
            **/
            // $spec->where("wgid = $wgid")->delete();

            // $specList = $spec->field('sid') ->where("inside_type = $inside_type and wgid = $wgid")->order(" use_num asc")->select();
            // if(count($specList)>$spec_size){
            //     //删除第一条信息
            //     $spec->delete($specList[0]['sid']);
            //     addActionLog("规格超额，删除了一条使用较少的商品规格");
            // }
            $list = $spec->where("wgid=$wgid")->find(); 
            if($list){
                 $this->show("<script>parent.layer.msg('该规格已存在规格无法设置。');parent.layer.closeAll('iframe');</script>",'utf-8');
            }else{ 
                 if($inside_type==0){ //内装  
                    $spec_size = C("SPEC_SIZE");  
                    $unit_id1 = I("unit_id1");
                    $num1 = I("num1");
                    $unit_id2 = I("unit_id2");
                    $num2 = I("num2");
                    $unit_id3 = I("unit_id3");
                    $num3 = I("num3");
                    $unit_id4 = I("unit_id4"); 
                    $num4 = 1;
                    $data = array(
                        'inside_type' => $inside_type,  
                        'wgid' => $wgid,
                        'unit_id1'=>$unit_id1
                    );
                    if($unit_id2){
                        $data['unit_id2'] =$unit_id2;
                        $data['num2'] =1;
                    }
                    if($unit_id3){
                        $data['unit_id3'] =$unit_id3;
                        $data['num3'] =1;
                    }
                    if($unit_id4) {
                        $data['unit_id4'] =$unit_id4;
                        $data['num4'] =1;
                    }
                    if($num1)
                        $data['num1'] =$num1;
                    if($num2)
                        $data['num2'] =$num2;
                    if($num3)
                        $data['num3'] =$num3;
                    if($num4)
                        $data['num4'] =$num4;

                    $list = $spec->add($data);
                    if($list){
                        addActionLog("新增了一条商品规格");
                        $this->show("<script>parent.history.go(0);</script>",'utf-8');
                    }
                    else{
                        $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
                    }
                }else{ //非内装 
                    $unit_id1 = I("f_unit_id1"); 
                    $unit_id2 = I("f_unit_id2"); 
                    $unit_id3 = I("f_unit_id3"); 
                    $unit_id4 = I("f_unit_id4"); 
                    $data = array(
                        'inside_type' => $inside_type, 
                        'wgid' => I('wgid'),
                        'unit_id1'=>$unit_id1
                    );
                    if($unit_id2)
                        $data['unit_id2'] =$unit_id2;
                    if($unit_id3)
                        $data['unit_id3'] =$unit_id3;
                    if($unit_id4) 
                        $data['unit_id4'] =$unit_id4;
                    $list = $spec->add($data);
                    if($list){
                        addActionLog("新增了一条商品规格"); 
                        $this->show("<script>parent.history.go(0);</script>",'utf-8');
                    }
                    else{
                        $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
                    }
                }
            }
        }else{
            $wgid = I('wgid');
            $m = M();
            $sql = "select a.unit_id,b.unit_name from db_wholesale_goods a,db_unit b where a.unit_id = b.unit_id and a.wgid = $wgid";
            $data = $m->query($sql);
            $this->assign("data",$data[0]);
            $this->assign('wgid',$wgid); 
            $this->display();
        }
    }

    //商品管理---商品标准库
    public function goodsStdList()
    {
        //查询所有商品标准库信息
        $wid =getWid();
        $where=" where delete_mark = 0 and status = 0";
        if(IS_POST){
            $search = I('search');
            if($search){
                $where .= " and name like '%$search%'";
            }
        }
        $page_size = C('PAGE_SIZE');
        $p = I("p");  
        $goods = M('Goods');   
        if($p){
            $p=($p-1)*$page_size+1;
        }else{ 
            $p = 1;
        } 
        $s_page=$p-1; //分页开始数
        $e_page=$page_size; //分页结束数
        $limit = " limit $s_page,$e_page";
        $sql = "select a.*, b.unit_name,c.brand_name,d.type_name,f.wgid FROM db_goods a LEFT JOIN db_unit b ON a.unit_id = b.unit_id LEFT JOIN db_brand c ON a.brand_id = c.brand_id LEFT JOIN db_goods_type d ON d.gtid = a.gtid LEFT JOIN (select a.gid,b.wgid from db_goods a,db_wholesale_goods b where a.gid = b.gid AND b.wid= $wid) f ON a.gid=f.gid"; 
        $sql .=$where." ORDER BY a.create_time desc ";   
        $page =getPageSql($sql,$page_size);
        $sql .=$limit; 
        $data = $goods->query($sql);   
        $this->assign('search',$search);
        $this->assign('data',$data); 
        $this->assign('p',$p); 
        $this->assign('page',$page->show());
        $this->display();
    }

    //商品管理--商品标准库多个导入/单个导入
    public function  goodsStdAdd()
    {
        $wid = getWid();
        $wholesale_goods = M('wholesale_goods');
        if(IS_POST) {
            $gid = I('gid');
            //验证批发商商品表中是否已存在该商品
            $dd =  $wholesale_goods ->where("wid =$wid and gid =$gid") -> find();
            if($dd){
                $this->ajaxReturn(ReturnJSON(100));  
            }else{
                $goods= M('goods');
                $list =$goods->find($gid);
                $unit_id = $list['unit_id'];
                $data = array(
                    'wid'=>$wid,
                    'gid'=>$gid,
                    'pic'=>$list['pic'],
                    'unit_id'=>$unit_id,
                    'price' =>$list['price'],
                    'create_time' => time()
                );
                $result= $wholesale_goods->add($data);
                if($result){
                    $this->ajaxReturn(0);
                }else{
                    $this->ajaxReturn(1);
                }
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //商品管理--商品标准库一键导入
    public function goodsImportAll(){
        if(IS_POST){
            $wid = getWid();
            $result = M()->query("CALL goodsImportAll($wid)");
            if($result[0]['resultcode']==0){
                $this->ajaxReturn(ReturnJSON(0));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //商品名称唯一性验证
    public function goodsVerify(){
        if(IS_POST){
            $gname = I("gname");//商品名称
            $wgid = I("wgid");
            $wid =getWid();
            $sql ="select a.`name` from db_goods a,db_wholesale_goods b where a.gid = b.gid and b.wid = $wid and a.`name`='$gname'";
            if($wgid){
                $sql .=" and b.wgid<>$wgid ";
            }
            $result = M()->query($sql);
            if($result){//存在
                $this->ajaxReturn(0);
            }else{//不存在
                $this->ajaxReturn(1);
            }
        }
    }

    //商品名称获取
    public function getGoodsName(){
        if(IS_POST){
            $m =M();
            $name = I("name");
            $sql ="select a.`name`,a.gid,a.unit_id,a.brand_id,a.pic,a.gtid,a.price,b.brand_name, c.unit_name FROM db_goods a left join db_brand b on a.brand_id=b.brand_id left join db_unit c on a.unit_id = c.unit_id WHERE  a.py LIKE '%".$name."%' or a.`name` = '".$name."' ORDER BY a.`name` "; 
            $data = $m->query($sql);
            if($data){
                $this->ajaxReturn(ReturnJSON(0,$data));
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    } 

    //商品类型----验证
    public function goodsTypeVerify()
    {
        $goods_type = M('goods_type');
        if(IS_POST) {
            $type_name = I('type_name');
            $wid =getWid();
            $where = "type_name='$type_name' and wid = $wid";
            $data = $goods_type->where($where)->find();
            if ($data) {
                $this->ajaxReturn(0);
            } else {
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //商品数据列设置展示
    public function goodsDataSet(){
        $wid =getWid(); 
        $gs_list = $this->getDataColumnInfo($wid);  
        // dump($gs_list);
        $this->assign("gs_list",$gs_list);
        $this->display();
    }
    //商品数据列设置数据提交
    public function dataColumnSubmit(){
        if(IS_POST){
            $wid =getWid();  
            $m = M("cdc");
            $list = $m->field('cdc_id')->where("wid =$wid")->find();
            $data =array(
                "wid"=>$wid,
                "gname"=>I("gname"),
                "gname_s"=>I("gname_s"),
                "gname_p"=>I("gname_p"),
                "gtname"=>I("gtname"),
                "gtname_s"=>I("gtname_s"),
                "gtname_p"=>I("gtname_p"),
                "pic"=>I("pic"),
                "pic_s"=>I("pic_s"),
                "pic_p"=>I("pic_p"),
                "bname"=>I("bname"),
                "bname_s"=>I("bname_s"),
                "bname_p"=>I("bname_p"),
                "price"=>I("price"),
                "price_s"=>I("price_s"),
                "price_p"=>I("price_p"),
                "uname"=>I("uname"),
                "uname_s"=>I("uname_s"),
                "uname_p"=>I("uname_p"),
                "ctime"=>I("ctime"),
                "ctime_s"=>I("ctime_s"),
                "ctime_p"=>I("ctime_p")
            );
            if($list){ //修改
                $cdc_id = $list['cdc_id'];
                $result = $m->where("cdc_id = $cdc_id")->save($data);
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
        $columns = getGoodsColumnName();
        $mrgsList = getGoodsColumn();
        $needs = getGoodsColumnNeed();
        $titles = getGoodsColumnTitle();
        $cdc = M("cdc")->where("wid=$wid")->find();
        $gs_list =array();
        foreach ($columns as $k => $v) {
            if(!empty($cdc)){
                if(empty($cdc[$v])){
                    $gs_list[$k]['show_name']=$titles[$v]; //显示名称 
                }
                else{
                    $gs_list[$k]['show_name']=$cdc[$v]; //显示名称
                } 
                $gs_list[$k]['isshow']= $cdc[$v.'_s']; //是否显示 0.显示 1.不显示
                $gs_list[$k]['isprint']= $cdc[$v.'_p']; //是否打印 0.打印 1.不打印
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