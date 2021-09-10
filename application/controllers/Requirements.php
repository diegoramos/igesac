<?php

require_once ("Secure_area.php");
require_once ("interfaces/Idata_controller.php");

defined('BASEPATH') OR exit('No direct script access allowed');

class Requirements extends Secure_area implements Idata_controller {

	public function __construct()
	{
        parent::__construct('requirements');
		  	$this->load->model('Requirement');
		  	$this->load->model('Category');
  			$this->lang->load('requirements');
  			$this->lang->load('module');
	}

	// List all your items
	public function index( $offset = 0 )
	{
        $params = $this->session->userdata('requirements_search_data') ? $this->session->userdata('requirements_search_data') : array('offset' => 0, 'order_col' => 'id', 'order_dir' => 'desc', 'search' => FALSE,'deleted' => 0);

        if ($offset != $params['offset']) {
            redirect('requirements/index/' . $params['offset']);
        }

        $this->check_action_permission('search');
        $config['base_url'] = site_url('requirements/sorting');
        $config['total_rows'] = $this->Requirement->count_all();
        $config['per_page'] = $this->config->item('number_of_items_per_page') ? (int) $this->config->item('number_of_items_per_page') : 20;
        $data['controller_name'] = strtolower(get_class());
        $data['per_page'] = $config['per_page'];
		$data['deleted'] = $params['deleted'];
        $data['search'] = $params['search'] ? $params['search'] : "";
        if ($data['search']) {
            $config['total_rows'] = $this->Requirement->search_count_all($data['search'],$params['deleted']);
            $table_data = $this->Requirement->search($data['search'],$params['deleted'], $data['per_page'], $params['offset'], $params['order_col'], $params['order_dir']);
        } else {
            $config['total_rows'] = $this->Requirement->count_all($params['deleted']);
            $table_data = $this->Requirement->get_all($params['deleted'],$data['per_page'], $params['offset'], $params['order_col'], $params['order_dir']);
        }

        $this->load->library('pagination');$this->pagination->initialize($config);
        $data['pagination'] = $this->pagination->create_links();
        $data['order_col'] = $params['order_col'];
        $data['order_dir'] = $params['order_dir'];
        $data['total_rows'] = $config['total_rows'];
        $data['manage_table'] = get_requirements_manage_table($table_data, $this);

        $this->load->view('requirements/manage', $data);
	}

    function sorting() {
        $this->check_action_permission('search');
		$params = $this->session->userdata('requirements_search_data');

        $search = $this->input->post('search') ? $this->input->post('search') : "";
        $per_page = $this->config->item('number_of_items_per_page') ? (int) $this->config->item('number_of_items_per_page') : 20;

        $offset = $this->input->post('offset') ? $this->input->post('offset') : 0;
        $order_col = $this->input->post('order_col') ? $this->input->post('order_col') : 'id';
        $order_dir = $this->input->post('order_dir') ? $this->input->post('order_dir') : 'asc';
				$deleted = $this->input->post('deleted') ? $this->input->post('deleted') : $params['deleted'];

        $requirements_search_data = array('offset' => $offset, 'order_col' => $order_col, 'order_dir' => $order_dir, 'search' => $search,'deleted' => $deleted);
        $this->session->set_userdata("requirements_search_data", $requirements_search_data);

        if ($search) {
            $config['total_rows'] = $this->Requirement->search_count_all($search,$deleted);
            $table_data = $this->Requirement->search($search, $deleted,$per_page, $this->input->post('offset') ? $this->input->post('offset') : 0, $this->input->post('order_col') ? $this->input->post('order_col') : 'id', $this->input->post('order_dir') ? $this->input->post('order_dir') : 'asc');
        } else {
            $config['total_rows'] = $this->Requirement->count_all($deleted);
            $table_data = $this->Requirement->get_all($deleted,$per_page, $this->input->post('offset') ? $this->input->post('offset') : 0, $this->input->post('order_col') ? $this->input->post('order_col') : 'id', $this->input->post('order_dir') ? $this->input->post('order_dir') : 'asc');
        }
        $config['base_url'] = site_url('expenses/sorting');
        $config['per_page'] = $per_page;
        $this->load->library('pagination');$this->pagination->initialize($config);
        $data['pagination'] = $this->pagination->create_links();
        $data['manage_table'] = get_expenses_manage_table_data_rows($table_data, $this);
        echo json_encode(array('manage_table' => $data['manage_table'], 'pagination' => $data['pagination'],'total_rows' => $config['total_rows']));
    }

