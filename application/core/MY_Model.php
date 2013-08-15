<?php

class MY_Model extends CI_Model {

	var $table_name = '';
	var $id_field = '';
	var $entry_field = '';
	var $stamp_field = '';
	var $status_field = '';
	var $deletion_field = '';
	var $order_by = '';
	var $order_dir = '';

    function __construct() {
        parent::__construct();
    }

	/**
	 * データを取得する
	 * @param array $fields
	 * @param array $where
	 * @return array
	 */
	function get($fields, $where=null, $join=null) {
		$row = $this->get_list($fields, $where, $join, 0, 1);
		if (empty($row)) {
			return array();
		} else {
			return $row[0];
		}
	}

	/**
	 * データ一覧を取得する
	 * @param array $fields
	 * @param array $where
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	function get_list($fields, $where=null, $join=null, $offset=null, $limit=20, $order=null) {
		$this->db->select($fields);
		$this->_set_where($where);
		$this->_set_order($order);
		$this->_set_join($join);

		if (isset($offset)) {
			$this->db->limit($limit, $offset);
		}
		$query = $this->db->get($this->table_name);

		$result = array();
		foreach ($query->result_array() as $row) {
			$result[] = $row;
		}
		return $result;
	}

	/**
	 * IDでデータを取得する
	 * @param int $id ID
	 * @param string or array $fields
	 * @return array
	 */
	function get_by_id($id, $fields='*', $join=array() ) {
		if (empty($id)) {
		  return array();
		}

		$where = array( $this->id_field => $id);
		$this->_set_join($join);
		return $this->get($fields, $where);
	}


	/**
	 * JOIN
	 * @param array $join
	 */
	function _set_join($join=null) {
		if (empty($join)) {
			return;
		}
		foreach ($join as $k => $tmp) {
			$this->db->join(isset($tmp[0])?$tmp[0]:'', isset($tmp[1])?$tmp[1]:'', isset($tmp[2])?$tmp[2]:'');
		}
	}


	/**
	 * where文設定
	 * @param array $where
	 */
	function _set_where($where = null) {
		if (empty($where)) {
			return;
		}

		foreach ($where as $field => $value) {
			if (empty($value)) {
				continue;
			}

			$field = explode(' ', $field);
			if (empty($field[1])) {
				$this->db->where($field[0], $value);
				continue;
			}

			switch ($field[1]) {
				case 'or_where':
				case 'where_in':
				case 'or_where_in':
				case 'where_not_in':
				case 'or_where_not_in':
				case 'like':
				case 'or_like':
				case 'not_like':
				case 'or_not_like':
					$this->db->$field[1]($field[0], $value);
					break;
				default:
					$this->db->where("{$field[0]} {$field[1]}", $value);
			}
		}
	}

	/**
	 * order文設定
	 * @param array $order
	 */
	function _set_order($order = null) {
		if (empty($order)) {
			return;
		}

		foreach ($order as $field => $value) {
			if (empty($value)) {
				$value = 'asc';
			}
			$this->db->order_by($field, $value);
		}
	}


}

?>
