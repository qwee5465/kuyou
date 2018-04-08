<?php

namespace Admin\Controller;

use Think\Controller;

class WholesaleController extends BaseController
{

	//构造函数
	public function _initialize(){
		// $this->m=M("WholesaleUser"); 
	}

    public function index()
    {
        $this->display();
    }

    //判断账号是否存在
    public function isPhone(){
        if(IS_POST){
            $phone =I("phone");
            $result = M("WholesaleUser")->where("phone = $phone")->find();
            if($result){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        } 
    }

    //供应商用户列表
    public function wholesaleList(){
    	if(IS_POST){ 
    		
    	}
    	$where = '';
    	$m =M("WholesaleUser");  
    	$p=getpage($m,$where); 
    	$data = $m->join("db_wholesale_info")->where("db_wholesale_user.info_id = db_wholesale_info.info_id")->order('create_time desc')->select();  
    	$this->assign('data',$data);// 赋值数据集 
		$this->assign('page', $p->show());// 赋值分页输出  
    	$this->display();
    }
     //供应商用户新增
    public function wholesaleAdd(){
    	if(IS_POST){
            $resultArr=array(
                "msg"=>"成功",
                "resultcode"=>"0"
            );
            M()->startTrans();//开启事务
            //添加公司信息
            $minfo = M("WholesaleInfo");
            $data =array(
                "unit_name"=>I("unit_name"),
                "pid"=>I("pid"),
                "cid"=>I("cid"),
                "did"=>I("did"),
                "street"=>I("street"),
                "legal_person"=>I("contacts"),
                "tel"=>I("phone")
            );
            $result = $minfo->add($data); 
    		if($result){
                $m = M("WholesaleUser");
                //添加用户信息 
                $data = array(
                    'phone' =>I('phone'), 
                    'info_id'=>$result,
                    'tel' =>I("phone"),
                    'password' =>md5(I("password")),  
                    'contacts' => I('contacts'), 
                    'remark' => I('remark'),
                    'create_time' => time(),
                    'create_id' => session('aid')
                ); 
                $result = $m->add($data); 
                if($result){
                    $user_role = M('UserRole');
                    $rid =I("rid");
                    $d  = array(
                        'rid' => $rid,  //杭州批发商BOSS
                        'u_type' => '批发商端', 
                        'uid' => $result
                    );
                    $result = $user_role->add($d);
                    if(!$result){
                        $resultArr["msg"]="新增用户角色时失败了";
                        $resultArr["resultcode"]=1; 
                    }
                }else{
                    $resultArr["msg"]="新增用户信息时失败了";
                    $resultArr["resultcode"]=1; 
                } 
			}else{
                $resultArr["msg"]="新增公司信息时失败了";
                $resultArr["resultcode"]=1; 
			}
            if($resultArr['resultcode']==1){
                M()->rollback();
                $this -> error($resultArr['msg']);
            }else{
                M()->commit();
                $this -> success('添加成功');
            }
    	}else{
            $m=M("role");
            $rlist = $m->field("rid,name")->where('role_type = 1 and fid=0 and `status` = 0')->select();
            $this->assign("rlist",$rlist);
    		$this->display();
    	} 
    }
    //供应商用户编辑
    public function wholesaleEdit(){
    	$m = M("WholesaleUser");
    	if(IS_POST){ 
    		$data = array(
                'wid'=>I('wid'),
    			'phone' =>I('phone'), 
    			'password' =>md5(123), 
    			'unit_name' => I('unit_name'),
    			'contacts' => I('contacts'),
    			'pid' => I('pid'),
    			'cid' => I('cid'),
    			'did' => I('did'),
    			'street' => I('street'),
    			'remark' => I('remark'),
    			'create_time' => time(),
    			'create_id' => session('aid')
    		);
    		$data = $m->save($data);
    		if($data){
				$this -> success('修改成功','./wholesaleList');
			}else{
				$this -> error('修改失败');
			}
    	}else{
    		$wid = I("wid");
    		$data = $m ->find($wid);
    		$this->assign("data",$data);
    		$this->display();
    	} 
    }

    //设置用户状态
    public function setUserStatus(){
        if(IS_POST){
           $wid = I("wid") ;
           $num = I("num");
           $data =array('status'=>$num);
           $result = M("wholesale_user")->where("wid = $wid")->save($data);
           if($result){
                $this->ajaxReturn(0);//修改成功
           }else{
                $this->ajaxReturn(1);//修改成功
           }
        }
    }
}