<?php
// +----------------------------------------------------------------------
// | 登录页
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class LoginController extends Controller
{
    public function login()
    {
        if(IS_POST){
            $m = M('Admin');
            $login_id = I('login_id');
            $password = I('password'); 
            $data = $m->field('aid,name')->where("login_id = '".$login_id."' and password = '".md5($password)."'")->find(); 
            if($data){
                //登录成功
                $aid = $data['aid'];
                session('aid',$aid);
                $user_role = M('UserRole');
                //查询该用户的角色信息 并保持到session中
                $d = $user_role->field('db_user_role.*,db_role.name')
                          ->join('db_role on db_user_role.rid = db_role.rid')
                          ->where("db_user_role.uid=$aid and db_user_role.u_type ='平台端'")
                          ->find();   
                session('rid',$d['rid']);
                //获取该角色的所有权限
                $power = M("Power");
                $plist = $power->field('db_power.p_code')
                      ->join('left join db_role_power on db_power.pid = db_role_power.pid')
                      ->where("db_power.fid=1000 and db_role_power.rid=". session('rid'))->select();
                session('p_codes',$plist);
                session('r_name',$d['name']);
                $this->success('登录成功',U('Admin/Index/index')); 
            }else{
                $this->error('用户名密码错误');
                $this->display();
            } 
        }else{
            if(session('aid')){  
                $this -> error('已登录，正在跳转到主页', U('Index/index'));  
            }
            $this->display();
        }
       
    }

    //验证码
    public function verify() { 
        //清除缓存
        ob_clean(); 
        $Verify = new \Think\Verify();
        $Verify -> fontSize = 30;
        //验证码字体大小
        $Verify -> codeSet   =  '0123456789';
        $Verify -> length = 4;
        //验证码位数
        $Verify -> entry('admin');
    }
    //ajxa检查验证码 
    public function checkCode() {
        if(IS_POST){
            $code = I("code");
            //验证码
            $verify = new \Think\Verify();
            if ($verify -> check($code,'admin')) {
                $this -> ajaxReturn(0);//成功 
            } else {
                $this -> ajaxReturn(1);//失败
                
            }
        }else{
            $this->ajaxReturn(ResultCode(7));
        }
    }

}