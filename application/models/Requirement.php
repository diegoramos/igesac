<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Requirement extends CI_Model
{

    /*
      Determines if a given person_id is a customer
     */

    function exists($requirement) {
        $this->db->from('requirements');
        $this->db->where('requirements.id', $requirement);
        $query = $this->db->get();

        return ($query->num_rows() == 1);
    }

    /*
      Returns all the requirements
    */

    function get_all($deleted=0,$limit = 10000, $offset = 0, $col = 'id', $order = 'desc',$location_id_override = NULL) {
			
			if (!$deleted)
			{
				$deleted = 0;
			}
			
		$location_id = $location_id_override ? $location_id_override : $this->Employee->get_logged_in_employee_current_location_id();
		$this->db->select('requirements.*, CONCAT(recv.last_name, ", ", recv.first_name) as employee_recv, requirement_status.color, requirement_status.name',false);
		$this->db->from('requirements');
		$this->db->join('people as recv', 'recv.person_id = requirements.employee_id','left');
		$this->db->join('requirement_status', 'requirement_status.id = requirements.status', 'left');
		$this->db->where('requirements.deleted', $deleted);
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
        $this->db->from('requirements');
        $this->db->where('location_id', $location_id);
        $this->db->where('deleted', $deleted);
        return $this->db->count_all_results();
    }

    /*
      Gets information about a particular expense
     */

    function get_info($requirement_id) {
        $this->db->from('requirements');
        $this->db->where('requirements.id', $requirement_id);
        $query = $this->db->get();

        if ($query->num_rows() == 1) {
            return $query->row();
        } else {
            //Get empty base parent object, as $supplier_id is NOT an supplier
            $fields = $this->db->list_fields('requirements');
            $requirement_obj = new stdClass;
            //Get all the fields from requirements table
            $fields = $this->db->list_fields('requirements');
            //append those fields to base parent object, we we have a complete empty object
            foreach ($fields as $field) {
                $requirement_obj->$field = '';
            }
            return $requirement_obj;
        }
    }

    function search_count_all($search, $deleted=0,$limit = 10000,$location_id_override = NULL) {
		if (!$deleted)
		{
			$deleted = 0;
		}
		$location_id = $location_id_override ? $location_id_override : $this->Employee->get_logged_in_employee_current_location_id();
			
  		$this->db->from('requirements');
  		$this->db->join('people as recv', 'recv.person_id = requirements.employee_id','left');
					 
 		if ($search)
 		{
				$this->db->where("(requirement_num LIKE '".$this->db->escape_like_str($search)."%' or 
				proyect_name LIKE '".$this->db->escape_like_str($search)."%' or 
				requirement_date = ".$this->db->escape($search).") and ".$this->db->dbprefix('requirements').".deleted=$deleted");			
		}
		else
		{
			$this->db->where('requirements.deleted',$deleted);
		}
 		
		$this->db->where('requirements.location_id', $location_id);
	
		$this->db->limit($limit);
      $result = $this->db->get();
      return $result->num_rows();
    }

    /*
      Preform a search on requirements
     */

    function search($search, $deleted=0,$limit = 20, $offset = 0, $column = 'id', $orderby = 'asc',$location_id_override = NULL) {
			
		$location_id = $location_id_override ? $location_id_override : $this->Employee->get_logged_in_employee_current_location_id();
		
		if (!$deleted)
		{
			$deleted = 0;
		}
			
			
   		$this->db->select('requirements.*, CONCAT(recv.last_name, ", ", recv.first_name) as employee_recv', false);
   		$this->db->from('requirements');
   		$this->db->join('people as recv', 'recv.person_id = requirements.employee_id','left');
			
	 		if ($search)
	 		{
				$this->db->where("(requirement_num LIKE '".$this->db->escape_like_str($search)."%' or 
				proyect_name LIKE '".$this->db->escape_like_str($search)."%' or 
				requirement_date = ".$this->db->escape($search).") and ".$this->db->dbprefix('requirements').".deleted=$deleted");		
			}
			else
			{
				$this->db->where('requirements.deleted',$deleted);
			}
			
			$this->db->where('requirements.location_id', $location_id);
		
       	$this->db->order_by($column,$orderby);
	 
       	$this->db->limit($limit);
      	$this->db->offset($offset);
      	return $this->db->get();
			
	  }

    /*
      Gets information about multiple requirements
     */

    function get_multiple_info($requirements_ids) {
        $this->db->from('requirements');
        $this->db->where_in('requirements.id', $requirements_ids);
        $this->db->order_by("id", "asc");
        return $this->db->get();
    }

    /*
      Inserts or updates a requirements
     */

    function save(&$requirement_data, $requirement_id = false) {
        if (!$requirement_id or !$this->exists($requirement_id)) {
            if ($this->db->insert('requirements', $requirement_data)) {
                $requirement_data['id'] = $this->db->insert_id();
                return true;
            }
            return false;
        }

        $this->db->where('id', $requirement_id);
        return $this->db->update('requirements', $requirement_data);
    }

    /*
      Get search suggestions to find requirements
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
		  
			$this->db->select("proyect_name");
	        $this->db->from('requirements');
	        $this->db->where('deleted', $deleted);
	        $this->db->like('proyect_name', $search,'after');
	        $this->db->limit($limit);
	        $by_type = $this->db->get();
	        $temp_suggestions = array();
	        foreach ($by_type->result() as $row) {
	            $temp_suggestions[] = $row->proyect_name;
	        }

      	  sort($temp_suggestions);
	        foreach ($temp_suggestions as $temp_suggestion) {
	            $suggestions[] = array('label' => $temp_suggestion,'subtitle' => '', 'avatar' => base_url()."assets/img/expense.png" );
	        }
	  
			$this->db->select("proyect_name");
	        $this->db->from('requirements');
	        $this->db->where('deleted', $deleted);
					
	        $this->db->like('proyect_name', $search,'after');
	        $this->db->limit($limit);
	        $by_requirement_description = $this->db->get();
	        $temp_suggestions = array();
	        foreach ($by_requirement_description->result() as $row) {
	            $temp_suggestions[] = $row->requirement_num;
	        }
			  
      	  sort($temp_suggestions);
	        foreach ($temp_suggestions as $temp_suggestion) {
	            $suggestions[] = array('label' => $temp_suggestion,'subtitle' => '', 'avatar' => base_url()."assets/img/expense.png" );
	        }
	  
	  
			$this->db->select("proyect_name");
	        $this->db->from('requirements');
	        $this->db->where('deleted', $deleted);
					
	        $this->db->like('proyect_name', $search,'after');
	        $this->db->limit($limit);
	  
	        $by_expense_reason = $this->db->get();
	        $temp_suggestions = array();
	        foreach ($by_expense_reason->result() as $row) {
	            $temp_suggestions[] = $row->proyect_name;
	        }

      	  sort($temp_suggestions);
	        foreach ($temp_suggestions as $temp_suggestion) {
	            $suggestions[] = array('label' => $temp_suggestion,'subtitle' => '', 'avatar' => base_url()."assets/img/expense.png" );
	        }
	  
	        $this->db->from('requirements');
	        $this->db->where("(requirement_num = ".$this->db->escape($search).") and deleted=$deleted");
	        $this->db->limit($limit);
	
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
      Deletes one Requirement
     */

    function delete($requirement_id) {
        $this->db->where('id', $requirement_id);
        return $this->db->update('requirements', array('deleted' => 1));
    }

    /*
      Deletes a list of requirement
     */

    function delete_list($requirement_ids) {
        $this->db->where_in('id', $requirement_ids);
        return $this->db->update('requirements', array('deleted' => 1));
    }
		
    function undelete_list($requirement_ids) {

        $this->db->where_in('id', $requirement_ids);
        return $this->db->update('requirements', array('deleted' => 0));
	}

	function change_status($requirement_ids)
	{
        $this->db->where_in('id', $requirement_ids['requirements_ids']);
        return $this->db->update('requirements', array('status' => $requirement_ids['status']));
	}
	
	// Custom Function Start

	function get_status_list()
	{
		return $this->db->get('requirement_status');
	}

}

/* End of file Requirement.php */
/* Location: ./application/models/Requirement.php */