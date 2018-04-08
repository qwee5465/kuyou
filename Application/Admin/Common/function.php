<?php

//权限样式设置
function setPowerStyle($p_id){
	$arr = split('-',$p_id);
	$str =''; 
	if(count($arr)!=1){
		for($i=0;$i<count($arr);$i++){
			if($i==count($arr)-1){
				$str.='&nbsp;&nbsp;&nbsp;┠─';
			}else{
				$str.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			} 
		}
	}else{
		$str.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	return $str;
}


/**
 * TODO 基础分页的相同代码封装，使前台的代码更少
 * @param $m 模型，引用传递
 * @param $where 查询条件
 * @param int $pagesize 每页查询条数
 * @return \Think\Page
 */
function getpage(&$m,$where,$pagesize=10){
    $m1=clone $m;//浅复制一个模型
    $count = $m->where($where)->count();//连惯操作后会对join等操作进行重置 
    $m=$m1;//为保持在为定的连惯操作，浅复制一个模型
    $p=new Think\Page($count,$pagesize);
    $p->lastSuffix=false;  
    $p->setConfig('header','<span style="position: absolute;left:15px;top: 5px;font-size:14px;color:#999; letter-spacing: 1px;">共<b>%TOTAL_ROW%</b>条记录&nbsp;&nbsp;&nbsp;&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</span>');
    $p->setConfig('prev','<img src="/kuyou/Public/img/prev.png" style="width:35px;height:35px;" />'); 
    $p->setConfig('next','<img src="/kuyou/Public/img/next.png"  style="width:35px;height:35px;" />');     
    $p->setConfig('last','末页');
    $p->setConfig('first','首页');
    $p->setConfig('theme',' %HEADER%  %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');

    $p->parameter=I('get.');
    $p->rollPage=8;
    $m->limit($p->firstRow,$p->listRows);

    return $p;
}

/**
 * TODO 基础分页的相同代码封装，使前台的代码更少
 * @param $m 模型，引用传递
 * @param $where 查询条件
 * @param int $pagesize 每页查询条数
 * @return \Think\Page
 */
function getPageSql($sql,$pagesize=10){
    $m = M();
    $m1=clone $m;//浅复制一个模型
    // $count = $m->where($where)->count();//连惯操作后会对join等操作进行重置 
    $data = $m->query($sql);
    $count =count($data);
    $m=$m1;//为保持在为定的连惯操作，浅复制一个模型
    $p=new Think\Page($count,$pagesize);
    $p->lastSuffix=false;  
    $p->setConfig('header','<span style="position: absolute;left:15px;top: 5px;font-size:14px;color:#999; letter-spacing: 1px;">共<b>%TOTAL_ROW%</b>条记录&nbsp;&nbsp;&nbsp;&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</span>');
    $p->setConfig('prev','<img src="/kuyou/Public/img/prev.png" style="width:35px;height:35px;" />'); 
    $p->setConfig('next','<img src="/kuyou/Public/img/next.png"  style="width:35px;height:35px;" />');     
    $p->setConfig('last','末页');
    $p->setConfig('first','首页');
    $p->setConfig('theme',' %HEADER%  %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');

    $p->parameter=I('get.');
    $p->rollPage=8;
    $m->limit($p->firstRow,$p->listRows);

    return $p;
}

/**
 * 设置状态 状态0.正常 1.禁用
 * @param $status 状态0\1
 */
function setStatus($status){
    switch ($status) {
        case '0': 
            return '正常';
            break; 
        default: 
            return '禁用';
            break;
    }
}

/**
 * 设置角色类型 
 * @param $role_type 0.平台用户 1.批发商用户
 */
function setRoleType($role_type){
    switch ($role_type) {
        case '0':  
            return '平台用户';
            break; 
        default: 
            return '批发商用户';
            break;
    }
}






/**
 * 公用方法 添加日志信息
 * @param $title   标题
 * @param $jsid   入库单据号
 * @param $osid   出库单据号
 * @param $type   日志类型0.其他1.入库2.出库
 */
function addActionLog($title,$jsid,$osid,$type=0){
    $m=M('ActionLog'); 
    $data = array(
        'terminal' =>'平台端', 
        'title' =>$title, 
        'create_time' =>time(), 
        'create_id' =>session('aid'),
        'type' =>$type,
        'jsid'=>$jsid,
        'osid'=>$osid
    );
    $m->add($data);
}

/**
 * 公用方法 添加日志信息
 * @param $p_code   权限代码
 * @return  如果权限数组中存在传入的参数的值 返回true 否则 返回 false
 */
function analysis_code($p_code){  
    $code = session('p_codes');
    foreach ($code as $k => $v) {
        if($v['p_code']==$p_code){ 
            return true;
        }
    } 
    return false;
}

/**
 * 公用方法 更加单位编号，获取单位名称。
 * @param $unit_id  单位编号
 * @return  如果存在$unit_id 返回单位名称 否则返回自己
 */
function getUnitNameById($unit_id){
    if($unit_id){
        $unit = M('Unit');
        $data = $unit->cache(true)->find($unit_id);
        if($data){
            return $data['unit_name'];
        }else{
            return $unit_id;
        }
    }else{
        return '';
    }
}

/**
 * 公用方法 根据商品类型编号。
 * @param $gtid  单位编号
 * @return  如果存在$gtid 返回商品类型名称 否则返回自己
 */
function getGoodsTypeNameById($gtid){  
    $goods_type = M('GoodsType');
    $data = $goods_type ->cache(true)-> find($gtid); 
    if($data){
        return $data['type_name'];
    }else{
        return $gtid;
    }
}


/**
 * 公用方法 品牌名称。
 * @param $brand_id  品牌编号
 * @return  $brand_id 返回品牌名称
 */
function getBrandNameById($brand_id){
    $brand = M('brand');
    $data = $brand->cache(true)->find($brand_id);
    if($data){
        return $data['brand_name'];
    }else{
        return $brand_id;
    }
}

 /**
 *+--------------------------------------------------------------------------------
 *    获取拼音首字母
 *+--------------------------------------------------------------------------------
 *    @return string
 *+--------------------------------------------------------------------------------
 */
function getFirstPY($title){
    if($title){
        $py=new \Think\PinYin(); 
        $str = $py->getFirstPY($title);
        return $str;
    }else{
        return "";
    }
}








