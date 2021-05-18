<?php

class Expense extends CI_Model {
    /*
      Determines if a given person_id is a customer
     */

    function exists($expense) {
        $this->db->from('expenses');
        $this->db->where('expenses.id', $expense);
        $query = $this->db->get();

        return ($query->num_rows() == 1);
    }

    /*
      Returns all the Expenses
     */

    function get_all($deleted=0,$limit = 10000, $offset = 0, $col = 'id', $order = 'desc',$location_id_override = NULL) {
			
			if (!$deleted)
			{
				$deleted = 0;
			}
			
		$location_id = $location_id_override ? $location_id_override : $this->Employee->get_logged_in_employee_current_location_id();
		$this->db->select('expenses.*, CONCAT(recv.last_name, ", ", recv.first_name) as employee_recv, CONCAT(appr.last_name, ", ", appr.first_name) as employee_appr, categories.id as category_id,categories.name as category', false);
		$this->db->from('expenses');
		$this->db->join('people as recv', 'recv.person_id = expenses.employee_id','left');
		$this->db->join('people as appr', 'appr.person_id = expenses.approved_employee_id','left');
		$this->db->join('categories', 'categories.id = expenses.category_id','left');
		$this->db->where('expenses.deleted', $deleted);
		$this->db->where('location_id', $location_id);
		$this->db->order_by($col, $order);
		$this->db->limit($limit);
		$this->db->offset($offset);
      return $this->db->get();
    }

    function count_all($deleted = 0,$location_id_override = NULL) {
			if (!$deleted)
			{
				$deleted = 0;
			}
		 
			$location_id = $location_id_override ? $location_id_override : $this->Employee->get_logged_in_employee_current_location_id();
        $this->db->from('expenses');
        $this->db->where('location_id', $location_id);
        $this->db->where('deleted', $deleted);
        return $this->db->count_all_results();
    }

    /*
      Gets information about a particular expense
     */

    function get_info($expense_id) {
        $this->db->from('expenses');
        $this->db->where('expenses.id', $expense_id);
        $query = $this->db->get();

        if ($query->num_rows() == 1) {
            return $query->row();
        } else {
            //Get empty base parent object, as $supplier_id is NOT an supplier
            $fields = $this->db->list_fields('expenses');
            $expense_obj = new stdClass;
            //Get all the fields from Expenses table
            $fields = $this->db->list_fields('expenses');
            //append those fields to base parent object, we we have a complete empty object
            foreach ($fields as $field) {
                $expense_obj->$field = '';
            }
            return $expense_obj;
        }
    }

    function search_count_all($search, $deleted=0,$limit = 10000,$location_id_override = NULL) {
			if (!$deleted)
			{
				$deleted = 0;
			}
			$location_id = $location_id_override ? $location_id_override : $this->Employee->get_logged_in_employee_current_location_id();
			
  		$this->db->from('expenses');
  		$this->db->join('people as recv', 'recv.person_id = expenses.employee_id','left');
  		$this->db->join('people as appr', 'appr.person_id = expenses.approved_employee_id','left');
		$this->db->join('categories', 'categories.id = expenses.category_id','left');
					 
 		if ($search)
 		{
				$this->db->where("(expense_type LIKE '".$this->db->escape_like_str($search)."%' or 
				expense_description LIKE '".$this->db->escape_like_str($search)."%' or 
				expense_reason LIKE '".$this->db->escape_like_str($search)."%' or
				".$this->db->dbprefix('categories').".name LIKE '".$this->db->escape_like_str($search)."%' or
				expense_note LIKE '".$this->db->escape_like_str($search)."%'  or expense_amount = ".$this->db->escape($search).") and ".$this->db->dbprefix('expenses').".deleted=$deleted");			
		}
		else
		{
			$this->db->where('expenses.deleted',$deleted);
		}
 		
		$this->db->where('expenses.location_id', $location_id);
	
		$this->db->limit($limit);
      $result = $this->db->get();
      return $result->num_rows();
    }

