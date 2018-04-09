<?php
namespace Api\Controller;
use Think\Controller;
class PublicController extends Controller {
	//获取规格信息  PS:此接口仅提供批发商端使用
    public function getUnitInfoByText(){
	    if(IS_POST){
	    	$unit_name = I('unit_name');
	    	$m = M("Unit");
	    	$where ="py like '%".$unit_name."%' or unit_name like '%".$unit_name."%'";
	    	$data = $m->where($where)->select(); 
	    	if($data){
                $this->ajaxReturn(ReturnJSON(0,$data));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
        	$this->ajaxReturn(ReturnJSON(7));
        }  
    }
    //获取规格所有信息  PS:此接口仅提供批发商端使用
    public function getUnitInfo(){
    	if(IS_POST){ 
	    	$m = M("Unit");
	    	$data = $m->select(); 
	    	if($data){
                $this->ajaxReturn(ReturnJSON(0,$data));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
        	$this->ajaxReturn(ReturnJSON(7));
        }  
    }

    //获取所有供应商信息  PS:此接口仅提供批发商端使用
    public function getSupplierInfo(){
        if(IS_POST){ 
            $m = M("Supplier");
            $wid = getWid();
            $data = $m->where("create_id = $wid")->select(); 
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
    * 根据搜索文本获取供应商信息  PS:此接口仅提供批发商端使用
    * @param name  
    * @return json
    **/
    public function getSupplierInfoByText(){
        if(IS_POST){
            $name = I('name'); 
            $btn = I('btn');
            $wid = getWid();
            $m = M("Supplier");
            $where ="create_id =$wid and (py like '%".$name."%' or name like '%".$name."%')";
            if($btn==1)
                $data = $m->where($where)->select(); 
            else
                $data = $m->where($where)->limit(5)->select(); 
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
    * 根据搜索文本获取供应商信息  PS:此接口仅提供批发商端使用
    * @param name  
    * @return json
    **/
    public function getSystemParam(){
        if(IS_POST){
            $wid =getWid();
            $system_param=M("system_param")->where("wid=$wid")->find();
            if(empty($system_param)){
                $system_param =getSystemParam();
            }
            $this->ajaxReturn($system_param);
        }else{
            $this->ajaxReturn(0);
        } 
    }

    /**
    * 根据搜索文本获取商品信息 PS:此接口仅提供批发商端使用
    * @param name  
    * @return json
    **/
    public function getGoodsInfoByText(){
        if(IS_POST){
            $name = I('name'); 
            $wid = getWid();
            $m = M(); 
            $sql ="select a.gid,a.price,a.wgid,a.wid,b.`name`,b.py,c.brand_name,d.unit_name,a.unit_id from db_wholesale_goods a,db_goods b,db_brand c,db_unit d where a.gid =b.gid and a.unit_id = d.unit_id and b.brand_id=c.brand_id and a.disable = 0 and a.wid = $wid and (b.`name` like '%".$name."%' or b.py like '".$name."%') limit 0,5";  
            $data = $m->query($sql); 
            if($data){
                foreach ($data as $k => $v) {
                    $data[$k]['title']= '【'.$v['name'].'-'.$v['brand_name'].'-'.$v['unit_name'].'】';
                    // $data[$k]['title']= '【'.$v['brand_name'].'-'.$v['name'].'】';
                    // $data[$k]['title']= '【'.$v['name'].'-'.$v['unit_name'].'】'; 
                } 
                $this->ajaxReturn(ReturnJSON(0,$data));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }  
    }

    /**
    * 根据搜索文本获取商品信息-出库单 PS:此接口仅提供批发商端使用
    * @param name  
    * @return json
    **/
    public function getGoodsInfoOut(){
        if(IS_POST){
            $name = I('name'); 
            $wid = getWid();
            $c_id = I("c_id"); 
            // $data = M()->query("CALL getGoodsInfoOut($c_id,'$name',$wid)"); 
            $data = M()->query("CALL getGoodsInfoOutNew($c_id,'$name',$wid)"); 
            if($data){
                foreach ($data as $k => $v) {
                    $data[$k]['title']= '【'.$v['name'].'-'.$v['brand_name'].'-'.$v['unit_name'].'】'; 
                    // //判断价格是否为空 1.空 
                    // if(empty($v['price']) || $v['is_enable']==0){
                    //     //查询该商品的最新价格 订单和出库单通用 该商品未有生产出库单时 查询不到价格
                    //     $wgid =$v['wgid'];
                    //     $sql ="select b.price price1 from db_out_stock a 
                    //         left join db_out_stock_detail b on a.osid = b.osid
                    //         left join db_client c on a.cid=c.c_id
                    //         where b.wgid = $wgid AND a.`status`= 1 AND a.cid = $c_id AND b.price > 0 ORDER BY a.auditing_time desc LIMIT 0,1";
                    //     $result = M()->query($sql);
                    //     if($result){ 
                    //         $data[$k]['price'] = $result[0]['price1'];
                    //     }else{
                    //         $data[$k]['price'] = 0;
                    //     }
                    // }
                    // //$data[$k]['title']= $v['name'];  
                } 
                // $clist = M("client")->field("than")->where("c_id = $c_id")->find();
                // if($clist){
                //     foreach ($data as $k => $v) {
                //         $data[$k]['than']= $clist['than'];
                //     } 
                // }
                $this->ajaxReturn(ReturnJSON(0,$data));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }  
    }

    /**
     * 获取入库商品记忆价
     */
    public function getJoinPrice(){
        if(IS_POST){
            $wgid = I("wgid");
            $sid = I("sid");
            $sql = "select b.price from db_join_stock  a
                left join db_join_stock_detail b on a.jsid = b.jsid
                where a.`status` = 1 and a.sid = $sid and b.wgid = $wgid and b.price > 0
                order by a.auditing_time desc limit 0,1";
            $data = M()->query($sql);  
            if($data){  
                $data = $data[0];
                $this->ajaxReturn(ReturnJSON(0,$data));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    /**
     * 获取出库商品记忆价 
     */
    public function getOutPrice(){
        if(IS_POST){
            $wgid = I("wgid");
            $c_id = I("c_id"); 
            $sql ="select b.price from db_out_stock a 
                left join db_out_stock_detail b on a.osid = b.osid
                left join db_client c on a.cid=c.c_id
                where b.wgid = $wgid AND a.`status`= 1 AND a.cid = $c_id AND b.price > 0 ORDER BY a.auditing_time desc LIMIT 0,1"; 
            $data = M()->query($sql);  
            if($data){  
                $data = $data[0];
                $this->ajaxReturn(ReturnJSON(0,$data));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        } 
    }

    /**
     * 根据商品编号获取规格信息
     */
    public function getSpecInfoById(){
        if(IS_POST){
            $wgid = I("wgid");
            $wid = getWid();
            $sql ="select bb.`name`, aa.gid, a.*, b.unit_name unit_name1,  c.unit_name unit_name2, d.unit_name unit_name3,  e.unit_name unit_name4 FROM  db_spec a LEFT JOIN db_wholesale_goods aa ON a.wgid = aa.wgid LEFT JOIN db_goods bb ON aa.gid = bb.gid LEFT JOIN db_unit b ON a.unit_id1 = b.unit_id LEFT JOIN db_unit c ON a.unit_id2 = c.unit_id LEFT JOIN db_unit d ON a.unit_id3 = d.unit_id LEFT JOIN db_unit e ON a.unit_id4 = e.unit_id WHERE  aa.wgid = $wgid AND aa.wid = $wid ORDER BY  a.inside_type ASC";
            $m=M();
            $data = $m->query($sql);  
            if($data){ 
                foreach ($data as $k => $v) {
                    $data[$k]['spec_rule'] =getInsideTypeName($v['inside_type'])."【".getSpecRule($v)."】";
                }
                $this->ajaxReturn(ReturnJSON(0,$data));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }  
    }

     /**
     * 获取商品库存剩余量
     */
    public function getStockNum(){
        if(IS_POST){
            $wgid = I("wgid");  
            $m=M("stock");
            $data = $m->field('num1,price')->where("wgid=$wgid")->find();  
            if(!$data){  
                $data['num1']=0;
            } 
            $this->ajaxReturn(ReturnJSON(0,$data));
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        } 
    }

    /**
     * 获取货损类型
     */
    public function getCargoDamage(){
        if(IS_POST){
            $wgid = I("wgid");  
            $m=M("CargoDamage");
            $data = $m->select();  
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
     * 根据批发商编号获取客户信息
     */
    public function getClient(){
        if(IS_POST){
            $name = I('name'); 
            $wid = getWid();
            $btn =I("btn");
            $m = M("Client");
            $where ="create_id =$wid and (py like '%".$name."%' or name like '%".$name."%')";
            if($btn==1)
                $data = $m->where($where)->order("create_time desc")->select(); 
            else
                $data = $m->where($where)->order("create_time desc")->limit(5)->select(); 
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
     * 获取指定客户是否存在合同
     */
    public function getClientContractInfo(){
        if(IS_POST){
            $cid =I("cid");
            $sql = " select a.cgpid,stime,etime from db_contract a 
                left join db_contract_detail b on a.cgpid=b.cgpid 
                where a.cid = $cid";
            $result = M()->query($sql); 
            if(!empty($result)){ 
                $t1 =time();
                if(floatval($result[0]['stime'])>$t1 || floatval($result[0]['etime'])<$t1){
                    $arr_result=array(
                        "resultcode" =>10,
                        "msg"=>"合同已过期,部分功能无法使用.",
                    );
                    $this->ajaxReturn($arr_result);
                }else{
                    $arr_result=array(
                        "resultcode" =>0,
                        "msg"=>"启用合同.",
                    );
                    $this->ajaxReturn($arr_result);
                } 
            }else{
                $arr_result=array(
                    "resultcode" =>10,
                    "msg"=>"无合同",
                );
                $this->ajaxReturn($arr_result);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    public function getSpecInfo(){
        if(IS_POST){
            $wgid =I("wgid");
            if($wgid>0){
                $sql ="select a.*,b.unit_name uname from db_spec a left join db_unit b on a.unit_id1 = b.unit_id where a.wgid =$wgid";
                $data = M()->query($sql);
                if($data){
                    $this->ajaxReturn(ReturnJSON(0,$data));
                }else{
                    $this->ajaxReturn(ReturnJSON(1));
                } 
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //商品搜索
    public function goodsSreach(){
        $wid =getWid(); 
        /*-------------------商品-------------------*/
        $gtid =I("gtid");$gtid1 =I("gtid1");$gtid2 =I("gtid2");$bname=I("bname");$gname=I("gname");
        $this->assign("gtid",$gtid);$this->assign("gtid1",$gtid1);$this->assign("gtid2",$gtid2);
        $this->assign("bname",$bname);$this->assign("gname",$gname);
        $where = " where a.wid = $wid ";
        if($gtid){
            $where .=" and a.gtid=$gtid";
        }
        if($gtid1){
            $where .=" and a.gtid1=$gtid1";
        }
        if($gtid2){
            $where .=" and a.gtid2=$gtid2";
        }
        if($bname){
            $where .=" and c.brand_name=$bname";
        }
        if($gname){
            $where .=" and (b.`name` like '%".$gname."%' or b.py like '%".$gname."%')";
        }
        $wid =getWid();
        $sql ="select b.`name` gname,c.brand_name bname,d.unit_name,e.type_name tname,f.type_name tname1,g.type_name tname2,a.*  from db_wholesale_goods a 
            left join db_goods b on a.gid=b.gid
            left join db_brand c on b.brand_id =c.brand_id
            left join db_unit d on a.unit_id = d.unit_id
            left join db_goods_type e on a.gtid = e.gtid
            left join db_goods_type f on a.gtid1 = f.gtid
            left join db_goods_type g on a.gtid2 = g.gtid";
        $sql.=$where;
        $list = M()->query($sql);
        foreach ($list as $k => $v) {
            $sql ="select a.unit_id1,a.num,b.unit_name from db_spec a left join db_unit b on a.unit_id1 = b.unit_id where a.wgid =".$v['wgid'];
            $list[$k]['usub'] = M()->query($sql);
        }  
        $this->assign("list",$list);
        // dump($list);
        /*-----------------商品类型-----------------*/
        $gtlist = M("goods_type")->where("series=1 and wid = $wid")->select();
        $this->assign("gtlist",$gtlist);
        /*-----------------品牌类型-----------------*/
        $blist = M("brand")->select(); 
        $this->assign("blist",$blist);

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

    //获取商品成本价
    public function getCostPrice(){
        if(IS_POST){
            $wgid = I("wgid");
            $result = M("stock")->field("price")->where("wgid=$wgid")->find();
            if(!empty($result)){
                $this->ajaxReturn(ReturnJSON(0,$result));
            }else{
                $this->ajaxReturn(ReturnJSON(1));
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }
}