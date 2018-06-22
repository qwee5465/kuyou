<?php
/**
 * 处理json数据用以返回
 * @param type $resultCode
 * @param type $list
 * @return type
 */
function ReturnJSON($resultCode, $list = array()) {
    $JSON['resultcode'] = $resultCode;
    if ($resultCode == 0) {
        $JSON['msg'] = ResultCode($resultCode);
    } else {
        $JSON['msg'] = ResultCode($resultCode);
    } 
    if (!empty($list)) {
        $JSON['result'] = $list;
    }
    return $JSON;
}

function ReturnJSON1($resultCode,$msg,$list = array()){
    $JSON['resultcode'] = $resultCode;
    if ($resultCode == 0) {
        $JSON['msg'] = $msg?$msg:ResultCode($resultCode);
    } else {
        $JSON['msg'] = $msg?$msg:ResultCode($resultCode);
    } 
    $JSON['result'] = $list;
    return $JSON;
}

/**
 * 处理json数据用以返回
 * @param type $resultCode
 * @param type $list
 * @return type
 */
function buildData($resultCode, $list = array()) {
    $JSON['resultcode'] = $resultCode;
    if ($resultCode == 0) {
        $JSON['msg'] = ResultCode($resultCode);
    } else {
        $JSON['msg'] = ResultCode($resultCode);
    } 
    $JSON['result'] = $list;
    return $JSON;
}

/**
 * 根据代码生成不同返回值
 * @param type $resultCode
 * @return string
 */
function ResultCode($resultCode) {
    switch ($resultCode) {
        case 0 : return '请求成功';
        case 1 : return '无数据或数据已删除';
        case 2 : return '用户存在';
        case 3 : return '用户名错误或者密码错误';
        case 4 : return '短信发送失败';
        case 5 : return '验证码错误';
        case 6 : return '数据更新失败或未更新数据';
        case 7 : return '请求类型错误';
        case 8 : return '数据有误';
        case 9 : return '删除失败';
        case 10 : return '单据审核失败';
        case 11 : return '该单据已有物品出库，请先反审核对应的出库验收单。';
        case 12 : return '语法错误，事务自动回滚.';
        case 13 : return '请求参数有误';
        case 401 : return '用户未登录，需登录';
        case 402 : return '所在用户无该接口操作权限';
        case 403 : return '系统错误';
        case 404 : return '接口不存在';
        case 507 : return '签名验证失败';
        case 508 : return '登录已过期';
        case 509 : return '已在别的地方登录';
        case 1009 : return '请求参数错误，前端自定义提示';
        default :return '请求失败';
    }
}


function getUnitName(){
    $info_id = session('info_id');
    $result = M("wholesale_info")->field("unit_name")->where("info_id = $info_id")->find();
    $unit_name ="";
    if($result){
        $unit_name = $result['unit_name'];
    }
    return $unit_name;
}

function getUnitName1($info_id){ 
    $result = M("wholesale_info")->field("unit_name")->where("info_id = $info_id")->find();
    $unit_name ="";
    if($result){
        $unit_name = $result['unit_name'];
    }
    return $unit_name;
}

//将字符串转成数字
function getNum($str){
    return floatval($str);
}

//图片上传 支持多图片上传
function uploadimg($subName = array('date','Ymd')){
    $config = array(
        'maxSize'    =>    3145728,
        'rootPath'   =>    './Public/Upload/Images/',
        'savePath'   =>    '',
        'saveName'   =>    array('uniqid',''),
        'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),
        'autoSub'    =>    true,
        'subName'    =>    $subName,
    );
    $upload = new \Think\Upload($config);// 实例化上传类 
    // 上传文件 
    $info   =   $upload->upload();
    if(!$info) {// 上传错误提示错误信息
        // return $upload->getError();
        return false; 
    } else{ //图片上传成功 
        return $info;
    } 
}
/**
 * 获取批发商账号
 */
function getWid(){
    $wid = "";
    if(session('fid')){
        $wid = session('fid');
    }else{
        $wid = session('wid');
    }
    return $wid;
}

