<?php
class Person extends CI_Model 
{
	/*Determines whether the given person exists*/
	function exists($person_id)
	{
		$this->db->from('people');	
		$this->db->where('people.person_id',$person_id);
		$query = $this->db->get();
		
		return ($query->num_rows()==1);
	}
	
	/*Gets all people*/
	function get_all($limit=10000, $offset=0)
	{
		$this->db->from('people');
		$this->db->order_by("last_name", "asc");
		$this->db->limit($limit);
		$this->db->offset($offset);
		return $this->db->get();		
	}
	
	function count_all()
	{
		$this->db->from('people');
		$this->db->where('deleted',0);
		return $this->db->count_all_results();
	}
	
	/*
	Gets information about a person as an array.
	*/
	function get_info($person_id)
	{
		$query = $this->db->get_where('people', array('person_id' => $person_id), 1);
		
		if($query->num_rows()==1)
		{
			return $query->row();
		}
		else
		{
			//create object with empty properties.
			$fields = $this->db->list_fields('people');
			$person_obj = new stdClass;
			
			foreach ($fields as $field)
			{
				$person_obj->$field='';
			}
			
			return $person_obj;
		}
	}
	
	/*
	Get people with specific ids
	*/
	function get_multiple_info($person_ids)
	{
		$this->db->from('people');
		$this->db->where_in('person_id',$person_ids);
		$this->db->order_by("last_name", "asc");
		return $this->db->get();		
	}
	
	/*
	Inserts or updates a person
	*/
	function save(&$person_data,$person_id=false)
	{		
		if(!empty($person_data))
		{
			if (isset($person_data['first_name']) && isset($person_data['last_name']))
			{
				$person_data['full_name'] = $person_data['first_name'].' '.$person_data['last_name'];
			}
		
			if (!$person_id or !$this->exists($person_id))
			{
				$person_data['create_date'] = date('Y-m-d H:i:s');
				if ($this->db->insert('people',$person_data))
				{
					$person_data['person_id']=$this->db->insert_id();
					return true;
				}
			
				return false;
			}
			$person_data['last_modified'] = date('Y-m-d H:i:s');
			$this->db->where('person_id', $person_id);
			return $this->db->update('people',$person_data);
		}
		
		return true;
	}
	
	/*
	Deletes one Person (doesn't actually do anything)
	*/
	function delete($person_id)
	{
		return true;; 
	}
	
	/*
	Deletes a list of people (doesn't actually do anything)
	*/
	function delete_list($person_ids)
	{	
		return true;	
 	}

	function update_mailchimp_subscriptions($email, $first_name, $last_name, $mailing_list_ids)
	{
		$this->load->helper('mailchimp');
		$this->load->library('mcapi', array('apikey' => $this->Location->get_info_for_key('mailchimp_api_key')));
		$mailing_list_ids = $mailing_list_ids == FALSE ? array() : $mailing_list_ids;
		$current_lists = get_mailchimp_lists($email);
		foreach($current_lists as $list)
		{
			//If a list we are currently subscribed to is not in the updated list, unsubscribe
			if (!in_array($list['id'], $mailing_list_ids))
			{
				$this->mcapi->listUnsubscribe($list['id'], $email, false, false, false);
			}
		}
		
		foreach($mailing_list_ids as $list)
		{
			$this->mcapi->listSubscribe($list, $email, array('FNAME' => $first_name, 'LNAME' => $last_name), 'html', false, true, false, false);
		}
	}
	
	function update_image($file_id,$person_id)
	{
		$this->db->set('image_id',$file_id);
		$this->db->where('person_id',$person_id);
		return $this->db->update('people');
	}
	
	function add_file($person_id,$file_id)
	{
		$this->db->insert('people_files', array('file_id' => $file_id, 'person_id' => $person_id));
	}
	
	function delete_file($file_id)
	{
	  $this->db->where('file_id',$file_id);
		$this->db->delete('people_files');
		$this->load->model('Appfile');
		return $this->Appfile->delete($file_id);
	}
	
	function get_files($person_id)
	{
		$this->db->select('people_files.*,app_files.file_name');
		$this->db->from('people_files');
		$this->db->join('app_files','app_files.file_id = people_files.file_id');
		$this->db->where('person_id',$person_id);
		$this->db->order_by('people_files.id');
		return $this->db->get();
	}	
	
}
?>
