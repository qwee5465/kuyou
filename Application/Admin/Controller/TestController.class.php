<?php
/**
* 测试代码控制器
**/
namespace Admin\Controller;
use Think\Controller; 
class TestController extends Controller {
    //SQL 解析缓存
    public function index(){  
        $sql ="CALL addStockDetail(21,1,100,3,1,1)";
        $m = M();
        $data = $m->query($sql);

        dump($data); 
    }
}