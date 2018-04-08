<?php

namespace WholesaleAdmin\Controller;

use Think\Controller;

class RoleController extends BaseController
{	
	//角色列表
    public function roleList()
    { 
        $wid = getWid();
    	$rlist = M("role")->where("wid=$wid")->select();
        $this->assign("rlist",$rlist);
        $this->display();
    }
    //角色新增
    public function roleAdd(){   
    	if(IS_POST){ 
    		$role =M('Role'); 
            $wid =getWid();
            $name = I("name");
            //判断角色名称是否已存在
            $result = $role->where("name='$name' and wid=$wid")->find();
            if(empty($result)){ //不存在该角色名称
                $darr=array(   
                    'name'=>I('name'),
                    'wid'=>$wid
                );
                $data = $role ->add($darr);
                if($data){
                    $this->show("<script>parent.history.go(0);</script>",'utf-8');
                }
                else{ 
                    $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
                }
            }else{
               $this->show("<script>parent.layer.msg('角色名称已存在');</script>",'utf-8'); 
            }
    		
    	}
    	$this->display();
    }

    //角色编辑
    public function roleEdit(){ 
    	$role =M('Role');
    	if(IS_POST){
            $rid =I('rid');
    		$name = I("name");
            $wid = getWid();
            //判断角色名称是否已经存在
            $result = $role->where("name='$name' and wid=$wid and rid <> $rid")->find();
            $data =array("name"=>$name,"rid"=>$rid);
            if(empty($result)){ 
                $data = $role->where("rid=$rid")->save($data);
                if($data){
                    $this->show("<script>parent.history.go(0);</script>",'utf-8');
                }
                else{ 
                    $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
                }
            }else{ 
                $this->show("<script>parent.layer.msg('角色名称已存在');</script>",'utf-8');   
                $this->assign("data",$data);
                $this->display();
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
            //判断角色名称是否被用户使用
            $result = M("user_role")->where("rid = $rid")->find();
            if(empty($result)){//未被使用
                $role = M('Role');
                $data = $role->delete($rid); 
                if($data){ 
                    $this->ajaxReturn(1); //删除成功
                }else{
                    $this->ajaxReturn(0); //删除失败
                }
            }else{
                 $this->ajaxReturn(2); //被引用无法删除
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
            //获取单位账号所有权限 
            $wid = getWid();
            $sql ="select a.*,b.pid,c.`name` pname,c.p_code,c.fid,c.url from db_user_role a 
                left join db_role_power b on a.rid = b.rid
                left join db_power c on b.pid = c.pid
                where a.uid = $wid and a.u_type='批发商端'";
            $data = M("")->query($sql); 
    		$rid =I('rid'); 
            //获取当前角色所拥有的所有权限
    		$cdata = M("role_power")->field('pid')->where('rid ='.$rid)->select();
    		$this->assign("data",$data);
    		$this->assign("cdata",$cdata);   
            $this->assign("rid",$rid);
    		$this->display(); 
    	}
    	
    }
}