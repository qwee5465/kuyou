<?php
// +----------------------------------------------------------------------
// | 报表
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class ReportsController extends BaseController
{
    public function daySaleDetails()
    {
        $this->display();
    }
    public function totalSale()
    {
        $this->display();
    }
    public function financeAnaly()
    {
        $this->display();
    }
    public function supplierGet()
    {
        $this->display();
    }
}