/**
 * 获取批发商账号及子账号信息
 */
function getAccountInfo(){
	$arr = array();
	if(session('fid')){
        $arr['wid'] = session('fid');
        $arr['sub_id'] = session('wid');
    }else{
        $arr['wid'] = session('wid');
        $arr['sub_id'] = '';
    }
    return $arr;
}

/*
 * 商品管理内装类型
 * $inside_type  0:内装  1：非内装
 * */
function getInsideTypeName($inside_type){
    switch($inside_type)
    {
        case '0':
            return '内装'; 
        case '1':
            return '非内装'; 
        default:
            return '未设置'; 
    }
}

/**
 * 根据规格信息生成规格规则
 * @param array $data
 * @return string
 */
function getSpecRule($data =array()){
    if(count($data)>0){
        $str ="";
        if($data["inside_type"] == 0){
            //内装 1箱=10袋，1袋=5公斤,1公斤=2斤
            if($data["unit_id4"]){
                $str.="1".$data["unit_name4"]."=".$data["num3"].$data["unit_name3"].",";
            }
            if($data["unit_id3"]){
                $str.="1".$data["unit_name3"]."=".$data["num2"].$data["unit_name2"].",";
            }
            if($data["unit_id2"]){
                $str.="1".$data["unit_name2"]."=".$data["num1"].$data["unit_name1"].",";
            }
        }else{//非内装
            if($data["unit_id4"]){
                $str.=$data["unit_name4"].",";
            }
            if($data["unit_id3"]){
                $str.=$data["unit_name3"].",";
            }
            if($data["unit_id2"]){
                $str.=$data["unit_name2"].",";
            }
            if($data["unit_id1"]){
                $str.=$data["unit_name1"].",";
            } 
        }
        if($str)
                $str = substr($str,0,strlen($str)-1);
        return $str;
    }else{
        return "";
    } 
}

/**
 * 获取审核状态
 * @param int $status
 * @return string
 */
function getStatus($status){
    switch($status) {
        case '0':
            return '未审核'; 
        case '1':
            return '已审核';  
    }
}

/**
 * 获取出库状态
 * @param int $status
 * @return string
 */
function getPStatus($status){
    switch($status) {
        case '0':
            return '未出库'; 
        case '1':
            return '已出库';  
    }
}
/**
 * 获取出库状态
 * @param int $is_enable
 * @return string
 */
function getIsEnable($is_enable){
    switch($is_enable) {
        case '0':
            return '禁用'; 
        case '1':
            return '启用';  
    }
}

/**
 * 获取打印状态中文说明
 * @param int $status
 * @return string
 */
function getPrintStatus($s){
    switch($s) {
        case '0':
            return '未打印'; 
        case '1':
            return '已打印';  
    }
}

/**
 * 保留2个小数点 四舍五入
 * @param  [string] $totalx [金额]
 * @return [string]         
 */
function keep4($totalx){
    return round($totalx,4);
}

/**
 * 获取批发商商品编号
 * @param  int $join_id 入库明细编号
 * @return int  $wgid        [批发商商品编号]
 */
function getWgid($join_id){ 
    $m=M("join_stock_detail");
    $wgid = $m->field('wgid')->where("join_id =$join_id")->find();
    return $wgid;
}

/**
 * 获取批发商用户公司信息 
 * @return array    
 */
function getInfoId(){
   return session("info_id");
}

function getDefaultCname($cname){
    if(!$cname){
        $cname = "库存更改客户";
    } 
    return $cname;
}

function getDefaultSname($sname){
     if(!$sname){
        $sname = "库存更改供应商";
    } 
    return $sname;
}

function getMachining($machining){
    if($machining==1){
        return "加工";
    }else{
        return "不加工";
    }
}


/**
 * 根据用户编号获取用户名称
 * @return string    
 */
function getUserNameById($wid){
    if(!empty($wid)){
        $result = M("wholesale_user")->where("wid =$wid")->find();
        if(empty($result)){
            return "无名称";
        }else{
            return $result["contacts"];
        }
    }else{
        return "暂无";
    }
   
}

