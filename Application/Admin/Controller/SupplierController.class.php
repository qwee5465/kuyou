<?php
// +----------------------------------------------------------------------
// | 信息管理模块-----供应商类型-----供应商
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class SupplierController extends BaseController
{
    //供应商类型
    public function supplierTypeList()
    {
       if(IS_POST){
           $search = I('search');
            if($search){
                $where= "type_name like '%$search%'";
            }
       }else{
           $where="";
       }
        $supplierType = M('supplier_type');
        $page =getpage($supplierType,$where,5);
        $list = $supplierType->where($where)->order('stid desc')->select();
        $this->assign('page',$page->show());
        $this->assign('list',$list);
        $this->assign('search',$search);
        $this->display();
    }

    //供应商类型-----新增
    public function supplierTypeAdd()
    {
        $supplierType = M('supplier_type');
        if(IS_POST){
            $type_name = I('type_name');
            $supplierType->type_name = $type_name;
            $list = $supplierType->add();
            if($list){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }
        $this->display();
    }

    //供应商类型----编辑
    public function supplierTypeEdit()
    {
        $stid = I('get.stid');
        $where = "stid=$stid";
        $supplierType = M('supplier_type');
        if(IS_POST){
            $type_naem = I('post.type_name');
            $supplierType->type_name = $type_naem;
            $data = $supplierType->where($where)->save();
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }else{
            $list = $supplierType->where($where)->find();
            $this->assign('list',$list);
            $this->display();
        }
    }

    //供应商类型----删除
    public function supplierTypeDel()
    {
        if(IS_POST){
            $stid = I('stid');
            $where = "stid=$stid";
            $supplierType = M('supplier_type');
            $list = $supplierType->where($where)->delete();
        }
        if ($list) {
            $this->ajaxReturn(0);
        }else{
            $this->ajaxReturn(1);
        }
    }

    //供应商
    public function supplierList()
    {
        if(IS_POST){
            $stid = I('stid');
            $search = I('search');
            if($search){
                $where= " name like '%$search%'";
            }
            if($stid){
                $where .=" db_supplier.stid=$stid";
            }
        }else{
            $where="";
        }
        $supplier_type = M('supplier_type');
        $list_type = $supplier_type->select();  //查询出全部类型名称
        $supplier = M('supplier');
        $page = getpage($supplier,$where,9);
        $field = 'db_supplier.sid,db_supplier_type.type_name,db_supplier.name,
                  db_supplier.phone,db_supplier.pid,db_supplier.cid,db_supplier.did,
                  db_supplier.street,db_supplier.remark';
        $list = $supplier->join('db_supplier_type ON db_supplier.stid = db_supplier_type.stid')
            ->where($where)->field($field)->order('sid desc')->select();
        $this->assign('page',$page->show());
        $this->assign('list',$list);
        $this->assign('stid',$stid);
        $this->assign('search',$search);
        $this->assign('list_type',$list_type);
        $this->display();
    }

    //供应商---新增
    public function supplierAdd()
    {
        $supplierType = M('supplier_type');
        $list = $supplierType->select();  //读取供应商类型
        if(IS_POST){
            $supplier= M("supplier");
            $data = array('phone' =>I('phone'), 'stid' =>I('stid'), 'name' => I('name'), 'pid' => I('pid'), 'cid' => I('cid'),
                          'did' => I('did'), 'street' => I('street'), 'remark' => I('remark'), 'create_time' => time(),
                'create_id' => session('aid')
            );
            $data = $supplier->add($data);
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

    //供应商----- 编辑
    public function supplierEdit()
    {
        $supplierType = M('supplier_type');
        $list_type = $supplierType->select();
        $sid = I('get.sid');
        $where = "sid= $sid";
        $supplier = M('supplier');
        $list =$supplier->join('db_supplier_type ON db_supplier.stid = db_supplier_type.stid')->where($where)->find();
        if(IS_POST){
            $data = array('phone' =>I('phone'), 'name' => I('name'), 'ctid' =>I('ctid'), 'pid' => I('pid'), 'cid' => I('cid'),
                'did' => I('did'), 'street' => I('street'), 'remark' => I('remark'), 'create_time' => time(),
               'create_id' => session('aid')
            );
            $data = $supplier->where($where)->save($data);
            if($data){
                $this -> success('修改成功',session('up_url'));
            }else{
                $this -> error('修改失败');
            }
        }else{
            session('up_url',I('server.HTTP_REFERER'));
            $this->assign('list_type',$list_type);
            $this->assign('list',$list);
            $this->display();
        }
    }

    //供应商------删除
    public function supplierDel()
    {
        if(IS_POST){
            $sid = I('sid');
            $where = "sid=$sid";
            $supplier = M('supplier');
            $list = $supplier->where($where)->delete();
        }
        if ($list) {
            $this->ajaxReturn(0);
        }else{
            $this->ajaxReturn(1);
        }
    }

    //供应商类型------验证
    public function supplierTypeVerify()
    {
        $supplier_type = M('supplier_type');
        if(IS_POST) {
            $type_name = I('type_name');
            $where = "type_name='$type_name'";
            $data = $supplier_type->where($where)->find();
            if ($data) {
                $this->ajaxReturn(0);
            } else {
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //供应商-----验证
    public function supplierVerify()
    {
        $supplier = M('supplier');
        if(IS_POST){
            $phone = I('phone');
            $where = "phone='$phone'";
            $data = $supplier->where($where)->find();
            if($data){
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxRetrun(ReturnJSON(7));
        }
    }
}