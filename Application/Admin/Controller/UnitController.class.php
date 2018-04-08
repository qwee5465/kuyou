<?php
// +----------------------------------------------------------------------
// | 系统设置----单位管理
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
class UnitController extends BaseController {
    public function unitList(){
    	$m = M("Unit");
    	$where ="1=1";
    	if(IS_POST){
    		$search = I("search");
    		$where .= " and unit_name like '%".$search."%'";
    		$this->assign('search',$search);
    	}
    	$page =getpage($m,$where,10); 
        $this->assign('page',$page->show());
    	$list = $m->where($where)->select(); 
    	$this->assign("list",$list);
        $this->display(); 
    } 

    //单位名称唯一验证
    public function isNuitName(){
    	if(IS_POST){
	    	$m = M("Unit");
	    	$unit_name = I("unit_name");
	    	$where ="unit_name = '".$unit_name."'";
	    	$unit_id = I("unit_id");
	    	if($unit_id){
	    		$where .= " and unit_id <> $unit_id";
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
    public function unitAdd(){
    	if(IS_POST){
    		$m = M('Unit');
    		$unit_name = I("unit_name");
    		$data =array(
    			"unit_name" => $unit_name,
                "py" => getFirstPY($unit_name)
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
    public function unitEdit(){
     	$unit_id = I("unit_id");
     	$m = M('Unit');
    	if(IS_POST){ 
    		$unit_name = I("unit_name");
    		$data =array(
    			"unit_id" => $unit_id,
                "unit_name" => $unit_name,
                "py" => getFirstPY($unit_name)
    		);
    		$data = $m->save($data);
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
    	}else{
    		$list = $m -> find($unit_id);
    		$this->assign("list",$list);
    		$this->display();
    	} 
    }

    //单位删除
    public function unitDel(){
    	 if(IS_POST){
            $unit_id = I('unit_id'); 
            $m = M('Unit');
            $list = $m->delete($unit_id);
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