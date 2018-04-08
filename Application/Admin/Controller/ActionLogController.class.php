<?php
// +----------------------------------------------------------------------
// | 系统设置----操作日志
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class ActionLogController extends BaseController
{
    public function actionLog()
    {
        $action_log = M('action_log');
        $page = getpage($action_log,10);
        $list = $action_log->order('log_id desc')->select();
        $this->assign('page',$page->show());
        $this->assign('list',$list);
        $this->display();
    }

    public function  actionLogDel()
    {
        if(IS_POST){
            $log_id = I('log_id');
            $where = "log_id = $log_id";
            $action_log = M('action_log');
            $list = $action_log->where($where)->delete();
            if ($list) {
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }
    }
    
}