    /*
      Preform a search on expenses
     */

    function search($search, $deleted=0,$limit = 20, $offset = 0, $column = 'id', $orderby = 'asc',$location_id_override = NULL) {
			
			$location_id = $location_id_override ? $location_id_override : $this->Employee->get_logged_in_employee_current_location_id();
			
			if (!$deleted)
			{
				$deleted = 0;
			}
			
			
   		$this->db->select('expenses.*, CONCAT(recv.last_name, ", ", recv.first_name) as employee_recv, CONCAT(appr.last_name, ", ", appr.first_name) as employee_appr,categories.id as category_id,categories.name as category', false);
   		$this->db->from('expenses');
   		$this->db->join('people as recv', 'recv.person_id = expenses.employee_id','left');
   		$this->db->join('people as appr', 'appr.person_id = expenses.approved_employee_id','left');
			$this->db->join('categories', 'categories.id = expenses.category_id','left');
			
	 		if ($search)
	 		{
					$this->db->where("(expense_type LIKE '".$this->db->escape_like_str($search)."%' or 
					expense_description LIKE '".$this->db->escape_like_str($search)."%' or 
					expense_reason LIKE '".$this->db->escape_like_str($search)."%' or
					".$this->db->dbprefix('categories').".name LIKE '".$this->db->escape_like_str($search)."%' or
					expense_note LIKE '".$this->db->escape_like_str($search)."%'  or expense_amount = ".$this->db->escape($search).") and ".$this->db->dbprefix('expenses').".deleted=$deleted");			
			}
			else
			{
				$this->db->where('expenses.deleted',$deleted);
			}
			
			$this->db->where('expenses.location_id', $location_id);
		
       $this->db->order_by($column,$orderby);
	 
       $this->db->limit($limit);
      $this->db->offset($offset);
      return $this->db->get();
			
	  }

    /*
      Gets information about multiple expenses
     */

    function get_multiple_info($expenses_ids) {
        $this->db->from('expenses');
        $this->db->where_in('expenses.id', $expenses_ids);
        $this->db->order_by("id", "asc");
        return $this->db->get();
    }

    /*
      Inserts or updates a expenses
     */

    function save(&$expense_data, $expense_id = false) {
        if (!$expense_id or !$this->exists($expense_id)) {
            if ($this->db->insert('expenses', $expense_data)) {
                $expense_data['id'] = $this->db->insert_id();
                return true;
            }
            return false;
        }

        $this->db->where('id', $expense_id);
        return $this->db->update('expenses', $expense_data);
    }

    /*
      Get search suggestions to find Expenses
     */

