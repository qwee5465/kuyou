<?php

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
        'terminal' =>'批发商端',
        'title' =>$title, 
        'create_time' =>time(), 
        'create_id' =>session('wid'),
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
 * 公用方法 判断
 * @param $brand_id  品牌编号
 * @return  $brand_id 返回品牌名称
 */
function isGid($gid){
    $mmm = M('WholesaleGoods');
    $wid =""; 
    if(session('fid')){
        $wid = session('fid');
    }else{
        $wid =session('wid');
    } 
    $data = $mmm->cache(true)->where("gid=$gid and wid =$wid")->find();
    if($data){
        return true;
    }else{
        return false;
    }
}


/**
 * 公用方法 操作日志。
 * @param  $wid  返回操作人名称
 * @return  $wid 返回操作人名称
 */
function getUserAction($wid){
    $wholesale_user = M('wholesale_user');
    $data = $wholesale_user->find($wid);
    if ($data) {
        return $data['contacts'];
    }else{
        return $wid;
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

/**
 *+--------------------------------------------------------------------------------
 *    获取商品数据列数据
 *+--------------------------------------------------------------------------------
 *    @return array
 *+--------------------------------------------------------------------------------
 */
//获取数据列显示状态
function getGoodsColumn(){
    $gc = C("GOODS_COLUMN_SHOW");
    $arr = explode(',',$gc);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}
//获取数据列显示标题
function getGoodsColumnTitle(){
    $gcn = C("GOODS_COLUMN_NAME");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}
//获取数据列名称
function getGoodsColumnName(){
    $gcn = C("GOODS_COLUMN_NAME");
    $arr = explode(',',$gcn); 
    foreach ($arr as $k => $v) {
       $arr[$k] =   substr($v,0,strpos($v,'=')); 
    }
    return $arr;
}
//获取必需显示的数据列信息
function getGoodsColumnNeed(){
    $gcn = C("GOODS_COLUMN_NEED");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

/**
 *+--------------------------------------------------------------------------------
 *    获取入库表头数据列设置
 *+--------------------------------------------------------------------------------
 *    @return array
 *+--------------------------------------------------------------------------------
 */
//获取数据列显示状态
function getJoinStockColumnT(){
    $gc = C("JOIN_STOCK_COLUMN_SHOW_T");
    $arr = explode(',',$gc);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

//获取数据列显示标题
function getJoinStockColumnTitleT(){
    $gcn = C("JOIN_STOCK_COLUMN_NAME_T");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}
//获取数据列名称
function getJoinStockColumnNameT(){
    $gcn = C("JOIN_STOCK_COLUMN_NAME_T");
    $arr = explode(',',$gcn); 
    foreach ($arr as $k => $v) {
       $arr[$k] =   substr($v,0,strpos($v,'=')); 
    }
    return $arr;
}

//获取必需显示的数据列信息
function getJoinStockColumnNeedT(){
    $gcn = C("JOIN_STOCK_COLUMN_NEED_T");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

/**
 *+--------------------------------------------------------------------------------
 *    获取入库表格数据列设置
 *+--------------------------------------------------------------------------------
 *    @return array
 *+--------------------------------------------------------------------------------
 */
//获取数据列显示状态
function getJoinStockColumn(){
    $gc = C("JOIN_STOCK_COLUMN_SHOW");
    $arr = explode(',',$gc);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

//获取数据列显示标题
function getJoinStockColumnTitle(){
    $gcn = C("JOIN_STOCK_COLUMN_NAME");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}
//获取数据列名称
function getJoinStockColumnName(){
    $gcn = C("JOIN_STOCK_COLUMN_NAME");
    $arr = explode(',',$gcn); 
    foreach ($arr as $k => $v) {
       $arr[$k] =   substr($v,0,strpos($v,'=')); 
    }
    return $arr;
}

//获取必需显示的数据列信息
function getJoinStockColumnNeed(){
    $gcn = C("JOIN_STOCK_COLUMN_NEED");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

/**
 *+--------------------------------------------------------------------------------
 *    获取出库表格数据列设置
 *+--------------------------------------------------------------------------------
 *    @return array
 *+--------------------------------------------------------------------------------
 */
//获取数据列显示状态
function getOutStockColumn(){
    $gc = C("OUT_STOCK_COLUMN_SHOW");
    $arr = explode(',',$gc);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

//获取数据列显示标题
function getOutStockColumnTitle(){
    $gcn = C("OUT_STOCK_COLUMN_NAME");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}
//获取数据列名称
function getOutStockColumnName(){
    $gcn = C("OUT_STOCK_COLUMN_NAME");
    $arr = explode(',',$gcn); 
    foreach ($arr as $k => $v) {
       $arr[$k] =   substr($v,0,strpos($v,'=')); 
    }
    return $arr;
}

//获取必需显示的数据列信息
function getOutStockColumnNeed(){
    $gcn = C("OUT_STOCK_COLUMN_NEED");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

/**
 *+--------------------------------------------------------------------------------
 *    获取出库表头部数据列设置
 *+--------------------------------------------------------------------------------
 *    @return array
 *+--------------------------------------------------------------------------------
 */
//获取数据列显示状态
function getOutStockColumnT(){
    $gc = C("OUT_STOCK_COLUMN_SHOW_T");
    $arr = explode(',',$gc);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

//获取数据列显示标题
function getOutStockColumnTitleT(){
    $gcn = C("OUT_STOCK_COLUMN_NAME_T");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}
//获取数据列名称
function getOutStockColumnNameT(){
    $gcn = C("OUT_STOCK_COLUMN_NAME_T");
    $arr = explode(',',$gcn); 
    foreach ($arr as $k => $v) {
       $arr[$k] =   substr($v,0,strpos($v,'=')); 
    }
    return $arr;
}

//获取必需显示的数据列信息
function getOutStockColumnNeedT(){
    $gcn = C("OUT_STOCK_COLUMN_NEED_T");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

/**
 *+--------------------------------------------------------------------------------
 *    获取销售汇总表格数据列设置
 *+--------------------------------------------------------------------------------
 *    @return array
 *+--------------------------------------------------------------------------------
 */
//获取数据列显示状态
function getReportsColumn(){
    $gc = C("REPORTS_COLUMN_SHOW");
    $arr = explode(',',$gc);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

//获取数据列显示标题
function getReportsColumnTitle(){
    $gcn = C("REPORTS_COLUMN_NAME");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}
//获取数据列名称
function getReportsColumnName(){
    $gcn = C("REPORTS_COLUMN_NAME");
    $arr = explode(',',$gcn); 
    foreach ($arr as $k => $v) {
       $arr[$k] =   substr($v,0,strpos($v,'=')); 
    }
    return $arr;
}

//获取必需显示的数据列信息
function getReportsColumnNeed(){
    $gcn = C("REPORTS_COLUMN_NEED");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

/**
 *+--------------------------------------------------------------------------------
 *    获取日销售明细表格数据列设置
 *+--------------------------------------------------------------------------------
 *    @return array
 *+--------------------------------------------------------------------------------
 */
//获取数据列显示状态
function getDaySaleColumn(){
    $gc = C("DAYSALE_COLUMN_SHOW");
    $arr = explode(',',$gc);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

//获取数据列显示标题
function getDaySaleColumnTitle(){
    $gcn = C("DAYSALE_COLUMN_NAME");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}
//获取数据列名称
function getDaySaleColumnName(){
    $gcn = C("DAYSALE_COLUMN_NAME");
    $arr = explode(',',$gcn);
    foreach ($arr as $k => $v) {
        $arr[$k] =   substr($v,0,strpos($v,'='));
    }
    return $arr;
}

//获取必需显示的数据列信息
function getDaySaleColumnNeed(){
    $gcn = C("DAYSALE_COLUMN_NEED");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}


/**
 *+--------------------------------------------------------------------------------
 *    获取财务表格数据列设置
 *+--------------------------------------------------------------------------------
 *    @return array
 *+--------------------------------------------------------------------------------
 */
//获取数据列显示状态
function getFinanceColumn(){
    $gc = C("FINANCE_COLUMN_SHOW");
    $arr = explode(',',$gc);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

//获取数据列显示标题
function getFinanceColumnTitle(){
    $gcn = C("FINANCE_COLUMN_NAME");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}
//获取数据列名称
function getFinanceColumnName(){
    $gcn = C("FINANCE_COLUMN_NAME");
    $arr = explode(',',$gcn);
    foreach ($arr as $k => $v) {
        $arr[$k] =   substr($v,0,strpos($v,'='));
    }
    return $arr;
}

//获取必需显示的数据列信息
function getFinanceColumnNeed(){
    $gcn = C("FINANCE_COLUMN_NEED");
    $arr = explode(',',$gcn);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}





