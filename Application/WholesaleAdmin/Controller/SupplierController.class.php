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
namespace WholesaleAdmin\Controller;

use Think\Controller;

class SupplierController extends BaseController
{
    //供应商类型
    public function supplierTypeList()
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
        $supplierType = M('supplier_type');
        $page =getpage($supplierType,$where,$page_size);
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
            $wid = getWid();
            $supplierType->type_name = $type_name;
            $supplierType->wid = $wid;
            $list = $supplierType->add();
            if($list){
                $this->ajaxReturn(0);//添加成功
                // $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->ajaxReturn(1);//添加失败
                // $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
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
        $list = $supplierType->where($where)->find();
        $this->assign('list',$list);
        $this->display(); 
    }

    public function supplierTypeEditAjax(){
        if(IS_POST){
            $stid = I("stid");
            $type_name = I('post.type_name'); 
            $wid = getWid();
            $supplier_type = M('supplier_type');
            $where = "type_name='$type_name' and stid <> $stid and wid =$wid";
            $result = $supplier_type->where($where)->find();
            // $this->ajaxReturn($supplier_type->_sql());
            // exit();
            if($result){ 
                $this->ajaxReturn(2);//供应商类型已存在
            }else{
                $data =array(
                    'type_name'=>$type_name,
                    'wid'=>$wid
                );
                $result = $supplier_type->where("stid=$stid")->save($data);
                if($result){
                    $this->ajaxReturn(0);//修改成功
                }else{
                    $this->ajaxReturn(1);//修改失败
                }
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }
     //供应商类型------验证
    public function supplierTypeVerify()
    { 
        if(IS_POST) { 
            $type_name = I('type_name');
            $stid = I("stid"); 
            $supplier_type = M('supplier_type');
            $wid = getWid();
            $where = "type_name='$type_name' and wid =$wid";
            if($stid){
                $where.=" and stid <> $stid ";
            }
            $data = $supplier_type->where($where)->find();
            if($data) {
                $this->ajaxReturn(0);
            }else {
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    //供应商类型----删除
    public function supplierTypeDel()
    {
        if(IS_POST){
            $stid = I('stid');
            //判断 供应商类型是否被引用
            $m =M("supplier");
            $where = "stid=$stid";
            $list = $m->where($where)->find(); 
            if($list){ 
                $this->ajaxReturn(2);
            }else{
                $supplierType = M('supplier_type');
                $list = $supplierType->where($where)->delete(); 
            }
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
        $create_id = '';
        //子账号查询信息时调用父账户编号 
        //用于多账号
        $create_id =getWid(); 
        $where="create_id= $create_id";  
        $stid = I('stid');  $name = I('name');  $phone = I('phone'); 
        $this->assign("stid",$stid); $this->assign("name",$name); $this->assign("phone",$phone);
        if($stid){
            $where .=" and db_supplier.stid=$stid";
        }
        if($name){
            $where .= " and name like '%$name%'";
        }
        if($phone){
            $where .= " and phone like '%$phone%'";
        } 
        $supplier_type = M('supplier_type');
        $wid = getWid();
        $list_type = $supplier_type->where("wid=$wid")->select();  //查询出全部类型名称
        $supplier = M('supplier');
        $p = empty(I("p")) ? 1 : I("p"); 
        $page_size = C("PAGE_SIZE"); 
        $page = getpage($supplier,$where,$page_size);
        $field = 'db_supplier.*,db_supplier_type.type_name';
        $list = $supplier->join('db_supplier_type ON db_supplier.stid = db_supplier_type.stid')
            ->where($where)->field($field)->order('sid desc')->select(); 
        $p=($p-1)*$page_size+1;
        $this->assign("p",$p);
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
        $wid =getWid();
        $list = $supplierType->where("wid=$wid")->select();  //读取供应商类型 
        if(IS_POST){
            $supplier= M("supplier");
            $data = array(
                'phone' =>I('phone'),
                'stid' =>I('stid'), 
                'name' => I('name'), 
                'pid' => I('pid'), 
                'cid' => I('cid'),
                'did' => I('did'), 
                'py' => getFirstPY(I('name')),
                'street' => I('street'),
                'remark' => I('remark'),
                'create_time' => time(),
            );
            if(session('fid')){ 
                $data['create_id'] = session('fid');
                $data['sub_id'] = session('wid');
            }else{
                $data['create_id'] = session('wid');
            }
 
                
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
        $wid =getWid();
        $list_type = $supplierType->where("wid=$wid")->select();  //读取供应商类型 
        $sid = I('get.sid');
        $where = "sid= $sid";
        $supplier = M('supplier');
        $list =$supplier->join('db_supplier_type ON db_supplier.stid = db_supplier_type.stid')->where($where)->find(); 
        if(IS_POST){
            $data = array(
                'phone' =>I('phone'), 
                'name' => I('name'), 
                'stid' =>I('stid'), 
                'pid' => I('pid'), 
                'cid' => I('cid'),
                'did' => I('did'), 
                'py' => getFirstPY(I('name')),
                'street' => I('street'), 
                'remark' => I('remark'), 
                'create_time' => time() 
            );
            $sid =I("sid");
            $data = $supplier->where("sid=$sid")->save($data);
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
            $m =M("join_stock");
            $list = $m->where($where)->find();
            if($list){
                $this->ajaxReturn(2);
            }else{
                $supplier = M('supplier');
                $list = $supplier->where($where)->delete();
            }
        }
        if ($list) {
            $this->ajaxReturn(0);
        }else{
            $this->ajaxReturn(1);
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