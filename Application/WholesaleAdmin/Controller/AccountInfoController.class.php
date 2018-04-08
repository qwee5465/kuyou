<?php
// +----------------------------------------------------------------------
// | 账号信息
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class AccountInfoController extends BaseController
{
    public function accountInfo()
    {
    	$wid = session("wid");
    	$m = M("wholesale_user");
        $sql ="select a.*,b.unit_name,b.pid,b.cid,b.did,b.street,b.legal_person,b.tel gstel from db_wholesale_user a,db_wholesale_info b where a.info_id=b.info_id and a.wid = $wid limit 0,1";
        $list = M()->query($sql); 
        if ($list) {
          $this->assign("list",$list[0]);
        }   
        // dump($list);
        $this->display();
    	
    }

    //基础信息提交
    public function baseInfoSubmit(){
        if(IS_POST){
            $wid = getWid();
            $m = M("wholesale_user");
            $update_id = session("wid");
            $data =array(
                "contacts"=>I("contacts"),
                "tel"=>I("tel"), 
                "address"=>I("address"),
                "update_time"=>time(),
                "update_id"=>$update_id
            );
            $result = $m->where("wid = $wid")->save($data);
            if($result){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }  
    }

    //公司信息提交 
    public function brandInfoSubmit(){
        if(IS_POST){
            $info_id = getInfoId();
            $m = M("wholesale_info"); 
            $data =array(
                "unit_name"=>I("unit_name"),
                "pid"=>I("pid"), 
                "cid"=>I("cid"), 
                "did"=>I("did"), 
                "street"=>I("street"), 
                "legal_person"=>I("legal_person"), 
                "tel"=>I("tel")
            );
            $result = $m->where("info_id = $info_id")->save($data);
            if($result){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }  
    }

    //修改密码
    public function upPassword(){
        if(IS_POST){
            $old_pwd = I("old_pwd");
            $new_pwd = I("new_pwd");
            //判断原密码是否正确
            $wid = session("wid");
            $m = M("wholesale_user");
            $result = $m->where("wid =$wid and password ='".md5($old_pwd)."'")->find();
            if($result){
                //修改密码
                $data=array(
                    "password"=>md5($new_pwd)
                );
                $result = $m->where("wid = $wid")->save($data);
                if($result){
                    $this->ajaxReturn(0);//修改成功
                }else{
                    $this->ajaxReturn(1);//修改失败
                }
            }else{
                //原密码错误
                $this->ajaxReturn(2);
            }
        }
    }


}