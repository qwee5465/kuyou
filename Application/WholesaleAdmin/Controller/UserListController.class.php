<?php
// +----------------------------------------------------------------------
// | 系统设置----用户管理
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class UserListController extends BaseController{

    public function userList()
    {
        $wid =getWid();
        $where="";
        if(IS_POST){
            $search =I("search");
            $this->assign("search",$search);
            if(!empty($search)){
                $where .=" and a.phone like '%$search%'";
            }
        }
        $sql="select a.*,b.rid,c.name from db_wholesale_user a left join db_user_role b on a.wid=b.uid left join db_role c on b.rid=c.rid where a.fid = $wid"; 
        $sql .=$where;
        $list = M("")->query($sql); 
        $this->assign("list",$list);
        $this->display();
    }

    //禁用用户
    public function disableUser(){
        if(IS_POST){
            $wid =I('wid');
            $wholesale_user = M('wholesale_user');
            $data =array(
                "status"=>1
            );
            $result = $wholesale_user->where("wid=$wid")->save($data);
            if($result){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    public function userAdd()
    {
        $wholesale_user = M('wholesale_user'); 
        $wid =getWid();
        if(IS_POST){
            $phone = I('phone');
            $rid = I("rid");
            if($this->checkPhone($phone,"")){ 
                $this->show("<script>parent.layer.msg('该号码已注册');</script>",'utf-8');
            }else if(empty($rid)){
                $this->show("<script>parent.layer.msg('请选择角色');</script>",'utf-8');
            }else{
                $contacts = I('contacts');
                $wid =session("wid");
                //判断$wid 的角色
                if($this->isRole($wid)){
                    //获取用户公司信息
                    $info_id = getInfoId();
                    $data = array(
                        'phone'=>$phone,
                        'password'=> md5(I("password")),
                        'info_id'=>$info_id,
                        'contacts'=>$contacts,
                        'tel'=>$phone,
                        'fid'=>$wid,
                        'status'=>1,
                        'create_time'=>time(),
                        'create_id'=>$wid
                    );
                    $list = $wholesale_user->add($data);
                    if($list){  
                        //添加角色
                        $data=array(
                            'rid' => $rid,
                            'u_type'=>'批发商端',
                            'uid'=>$list,
                        );
                        $result = M("user_role")->add($data);
                        if($result){
                            $this->show("<script>parent.history.go(0);</script>",'utf-8');
                        }else{
                            $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
                        }
                    }
                    
                }
            }
        }
        //获取角色信息 
        $rlist = M("role")->where("wid=$wid")->select();
        $this->assign("rlist",$rlist);
        $this->display();
    }

    //判断角色是否为 BOSS 级别
    public function isRole($wid){ 
        $result = M("wholesale_user")->where("wid = $wid and fid = 0")->find(); 
        if($result){
            return true;
        }else{
            return false;
        }
    }

    //手机/用户名唯一性验证
    public function checkPhone($phone,$wid){
        $where ="phone='$phone' ";
        if(!empty($wid)){
            $where.=" and wid <> $wid ";
        }
        $result = M("wholesale_user")->where($where)->find();
        if($result){
            return true; //已经存在该手机号或用户
        }else{
            return false;//没有改手机或用户名
        }
    }


    public function userEdit()
    {
        $wid = I('wid');
        $rid = I("rid");
        $where = "wid = $wid";
        $wholesale_user = M('wholesale_user'); 
        if(IS_POST){
            $phone =I("phone");
            $rid = I("rid");
            if($this->checkPhone($phone,$wid)){ 
                $this->show("<script>parent.layer.msg('该号码已注册');</script>",'utf-8');
            }else if(empty($rid)){
                $this->show("<script>parent.layer.msg('请选择角色');</script>",'utf-8');
            }else{
                $data =array(
                    "phone"=>$phone,
                    "password"=>md5(I("password")),
                    "contacts"=>I("contacts")
                );
                $result = $wholesale_user->where($where)->save($data);
                $data1 =array(
                    "rid" => $rid,
                );
                $result = M("user_role")->where("uid = $wid")->save($data1);
                if(!empty($result) || !empty($result1)){
                    $this->show("<script>parent.history.go(0);</script>",'utf-8');
                }
                else{
                    $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
                }
            }
        }else{
            $field = "wid,phone,contacts";
            $list = $wholesale_user->field($field)->where($where)->find();
            $wid1 =getWid();
            $rlist = M("role")->where("wid=$wid1")->select();
            $this->assign("rlist",$rlist);
            $this->assign("rid",$rid);
            // dump($rid);
            $this->assign('list',$list); 
            $this->display();
        }
    }

    public function userListDel()
    {
        if(IS_POST){
            $wid =I('wid');
            $wholesale_user = M('wholesale_user');
            $data = $wholesale_user->delete($wid);
            if($data){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    // 账号唯一性验证
    public function userListVerify(){
        $wholesale_user = M('wholesale_user');
        if(IS_POST){
            $phone = I('phone');
            $wid = I('wid');
            $where = "phone = '$phone'";
            if($wid){
                $where .=" and wid <> $wid";
            }
            $data = $wholesale_user->where($where)->find();
            // $this->ajaxReturn($wholesale_user->_sql());
            if($data) {
                $this->ajaxReturn(1);
            } else {
                $this->ajaxReturn(0);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }
}