<?php
// +----------------------------------------------------------------------
// | 主页
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class IndexController extends BaseController
{
    public function index()
    {
    	//根据用户角色获取一级权限列表
    	$m= M('Power');
    	if(session('rid')==12){
    		$data = $m->where('ptid = 1 and fid = 0 and name <>'."'首页'")->order('position asc')->select();  
	    	foreach ($data as $k => $v) {
	    		$data[$k]['sub'] = $m->where('ptid = 1 and fid ='.$v['pid'])->order('position asc')->select(); 
	    	}
    	}else{
    		$where ='db_role_power.rid ='.session('rid').' and db_role.status=0 and db_power.ptid = 1 and db_power.fid = 0 and db_power.name <>'."'首页'";  
	    	$data = $m->field('db_power.*')
	    			  ->join('db_role_power on db_power.pid = db_role_power.pid') 
	    			  ->join('db_role on db_role_power.rid=db_role.rid') 
	                  ->where($where) -> order('db_power.position asc') ->select();   
	    	foreach ($data as $k => $v) { 
	    		$where ='db_role_power.rid ='.session('rid').' and db_role.status=0 and db_power.ptid = 1 and db_power.fid =  '.$v['pid'].'  and db_power.name <>'."'首页'";
	    		$list = $m->field('db_power.*')
	                  ->join('db_role_power on db_power.pid = db_role_power.pid') 
	    			  ->join('db_role on db_role_power.rid=db_role.rid')
	                  ->where($where)->order('db_power.position asc')->select(); 
	    		$data[$k]['sub'] = $list;
	    	}
    	}   
    	$this->assign('data',$data);
        $this->display();
    } 

    //注销
    public function logon(){
    	if(IS_POST){ 
	    	session(null);
	    	$this->ajaxReturn(0);
    	}
    }
}