	public function search()
	{
		$params = $this->session->userdata('requirements_search_data');
        $this->check_action_permission('search');

        $search = $this->input->post('search');
        $offset = $this->input->post('offset') ? $this->input->post('offset') : 0;
        $order_col = $this->input->post('order_col') ? $this->input->post('order_col') : 'id';
        $order_dir = $this->input->post('order_dir') ? $this->input->post('order_dir') : 'asc';
		$deleted = isset($params['deleted']) ? $params['deleted'] : 0;
        $requirements_search_data = array('offset' => $offset, 'order_col' => $order_col, 'order_dir' => $order_dir, 'search' => $search,'deleted' => $deleted);
        $this->session->set_userdata("requirements_search_data", $requirements_search_data);
        $per_page = $this->config->item('number_of_items_per_page') ? (int) $this->config->item('number_of_items_per_page') : 20;
        $search_data = $this->Requirement->search($search, $deleted,$per_page, $this->input->post('offset') ? $this->input->post('offset') : 0, $this->input->post('order_col') ? $this->input->post('order_col') : 'id', $this->input->post('order_dir') ? $this->input->post('order_dir') : 'asc');
        $config['base_url'] = site_url('expenses/search');
        $config['total_rows'] = $this->Requirement->search_count_all($search,$deleted);
        $config['per_page'] = $per_page;
        $this->load->library('pagination');$this->pagination->initialize($config);
        $data['pagination'] = $this->pagination->create_links();
        $data['manage_table'] = get_requirements_manage_table_data_rows($search_data, $this);
        echo json_encode(array('manage_table' => $data['manage_table'], 'pagination' => $data['pagination'],'total_rows' => $config['total_rows']));
	}

    public function buscar($search)
    {
        echo $search;
        $search_data = $this->Requirement->search($search);
        $config['total_rows'] = $this->Requirement->search_count_all($search);
    }

    function clear_state() {
            $params = $this->session->userdata('requirements_search_data');
            $this->session->set_userdata('requirements_search_data',array('offset' => 0, 'order_col' => 'id', 'order_dir' => 'desc', 'search' => FALSE,'deleted' => $params['deleted']));
            redirect('requirements');
    }

    public function suggest() {
            //allow parallel searchs to improve performance.
            session_write_close();
            $params = $this->session->userdata('requirements_search_data') ? $this->session->userdata('requirements_search_data') : array('deleted' => 0);
            $suggestions = $this->Requirement->get_search_suggestions($this->input->get('term'),$params['deleted'], 100);
            echo json_encode(H($suggestions));
    }

	// Add a new item
	public function view($requirement_id=-1, $redirect_code = 0)
	{
        $this->check_action_permission('add_update');
        $logged_employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $data['requirement_info'] = $this->Requirement->get_info($requirement_id);
        $data['logged_in_employee_id'] = $logged_employee_id;
        $data['all_modules'] = $this->Module->get_all_modules();
        $data['controller_name'] = strtolower(get_class());
        $data['redirect_code'] = $redirect_code;
                          
            $employees = array();
            
            foreach($this->Employee->get_all()->result() as $employee)
            {
                $employees[$employee->person_id] = $employee->first_name .' '.$employee->last_name;
            }
            
            $data['employees'] = $employees;
          
        $this->load->view("requirements/form", $data);
	}

