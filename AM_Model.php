<<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
Class AM_Model extends CI_Model
{
	private $_table_name;

	public function __Construct()
	{
		parent::__construct();		
	}

	function get($conditions)
	{
		$query = $this->db->get_where($this->_table_name,$conditions);

		if($query->num_rows == 1){
			return $query->row_array();
		}else{
			return false;
		}
	}

	function get_all($conditions,$sorts = array(),$limit = 50,$offset = 0)
	{
		$this->db->select('*');
		$this->db->from($this->table_name)->where($conditions);
		$str = '';

		if(!empty($sorts)){
			foreach($sorts as $k=>$v){
				$str .= $k.' '.$v.',';
			}
			$sorts = rtrim($str,',');
			$this->db->order_by($sorts);
		}

		$query = $this->db->limit($limit,$offset)->get();
		return $query->result_array();
	}

	function get_page($conditions,$sorts = array(),$limit = 50,$offset = 0)
	{
		$this->db->select($conditions['select']);
		$this->db->from($this->_table_name);

		foreach($conditions['joins'] as $k=>$v)
		{
			$this->db->join($k,$v);	
		}
		
		$this->db->where($conditions['where']);

		if(!empty($sorts)){
			$str = '';

			foreach($sorts as $k=>$v)
			{
				$str .= $k.' '.$v.',';
			}

			$sorts = rtrim($str,',');
			$this->db->order_by($sorts);
		}

		$this->db->limit($limit,$offset);
		$page['data'] = $this->db->get()->result_array();
		return $page;
	}

	function insert($data)
	{
		if($this->db->insert($this->table_name,$data))
			return true;
		else 
			return $this->db->affected_rows();
	}

	function update($id,$data)
	{
		$this->db->where('id',$id);
		if($this->db->update($this->_table_name,$data)){
			return true;
		}else{
			return false;
		}
	}

	function get_fields()
	{
		return $this->db->list_fields($this->table_name);
	}

}