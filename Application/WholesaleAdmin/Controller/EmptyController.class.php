<?php
// +----------------------------------------------------------------------
// | 父控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class EmptyController extends Controller {
    public function index()
    {
        header("Location:".U('WholesaleAdmin/Empty/empty2')); 
    } 
    
}