	//Update one item
	public function save($id=-1)
	{
        $this->check_action_permission('add_update');        
          
        $requirement_data = array(
            'requirement_num' => $this->input->post('requirement_num'),
            'proyect_name' => $this->input->post('proyect_name'),
            'requirement_date' => date('Y-m-d',  strtotime($this->input->post('requirement_date'))),
            'employee_id' => $this->input->post('employee_id'),
            //'approved_employee_id' => $this->input->post('approved_employee_id') ? $this->input->post('approved_employee_id') : NULL,
            'location_id' => $this->Employee->get_logged_in_employee_current_location_id(),
        );

        if ($this->Requirement->save($requirement_data, $id)) 
        {
            
            $redirect = $this->input->post('redirect');
            
            $success_message = '';
            //New item
            if ($id == -1) 
                {
                $success_message = H(lang('requirements_successful_adding').' '.$requirement_data['requirement_num']);
                echo json_encode(array('success' => true, 'message' => $requirement_data, 'id' => $requirement_data['id'], 'redirect' => $redirect));
            } else 
                { //previous item
                $success_message = H(lang('common_items_successful_updating') . ' ' . $requirement_data['requirement_num']);
                $this->session->set_flashdata('manage_success_message', $success_message);
                echo json_encode(array('success' => true, 'message' => $success_message, 'id' => $id, 'redirect' => $redirect));
            }
        } 
          else 
          {//failure
            echo json_encode(array('success' => false, 'message' => lang('requirements_error_adding_updating')));
        }
	}

	//Delete one item
    function delete() {
        //$this->check_action_permission('delete');
        $requirements_to_delete = $this->input->post('ids');
        if ($this->Requirement->delete_list($requirements_to_delete)) {
            echo json_encode(array('success' => true, 'message' => lang('expenses_successful_deleted') . ' ' . lang('expenses_one_or_multiple')));
        } else {
            echo json_encode(array('success' => false, 'message' => lang('expenses_cannot_be_deleted')));
        }
    }
    
    function undelete() {
        $this->check_action_permission('delete');
        $expenses_to_delete = $this->input->post('ids');
        if ($this->Requirement->undelete_list($expenses_to_delete)) {
            echo json_encode(array('success' => true, 'message' => lang('expenses_successful_undeleted') . ' ' . lang('expenses_one_or_multiple')));
        } else {
            echo json_encode(array('success' => false, 'message' => lang('expenses_cannot_be_undeleted')));
        }
    }

    function get_status()
    {
        $data['status'] = $this->Requirement->get_status_list();
        echo "<pre>";
        print_r ($data['status']->result());
        echo "</pre>";
    }

    function change_status()
    {
        //$this->check_action_permission('delete');
        $requirements_to_change = $this->input->post();
        if ($this->Requirement->change_status($requirements_to_change)) {
            echo json_encode(array('success' => true, 'message' => lang('expenses_successful_deleted') . ' ' . lang('expenses_one_or_multiple')));
        } else {
            echo json_encode(array('success' => false, 'message' => lang('expenses_cannot_be_deleted')));
        }
    }

    function reload_requirements_table()
    {
        $config['base_url'] = site_url('items/sorting');
        $config['per_page'] = $this->config->item('number_of_items_per_page') ? (int)$this->config->item('number_of_items_per_page') : 20; 
        $params = $this->session->userdata('items_search_data') ? $this->session->userdata('items_search_data') : array('offset' => 0, 'order_col' => 'item_id', 'order_dir' => 'desc', 'search' => FALSE, 'category_id' => FALSE, 'fields' => 'all','deleted' => 0);

        $data['per_page'] = $config['per_page'];
        $data['search'] = $params['search'] ? $params['search'] : "";
        $data['category_id'] = $params['category_id'] ? $params['category_id'] : "";
        
        $data['fields'] = $params['fields'] ? $params['fields'] : "all";
        
        if ($data['search'] || $data['category_id'])
        {
            $config['total_rows'] = $this->Item->search_count_all($data['search'],$params['deleted'], $data['category_id'],30000, $data['fields']);
            $table_data = $this->Item->search($data['search'],$params['deleted'],$data['category_id'],$data['per_page'],$params['offset'],$params['order_col'],$params['order_dir'], $data['fields']);
        }
        else
        {
            $config['total_rows'] = $this->Item->count_all($params['deleted']);
            $table_data = $this->Item->get_all($params['deleted'],$data['per_page'],$params['offset'],$params['order_col'],$params['order_dir']);
        }
        
        echo get_items_manage_table($table_data,$this);
    }
}

/* End of file Requirements.php */
/* Location: ./application/controllers/Requirements.php */
