<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MB_Base extends CI_Controller{
	private $_data;
	public $user;
	public function __construct()
	{
		parent::__construct();	
		$this->_data = array();
		$this->user = null;	
		echo __FILE__.'<br/>';
	}
	
}

class MY_Controller extends MB_Base {
	public function __construct()
	{
		parent::__construct();	
		$this->_data = array();
		$this->user = null;	
		//$this->_set_defaults();
	}
	
	function _check_login(){
		if (empty($this->user) or $this->user == null  or $this->user['role_id'] != 1){
			redirect('guest/login');			
		}else{
			return true;
		}
	}	
		
	/*function _set_defaults(){	
			
		if ($this->session->user_data('logged_in_user_id') && $this->session->user_data('is_logged_in') == 1 ){
			$this->load->model('User_model');
			$this->user = $this->User_model->get_user((int)$this->session->user_data('logged_in_user_id'));
			$this->_data['user'] = $this->user;
		}
		$this->_data['page_title'] = 'AdServer';;	
		//$this->_data['left_view'] = 'left_default';		
	}*/
}

//for admin
class AdminController extends MB_Base {

	//public $menu = 'dashboard';
	//public $submenu = 'dashboard';

	public function __construct()
	{
		parent::__construct();	
		$this->_data = array();
		$this->user = null;	
		$this->_set_defaults();
	}
	
	function _check_login(){
		if ((empty($this->user) or $this->user == null) or $this->user['role_id'] != 2){
			redirect('guest/login');
		}else{			
			return true;
		}
	}	

	public function getpaging($segment){
		$paging =  array();
		if($this->uri->segment($segment) !=  false){
			$paging['offset'] =   (int)$this->uri->segment($segment);;
			$paging['uri_segment'] =  $segment;

		}else{
			$paging['offset'] =  0;
			$paging['uri_segment'] = $segment;

		}
	
		return $paging;
	}		
		
	function _set_defaults(){	
			
		if ($this->session->user_data('logged_in_user_id') && $this->session->user_data('is_logged_in') == 1 ){
			$this->load->model('User_model');
			$this->user = $this->User_model->get_user((int)$this->session->user_data('logged_in_user_id'));
			$this->_data['user'] = $this->user;
		}
		$this->_data['page_title'] = 'AdServer';;			
	}

	public function slugify($text)
    { 
      //replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
      //trim
        $text = trim($text, '-');
      //transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
      //lowercase
        $text = strtolower($text);
      //remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        if (empty($text))
           return 'n-a'; 
		else
           return $text;
    }

	protected function _get_labels()
	{
		foreach($this->_data['fields'] as $field)
		{
			$labels[] = ucfirst(str_replace('_',' ',$this->_data['fields']));
		}
		$this->_data['labels'] = $labels;
		return $this->_data['labels'];
	}

	protected function _get_exclusions()
	{
		$this->_data['exclusions'] = array('created','modified','id','token','tokens','reset_password_hash');
		return $this->_data['exclusions'];
	}

	protected function _get_table__data($table_name)
	{
		$this->_data['fields'] = $this->db->list_fields($table_name);
		$this->_data['fields'] = array_diff($this->_data['fields'],$this->_data['exclusions']);
		return $this->_data['fields'];
	}

	protected function _set_table($table)
	{
		$this->_data['table'] = $table;
	}


	protected function _validate_form($type)
	{
		$i = 0;
		foreach($this->_data['fields'] as $field)
		{
			if(($field == 'email') || ($field == 'email_id') || ($field == 'username') || ($field == 'user_name') && ($type == 'insert')){
				$val_type = 'required|valid_email|'.$this->_data['table'].'.unique';
			}else{
				if(($field == 'email') || ($field == 'email_id') || ($field == 'username') || ($field == 'user_name') && ($type == 'edit')){
				$val_type = 'required|valid_email';
			}else{
				if(($field == 'password') || ($field == 'passwd') || ($field == 'pwd')){
				$val_type = 'required|matches[Confirmpassword]';
				$this->form_validation->set_rules('Confirmpassword','Confirm password','required');
			}else{
				$val_type = 'required|matches[Confirmpassword]';
			}
			}
			}

			$this->form_validation->set_rules($field,$this->_data['labels'][$i++],$val_type);
		}
	}

	//protected 

}