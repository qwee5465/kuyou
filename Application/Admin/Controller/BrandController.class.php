<?php
// +----------------------------------------------------------------------
// | 其他管理----品牌管理
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;

class BrandController extends BaseController {
    public function brandList(){
        $m = M("Brand");
        $where ="1=1"; 
        if(IS_POST){
            $search = I("search");
            $where .= " and brand_name like '%".$search."%'";
            $this->assign('search',$search);
        }
        $page =getpage($m,$where,10); 
        $this->assign('page',$page->show());
        $list = $m->where($where)->select(); 
        $this->assign("list",$list);
        $this->display(); 
    } 

    //单位名称唯一验证
    public function isBrandName(){
        if(IS_POST){
            $m = M("Brand");
            $brand_name = I("brand_name");
            $where ="brand_name = '".$brand_name."'";
            $brand_id = I("brand_id");
            if($brand_id){
                $where .= " and brand_id <> $brand_id";
            }
            $data = $m->where($where)->find(); 
             if ($data) {
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }  
    }
    //单位新增
    public function brandAdd(){
        if(IS_POST){
            $m = M('Brand');
            $brand_name = I("brand_name");
            $data =array(
                "brand_name" => $brand_name
            );
            $data = $m->add($data);
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }
        $this->display();
    }

    //单位编辑
    public function brandEdit(){
        $brand_id = I("brand_id");
        $m = M('Brand');
        if(IS_POST){ 
            $brand_name = I("brand_name");
            $data =array(
                "brand_id" => $brand_id,
                "brand_name" => $brand_name
            );
            $data = $m->save($data);
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }else{
            $list = $m -> find($brand_id);
            $this->assign("list",$list);
            $this->display();
        } 
    }

    //单位删除
    public function brandDel(){
         if(IS_POST){
            $brand_id = I('brand_id'); 
            $m = M('Brand');
            $list = $m->delete($brand_id);
            if ($list) {
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        } 
    }

}