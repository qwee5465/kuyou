<?php
// +----------------------------------------------------------------------
// | 登录控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace Ipad\Controller;
use Think\Controller;
class LoginController extends Controller {
	public function login(){  
        $this->display(); 
    }

    public function loginAjax(){
    	if(IS_POST){ 
            $phone = I('login_id');
            $password = I('password'); 
            $sql ="select a.wid,a.contacts,a.fid,a.`status`,a.info_id,b.rid from db_wholesale_user a
			left join db_user_role b on b.uid = a.wid
			left join db_role_power c on b.rid = c.rid
			left join db_power d on d.pid = c.pid
			where a.phone = '".$phone."' and a.password = '".md5($password)."' and d.p_code ='P97'";
			
			$data = M()->query($sql); 
			$arr_result=array(
				'resultcode'=> 1,
				'msg' => '登录成功'
			);
            if($data){
                if($data[0]['status'] == 0){
                    //登录成功     
                    $arr_result['subid'] =$data[0]['wid'];  
                    if(!empty($data[0]['fid'])){
                        $arr_result['wid']=$data[0]['fid'];
                    }else{
                        $arr_result['wid']=$data[0]['wid'];
                    }
                    $arr_result['info_id']=$data[0]['info_id'];
                    $arr_result['contacts']=$data[0]['contacts'];
                    $arr_result['rid']=$data[0]['rid']; 
                    $this->ajaxReturn($arr_result); 
                }else{
	            	$arr_result['resultcode']= 10;
	            	$arr_result['msg']="该用户为禁用状态，请联系管理员.";
                    $this->ajaxReturn($arr_result);
                }
            }else{
            	$arr_result['resultcode']= 10;
            	$arr_result['msg']="权限不足或用户名密码错误.";
                $this->ajaxReturn($arr_result); 
            }
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