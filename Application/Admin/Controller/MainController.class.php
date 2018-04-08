<?php
// +----------------------------------------------------------------------
// | 首页
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class MainController extends BaseController
{
    public function main()
    {
        $this->display();
    }
}