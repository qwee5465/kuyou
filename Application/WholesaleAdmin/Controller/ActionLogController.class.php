<?php
// +----------------------------------------------------------------------
// | 系统设置----操作日志
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class ActionLogController extends BaseController
{

    public function actionLog()
    {
        $wid = session('wid');
        $action_log = M('action_log');
        $where = "create_id = $wid and terminal= '批发商端'";
        if(IS_POST){
            $wholesale_user = M('wholesale_user');
            $create_time1 = strtotime(I('create_time1'));
            $create_time2 = strtotime(I('create_time2'));
            $contacts = I('contacts');
            $title = I('title');
            $data = $wholesale_user->where("contacts='$contacts'")->find();
            $create_id = $data['wid'];
            if(!empty($title) ||!empty($contacts)){
                $where.=" and create_time between '$create_time1' and '$create_time2' and create_id = $create_id and title = '$title'";
            }
        }
        $page = getpage($action_log,$where,10);
        $list = $action_log->where($where)->order('log_id desc')->select();
//        echo $action_log->getLastSql();
        $this->assign('page',$page->show());
        $this->assign('list',$list);
        $this->display();
    }


    public function  actionLogDel()
    {
        if(IS_POST){
            $log_id = I('log_id');
            $action_log = M('action_log');
            $list = $action_log->delete($log_id);
            if ($list) {
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $this->ajaxReturn(ReturnJSON(7));
        }
    }

    public function actionLogVerify()
    {
        $wholesale_user = M('wholesale_user');
        if(IS_POST) {
            $contacts = I('contacts');
            $where = "contacts='$contacts'";
            $data = $wholesale_user->where($where)->find();
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