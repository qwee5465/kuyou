<?php
// +----------------------------------------------------------------------
// | 信息管理----货损类型
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class CargoDamageController extends BaseController
{
    //货损类型
	public function cargoDamage()
    {
        if(IS_POST){
            $search = I('search');
            if($search){
                $where= "cd_name like '%$search%'";
            }
        }else{
            $where="";
        }
        $cargo_damage = M('cargo_damage');
        $page =getpage($cargo_damage,$where,10);
        $list = $cargo_damage->where($where)->order('cdid desc')->select();
        $this->assign('page',$page->show());
        $this->assign('search',$search);
        $this->assign('list',$list);
        $this->display();
    }

    //货损类型----新增
    public function cargoDamageAdd()
    {
        $cargo_damage = M('cargo_damage');
        if(IS_POST){
            $cd_name = I('cd_name');
            $cargo_damage->cd_name = $cd_name;
            $list = $cargo_damage->add();
            if($list){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }
        $this->display();
    }

    //货损类型----删除
    public function cargoDamageDel()
    {
        $cdid = I('get.cdid');
        $where = "cdid=$cdid";
        $cargo_damage = M('cargo_damage');
        $list = $cargo_damage->where($where)->delete();
        if ($list) {
            $this->ajaxReturn(0);
        }else{
            $this->ajaxReturn(1);
        }
    }

    //货损类型---编辑
    public function cargoDamageEdit()
    {
        $cdid = I('get.cdid');
        $where = "cdid=$cdid";
        $cargo_damage = M('cargo_damage');
        if(IS_POST){
            $cd_name = I('post.cd_name');
            $cargo_damage->cd_name = $cd_name;
            $data = $cargo_damage->where($where)->save();
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }else{
            $list = $cargo_damage->where($where)->find();
            $this->assign('list',$list);
            $this->display();
        }
    }
    //货损类型----验证
    public function cargoDamageVerify()
    {
        $cargo_damage = M('cargo_damage');
        if(IS_POST) {
            $cd_name = I('cd_name');
            $where = "cd_name='$cd_name'";
            $data = $cargo_damage->where($where)->find();
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