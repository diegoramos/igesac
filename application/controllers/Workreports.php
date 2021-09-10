<?php

require_once ("Secure_area.php");
require_once ("interfaces/Idata_controller.php");

defined('BASEPATH') OR exit('No direct script access allowed');

class Workreports extends Secure_area implements Idata_controller {

	public function __construct()
	{
		parent::__construct();
		//Load Dependencies

	}

	// List all your items
	public function index( $offset = 0 )
	{
		$data = "ok";		
		$this->load->view('Workreports/manage', $data);
	}

	public function search()
	{
		// code...
	}

	public function suggest()
	{
		// code...
	}

	public function view($data_item_id=-1)
	{
		// code...
	}

	// Add a new item
	public function save($data_item_id=-1)
	{

	}

	//Update one item
	public function update( $id = NULL )
	{

	}

	//Delete one item
	public function delete()
	{

	}

}

/* End of file Workreports.php */
/* Location: ./application/controllers/Workreports.php */
