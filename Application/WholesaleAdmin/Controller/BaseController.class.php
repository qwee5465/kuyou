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

class BaseController extends Controller {
    public function _initialize()
    {
        //基类处理账号登录后逻辑问题 
        if(!isset($_SESSION['wid']) or !isset($_SESSION['rid'])){
            $html = U('WholesaleAdmin/Login/login');
            $url = $_SERVER['PHP_SELF'];
            if(strpos($url,"Index/index")>0){
                header('Location:'.$html);
            }else{
                echo "<script>parent.location.href='".$html."'</script>";
            } 
            exit();
        } 
        $this->assign('version','1.0.4');
    } 

    public function _empty(){        
    	//把所有城市的操作解析到city方法 
    	header("Location:".U('WholesaleAdmin/Empty/empty1'));
    	// $this->show("<h2>找不到这个页面</h2>",'utf-8');
    }
}