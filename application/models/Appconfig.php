<?php

require_once  FCPATH . 'vendor/autoload.php';

use Bitzua\Curl;

class Appconfig extends CI_Model 
{
	
	function exists($key)
	{
		$this->db->from('app_config');	
		$this->db->where('app_config.key',$key);
		$query = $this->db->get();
		
		return ($query->num_rows()==1);
	}
	
	function get_all()
	{
		$this->db->from('app_config');
		$this->db->order_by("key", "asc");
		return $this->db->get();		
	}
	//Aqui va todo los datos
	function verificar_tipo_cambio(){
    	$fecha = date('Y-m-d');
    	if ($this->get_for_update_tipo_cambio($fecha)->num_rows()>0) {
			
    	}else{
			$res = $this->get_real_tipo_cambio();
    		if ($res!=null) {
    			$new_data = array(
    				'compra'=>$res['compra'],
    				'venta'=>$res['venta'],
    				'fecha'=>$fecha
					);
					if ($this->exists_tipo_cambio()->num_rows()>0) {
						$this->update_tipo_cambio($new_data);
					} else {
						$this->save_tipo_cambio($new_data);
					}
    		}
    	}
	}
	function exists_tipo_cambio(){
		return $this->db->get('tipo_cambio');
	}
	function save_tipo_cambio($data){
		$this->db->insert('tipo_cambio', $data);
	}
	function get_for_update_tipo_cambio($fecha){
    	return $this->db->get_where('tipo_cambio',['fecha'=>$fecha]);
	}
	function update_tipo_cambio($data){
    	$this->db->update('tipo_cambio', $data);
	}
	function get_tipo_cambio(){
    	return $this->db->get('tipo_cambio')->row();
	}
	
	function get_real_tipo_cambio()
  {
    //require_once(APPPATH . 'libraries/TipoCambioSunat/TipoCambioSunat.php');
		//$tipo_cambio = new TipoCambioSunat();
		//$result = $tipo_cambio->consultarTipoCambio();
    //return isset($result[0]) ? $result[0] : null;

		$refer  = 'https://www.sunat.gob.pe/';
		$url 	= 'https://www.sunat.gob.pe/a/txt/tipoCambio.txt';
		$curl 	= new cURL($refer, $url);
		$getRes = $curl->getRequest();

		$arr = explode('|', trim($getRes));

			$fecha  = \DateTime::createFromFormat("d/m/Y", trim($arr[0]));
			$result = [
				"fecha"  => trim($fecha->format("Y-m-d H:i:s")),
				"compra" => trim($arr[1]),
				"venta"  => trim($arr[2]),
			];

		return $result;

	}
	//fin de tipo cambio
	function get($key)
	{
		return $this->config->item($key);
	}
	
	function delete($key)
	{
		if ($key)
		{
			$this->db->where('key',$key);
			$this->db->delete('app_config');
		}
	}
	function save($key,$value)
	{
		$config_data = array(
			'key'=>$key,
			'value'=>$value
		);
		return $this->db->replace('app_config', $config_data);
	}
	
	function get_key_directly_from_database($key)
	{
		$this->db->from('app_config');
		$this->db->where("key", $key);
		$row = $this->db->get()->row_array();
		if (!empty($row))
		{
			return $row['value'];
		}
		return NULL;	
	}
	
	function get_raw_kill_ecommerce_cron()
	{
		$this->db->from('app_config');
		$this->db->where("key", "kill_ecommerce_cron");
		$row = $this->db->get()->row_array();
		if (!empty($row))
		{
			return $row['value'];
		}
		return 0;	
	}
	
	function get_raw_qb_cron_running()
	{
		$this->db->from('app_config');
		$this->db->where("key", "qb_cron_running");
		$row = $this->db->get()->row_array();
		if (!empty($row))
		{
			return $row['value'];
		}
		return 0;	
	}
	
	function get_raw_kill_qb_cron()
	{
		$this->db->from('app_config');
		$this->db->where("key", "kill_qb_cron");
		$row = $this->db->get()->row_array();
		if (!empty($row))
		{
			return $row['value'];
		}
		return 0;	
	}
	
