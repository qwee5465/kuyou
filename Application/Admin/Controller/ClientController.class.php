<?php
// +----------------------------------------------------------------------
// | 信息管理---客户
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class ClientController extends BaseController
{
    // 客户类型
    public function clientType()
    {
        if(IS_POST){
            $search = I('search');
            if($search){
                $where= "type_name like '%$search%'";
            }
        }else{
            $where="";
        }
        $client_type = M('client_type');
        $page =getpage($client_type,$where,5);
        $list = $client_type->where($where)->order('ctid desc')->select();
        $this->assign('page',$page->show());
        $this->assign('search',$search);
        $this->assign('list',$list);
        $this->display();
    }

    //客户类型---新增
    public function clientTypeAdd()
    {
        $client_type = M('client_type');
        if(IS_POST){
            $type_name = I('type_name');
            $client_type->type_name = $type_name;
            $list = $client_type->add();
            if($list){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }
        $this->display();
    }

    //客户类型----删除
    public function clientTypeDel()
    {
        if(IS_POST){
            $ctid = I('ctid');
            $where = "ctid=$ctid";
            $client_type = M('client_type');
            $list = $client_type->where($where)->delete();
        }
        if ($list) {
            $this->ajaxReturn(0);
        }else{
            $this->ajaxReturn(1);
        }
    }

    //客户类型----编辑
    public function clientTypeEdit()
    {
        $ctid = I('get.ctid');
        $where = "ctid=$ctid";
        $client_type = M('client_type');
        if(IS_POST){
            $type_name = I('post.type_name');
            $client_type->type_name = $type_name;
            $data = $client_type->where($where)->save();
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }else{
            $list = $client_type->where($where)->find();
            $this->assign('list',$list);
            $this->display();
        }
    }

    //客户信息
     public function clientInfo()
     {
         if(IS_POST){
             $ctid = I('ctid');
             $search = I('search');
             if($search){
                 $where= " name like '%$search%'";
             }
             if($ctid){
                 $where .=" db_client.ctid=$ctid";
             }
         }else{
             $where="";
         }
         $client_type = M('client_type');
         $list_type = $client_type->select();
         $client = M('client');
         $page = getpage($client,$where,9);
         $field = 'db_client.c_id,db_client_type.type_name,db_client.name,
                  db_client.phone,db_client.pid,db_client.cid,db_client.did,
                  db_client.street,db_client.remark';
         $list = $client->join('db_client_type ON db_client.ctid = db_client_type.ctid')
             ->where($where)->field($field)->order('c_id desc')->select();
         $this->assign('page',$page->show());
         $this->assign('list',$list);
         $this->assign('ctid',$ctid);
         $this->assign('search',$search);
         $this->assign('list_type',$list_type);
         $this->display();
     }

    //客户信息 ----新增
    public function clientInfoAdd()
    {
        $client_Type = M('client_type');
        $list = $client_Type->select();  //读取供应商类型
        if(IS_POST){
            $client = M('client');
            $data = array(
                'phone' =>I('phone'),
                'ctid' =>I('ctid'),
                'name' => I('name'),
                'pid' => I('pid'),
                'cid' => I('cid'),
                'did' => I('did'),
                'street' => I('street'),
                'remark' => I('remark'),
                'create_time' => time(),
//                'create_id' => session('aid')
            );
            $data = $client->add($data);
            if($data){
                $this -> success('添加成功',session('up_url'));
            }else{
                $this -> error('添加失败');
            }
        }else{
            session('up_url',I('server.HTTP_REFERER'));
            $this->assign('list',$list);
            $this->display();
        }
    }

    //客户信息---删除
    public function clientInfoDel()
    {
        if(IS_POST){
            $c_id = I('c_id');
            $where = "c_id=$c_id";
            $client = M('client');
            $list = $client->where($where)->delete();
        }
        if ($list) {
            $this->ajaxReturn(0);
        }else{
            $this->ajaxReturn(1);
        }
    }

    //客户信息---编辑
    public function clientInfoEdit()
    {
        $client_type = M('client_type');
        $list_type = $client_type->select();
        $c_id = I('get.c_id');
        $where = "c_id= $c_id";
        $client = M('client');
        if(IS_POST){
            $data = array('phone' =>I('phone'), 'name' => I('name'), 'ctid' =>I('ctid'), 'pid' => I('pid'), 'cid' => I('cid'),
                          'did' => I('did'), 'street' => I('street'), 'remark' => I('remark'), 'create_time' => time(),
//               'create_id' => session('aid')
            );
            $data = $client->where($where)->save($data);
            if($data){
                $this -> success('修改成功',session('up_url'));
            }else{
                $this -> error('修改失败');
            }
        }else{
            session('up_url',I('server.HTTP_REFERER'));
            $list =$client->join('db_client_type ON db_client.ctid = db_client_type.ctid')->where($where)->find();
            $this->assign('list_type',$list_type);
            $this->assign('list',$list);
            $this->display();
        }
    }

    //客户类型--验证
    public function clientTypeVerify()
    {
        $client_type = M('client_type');
        if(IS_POST) {
            $type_name = I('type_name');
            $where = "type_name='$type_name'";
            $data = $client_type->where($where)->find();
            if ($data) {
                $this->ajaxReturn(0);
            } else {
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    } 
}