<?php

class Codegen extends CI_Controller
{
	private $_tables;
	private $_fields;
	private $_db_fields;
	private $_exclusions;
	private $_db_exclusions;
	private $_cntrlr_name;
	private $_labels;
	private $_table_name;
	private $_model_name;
	private $_cntrlr_function;
	private $_op;
	private $_success;
	private $_error;

	function __construct()
	{
		parent::__construct();
		$this->_exclusions = array('id','created','modified');var_dump(array_diff($this->_exclusions,array('id')));
		$this->_db_fields = $this->db->list_fields('registration');print_r($this->_db_fields);
		$this->_fields = array_diff($this->_db_fields,$this->_exclusions);
		$labels = str_replace('_',' ',$this->_fields);
		//$this->_labels = array_walk($labels,'upper');
		$this->_labels = array_map('ucfirst',$labels);
		ReflectionFunction::export('get_object_vars');
		ReflectionFunction::export('get_class_vars');
		ReflectionFunction::export('get_defined_vars');
		//var_dump(get_object_vars($this));
		//var_dump(get_class_vars('Codegen'));
		var_dump(get_defined_vars());
		var_dump(get_defined_functions());
	}

	function upper(&$item,$key)
	{
		return ucfirst($item);
	}

	function index()
	{print_r($this->_labels);print_r($this->_fields);
		var_dump($this->_labels);
		$this->generate_code();
	}

	function generate_code()
	{
		$this->form_validation->set_rules('table','Table name','callback_validate_name');
		$this->form_validation->set_rules('table','Table name','callback_validate_name');
		
		$this->_tables = $this->db->list_tables();
		array_unshift($this->_tables,'--select--');
		$data['tables'] = array_combine($this->_tables,$this->_tables);
		$func_array = array(
									'index'=>array('row'=>'get','page'=>'get_page','all'=>'get_all'),
									'view'=>array('row'=>'get'),
									'add'=>array('all'=>'get_all','insert'=>'insert'),
									'edit'=>array('row'=>'get','all'=>'get_all','update'=>'update'),
									'delete'=>array('delete'=>'delete')
							);
		$keys = array_keys($func_array);

		array_unshift($keys,'all');
		array_unshift($keys,'--select--');

		$data['functions'] = array_combine($keys,$keys);

		if($this->form_validation->run())
		{var_dump($this->input->post());
			$table = $this->input->post('tables');
			$this->_cntrlr_function = $this->input->post('functions');var_dump(get_defined_vars());
			$this->_cntrlr_model_functions = ($this->_cntrlr_function == 'all') ?														$func_array : $func_array[$this->_cntrlr_function];
			echo 'cnt model : ';var_dump($this->_cntrlr_model_functions);
			echo $this->_table_name = $table;
			echo $this->_model_name = rtrim($table,'s').'_model';
			$data['success'] = 'You have selected table '.$table.' for code generation';
			var_dump(get_class_vars('Codegen'));
			$this->_generate_controller();
		}

		$data['action'] = site_url().'/'.strtolower(__CLASS__).'/'.__FUNCTION__;
		$this->load->view('codegen/collect_data',$data);
	}

	function validate_name($str)
	{
		if($str == '--select--'){
			return false;
		}else{
			return true;
		}
	}

	private function _set_cntrlr_name()
	{
		$this->_cntrlr_name = $this->_table_name;
	}

	private function _set_flash_messages()
	{
		$this->_success = '$this->session->set_flashdata(\'success\',\'Record has been '.$this->_op. ' successfully\');';
		$this->_error = '$this->session->set_flashdata(\'error\',\'Record not '.$this->_op.' in database\');';
	}

	private function _generate_controller()
	{
		$this->_set_cntrlr_name();	

		$cntrlr_dir = dirname(__FILE__);
		$cntrlr_file = $cntrlr_dir.'\\'.$this->_cntrlr_name;
		$fp = fopen("$cntrlr_file.php",'a');
		$cntrlr = '<?php'."\r\n\r\nclass ".ucfirst($this->_cntrlr_name).
					" extends CI_Controller\r\n{\r\n";
		$cntrlr .= "\t".'private $_data;'."\r\n\r\n";
		$cntrlr .= "\t".'function __construct()'."\r\n";
		$cntrlr .= "\t{\r\n\t\t";
		$cntrlr .= 'parent::__construct();';
		$cntrlr .= "\r\n\t}\r\n\r\n";
		echo fwrite($fp,$cntrlr),"\r\n";

		if($this->_cntrlr_function == 'all'){
			$count = count($this->_cntrlr_model_functions);
			$keys = array_keys($this->_cntrlr_model_functions);

			for($i = 0;$i < $count;$i++){
				$this->_generate_cntrlr_function($fp,$keys[$i],$this->_cntrlr_model_functions[$keys[$i]]);
			}

		}else{
			$this->_generate_cntrlr_function($fp,$this->_cntrlr_function,$this->_cntrlr_model_functions);
		}
	}

