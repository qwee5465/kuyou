<?php
// +----------------------------------------------------------------------
// | 用户端-----登录页
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class LoginController extends Controller
{
    public function login()
    {  
        if(IS_POST){
            $wholesale = M('wholesale_user');
            $phone = I('login_id');
            $password = I('password');
            $where = "phone = '".$phone."' and password = '".md5($password)."'";
            $data = $wholesale->field('wid,contacts,fid,status,info_id')->where($where)->find(); 
            if($data){
                if($data['status'] == 0){
                    //登录成功
                    $wid = $data['wid'];
                    if($data['fid']!=0){
                        session('fid',$data['fid']);
                    }
                    session('wid',$wid);
                    session("info_id",$data['info_id']);
                    session("contacts",$data['contacts']);
                    $user_role = M('UserRole');
                    //查询该用户的角色信息 并保持到session中
                    $d = $user_role->field('db_user_role.*,db_role.name')
                        ->join('db_role on db_user_role.rid = db_role.rid')
                        ->where("db_user_role.uid=$wid and db_user_role.u_type ='批发商端'")
                        ->find();
                    session('rid',$d['rid']);
                    //获取该角色的所有权限
                    $power = M("Power");
                    $plist = $power->field('db_power.p_code')
                        ->join('left join db_role_power on db_power.pid = db_role_power.pid')
                        ->where("db_power.fid=1000 and db_role_power.rid=". session('rid'))->select();
                    $rand = time().rand(1000,9999);
                    //修改登录随机数
                    $updata = array(
                        "login_rand"=>$rand
                    );
                    $wholesale->where("wid = $wid")->save($updata);
                    session('p_codes',$plist);
                    session('r_name',$d['name']); 
                    session('login_rand',$rand);
                    /*--------------------获取系统参数设置数据----------------------*/
                    $splist = M("system_param")->where("wid=".getWid())->find();
                    if(empty($splist)){ 
                       $splist=getSystemParam(); 
                    } 
                    session("system_param",$splist); 
                    $this->success('登录成功',U('WholesaleAdmin/Index/index'));
                }else{
                    $this->error('该用户为禁用状态，请联系管理员.');
                }
            }else{
                $this->error('用户名密码错误');
                $this->display();
            }
        }else{
            if(isset($_SESSION['wid'])){
                header('Location:'.U('WholesaleAdmin/Index/index'));
            } 
            $this->display();
        }

    }
    public function findVerify()
    {
        $this->display();
    }
    public function register()
    {
        $this->display();
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