<?php
// +----------------------------------------------------------------------
// | 系统设置----回收站
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.fatwolf.cn/ )
// +----------------------------------------------------------------------
// | Author: 银狼
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class RecycleBinController extends BaseController
{
    public function recycleBin()
    {
    	$sql ="select c.`name` gname,d.unit_name,SUM(a.num1) as sum1  from db_out_stock_detail a  left join db_wholesale_goods b on a.wgid = b.wgid left join db_goods c on b.gid=c.gid left join db_unit d on d.unit_id = a.unit_id1 GROUP BY c.`name`,d.unit_name order by sum1 desc";
    	$list =M("")->query($sql);
    	$this->assign("list",$list);
        $this->display();
    }
}