<?php
// +----------------------------------------------------------------------
// | 信息管理----商品以下
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class ProductController extends BaseController
{
    //商品类型
    public function productType()
    {
        if(IS_POST){
            $search = I('search');
            if($search){
                $where= "type_name like '%$search%'";
            }
        }else{
            $where="";
        }
        $goods_type = M('goods_type');
        $page =getpage($goods_type,$where,5);
        $list = $goods_type->where($where)->order('gtid desc')->select();
        $this->assign('page',$page->show());
        $this->assign('search',$search);
        $this->assign('list',$list);
        $this->display();
    }

    //商品类型----新增
    public function productTypeAdd()
    {
        $goods_type = M('goods_type');
        if(IS_POST){
            $type_name = I('type_name');
            $goods_type->type_name = $type_name;
            $list = $goods_type->add();
            if($list){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }
        $this->display();
    }

    //商品类型---删除
    public function productTypeDel()
    {
        $gtid = I('get.gtid');
        $where = "gtid=$gtid";
        $goods_type = M('goods_type');
        $list = $goods_type->where($where)->delete();
        if ($list) {
            $this->ajaxReturn(0);
        }else{
            $this->ajaxReturn(1);
        }
    }

    //商品类型----编辑
    public function productTypeEdit()
    {
        $gtid = I('get.gtid');
        $where = "gtid=$gtid";
        $goods_type = M('goods_type');
        if(IS_POST){
            $type_name = I('post.type_name');
            $goods_type->type_name = $type_name;
            $data = $goods_type->where($where)->save();
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }else{
            $list = $goods_type->where($where)->find();
            $this->assign('list',$list);
            $this->display();
        }
    }

    //商品管理
    public function productMg()
    {
        $where="1=1 ";
        if(IS_POST){
            $search = I('search');
            if($search){
                $where .= "and a.name like '%$search%'";
            }
        }
        $goods = M('goods');
        $sql = "SELECT a.gid,b.type_name,a.`name`,a.pic,c.brand_name,a.price,d.unit_name AS unit_id1,e.unit_name AS unit_id2,
	            f.unit_name AS unit_id3,g.unit_name AS unit_id4,a.inside_type,a.create_time FROM db_goods a
                LEFT JOIN db_goods_type b ON b.gtid = a.gtid
                LEFT JOIN db_brand c ON c.brand_id = a.brand_id
                LEFT JOIN db_unit d ON d.unit_id = a.unit_id1
                LEFT JOIN db_unit e ON e.unit_id = a.unit_id2
                LEFT JOIN db_unit f ON f.unit_id = a.unit_id3
                LEFT JOIN db_unit g ON g.unit_id = a.unit_id4
                WHERE  $where ORDER BY gid DESC " ;
        $data = $goods->query($sql);
        $this->assign('search',$search);
        $this->assign('data',$data);
        $this->display();
    }

    // 商品标准库
    public function productStdLib()
    {
        $this->display();
    }
    //货损类型
	public function cargoDamage()
    {
        if(IS_POST){
            $search = I('search');
            if($search){
                $where= "cd_name like '%$search%'";
            }
        }else{
            $where="";
        }
        $cargo_damage = M('cargo_damage');
        $page =getpage($cargo_damage,$where,5);
        $list = $cargo_damage->where($where)->order('cdid desc')->select();
        $this->assign('page',$page->show());
        $this->assign('search',$search);
        $this->assign('list',$list);
        $this->display();
    }

    //货损类型----新增
    public function cargoDamageAdd()
    {
        $cargo_damage = M('cargo_damage');
        if(IS_POST){
            $cd_name = I('cd_name');
            $cargo_damage->cd_name = $cd_name;
            $list = $cargo_damage->add();
            if($list){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }
        $this->display();
    }

    //货损类型----删除
    public function cargoDamageDel()
    {
        $cdid = I('get.cdid');
        $where = "cdid=$cdid";
        $cargo_damage = M('cargo_damage');
        $list = $cargo_damage->where($where)->delete();
        if ($list) {
            $this->ajaxReturn(0);
        }else{
            $this->ajaxReturn(1);
        }
    }

    //货损类型---编辑
    public function cargoDamageEdit()
    {
        $cdid = I('get.cdid');
        $where = "cdid=$cdid";
        $cargo_damage = M('cargo_damage');
        if(IS_POST){
            $cd_name = I('post.cd_name');
            $cargo_damage->cd_name = $cd_name;
            $data = $cargo_damage->where($where)->save();
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }else{
            $list = $cargo_damage->where($where)->find();
            $this->assign('list',$list);
            $this->display();
        }
    }

    //商品类型----ajax验证
    public function productTypeVerify()
    {
        $goods_type = M('goods_type');
        if(IS_POST) {
            $type_name = I('type_name');
            $where = "type_name='$type_name'";
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
    //货损类型----验证
    public function cargoDamageVerify()
    {
        $cargo_damage = M('cargo_damage');
        if(IS_POST) {
            $cd_name = I('cd_name');
            $where = "cd_name='$cd_name'";
            $data = $cargo_damage->where($where)->find();
            if ($data) {
                $this->ajaxReturn(0);
            } else {
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }
}