/**
 * 获取系统参数单位转换
 * @param  int  数量
 * @param  string  单位
 * @return string    
 */
function unitConvert($system_param,$num,$uname){ 
    $result ="";
    switch($system_param['param_is_convert_unit']){
        case "0":
            $result = $num.$uname; 
            break;
        case "1":
            if($uname=="公斤"){
                $result = ($num*2)."斤"; 
            }else{
                $result = $num.$uname; 
            } 
            break;
        case "2":
            if($uname=="斤"){
                $result = ($num/2)."公斤"; 
            }else{
                $result = $num.$uname;
            } 
            break;
    }
    return $result;
}

/**
 * 获取系统参数单位转换
 * @param  int  数量
 * @param  string  单位
 * @return string    
 */
function unitConvertNotUnit($system_param,$num,$uname){ 
    $result ="";
    switch($system_param['param_is_convert_unit']){
        case "0":
            $result = $num; 
            break;
        case "1":
            if($uname=="公斤"){
                $result = ($num*2); 
            }else{
                $result = $num; 
            } 
            break;
        case "2":
            if($uname=="斤"){
                $result = ($num/2); 
            }else{
                $result = $num;
            } 
            break;
    }
    return $result;
}


/**
 *+--------------------------------------------------------------------------------
 *    获取系统参数设置数据
 *+--------------------------------------------------------------------------------
 *    @return array
 *+--------------------------------------------------------------------------------
 */
function getSystemParam(){
    // $wid =getWid();
    $sp = C("SYSTEM_PARAM");
    $arr = explode(',',$sp);
    $new_arr =array();
    foreach ($arr as $k => $v) {
        $arr1 = explode('=',$v);
        $new_arr[$arr1[0]]=$arr1[1];
    }
    return $new_arr;
}

/**
 *+--------------------------------------------------------------------------------
 *    自定义小数点方法，读取系统参数设置数据中的(数量，单价，金额，是否四舍五入)进行处理
 *+--------------------------------------------------------------------------------
 *    @param  [string] $system_param [num,price,price]
 *    @param  [int] $num [数值]
 *    @return float
 *+--------------------------------------------------------------------------------
 */
function customDecimal($system_param,$num){
    $arr = session("system_param"); 
    $result = 0;
    switch($system_param){
        case "num":
            $param_num = intval($arr['param_num']);
            $result = calculateDecimal($param_num,$num);
            break;
        case "price":
            $param_price = intval($arr['param_price']);
            $result = calculateDecimal($param_price,$num);
            break;
        case "sum":
            $param_sum = intval($arr['param_sum']);
            $result = calculateDecimal($param_sum,$num);
            break;
    }
    return $result;
} 

/**
 *+--------------------------------------------------------------------------------
 *    计算小数点方法
 *+--------------------------------------------------------------------------------
 *    @param  [int] $param 保留几位小数点
 *    @param  [int] $num [数值]
 *    @return float
 *+--------------------------------------------------------------------------------
 */
function calculateDecimal($param,$num){  
    $arr = session("system_param");
    $param_is_round = $arr["param_is_round"]; 
    if($param_is_round=="1"){
        return round($num,$param);
    }else{  
        $num.="";
        return floatval(substr($num,0,strpos($num,'.')+$param+1)); 
        // $aa = 1;
        // for($i=0;$i<$param;$i++){
        //     $aa = $aa *10;
        // }  
        // return (floor($num*$aa))/$aa;
    }
}

/**
 *+--------------------------------------------------------------------------------
 *    自定义小数点方法，读取系统参数设置数据中的(数量，单价，金额，是否四舍五入)进行处理
 *+--------------------------------------------------------------------------------
 *    @param  [string] $system_param [num,price,price]
 *    @param  [int] $num [数值]
 *    @return float
 *+--------------------------------------------------------------------------------
 */
