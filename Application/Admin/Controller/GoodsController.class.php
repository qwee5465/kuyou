<?php
// +----------------------------------------------------------------------
// | 信息管理----商品类型---商品标准库
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class GoodsController extends BaseController
{
    //商品类型
    public function goodsTypeList()
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
    public function goodsTypeAdd()
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
    public function goodsTypeDel()
    {
        if(IS_POST){
            $gtid = I('gtid');
            $where = "gtid=$gtid";
            $goods_type = M('goods_type');
            $list = $goods_type->where($where)->delete();
            if ($list) {
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        } 
    }

    //商品名称唯一性验证
    public function isName(){
        if(IS_POST){ 
            $m =M('Goods');
            $name = I("name");
            $where =" name = '".$name."'";
            $gid = I("gid");
            if($gid){
               $where .=" and gid <> $gid"; 
            }
            $data = $m->where($where)->select();
            if($data){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{  
            $this->ajaxReturn(ReturnJSON(7));
        } 
    }

    //商品类型----编辑
    public function goodsTypeEdit()
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

     //商品标准库列表
    public function goodsList()
    {
        $where=" delete_mark = 0 and status = 0";
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
        $sql = "select a.name,a.*,b.unit_name,c.brand_name from db_goods a,db_unit b,db_brand c 
                where a.unit_id=b.unit_id and a.brand_id = c.brand_id "; 
        $sql .=" and ".$where." ORDER BY a.create_time asc ";   
        $page =getPageSql($sql,$page_size);
        $sql .=$limit; 
        $data = $goods->query($sql);   
        $this->assign('search',$search);
        $this->assign('data',$data); 
        $this->assign('p',$p); 
        $this->assign('page',$page->show());
        $this->display();
    }

    //商品标准库--新增
    public function goodsAdd()
    {
        $unit = M('unit');  //单位
        $goods = M('goods'); //商品表
        $brand = M('brand');  //品牌
        $goods_type = M('goods_type'); //商品类型
        $unitList = $unit->select();
        $brandList = $brand->select();
        $goodsTypeList = $goods_type->select();
        if(IS_POST){
            $info = uploadimg('Goods'); 
            if($info){
                $pic ="/Upload/Images/".$info["pic"]["savepath"].$info["pic"]["savename"];
            }
            $name= I('name');
            $gtid = I('gtid');
            $price = I('price');
            $unit_id = I('unit_id');
            $brand_id = I('brand_id');
            $data = array(
                'gtid'=>$gtid,
                'name'=>$name,
                'py'=> getFirstPY("$name"),
                'pic'=>$pic,
                'brand_id'=>$brand_id,
                'price'=>$price,
                'unit_id' =>$unit_id,
                'create_time'=>time(),
                'create_id'=>session('aid'),
                'delete_mark' => 0
            );
            $list = $goods->add($data);
            if($list){
                $this->success('添加成功',session('up_url'));
            }else{
                $this->error('添加失败');
            }
        }else{
            session('up_url',I('server.HTTP_REFERER'));
            $this->assign('unitList',$unitList);
            $this->assign('brandList',$brandList);
            $this->assign('goodsTypeList',$goodsTypeList);
            $this->display();
        } 
    }

    //商品标准库---编辑
    public function goodsEdit()
    {
        $gid = I('get.gid');
        $where = "gid = $gid";
        $unit = M('unit');  //单位
        $goods = M('goods'); //商品表
        $brand = M('brand');  //品牌
        $goods_type = M('goods_type'); //商品类型
        $unitList = $unit->select();
        $brandList = $brand->select();
        $goodsTypeList = $goods_type->select();
        $list = $goods->where($where)->find();
        if(IS_POST){
            $info = uploadimg('Goods');
            if($info){
                $pic ="/Upload/Images/".$info["pic"]["savepath"].$info["pic"]["savename"];
            }
            $name = I('name');
            $data = array(
                'gtid'=>I('gtid'),
                'name'=>$name, 
                'py'=> getFirstPY("$name"),
                'brand_id'=>I('brand_id'),
                'price'=>I('price'),
                'unit_id' =>I('unit_id'),
                'create_time'=>time(),
                'create_id'=>session('aid')
            );
            if($pic)
                $data['pic'] = $pic;
            $goodsEdit = $goods->where($where)->save($data);
            if($goodsEdit){
                $this -> success('修改成功',session('up_url'));
            }else{
                $this -> error('修改失败');
            }
        }else{
            session('up_url',I('server.HTTP_REFERER'));
            $this->assign('gid',$gid);
            $this->assign('list',$list);
            $this->assign('unitList',$unitList);
            $this->assign('brandList',$brandList);
            $this->assign('goodsTypeList',$goodsTypeList);
            $this->display();
        }
    }

    //商品标准库---删除
    public function goodsDel()
    {
        if(IS_POST){ 
            $gid = I('gid'); 
            $data = array(
                'gid'=>$gid, 
                'delete_mark'=> 1
            );
            $goods = M('goods');
            $list =$goods->save($data);
            if($list){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            } 
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //商品类型----ajax验证
    public function goodsTypeVerify()
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

    //待审核商品
    public function pendingAuditGoods(){
        $where=" where a.delete_mark = 0 and a.status = 1";
        if(IS_POST){
            $search = I('search');
            if($search){
                $where .= " and a.name like '%$search%'";
            }
        }
        $p = empty(I("p")) ? 1 : I("p"); 
        $page_size = C("PAGE_SIZE"); 
        $s_num =  ($p-1)*$page_size+1;
        $this->assign("s_num",$s_num);
        $s_page=$p-1; //分页开始数
        $e_page=$page_size; //分页结束数
        $limit = " limit $s_page,$e_page";
        $goods = M('Goods');  
        $sql ="select a.*,b.brand_name bname,c.unit_name uname,d.type_name tname from db_goods a  left join db_brand b on a.brand_id = b.brand_id left join db_unit c on a.unit_id = c.unit_id left join db_goods_type d on d.gtid = a.gtid"; 
        $sql .=$where." order by a.create_time desc ";
        $page =getPageSql($sql,$page_size); 
        $sql .=$limit;  
        $data = M()->query($sql);
        //dump($data); 
        $this->assign('search',$search);
        $this->assign('data',$data);  
        // dump($data);
        $this->assign('page',$page->show());
        $this->display();
    }

    //审核商品
    public function AuditingGoods(){
        if(IS_POST){
            $gid = I('gid');
            $m = M('Goods');
            $data = array(
                "gid"=>$gid, 
                "status"=>0
            );
            $list = $m->save($data);
            if ($list) {
                $this->ajaxReturn(0);
            } else {
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    // 数据设置列
    public function goodsDataSet(){
        $this->display();
    }
}