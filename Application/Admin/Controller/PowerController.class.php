<?php
namespace Admin\Controller;
use Think\Controller;
class PowerController extends BaseController {
    public function index(){
        $this->display();
    }

    //权限列表
    public function powerlist(){   
    	$power = M('Power');
    	if(IS_POST){ //表单提交 
			$info = uploadimg('Power');  
			$pic ="";
			if($info){
				$pic ="/Upload/Images/".$info["pic"]["savepath"].$info["pic"]["savename"]; 
			}  
			$p_code= $power->order('pid desc')->find();
			if(!$p_code){
				$p_code = "P0";
			} else{
				$p_code ="P".((int)$p_code['pid']+1);
			}
			$darr=array( 
				'pic'=>$pic,
				'ptid'=>I('ptid'),
				'name'=>I('name'),
				'url'=>I('url'),
				'p_code'=>$p_code,
				'fid'=>I('pid')
			);
			$data = $power ->add($darr);
			if($data){
                addActionLog("新增了一个权限,权限名称为【".I('name')."】");
				$this -> success('添加成功');
			}else{
				$this -> error('添加失败');
			}
    	}else{
    		$power_list = M('PowerType');
    		$plist = $power->where('fid=0 and ptid = 1')->select(); 
    		$ptlist =  $power_list->select(); 
			$p=getpage($power,'');
            // $sql ="select a.*,b.type_name from db_power a left join db_power_type b on a.ptid=b.ptid order by a.pid asc";
            // $data=$power->query($sql);
			$data=$power->field('db_power.*,db_power_type.type_name')
                        ->join('left join db_power_type on db_power.ptid=db_power_type.ptid') 
                        ->select(); 
			$this->assign('data',$data);// 赋值数据集 
			$this->assign('page', $p->show());// 赋值分页输出 
    		$this->assign('plist', $plist);
    		$this->assign('ptlist', $ptlist);  
			$this->display(); // 输出模板 
    	} 
    }


    //编辑权限
    public function powerEdit(){  
        $power =M('Power'); 
        if(IS_POST){
            $info = uploadimg('Power');
            $pic ="";
            if($info){
                $pic ="/Upload/Images/".$info["pic"]["savepath"].$info["pic"]["savename"]; 
            }  
            $power->pid=I('pid');
            $power->name=I('name');
            $power->url=I('url');
            if($info)
                $power->pic=$pic;
            $data = $power->save();
            if($data){
                $this->show("<script>parent.history.go(0);</script>",'utf-8');
            }
            else{ 
                $this->show("<script>parent.layer.closeAll();</script>",'utf-8');
            }
        }else{
            $data = $power->find(I('pid')); 
            $this->assign('data',$data);
            $this->display();
        }
    }

    //删除权限
    public function powerDel(){
    	if(IS_POST){
    		$pid =I('pid'); 
    		$power = M('Power');
    		$data = $power->delete($pid); 
			if($data){ 
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
    	}else{
    		$this->ajaxReturn(ReturnJSON(7));
    	} 
    }

    public function changeptid(){
        if(IS_POST){
            $ptid =I('ptid'); 
            $power = M('Power');
            $data = $power-> where('fid=0 and ptid ='.$ptid)  ->select(); 
            if($data){
                $this->ajaxReturn($data);
            }else{
                $this->ajaxReturn(1);
            }
        }
    }

}