function customDecimal1($arr,$system_param,$num){ 
    $result = 0;
    switch($system_param){
        case "num":
            $param_num = intval($arr['param_num']);
            $result = calculateDecimal1($arr,$param_num,$num);
            break;
        case "price":
            $param_price = intval($arr['param_price']);
            $result = calculateDecimal1($arr,$param_price,$num);
            break;
        case "sum":
            $param_sum = intval($arr['param_sum']);
            $result = calculateDecimal1($arr,$param_sum,$num);
            break;
    }
    return $result;
} 

/**
 *+--------------------------------------------------------------------------------
 *    计算小数点方法
 *+--------------------------------------------------------------------------------
 *    @param  [int] $param 保留几位小数点
 *    @param  [int] $num [数值]
 *    @return float
 *+--------------------------------------------------------------------------------
 */
function calculateDecimal1($arr,$param,$num){ 
    $param_is_round = $arr["param_is_round"];
    if($param_is_round=="1"){
        return round($num,$param);
    }else{
        $num.="";
        return floatval(substr($num,0,strpos($num,'.')+$param+1));  
        // $aa = 1;
        // for($i=0;$i<$param;$i++){
        //     $aa = $aa *10;
        // } 
        // return (floor($num*$aa))/$aa;
    }
} 

/** 
 * 人民币小写转大写 
 * 
 * @param string $number 数值 
 * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆" 
 * @param bool $is_round 是否对小数进行四舍五入 
 * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30， 
 *             有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的 
 * @return string 
 */ 
function num2rmb($number = 0, $int_unit = '元', $is_round = TRUE, $is_extra_zero = FALSE) 
{ 
    // 将数字切分成两段 
    $parts = explode('.', $number, 2); 
    $int = isset($parts[0]) ? strval($parts[0]) : '0'; 
    $dec = isset($parts[1]) ? strval($parts[1]) : ''; 
 
    // 如果小数点后多于2位，不四舍五入就直接截，否则就处理 
    $dec_len = strlen($dec); 
    if (isset($parts[1]) && $dec_len > 2) 
    { 
        $dec = $is_round 
                ? substr(strrchr(strval(round(floatval("0.".$dec), 2)), '.'), 1) 
                : substr($parts[1], 0, 2); 
    } 
 
    // 当number为0.001时，小数点后的金额为0元 
    if(empty($int) && empty($dec)) 
    { 
        return '零'; 
    } 
 
    // 定义 
    $chs = array('0','壹','贰','叁','肆','伍','陆','柒','捌','玖'); 
    $uni = array('','拾','佰','仟'); 
    $dec_uni = array('角', '分'); 
    $exp = array('', '万'); 
    $res = ''; 
 
    // 整数部分从右向左找 
    for($i = strlen($int) - 1, $k = 0; $i >= 0; $k++) 
    { 
        $str = ''; 
        // 按照中文读写习惯，每4个字为一段进行转化，i一直在减 
        for($j = 0; $j < 4 && $i >= 0; $j++, $i--) 
        { 
            $u = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位 
            $str = $chs[$int{$i}] . $u . $str; 
        } 
        //echo $str."|".($k - 2)."<br>"; 
        $str = rtrim($str, '0');// 去掉末尾的0 
        $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0 
        if(!isset($exp[$k])) 
        { 
            $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位 
        } 
        $u2 = $str != '' ? $exp[$k] : ''; 
        $res = $str . $u2 . $res; 
    } 
 
    // 如果小数部分处理完之后是00，需要处理下 
    $dec = rtrim($dec, '0'); 
 
    // 小数部分从左向右找 
    if(!empty($dec)) 
    { 
        $res .= $int_unit; 
 
        // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求 
        if ($is_extra_zero) 
        { 
            if (substr($int, -1) === '0') 
            { 
                $res.= '零'; 
            } 
        } 
 
        for($i = 0, $cnt = strlen($dec); $i < $cnt; $i++) 
        { 
            $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位 
            $res .= $chs[$dec{$i}] . $u; 
        } 
        $res = rtrim($res, '0');// 去掉末尾的0 
        $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0 
    } 
    else 
    { 
        $res .= $int_unit . '整'; 
    } 
    return $res; 
} 





