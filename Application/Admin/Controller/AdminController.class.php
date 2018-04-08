<?php
// +----------------------------------------------------------------------
// | 系统设置----平台用户管理
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;

class AdminController extends BaseController
{

	//构造函数
	public function _initialize(){
		// $this->m=M("WholesaleUser"); 
	}

    public function index()
    {
        $this->display();
    }

    //平台用户列表
    public function adminList(){ 
    	$where = '';
    	$m =M("admin"); 
    	$p=getpage($m,$where); 
    	$data = $m->where("aid <> 1")->order('aid desc')->select();  
        $this->assign('data',$data);// 赋值数据集 
		$this->assign('page', $p->show());// 赋值分页输出  
    	$this->display();
    }
     //平台用户新增
    public function adminAdd(){
    	if(IS_POST){
    		$m = M("Admin");
    		$data = array(
    			'login_id' =>I('login_id'), 
    			'password' =>md5(123), 
    			'name' => I('name'),
    			'tel' => I('tel'),  
    			'create_time' => time(),
    			'create_id' => session('aid')
    		);
    		$data = $m->add($data); 
    		if($data){ 
                $user_role = M("UserRole");
                $arr =array(
                    'rid' =>I('rid'),
                    'u_type' => '平台端',
                    'uid' => $data
                );
                $user_role->add($arr);
				$this -> success('添加成功');
			}else{
				$this -> error('添加失败');
			}
    	}else{ 
            $role =M("Role");
            $list = $role->where('role_type = 0')->select();   
            $this->assign('list',$list);// 赋值数据集 
    		$this->display();
    	} 
    }
    //平台用户编辑
    public function adminEdit(){
    	$m = M("Admin");
    	if(IS_POST){ 
    		$data = array(
                'aid'=>I('aid'),
    			'login_id' =>I('login_id'), 
    			'password' =>md5(123), 
    			'name' => I('name'),
    			'tel' => I('tel'),  
    			'update_time' => time(),
    			'update_id' => session('aid')
    		);
    		$data = $m->save($data);
    		if($data){
                $user_role = M("UserRole");
                $user_role->rid = I('rid');
                $user_role->u_type = '平台端';
                $uid = I('aid'); 
                $user_role->where("uid = $uid")->save();
				$this -> success('修改成功','./adminList');
			}else{
				$this -> error('修改失败');
			}
    	}else{
    		$aid = I("aid");
    		$data = $m ->where("aid = $aid") ->find(); 
            $role =M("Role");
            $user_role =M("UserRole");
            $rid = $user_role->where("uid =$aid")->find()['rid']; 
            $list = $role->where('role_type = 0')->select();   
            $this->assign('list',$list);// 赋值数据集 
            $this->assign("data",$data);
            $this->assign("rid",$rid);
    		$this->display();
    	} 
    }
    //删除平台用户
    public function adminDel(){
    	if(IS_POST){
    		$aid =I('aid'); 
    		$m = M('Admin');
    		$data = $m->delete($aid); 
			if($data){ 
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
    	}else{
    		$this->ajaxReturn(ReturnJSON(7));
    	} 
    }
    //账号验证
    public function check_Login(){
        if(IS_POST){
            $aid =I('aid'); 
            $login_id = I('login_id');
            $where = "login_id = '".$login_id."'";
            if($aid){
                $where .= " and  aid <> $aid";
            }
            $m = M('Admin'); 
            $data = $m->where($where)->find();
            if($data){ 
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        } 
    } 

}