	function get_raw_ecommerce_cron_running()
	{
		$this->db->from('app_config');
		$this->db->where("key", "ecommerce_cron_running");
		$row = $this->db->get()->row_array();
		if (!empty($row))
		{
			return $row['value'];
		}
		return 0;	
	}
	
	function get_raw_number_of_decimals()
	{
		$this->db->from('app_config');
		$this->db->where("key", "number_of_decimals");
		$row = $this->db->get()->row_array();
		if (!empty($row))
		{
			return $row['value'];
		}
		return 2;	
	}
	
	function get_raw_language_value()
	{
		$this->db->from('app_config');
		$this->db->where("key", "language");
		$row = $this->db->get()->row_array();
		if (!empty($row))
		{
			return $row['value'];
		}
		return '';	
	}

	function get_raw_version_value()
	{
		$this->db->from('app_config');
		$this->db->where("key", "version");
		$row = $this->db->get()->row_array();
		if (!empty($row))
		{
			return $row['value'];
		}
		return '';	
	}
		
	function get_force_https()
	{
		if ($this->db->table_exists('app_config'))
		{
			$this->db->from('app_config');
			$this->db->where("key", "force_https");
			$row = $this->db->get()->row_array();
			if (!empty($row))
			{
				return $row['value'];
			}
			return '';
		}
		
		return '';
	}
	
	function get_do_not_force_http()
	{
     $this->db->from('app_config');
     $this->db->where("key", "do_not_force_http");
     $row = $this->db->get()->row_array();
     if (!empty($row))
     {
			 return $row['value'];
     }
     return '';
	}
	
	function get_raw_phppos_session_expiration()
	{
		$this->db->from('app_config');
		$this->db->where("key", "phppos_session_expiration");
		$row = $this->db->get()->row_array();
		if (!empty($row))
		{
			if (is_numeric($row['value']))
			{
				return (int)$row['value'];
			}
			
		}
		return NULL;	
	}
	
	function batch_save($data)
	{
		if (isset($data['default_tax_1_name']))
		{
			//Check for duplicate taxes
			for($k = 1;$k<=5;$k++)
			{
				$current_tax = $data["default_tax_${k}_name"].$data["default_tax_${k}_rate"];
			
				for ($j = 1;$j<=5;$j++)
				{
					$check_tax = $data["default_tax_${j}_name"].$data["default_tax_${j}_rate"];
					if ($j!=$k && $current_tax != '' && $check_tax != '')
					{
						if ($current_tax == $check_tax)
						{
							return FALSE;
						}
					}
				}
			}
		}
		
		$success = true;
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();
		foreach($data as $key=>$value)
		{
			if(!$this->save($key, $value))
			{
				$success=false;
				break;
			}
		}
		
		$this->db->trans_complete();		
		return $success;
		
	}
		
	function get_logo_image()
	{
		if ($this->config->item('company_logo'))
		{
			return app_file_url($this->get('company_logo'));
		}
		return  base_url().'assets/img/header_logo.png';
	}
		
	function get_additional_payment_types()
	{
		$return = array();
		$payment_types = $this->get('additional_payment_types');
		
		if ($payment_types)
		{
			$return = array_map('trim', explode(',',$payment_types));
		}
		
		return $return;
	}
	
	function mark_mercury_activate($mercury_activate_seen = true)
	{
		$this->db->query('REPLACE INTO '.$this->db->dbprefix('app_config').' (`key`, `value`) VALUES ("mercury_activate_seen", "'.($mercury_activate_seen ? 1 : 0).'")');
	}
	
	function set_all_locations_use_global_tax()
	{
		$this->load->model('Location');
		return $this->Location->set_all_locations_use_global_tax();
	}
	
	function all_locations_use_global_tax()
	{
		$this->load->model('Location');
		return $this->Location->all_locations_use_global_tax();
	}
	
	function get_primary_key_next_index($table)
	{
		$tables_to_col = array(
			'items' => 'item_id',
			'item_kits'=> 'item_kit_id',
			'sales' => 'sale_id',
			'receivings' => 'receiving_id',	
		);
		
		if(isset($tables_to_col[$table]))
		{
			$this->db->select("IFNULL(MAX(".$tables_to_col[$table]."),0)+1 as max_id", false);
			$this->db->from($table);
			$max_id = $this->db->get()->row()->max_id;
			
			return $max_id;
		}
		
		return false;
	}
	
