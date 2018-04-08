<?php
// +----------------------------------------------------------------------
// | 系统设置-----回收站
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class RecycleBinController extends BaseController
{
    public function recycleBin()
    {
        $this->display();
    }
}