    function get_search_suggestions($search, $deleted=0,$limit = 25) 
	 {
			if (!trim($search))
			{
				return array();
			}
			
			if (!$deleted)
			{
				$deleted = 0;
			}
			
		  
		  $suggestions = array();
		  
			  $this->db->select("expense_type");
	        $this->db->from('expenses');
	        $this->db->where('deleted', $deleted);
	        $this->db->like('expense_type', $search,'after');
	        $this->db->limit($limit);
	        $by_type = $this->db->get();
	        $temp_suggestions = array();
	        foreach ($by_type->result() as $row) {
	            $temp_suggestions[] = $row->expense_type;
	        }

      	  sort($temp_suggestions);
	        foreach ($temp_suggestions as $temp_suggestion) {
	            $suggestions[] = array('label' => $temp_suggestion,'subtitle' => '', 'avatar' => base_url()."assets/img/expense.png" );
	        }
	  
			  $this->db->select("expense_description");
	        $this->db->from('expenses');
	        $this->db->where('deleted', $deleted);
					
	        $this->db->like('expense_description', $search,'after');
	        $this->db->limit($limit);
	        $by_expense_description = $this->db->get();
	        $temp_suggestions = array();
	        foreach ($by_expense_description->result() as $row) {
	            $temp_suggestions[] = $row->expense_description;
	        }
			  
      	  sort($temp_suggestions);
	        foreach ($temp_suggestions as $temp_suggestion) {
	            $suggestions[] = array('label' => $temp_suggestion,'subtitle' => '', 'avatar' => base_url()."assets/img/expense.png" );
	        }
	  
	  
			  $this->db->select("expense_reason");
	        $this->db->from('expenses');
	        $this->db->where('deleted', $deleted);
					
	        $this->db->like('expense_reason', $search,'after');
	        $this->db->limit($limit);
	  
	        $by_expense_reason = $this->db->get();
	        $temp_suggestions = array();
	        foreach ($by_expense_reason->result() as $row) {
	            $temp_suggestions[] = $row->expense_reason;
	        }

      	  sort($temp_suggestions);
	        foreach ($temp_suggestions as $temp_suggestion) {
	            $suggestions[] = array('label' => $temp_suggestion,'subtitle' => '', 'avatar' => base_url()."assets/img/expense.png" );
	        }
	  
	        $this->db->from('expenses');
	        $this->db->where("(expense_amount = ".$this->db->escape($search).") and deleted=$deleted");
	        $this->db->limit($limit);
	  
	        $by_expense_amount = $this->db->get();
	        $temp_suggestions = array();
	        foreach ($by_expense_amount->result() as $row) {
	            $temp_suggestions[] = to_currency_no_money($row->expense_amount);
	        }

      	  sort($temp_suggestions);
	        foreach ($temp_suggestions as $temp_suggestion) {
	            $suggestions[] = array('label' => $temp_suggestion,'subtitle' => '', 'avatar' => base_url()."assets/img/expense.png" );
	        }
	 
	  		$this->db->select('name');
	  		$this->db->from('categories');
	      $this->db->like('name', $search,'after');
		
	
	  		$this->db->limit($limit);
	
	  		$by_category = $this->db->get();
	
	  		$temp_suggestions = array();
	  		foreach($by_category->result() as $row)
	  		{
	  			$temp_suggestions[] = $row->name;
	  		}
	
			sort($temp_suggestions);
	  		foreach($temp_suggestions as $temp_suggestion)
	  		{
	  			$suggestions[]=array('label'=> $temp_suggestion,'subtitle' => '', 'avatar' => base_url()."assets/img/expense.png" );		
	  		}
				$suggestions = array_map("unserialize", array_unique(array_map("serialize", $suggestions)));
				
        //only return $limit suggestions
        if (count($suggestions > $limit)) {
            $suggestions = array_slice($suggestions, 0, $limit);
        }
        return $suggestions;
    }

    /*
      Deletes one Expense
     */

    function delete($expense_id) {
        $this->db->where('id', $expense_id);
        return $this->db->update('expenses', array('deleted' => 1));
    }

    /*
      Deletes a list of expeses
     */

    function delete_list($expense_ids) {

        $this->db->where_in('id', $expense_ids);
        return $this->db->update('expenses', array('deleted' => 1));
    }
		
    function undelete_list($expense_ids) {

        $this->db->where_in('id', $expense_ids);
        return $this->db->update('expenses', array('deleted' => 0));
	}
	
	// Custom Function Start
	function get_expenses_not_in_quickbooks_or_modified_since_last_sync() {
		$qb_setup_date  = $this->config->item('qb_setup_date');
		$this->db->from('expenses');
		if (!empty($qb_setup_date)) {
			$this->db->where('expenses.expense_date > ', $qb_setup_date);
		}
		$this->db->where('expenses.accounting_id', NULL);
		$data = $this->db->get();
		return $data;
	}

	function link_qb_expense($expense_id, $qb_expense_id)
	{
		$this->db->where('id', $expense_id);
		return $this->db->update('expenses', array('accounting_id' => $qb_expense_id));
	}
	// Custom Function Ends

}
?>
