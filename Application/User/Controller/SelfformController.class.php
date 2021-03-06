<?php
namespace User\Controller;
use Spark\Util\Page;
class SelfformController extends UserController{
	public $token;
	public $selfform_model;
	public $selfform_input_model;
	public $selfform_value_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->selfform_model = M('Selfform');
		$this->selfform_input_model = M('Selfform_input');
		$this->selfform_value_model = M('Selfform_value');
		$this->token = session('token');
		$this->assign('token',$this->token);
		$this->assign('module','Selfform');
	}
	
	public function index(){
		if (IS_POST){
			if ($_POST['token']!=$this->token){
				exit();
			}
			for ($i=0;$i<40;$i++){
				if (isset($_POST['id_'.$i])){
					
				}
			}
			$this->success('操作成功',U('Selfform/index',array('token'=>$this->token)));
		}
		else{
			$where=array('token'=>$this->token);
			if(IS_POST){
				$key = I('post.searchkey');
				if(empty($key)){
					$this->error("关键词不能为空");
				}

				$where['name|intro|keyword|content'] = array('like',"%$key%");
				$list = $this->selfform_model->where($where)->select();
				$count      = $this->selfform_model->where($where)->count();
				$Page       = new Page($count,20);
				$show       = $Page->show();
			}
			else {
				$count      = $this->selfform_model->where($where)->count();
				$Page       = new Page($count,20);
				$show       = $Page->show();
				$list=$this->selfform_model->where($where)->order('time DESC')->select();
			}
			$this->assign('list',$list);
			$this->assign('page',$show);
			$this->display();
		}
	}
	
	public function add(){ 
		if(IS_POST){
			$this->all_insert('Selfform','index?token='.$this->token);
		}else{
			$set=array();
			$set['endtime']=time()+10*24*3600;
			$this->assign('set',$set);
			$this->display('set');
		}
	}
	
	public function set(){
        $id = I('get.id'); 
		$checkdata = $this->selfform_model->where(array('id'=>$id))->find();
		if(empty($checkdata)||$checkdata['token']!=$this->token){
            $this->error("没有相应记录.您现在可以添加.",U('Selfform/add'));
        }
		if(IS_POST){
            $where=array('id'=>I('post.id'),'token'=>$this->token);
			$check=$this->selfform_model->where($where)->find();
			if($check==false){
				$this->error('非法操作');
			}
			if($this->selfform_model->create()){
				if($this->selfform_model->where($where)->save($_POST)){
					$this->success('修改成功',U('Selfform/index',array('token'=>$this->token)));
					$keyword_model=M('Keyword');
					$keyword_model->where(array('token'=>$this->token,'pid'=>$id,'module'=>'Selfform'))->save(array('keyword'=>$_POST['keyword']));
				}
				else{
					$this->error('操作失败');
				}
			}
			else{
				$this->error($this->selfform_model->getError());
			}
		}
		else{
			$this->assign('isUpdate',1);
			$this->assign('set',$checkdata);
			$this->display();
		}
	}
	
	public function del(){
        $id = I('get.id',0,'intval');
        if(IS_GET){                              
            $where=array('id'=>$id,'token'=>$this->token);
            $check=$this->selfform_model->where($where)->find();
            if($check==false){
				$this->error('非法操作');
			}

            $back=$this->selfform_model->where($wehre)->delete();
            if($back==true){
            	$keyword_model=M('Keyword');
            	$keyword_model->where(array('token'=>$this->token,'pid'=>$id,'module'=>'Selfform'))->delete();
                $this->success('操作成功',U('Selfform/index',array('token'=>$this->token)));
            }
			else{
                 $this->error('服务器繁忙,请稍后再试',U('Selfform/index',array('token'=>$this->token)));
            }
        }        
	}
	
	public function inputs(){
		$formid = I('get.id');
		$thisForm=$this->selfform_model->where(array('id'=>$formid))->find();
		
		$this->assign('thisForm',$thisForm);
		$where=array('formid'=>$formid);
		$list = $this->selfform_input_model->where($where)->order('taxis ASC')->select();
		$this->assign('list',$list);
		$this->display();
	}
	
	public function inputAdd(){ 
		if(IS_POST){
			$this->insert('Selfform_input','inputs?id='.I('post.formid'));
		}
		else{
			$formid=intval($_GET['formid']);
			$thisForm=$this->selfform_model->where(array('id'=>$formid))->find();
			$this->assign('thisForm',$thisForm);
			$this->inputTypeOptions();
			$this->requireOptions(0);
			$this->regexOptions();
			$this->display('inputset');
		}
	}
	
	public function inputTypeOptions($selected=''){
		$options=array(
			array('value'=>'text','text'=>'文本输入框'),
			array('value'=>'textarea','text'=>'多行文本输入框'),
			array('value'=>'select','text'=>'下拉列表'),
			array('value'=>'date','text'=>'日期输入'),
			array('value'=>'time','text'=>'时间输入'),
			array('value'=>'datetime','text'=>'日期时间输入')
		);
		$str='';
		foreach ($options as $o){
			$selectedStr='';
			if ($selected==$o['value']){
				$selectedStr=' selected';
			}
			$str.='<option value="'.$o['value'].'"'.$selectedStr.'>'.$o['text'].'</option>';
		}
	
		$this->assign('inputTypeOptions',$str);
	}
	
	public function requireOptions($selected=0){
		$options=array(
			array('value'=>'0','text'=>'可选'),
			array('value'=>'1','text'=>'必填')
		);
		$str='';
		foreach ($options as $o){
			$selectedStr='';
			if ($selected==$o['value']){
				$selectedStr=' selected';
			}
			$str.='<option value="'.$o['value'].'"'.$selectedStr.'>'.$o['text'].'</option>';
		}
		$this->assign('requireOptions',$str);
	}
	
	public function regexOptions(){
		$options=array(
			array('value'=>'','text'=>'无限制'),
			array('value'=>'/^[A-Za-z]+$/','text'=>'英文大小写字符'),
			array('value'=>'/^[\u4E00-\u9FA5]+$/','text'=>'中文字符'),
			array('value'=>'/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/','text'=>'邮箱'),
			array('value'=>'/^[1-9]\d*|0$/','text'=>'0或正整数'),
			array('value'=>'/^[0-9]{1,30}$/','text'=>'正整数'),
			array('value'=>'/^[-\+]?\d+(\.\d+)?$/','text'=>'小数'),
			array('value'=>'/^13[0-9]{9}$|^15[0-9]{9}$|^18[0-9]{9}$/','text'=>'手机号码'),
			array('value'=>'0','text'=>'自定义规则'),
		);
		$str='';
		foreach ($options as $o){
			$str.='<option value="'.$o['value'].'">'.$o['text'].'</option>';
		}
		$this->assign('regexOptions',$str);
	}
	
	public function inputset(){
        $id = intval(I('get.id')); 
		$checkdata = $this->selfform_input_model->where(array('id'=>$id))->find();
		if(empty($checkdata)){
            $this->error("没有相应记录.您现在可以添加.",U('Selfform/inputAdd'));
        }
		if(IS_POST){
            $where=array('id'=>I('post.id'));
            $thisInput=$this->selfform_input_model->where($where)->find();
            $thisForm=$this->selfform_model->where(array('id'=>$thisInput['formid']))->find();
            if ($thisForm['token']!=$this->token){
            	exit();
            }
			if($this->selfform_input_model->create()){
				$rt=$this->selfform_input_model->where($where)->save($_POST);
				if($rt){
					$this->success('修改成功',U('Selfform/inputs',array('token'=>$this->token,'id'=>$thisForm['id'])));
				}
				else{
					$this->error('操作失败');
				}
			}
			else{
				$this->error($this->selfform_input_model->getError());
			}
		}
		else{
			$this->assign('isUpdate',1);
			$this->assign('set',$checkdata);
			$formid=intval($checkdata['formid']);
			$thisForm=$this->selfform_model->where(array('id'=>$formid))->find();
			$this->assign('thisForm',$thisForm);

			$this->inputTypeOptions($checkdata['fieldname']);
			$this->requireOptions($checkdata['require']);
			$this->regexOptions();
			$this->display();
		}
	}
	
	public function inputDelete(){
		$id = intval(I('get.id'));
		$where=array('id'=>$id);
		$thisInput=$this->selfform_input_model->where($where)->find();
		$thisForm=$this->selfform_model->where(array('id'=>$thisInput['formid']))->find();
		if ($thisForm['token']!=$this->token){
			exit();
		}
		$back=$this->selfform_input_model->where($wehre)->delete();
		if($back==true){
			$this->success('操作成功',U('Selfform/inputs',array('token'=>$this->token,'id'=>$thisForm['id'])));
		}else{
			$this->error('服务器繁忙,请稍后再试',U('Selfform/inputs',array('token'=>$this->token,'id'=>$thisForm['id'])));
		}
	}
	
	//查看用户订单
	public function infos(){
		$id = I('get.id',0,'intval');
		$where=array('id'=>$id,'token'=>session('token'));
		$thisForm=$this->selfform_model->where($where)->find();
		if($thisForm == null){
			$this->error('表单不存在！');
			exit;
		}
		$this->assign('thisForm',$thisForm);
		
		//表头信息
		$fieldWhere=array('formid'=>$thisForm['id']);
		$fields = $this->selfform_input_model->where($fieldWhere)->order('taxis ASC')->select();
		$fieldsByKey=array();
		if ($fields){
			foreach ($fields as $l){
				$fieldsByKey[$l['fieldname']]=$l;
			}
		}
		$this->assign('fields',$fields);
		//提交的信息
		$infoWhere=array('formid'=>$thisForm['id']);
		(I('start_date')&&($infoWhere['time'][] = ['egt',strtotime(I('start_date'))]));
		(I('end_date')&&($infoWhere['time'][] = ['lt',strtotime(I('end_date'))+24*3600]));
		
		$count      = $this->selfform_value_model->where($infoWhere)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$list=$this->selfform_value_model->where($infoWhere)->order('time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		if ($list){
			$i=0;
			foreach ($list as $l){
				$values=unserialize($l['values']);
                $list[$i]['status']=$l['status'];
				$list[$i]['order_id']=$l['order_id'];
				$list[$i]['time']=date('Y-m-d H:i',$l['time']);
				if ($fields){
					foreach ($fields as $f){
						$list[$i]['input'][$f['fieldname']]=$values[$f['fieldname']];
					}
				}
				unset($list[$i]['formid']);
				unset($list[$i]['values']);
				unset($list[$i]['wechat_id']);
				$i++;
			}
		}
		$this->assign('count',$count);
		$this->assign('page',$show);
		$this->assign('list',$list);
		//
		$this->display();
	}
	
	/**
	 *@method export 预约订单导出到excel
	 */
	public function export(){
		Vendor('PHPExcel.PHPExcel');
		$data = array();
		$id = I('get.id',0,'intval');
		$where=array('id'=>$id);
		$thisForm=$this->selfform_model->where($where)->find();
		
		//表头信息
		$fieldWhere=array('formid'=>$thisForm['id']);
		$fields = $this->selfform_input_model->where($fieldWhere)->order('taxis ASC')->select();
		$title=['订单号','订单时间'];
		if ($fields){
			foreach ($fields as $l){
				$title[] = $l['displayname'];
			}
		}
		$data[] = $title;
		//提交的信息
		$infoWhere=array('formid'=>$thisForm['id']);
		(I('start_date')&&($infoWhere['time'][] = ['egt',strtotime(I('start_date'))]));
		(I('end_date')&&($infoWhere['time'][] = ['lt',strtotime(I('end_date'))+24*3600]));
		
		$list=$this->selfform_value_model->where($infoWhere)->order('time DESC')->select();
		if ($list){
			foreach ($list as $l){
				$row = array();
				$values = unserialize($l['values']);
				$row[] = $l['order_id']. ' ';
				$row[] = date('Y-m-d H:i',$l['time']);
				if ($fields){
					foreach ($fields as $f){
						$row[]  = $values[$f['fieldname']];
					}
				}
                $row[]=$l['status']=='0'?"未处理":"已处理";
				$data[] = $row;
			}
		}
		
		createExcel('',$data);
	}
	
	/**
	 * @method infoDelete
	 * @desc 删除用户提交信息
	 */
	public function infoDelete(){
		$thisInfo=$this->selfform_value_model->where(array('id'=>intval($_GET['id'])))->find();
		$thisFrom=$this->selfform_model->where(array('id'=>$thisInfo['formid']))->find();
		if ($thisFrom['token']!=$this->token){
			exit();
		}
		$back=$this->selfform_value_model->where(array('id'=>intval($_GET['id'])))->delete();
		if($back==true){
			$this->success('操作成功',U('Selfform/infos',array('token'=>$this->token,'id'=>$thisFrom['id'])));
		}
		else{
			$this->error('服务器繁忙,请稍后再试',U('Selfform/infos',array('token'=>$this->token,'id'=>$thisFrom['id'])));
		}
	}

    public function infoDealwith(){
        $thisInfo=$this->selfform_value_model->where(array('id'=>intval($_GET['id'])))->find();
        $thisFrom=$this->selfform_model->where(array('id'=>$thisInfo['formid']))->find();
        $status=$thisInfo['status'];
        if($status=='0'){
            $status=1;
        }else{
            $status=0;
        }
        $this->selfform_value_model->where(array('id'=>intval($_GET['id'])))->setField('status', $status);
        $this->success('操作成功',U('Selfform/infos',array('token'=>$this->token,'id'=>$thisFrom['id'])));
    }
	
	/**
	 * @method product
	 * @desc 显示表单产品
	 */
	public function product(){
		$id = intval(I('get.id'));
		$where=array('id'=>$id);
		$thisForm=$this->selfform_model->where($where)->find();
		
		$this->assign('thisForm',$thisForm);
		
		$products=M('Selfform_product')->where(['form_id'=>$id])->order('sortid')->select();
		$this->assign('products',$products);
		
		$this->display();
	}
	
	/**
	 * @method addproduct
	 * @desc 添加或编辑表单产品
	 */
	public function productedit(){
		if(IS_POST){
			$pattern = "/<(\/?)(script|i?frame|style|html|body|title|font|strong|span|div|marquee|link|meta|\?|\%)([^>]*?)>/isU";
			$_POST['detail'] = preg_replace($pattern,"",$_POST['detail']);
			
			$db   = D('Selfform_product');
			if ($db->create() === false) {
				$this->error($db->getError());
			} 
			if(empty($_POST['id'])){
				if($db->add()){
					$this->success('添加成功！');
				}
				else{
					$this->error('添加失败！');
				}
			}
			else{
				if($db->save()){
					$this->success('保存成功！');
				}
				else{
					$this->error('保存失败！');
				}
			}
		}
		else{
			$id = I('get.id',0,'intval');
			if($id){
				$product = M('Selfform_product')->find($id);
				$this->assign('product',$product);
				$title = '编辑预约产品';
			}
			else{
				$title = '添加预约产品';
			}
			$this->assign('title',$title);
			$this->display();
		}
	}
	
	/**
	 * @method delproduct
	 * @desc 删除表单产品
	 */
	public function delproduct($id){
		$ret = M('selfform_product')->where(['token'=>session('token'),'id'=>$id])->delete();
		if($ret) $this->success('删除成功！');
		else $this->error('删除失败！');
	}
}


?>