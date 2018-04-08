<?php

namespace Admin\Controller;

use Think\Controller;

class RoleController extends BaseController
{	
	//角色列表
    public function roleList()
    {
    	$role =M('Role');
    	$data = $role->where('rid!=1') ->select();
    	$this->assign('data',$data);
        $this->display();
    }
    //角色新增
    public function roleAdd(){   
    	if(IS_POST){ 
    		$role =M('Role'); 
    		$darr=array(   
				'name'=>I('name'),
				'status'=>I('status'),
                'role_type'=>1,
                'fid'=> 0
			);
			$data = $role ->add($darr);
			if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{ 
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
    	}
    	$this->display();
    }

    //角色编辑
    public function roleEdit(){ 
    	$role =M('Role');
    	if(IS_POST){
    		$role->rid=I('rid');
    		$role->name=I("name");
    		$role->status=I("status");
    		$data = $role->save();
    		if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{ 
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
    	}else{
    		$data = $role->find(I('rid'));
	    	$this->assign("data",$data);
	    	$this->display();
    	} 
    }

    //角色删除
    public function roleDel(){ 
    	if(IS_POST){
    		$rid =I('rid'); 
    		$role = M('Role');
    		$data = $role->delete($rid); 
			if($data){ 
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
    	}else{
    		$this->ajaxReturn(ReturnJSON(7));
    	} 
    }

    public function set_status(){
        if(IS_POST){
            $rid =I('rid'); 
            $status = I('status');
            $role = M('Role');
            $role->rid =$rid;
            $role->status =$status; 
            $data = $role->save();  
            if($data){ 
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        } 
    }

    //查看角色权限、设置角色权限
    public function roleSetPower(){ 
		$current_rid = session('rid'); 
    	//超级管理员进行操作 
    	if(IS_POST){
    		$pid = I("pid");
    		$rid = I('rid');
    		$ptid =I('ptid');
    		if($pid){ 
    			$m = M("RolePower");
    			//删除该角色拥有的所有权限
                $m->where("rid=$rid")->delete(); 
				//新增角色权限
    			foreach ($pid as $val){
    				$data  = array(
						'rid' => $rid,
						'pid'=> $val 
					);
					$m->add($data);
    			}
    			$this->success("保存成功","./roleList");
    		} 
    	}else{
	    	if($current_rid==12){ 
	    		$power = M('Power'); $m = M("RolePower");
	    		$ptid =I('ptid');
	    		$data = $power->where('ptid='.$ptid)->select();
	    		$rid =I('rid'); 
	    		$cdata = $m->field('pid')->where('rid ='.$rid)->select();
	    		//获取当前角色所拥有的所有权限 
	    		$powerType =M('PowerType');
	    		$ptdata = $powerType->select();
	    		$this->assign("ptid",$ptid);
	    		$this->assign("rid",$rid);
	    		$this->assign("ptdata",$ptdata);
	    		$this->assign("data",$data);
	    		$this->assign("cdata",$cdata);  
	    		$this->display();
	    	}else{
	    		$this->error("您没有权限访问","./roleList");
	    	}  
    	}
    	
    }
}