	private function _generate_cntrlr_function($fp,$cntrlr_func,$model_funcs)
	{
		$function = "\tfunction ".$cntrlr_func."()\r\n\t{\r\n\t\t";
		switch($cntrlr_func)
		{
			case 'index':
				/*$model_func = $model_funcs['page'].'()';*/
				$function .= '$this->_data['."'$this->_table_name'".'] = '.'$this->'.$this->_model_name.'->'.$model_funcs['page'].'(array(),array())'.";\r\n\t\t";
				break;
			case 'view':
				$model_func = $model_funcs['row'].'()';
				break;
			case 'add':
				$this->_op = 'added';
				$this->_set_flash_messages();
				break;
			case 'edit':
				$this->_op = 'updated';
				$this->_set_flash_messages();
				$function .= '$id = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;'."\r\n\t\t";
				$function .= '$this->_data['."'$this->_table_name'".'] = '.                                                       '$this->'.$this->_model_name.'->'.$model_funcs['row'].'(array($id))'.";\r\n\r\n\t\t";
				break;				
			case 'delete':
				$this->_op = 'deleted';
				$this->_set_flash_messages();
				$function .= '$id = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;'."\r\n\r\n\t\t";
				$function .= 'if($this->'.$this->_model_name.'->'.$model_funcs['delete'].'($id))'."{\r\n\t\t\t";
				$function .= $this->_success."\r\n\t\t\t".'redirect('."'$this->_cntrlr_name/index'".');'."\r\n\t\t}else{\r\n\t\t\t$this->_error\r\n\t\t}\r\n\t}\r\n\r\n}";
				/*$model_func = $model_funcs['delete'].'()';*/
				break;
		}

		if(($cntrlr_func == 'add') || ($cntrlr_func == 'edit')){
			$function .= $this->_generate_validation();
		}

		if($cntrlr_func == 'add')
		{
			$function .= 'if($this->form_validation->run())'."\r\n\t\t{\r\n\t\t\t";
			
			
			$function .= $this->_get_form_data().'if($this->'.$this->_model_name.'->'.$model_funcs['insert'].'($form_data)'."{\r\n\t\t\t\t".$this->_success."\r\n\t\t\t\t".'redirect('."'$this->_cntrlr_name/index'".');'."\r\n\t\t\t}else{\r\n\t\t\t\t$this->_error\r\n\t\t\t}\r\n\t\t}\r\n\r\n\t\t";		
		}

		if($cntrlr_func == 'edit')
		{
			$function .= 'if($this->form_validation->run())'."\r\n\t\t{\r\n\t\t\t";
			$function .= $this->_get_form_data().'if($this->'.$this->_model_name.'->'.$model_funcs['update'].'($id,$form_data)'."{\r\n\t\t\t\t".$this->_success."\r\n\t\t\t\t".'redirect('."'$this->_cntrlr_name/index'".');'."\r\n\t\t\t}else{\r\n\t\t\t\t$this->_error\r\n\t\t\t}\r\n\t\t}\r\n\r\n\t\t";		
		}

		if($cntrlr_func != 'delete'){
			$function .= '$this->load->view('."'$this->_cntrlr_name/$cntrlr_func'".',$this->_data);'."\r\n\t}\r\n\r\n";
		}

		echo fwrite($fp,$function);
	}

	private function _generate_validation()
	{		
		//$validation = 'function validate_form()'."\r\n\t{\r\n";
		$validation = '';
		$i = 1;

		foreach($this->_fields as $field)
		{
			$valid_rule = "'$field',"."'".$this->_labels[$i++]."'".",'required'";
			$validation .= '$this->form_validation->set_rules('.$valid_rule.");\r\n\t\t";
		}

		$validation .= "\r\n\t\t";
		//$validation .= "\t\t}\r\n\t\t";
		return $validation;
	}

	private function _get_form_data()
	{		
		$this->_db_exclusions = array_intersect($this->_db_fields,array_diff($this->_exclusions,array('id')));

		$form_data = '$form_data = array('."\r\n\t\t\t\t\t\t\t\t\t";
		$count = count($this->_fields);

		if($count > 0){
			foreach($this->_fields as $field)
			{
				--$count;
				$form_data .= "'$field'".' => $this->input->post('."'$field'".'),'."\r\n\t\t\t\t\t\t\t";
				if($count > 0){
					$form_data .= "\t\t";
				}
			}
		}
		
		$count = count($this->_db_exclusions);
		
		if(!empty($this->_db_exclusions))
		{
			$form_data .= "\t\t";
			foreach($this->_db_exclusions as $dex){
				--$count;
				$form_data .= "'$dex'".' => '."''".",\r\n\t\t\t\t\t\t\t";
				if($count > 0){
					$form_data .= "\t\t";
				}
			}
		}

		return $form_data .= ");\r\n\r\n\t\t\t";
	}

	private function _generate_view()
	{
		$view = 'registration';
		$view_dir = dirname(dirname(__FILE__)).'\\views\\'.$this->_ctrl;
		var_dump(mkdir($view_dir));
		$view_file = $view_dir.'\\'.$view;
		$fp = fopen("$view_file.php",'a');		
		$form = '<?=form_open()?>'."\r\n";
		$form .= "\t<table>\r\n";
		$i = 1;
		foreach($this->_fields as $field):
			$form .= "\t\t<tr><td>".
					'<label for="'.$field.'"'.'>'.$this->_labels[$i++].'</label></td>'."\r\n".
					"\t\t\t".'<td><input type="text"name="'.$field.'"'.'/></td></tr>'."\r\n";			
		endforeach;

		$form .= "\t\t".'<tr><td><input type="submit"name="submit"value="submit"/></td></tr>';
		$form .= "\r\n\t</table>";
		echo fwrite($fp,$form);
	}

}