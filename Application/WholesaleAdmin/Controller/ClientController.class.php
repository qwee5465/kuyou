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
namespace WholesaleAdmin\Controller;

use Think\Controller;

class ClientController extends BaseController
{
    // 客户类型
    public function clientType()
    {
        $wid = getWid();
        $where=" wid = $wid"; 
        $search = I('search');
        if($search){
            $where .= " and type_name like '%$search%'";
        } 
        $p = empty(I("p")) ? 1 : I("p"); 
        $page_size = C("PAGE_SIZE"); 
        $s_num =  ($p-1)*$page_size+1;
        $this->assign('s_num',$s_num);
        $client_type = M('client_type');
        $page =getpage($client_type,$where,$page_size);
        $list = $client_type->where($where)->order('ctid desc')->select();
        $this->assign('page',$page->show());
        $this->assign('search',$search);
        $this->assign('list',$list);
        $this->display();
    }

    //判断客户简码是否存在
    public function isCode(){
        if(IS_POST){
           $code = I("code");
           $wid = getWid(); 
           $c_id =I("c_id");
           $result = M("client")->where("c_id <> '$c_id' and code = '$code' and create_id=$wid")->find();
           if($result){
                $this->ajaxReturn(0); //已存在
           }else{
                $this->ajaxReturn(1); //不存在
           }
        }
    }

    //客户类型---新增
    public function clientTypeAdd()
    {
        $client_type = M('client_type');
        if(IS_POST){
            $type_name = I('type_name');
            $client_type->type_name = $type_name;
            $client_type->wid = getWid();
            $list = $client_type->add(); 
            if($list){
                $this->ajaxReturn(0);
                //$this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->ajaxReturn(1);
                //$this->show("<script>parent.layer.closeAll();</script>",'utf-8');
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
            //判断客户类型是否被引用
            $m=M("client");
            $list = $m->where($where)->find();
            if($list){
                $this->ajaxReturn(2);
            }else{
                $client_type = M('client_type');
                $list = $client_type->where($where)->delete();
            } 
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
        $ctid = I('ctid');
        $where = "ctid=$ctid";
        $client_type = M('client_type');
        $list = $client_type->where($where)->find();
        $this->assign('list',$list);
        $this->display(); 
    }

    public function clientTypeEditAjax(){
        if(IS_POST){
            $ctid = I("ctid");
            $wid =getWid();
            $where = "ctid=$ctid and wid = $wid";
            $type_name = I('type_name');
            $client_type = M('client_type');
            $client_type->type_name = $type_name;
            $data = $client_type->where($where)->save();
            if($data){
                $this->ajaxReturn(0); 
            }else{
                $this->ajaxReturn(1); 
            }
        } 
    }

    //客户信息
    public function clientInfo()
    {
        $create_id = getWid();
        $where="create_id= $create_id";  
        $ctid = I('ctid');  $name = I('name');  $phone = I('phone'); $code = I("code");
        $this->assign("ctid",$ctid);$this->assign("name",$name);
        $this->assign("phone",$phone);$this->assign("code",$code);
        if($ctid){
            $where .=" and db_client.ctid=$ctid ";
        } 
        if($name){
            $where .= " and db_client.name like '%$name%' ";
        }
        if($phone){
            $where .= " and db_client.phone like '%$phone%' ";
        } 
        if($code){
            $where .= " and db_client.code ='$code' ";
        } 
        $client_type = M('client_type');
        $list_type = $client_type->where("wid = $create_id")->select();
        $client = M('client');
        $p = empty(I("p")) ? 1 : I("p"); 
        $page_size = C("PAGE_SIZE");
        $p=($p-1)*$page_size+1; 
        $page = getpage($client,$where,$page_size);
        $field = 'db_client.c_id,db_client.code,db_client.than,db_client_type.type_name,db_client.name,
                  db_client.phone,db_client.pid,db_client.cid,db_client.did,
                  db_client.street,db_client.remark';
        $list = $client->join('left join db_client_type ON db_client.ctid = db_client_type.ctid')
             ->where($where)->field($field)->order('create_time desc')->select();
        $this->assign('page',$page->show()); 
        $this->assign('list',$list);
        $this->assign('ctid',$ctid);
        $this->assign('p',$p);
        $this->assign('search',$search);
        $this->assign('list_type',$list_type);
        $this->display();
     }

    //客户信息 ----新增
    public function clientInfoAdd()
    { 
        if(IS_POST){
            $client = M('client');
            $wid =getWid();
            //判断客户简码是否存在
            $code = I("code"); 
            $result = M("client")->where("code = '$code' and create_id=$wid")->find();
            if($result){
                $this->error('新增失败,客户简码已存在请更换!'); 
            }else{
                $data = array(
                    'phone' =>I('phone'),
                    'code' => I('code'),
                    'than' => I('than'),
                    'ctid' =>I('ctid'),
                    'name' => I('name'),
                    'pid' => I('pid'),
                    'cid' => I('cid'),
                    'did' => I('did'),
                    'py'  => getFirstPY(I('name')),
                    'street' => I('street'),
                    'remark' => I('remark'),
                    'create_time' => time(),
                    'create_id' => $wid
                ); 
                $data = $client->add($data);
                if($data){
                    $this -> success('添加成功',session('up_url'));
                }else{
                    $this -> error('添加失败');
                }
            }
        }else{
            $client_Type = M('client_type');
            $list = $client_Type->where("wid=".getWid())->select();  //读取供应商类型 
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
            $m=M("out_stock");
            $list =$m->where("cid =$c_id")->find();
            if($list){
                $this->ajaxReturn(2);
            }else{
                $client = M('client');
                $list = $client->where($where)->delete();
            } 
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
        $wid =getWid();
        $list_type = $client_type->where("wid =$wid")->select();
        $c_id = I('get.c_id');
        $where = "c_id= $c_id";
        $client = M('client'); 
        if(IS_POST){
            $c_id =I("c_id");
            $code = I("code"); 
            $result = M("client")->where("c_id <> '$c_id' and code = '$code' and create_id=$wid")->find();
            if($result){
                $this->error('修改失败,客户简码已存在请更换!'); 
            }else{
                $data = array(
                    'phone' =>I('phone'),
                    'name' => I('name'), 
                    'code' => I('code'),
                    'than' => I('than'),
                    'ctid' =>I('ctid'), 
                    'pid' => I('pid'), 
                    'cid' => I('cid'),
                    'did' => I('did'), 
                    'py'  => getFirstPY(I('name')),
                    'street' => I('street'), 
                    'remark' => I('remark'), 
                    'create_time' => time(),
                    'create_id' => $wid
                );
                $data = $client->where("c_id=$c_id")->save($data);
                if($data){
                    $this -> success('修改成功',session('up_url'));
                }else{
                    $this -> error('修改失败');
                }
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
            $wid = getWid();
            $where = "type_name='$type_name' and wid = $wid";
            $ctid = I("ctid");
            if($ctid){
                $where .=" and ctid <> $ctid";
            }
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

    //验证号码是否存在
    public function clientInfoVerify()
    {
        $client = M('client');
        if(IS_POST){
            $phone = I('phone');
            $wid = getWid();
            $where = "phone = '$phone' and create_id = $wid";
            $data = $client->where($where)->find();
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