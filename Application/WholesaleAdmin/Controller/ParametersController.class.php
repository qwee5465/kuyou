<?php
// +----------------------------------------------------------------------
// | 系统设置----参数设置
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace WholesaleAdmin\Controller;

use Think\Controller;

class ParametersController extends BaseController
{
    public function parametersList()
    {     
        // dump(customDecimal("num",2.34567));
        // dump(customDecimal("price",2.34567));
        // dump(customDecimal("sum",2.34567));

        $wid =getWid(); 
    	$overdue_day =C("OVERDUE_DAY"); 
    	$result = M("overdue_set")->where("wid = $wid")->find();
    	if($result){
    		$overdue_day =$result['overdue_day'];
    	}
        /*----------------获取系统参数-----------------*/
        $splist = M("system_param")->where("wid=$wid")->find();
        if(empty($splist)){
           $splist=getSystemParam(); 
        }  
        /*-------------将数据渲染页面上----------------*/
    	$this->assign("overdue_day",$overdue_day);
        $this->assign("splist",$splist);
        $this->display();
    }

    //设置系统参数——数量
    public function setParamNum(){
        if(IS_POST){
            $wid = getWid();
            //查询是否有设置过系统参数
            $m = M("system_param");
            $param_num = I("param_num");
            $slist = $m->where("wid=$wid")->find();
            if($slist){ //已存在修改
                $data = array(
                    'param_num' => $param_num,
                );
                $result = $m->where("wid=$wid")->save($data);
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }else{
                $arr = getSystemParam();
                $data = array(
                    'param_num' => $param_num,
                    'param_price' => $arr['param_price'],
                    'param_sum' => $arr['param_sum'],
                    'param_is_round' => $arr['param_is_round'],
                    'param_is_convert_unit' => $arr['param_is_convert_unit'],
                    'wid' => $wid,
                );
                $result = $m->where("wid=$wid")->add($data);
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }
            
        }
    }

    //设置系统参数——单价
    public function setParamPrice(){
        if(IS_POST){
            $wid = getWid();
            //查询是否有设置过系统参数
            $m = M("system_param");
            $param_price = I("param_price");
            $slist = $m->where("wid=$wid")->find();
            if($slist){ //已存在修改
                $data = array(
                    'param_price' => $param_price,
                );
                $result = $m->where("wid=$wid")->save($data);
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }else{ 
                $arr = getSystemParam();
                $data = array(
                    'param_num' => $arr['param_num'],
                    'param_price' => $param_price,
                    'param_sum' => $arr['param_sum'],
                    'param_is_round' => $arr['param_is_round'],
                    'param_is_convert_unit' => $arr['param_is_convert_unit'],
                    'wid' => $wid,
                );
                $result = $m->where("wid=$wid")->add($data);
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }
            
        }
    }

    //设置系统参数——金额
    public function setParamSum(){
        if(IS_POST){
            $wid = getWid();
            //查询是否有设置过系统参数
            $m = M("system_param");
            $param_sum = I("param_sum");
            $slist = $m->where("wid=$wid")->find();
            if($slist){ //已存在修改
                $data = array(
                    'param_sum' => $param_sum,
                );
                $result = $m->where("wid=$wid")->save($data);
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }else{
                $arr = getSystemParam(); 
                $data = array(
                    'param_num' => $arr['param_num'],
                    'param_price' => $arr['param_price'],
                    'param_sum' => $param_sum,
                    'param_is_round' => $arr['param_is_round'],
                    'param_is_convert_unit' => $arr['param_is_convert_unit'],
                    'wid' => $wid,
                );
                $result = $m->where("wid=$wid")->add($data);
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }
            
        }
    }
    //设置系统参数——是否四舍五入
    public function setParamIsRound(){
        if(IS_POST){
            $wid = getWid();
            //查询是否有设置过系统参数
            $m = M("system_param");
            $param_is_round = I("param_is_round");
            $slist = $m->where("wid=$wid")->find();
            if($slist){ //已存在修改
                $data = array(
                    'param_is_round' => $param_is_round,
                );
                $result = $m->where("wid=$wid")->save($data);
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }else{
                $arr = getSystemParam();
                $data = array(
                    'param_num' => $arr['param_num'],
                    'param_price' => $arr['param_price'],
                    'param_sum' => $arr['param_sum'],
                    'param_is_round' => $param_is_round, 
                    'param_is_convert_unit' => $arr['param_is_convert_unit'],
                    'wid' => $wid,
                );
                $result = $m->where("wid=$wid")->add($data);
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }
            
        }
    }

    //设置系统参数——是否转换单位
    public function setParamIsConvertUnit(){
        if(IS_POST){
            $wid = getWid();
            //查询是否有设置过系统参数
            $m = M("system_param");
            $param_is_convert_unit = I("param_is_convert_unit");
            $slist = $m->where("wid=$wid")->find();
            if($slist){ //已存在修改
                $data = array(
                    'param_is_convert_unit' => $param_is_convert_unit,
                );
                $result = $m->where("wid=$wid")->save($data); 
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }else{
                $arr = getSystemParam();
                $data = array(
                    'param_num' => $arr['param_num'],
                    'param_price' => $arr['param_price'],
                    'param_sum' => $arr['param_sum'],
                    'param_is_round' => $arr['param_is_round'],
                    'param_is_convert_unit' => $param_is_convert_unit,
                    'wid' => $wid,
                );
                $result = $m->where("wid=$wid")->add($data);
                if($result){
                    $this->ajaxReturn(1);//成功
                }else{
                    $this->ajaxReturn(0);//失败
                }
            }
            
        }
    }

    //提交保质天数
    public function submitOverdueDay(){
    	if(IS_POST){
    		$overdue_day = I("overdue_day");
    		$wid = getWid();
    		$data = array(
    			"wid"=>$wid,
    			"overdue_day" => $overdue_day
    		);
    		$m = M("overdue_set");
    		$result = $m->where("wid")->find();
    		if($result){
    			$result = $m->where("id =".$result['id'])->save($data);
    			if($result){
    				$this->ajaxReturn(0); //修改成功
    			}else{
    				$this->ajaxReturn(1); //修改失败
    			} 
    		}else{
    			$result = $m->add($data);
    			if($result){
    				$this->ajaxReturn(0); //新增成功
    			}else{
    				$this->ajaxReturn(1); //新增失败
    			}
    		}
    	}
    }
}