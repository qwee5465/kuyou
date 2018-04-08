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

class CgpController extends BaseController
{
    public function cgpList()
    {
        $wid = getWid(); //登录id
        $page_size = C('PAGE_SIZE');
        $p = I("p"); 
        $s_num = 0;
        if($p){  
            $p=($p-1)*$page_size+1;
            $s_num = $p;
        }else{
            $p = 1;
            $s_num =  ($p-1)*$page_size+1; 
        }
        $this->assign("s_num",$s_num);
        $s_page=$p-1; //分页开始数
        $e_page=$page_size; //分页结束数
        $limit = " limit $s_page,$e_page";
        $sql = "SELECT f.`name` cname,a.cid,a.price,c.`name`,d.brand_name,e.unit_name FROM db_cgp a
                LEFT JOIN db_wholesale_goods b ON a.wgid = b.wgid LEFT JOIN db_goods c ON b.gid = c.gid
                LEFT JOIN db_brand d ON d.brand_id = c.brand_id LEFT JOIN db_unit e ON e.unit_id = c.unit_id
                LEFT JOIN db_client f ON f.c_id = a.c_id LEFT JOIN db_client_type g ON f.ctid = g.ctid
                WHERE a.wid = $wid  ";
        $ctid = I('ctid');$c_id = I('c_id');
        $this->assign("ctid",$ctid);$this->assign("c_id",$c_id);
        if (!$ctid == "0"){
            $sql.= "AND g.ctid = $ctid";
        }
        if (!$c_id == "0"){ 
            $sql.= " AND f.c_id = $c_id";
        }
        $sql.=" ORDER BY a.cid DESC";
        $page =getPageSql($sql,$page_size);
        $sql.=$limit;
        $list = M('')->query($sql);
        $list_type = M('client_type')->where("wid = $wid")->select();   //客户类型 
        $result = M('client')->where("create_id = $wid")->order("create_time desc")->select();  
        $this->assign('result',$result);
        $this->assign('list',$list);
        $this->assign('list_type',$list_type);
        $this->assign('page',$page->show());
        $this->display();
    }


    #客户商品列表-----添加商品
    public function cgpAdd()
    {
        if(IS_POST){
            $cgp = M('cgp');
            $wid = getWid(); //登录id
            $wgid = I('wgid');      //商品名称
            $price = I('price');    //商品单价
            $c_id = I('c_id');    //客户id
            if (empty($wgid) || empty($price)){

            }else{
                $where = "c_id = $c_id and wgid = $wgid";
                $list = $cgp->where($where)->select();
                if (!empty($list)){
                    $data['price'] = $price;
                    $cgp->where($where)->save($data);
                    $sql= "SELECT f.`name` cname,a.cid,a.price,c.`name`,d.brand_name,e.unit_name FROM db_cgp a
                    LEFT JOIN db_wholesale_goods b ON a.wgid = b.wgid LEFT JOIN db_goods c ON b.gid = c.gid
                    LEFT JOIN db_brand d ON d.brand_id = c.brand_id LEFT JOIN db_unit e ON e.unit_id = c.unit_id
                    LEFT JOIN db_client f ON f.c_id = a.c_id LEFT JOIN db_client_type g ON f.ctid = g.ctid
                    WHERE a.c_id = $c_id AND a.wgid = $wgid";
                    $result = M('')->query($sql);
                    $this->ajaxReturn($result);
                }else{
                    $cgp->wid = $wid;
                    $cgp->c_id = $c_id;
                    $cgp->wgid = $wgid;
                    $cgp->price = $price;
                    $list = $cgp->add();
                    $sql= "SELECT f.`name` cname,a.cid,a.price,c.`name`,d.brand_name,e.unit_name FROM db_cgp a
                    LEFT JOIN db_wholesale_goods b ON a.wgid = b.wgid LEFT JOIN db_goods c ON b.gid = c.gid
                    LEFT JOIN db_brand d ON d.brand_id = c.brand_id LEFT JOIN db_unit e ON e.unit_id = c.unit_id
                    LEFT JOIN db_client f ON f.c_id = a.c_id LEFT JOIN db_client_type g ON f.ctid = g.ctid
                    WHERE a.cid = $list";
                    $result = M('')->query($sql);
                    $this->ajaxReturn($result);
                }
            }
        }
    }
    //客户名称
    public function clientName()
    {
        if (IS_POST){
            $wid = getWid();
            $ctid = I('ctid'); 
            $list = M('client')->where("create_id = $wid and ctid =$ctid ")->order("create_time desc")->select();   
            if ($list){
                $this->ajaxReturn($list);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(7);
        }
    }
    //删除
    public function cgpDel()
    {
        if (IS_POST){
            $cid = I('cid');
            $list = M('cgp')->delete($cid);
            if ($list) {
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }
    }
}