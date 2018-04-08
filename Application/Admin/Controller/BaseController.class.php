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
namespace Admin\Controller;

use Think\Controller;

class BaseController extends Controller {
   public function _initialize()
   {	 
   		if(!isset($_SESSION['aid']) or !isset($_SESSION['rid'])){   
   			header('Location:'.U('Admin/Login/login'));
   			exit();
   		} 
        //基类处理账号登录后逻辑问题
   } 
}