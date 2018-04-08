<?php
// +----------------------------------------------------------------------
// | 其他管理----品牌管理
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;
use Think\Controller;

class MachiningTypeController extends BaseController {
    public function machiningTypeList(){
        $this->display();
    }

    public function getMachiningInfo(){
       $list = M("unit")->select(); 
       $result['data']=$list;
       $result['recordsTotal']=count($list); 
       $result['recordsFiltered']=count($list); 
       $result['draw']=1;  
       $this->ajaxReturn($result);
    }
}