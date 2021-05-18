<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Number_invoice extends CI_Model {

	function get_numeration_by_location($location_id='')
	{
		$this->db->select('prefijo,numeracion,nombre');
		$query = $this->db->get_where('prefix',['location_id'=>$location_id]);
		return $query;
	}
	function get_numeration_by_id($prefix_id='')
	{
		$this->db->select('prefix_id,prefijo,numeracion,nombre');
		$query = $this->db->get_where('prefix',['prefix_id'=>$prefix_id]);
		return $query->row();
	}

	function update_prefix($data,$where)
	{
		$this->db->update('prefix', $data,$where);
	}

	function insert_prefix($data)
	{
		$this->db->insert('prefix', $data);
	}

	////TODO SALES
	function get_numeration($invoice='',$location_id=-1)
	{
		$query = $this->db->get_where('prefix',['nombre'=>$invoice,'location_id'=>$location_id]);
		return $query->row();
	}
	/**************** TODO BAJA AQUI ******************/
	function get_numeration_baja($fecha='')
	{
		$query = $this->db->get_where('correlativo_baja',['nombre'=>$fecha]);
		return $query->row();
	}
	function update_numeration_baja($fecha='',$data_numeration_b)
	{	
		$this->db->where('nombre', $fecha);
		$this->db->update('correlativo_baja', $data_numeration_b);
		$this->db->trans_complete();
	}
	function save_numeration_baja($data=null)
	{
		$this->db->insert('correlativo_baja',$data);
	}

	/**************** TODO RESUMEN AQUI ***************/

	function update_numeration_resumen($fecha='',$data_numeration_b)
	{	
		$this->db->where('nombre', $fecha);
		$this->db->update('correlativo_resumen', $data_numeration_b);
		$this->db->trans_complete();
	}

	function get_numeration_resumen_diario($fecha='')
	{
		$query = $this->db->get_where('correlativo_resumen',['nombre'=>$fecha]);
		return $query->row();
	}
	function save_numeration_resumen_diario($data=null)
	{
		$this->db->insert('correlativo_resumen',$data);
	}
	function save_sunat_serie_by_id_sale($sale_id=-1,$suspended=0)
	{
		$this->db->select('sales_sunat.*,prefix.name_sales,prefix.nombre');
		$this->db->from('sales_sunat');
		$this->db->join('prefix', 'prefix.prefix_id = sales_sunat.prefix_id');
		$this->db->where('sale_id', $sale_id);
		$this->db->where('suspended', $suspended);
		$query = $this->db->get();
		return $query->row();
	}

	function update_numeration($prefix_id,$data_numeration)
	{
		$this->db->where('prefix_id', $prefix_id);
		$this->db->update('prefix', $data_numeration);
		$this->db->trans_complete();
	}
	function save_sunat_serie($data=null)
	{
		$this->db->insert('sales_sunat', $data);
	}

	//TODO RECIVINGS

	function get_numeration_receiving($invoice='',$location_id=-1)
	{
		$query = $this->db->get_where('prefix',['nombre'=>$invoice,'location_id'=>$location_id]);
		return $query->row();
	}
	function save_sunat_serie_by_id_receiving($receiving_id=-1,$suspended=0)
	{
		$this->db->select('receivings_sunat.*,prefix.name_sales');
		$this->db->from('receivings_sunat');
		$this->db->join('prefix', 'prefix.prefix_id = receivings_sunat.prefix_id');
		$this->db->where('receiving_id', $receiving_id);
		$this->db->where('suspended', $suspended);
		$query = $this->db->get();
		return $query->row();
	}
	function save_sunat_serie_receiving($data=null)
	{
		$this->db->insert('receivings_sunat', $data);
	}
}

/* End of file Number_invoice.php */
/* Location: ./application/models/Number_invoice.php */

 ?>