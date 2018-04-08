<?php
// +----------------------------------------------------------------------
// | 批发商端----主页
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class IndexController extends BaseController 
{
    public function index()
    {
        //根据用户角色获取一级权限列表
        $m= M('Power'); 
        $where ='db_role_power.rid ='.session('rid').' and db_power.ptid = 2 and db_power.jy_status = 0 and db_power.fid = 0 and db_power.name <>'."'首页'";
        $data = $m->field('db_power.*')
            ->join('db_role_power on db_power.pid = db_role_power.pid')
            ->where($where)->order('db_power.position asc')->select();
        foreach ($data as $k => $v) {
            $where ='db_role_power.rid ='.session('rid').' and db_power.ptid = 2 and db_power.jy_status = 0 and db_power.fid =  '.$v['pid'].'  and db_power.name <>'."'首页'";
            $list = $m->field('db_power.*')
                ->join('db_role_power on db_power.pid = db_role_power.pid')
                ->where($where)->order('db_power.position asc')->select();
            $data[$k]['sub'] = $list;
        }  
        $this->assign('contacts',session("contacts"));
        $this->assign('data',$data);
        // dump($data);
        $this->display();
    }
    //注销
    public function logon(){
        if(IS_POST){
            session(null);
            $this->ajaxReturn(0);
        }
    }
    
    public function loginRand(){
        //单账号登录限定
        $wid = session("wid");
        $data = M("wholesale_user")->field("login_rand")->where("wid = $wid")->find();
        // dump($data['login_rand']);dump(session("login_rand"));
        if($data['login_rand'] != session("login_rand")){
            // dump("账号在其他地方登录了.");
            // if(strpos($_SERVER['PHP_SELF'],"/Index/index")>0) 
            session(null);
            $this->ajaxReturn(1); //账号在其他地方登录了
        }else{
            $this->ajaxReturn(0); //正常
        }
    }
}