	function change_auto_increment($table, $value)
	{	
		if(!is_numeric($value) || intval($value) < 1)
		{
			return false;
		}
		
		$max = intVal($this->get_primary_key_next_index($table));
			
		if(intval($value) < $max)
		{
			$value = $max +1;
		}
			
		$this->db->query('ALTER TABLE '. $this->db->dbprefix($table). ' AUTO_INCREMENT '. $value);
		
		return $value;
	}
	
	function get_exchange_rates()
	{
		$this->db->from('currency_exchange_rates');
		$this->db->order_by('id');
		return $this->db->get();
	}
	//My nuevo agregado para actualizacion de local
	function update_exchange_rates($dat)
	{
		$data = array(
			'exchange_rate'=>$dat['exchange_rate']
		);
		
		$this->db->where('id', $dat['id']);
		$this->db->update('currency_exchange_rates', $data);
	}
	
	
	function save_exchange_rates($currency_exchange_rates_to, $currency_exchange_rates_symbol, $currency_exchange_rates_rate,$currency_exchange_rates_symbol_location,$currency_exchange_rates_number_of_decimals,$currency_exchange_rates_thousands_separator,$currency_exchange_rates_decimal_point)
	{
		$this->db->truncate('currency_exchange_rates');
		$currency_exchange_rates_to = $currency_exchange_rates_to ? $currency_exchange_rates_to : array();
		for($k = 0; $k< count($currency_exchange_rates_to); $k++)
		{
			$currency_exchange_rate_to = $currency_exchange_rates_to[$k];
			$currency_exchange_rate_symbol = $currency_exchange_rates_symbol[$k];
			$currency_exchange_rate = $currency_exchange_rates_rate[$k];			
			$currency_exchange_rate_symbol_location = $currency_exchange_rates_symbol_location[$k];
			$currency_exchange_rate_number_of_decimals = $currency_exchange_rates_number_of_decimals[$k];
			$currency_exchange_rate_thousands_separator = $currency_exchange_rates_thousands_separator[$k];
			$currency_exchange_rate_decimal_point = $currency_exchange_rates_decimal_point[$k];
				
			$this->db->insert('currency_exchange_rates', array(
				'currency_symbol' => $currency_exchange_rate_symbol,
				'currency_code_to' => $currency_exchange_rate_to,
				'exchange_rate' => $currency_exchange_rate,
				'currency_symbol_location' => $currency_exchange_rate_symbol_location,
				'number_of_decimals' => $currency_exchange_rate_number_of_decimals,
				'thousands_separator' => $currency_exchange_rate_thousands_separator,
				'decimal_point' => $currency_exchange_rate_decimal_point,
			));
		}
		
		return true;
	}
	
	public function get_api_keys()
	{
		$this->db->from('keys');
		$this->db->order_by('id');
		return $this->db->get()->result();
	}
	
  public function generate_key()
  {
    do
    {
        // Generate a random salt
        $salt = base_convert(bin2hex($this->security->get_random_bytes(64)), 16, 36);

        // If an error occurred, then fall back to the previous method
        if ($salt === FALSE)
        {
            $salt = hash('sha256', time() . mt_rand());
        }

        $new_key = substr($salt, 0, config_item('rest_key_length'));
    }
    while ($this->key_exists($new_key));

    return $new_key;
  }
	
  /* Private Data Methods */


  private function key_exists($key)
  {
      return $this->db
          ->where(config_item('rest_key_column'), $key)
          ->count_all_results(config_item('rest_keys_table')) > 0;
  }

  public function insert_key($key, $data)
  {
      $data[config_item('rest_key_column')] = sha1($key);
			$data['key_ending'] = substr($key,-7);
      $data['date_created'] = function_exists('now') ? now() : time();

      return $this->db
          ->set($data)
          ->insert(config_item('rest_keys_table'));
  }
	public function delete_api_key($api_key_id)
	{
  	$this->db->where('id', $api_key_id)->delete(config_item('rest_keys_table'));
	}
}

?>