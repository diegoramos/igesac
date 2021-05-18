<?php
class Item_taxes_finder extends CI_Model
{
	/*
	Gets tax info for a particular item
	*/
	function get_info($item_id, $transaction_type = 'sale') //Can be sale or receiving
	{
		$CI =& get_instance();			
		
		$location_id_to_use = $CI && property_exists($CI,'cart') && $CI->cart->location_id ? $CI->cart->location_id : false;
		$this->load->model('Tax_class');
		if($transaction_type == 'receiving')
		{
			$item_info = $this->Item->get_info($item_id);
			$supplier_id = $item_info->supplier_id;
			
			if ($supplier_id)
			{
				$this->load->model('Supplier');
				
				$supplier_info = $this->Supplier->get_info($supplier_id, TRUE);
				
				if($supplier_info->override_default_tax)
				{
					$this->load->model('Supplier_taxes');
					
					if ($supplier_info->tax_class_id)
					{
						return $this->Tax_class->get_taxes($supplier_info->tax_class_id);
					}
					return $this->Supplier_taxes->get_info($supplier_id);
				}
				
			}
		}
		
		if($transaction_type == 'sale')
		{
			
			if ($item_id == $CI->Item->get_item_id_for_flat_discount_item())
			{
				return array();
			}
			
			$this->load->model('Customer');
			
			if ($CI && property_exists($CI,'cart') && method_exists($CI->cart,'get_has_delivery') && $CI->cart->get_has_delivery())
			{
				if($this->config->item('do_not_tax_service_items_for_deliveries'))
				{
					$item_info = $this->Item->get_info($item_id);
					if ($item_info->is_service)
					{
						return array();
					}
				}
			}
			
			
			if ($CI && property_exists($CI,'cart') && method_exists($CI->cart,'get_has_delivery') && $CI->cart->get_has_delivery() && method_exists($CI->cart,'get_delivery_tax_group_id') && $delivery_tax_group_id = $this->cart->get_delivery_tax_group_id())
			{
				return $this->Tax_class->get_taxes($delivery_tax_group_id);					
			}
			
			if ($CI && property_exists($CI,'cart') && property_exists($CI->cart,'customer_id') && $this->cart->customer_id && $this->cart->get_mode() !='store_account_payment' && $this->cart->get_mode() !='purchase_points')
			{
				$customer_id = $this->cart->customer_id;
				$customer_info = $this->Customer->get_info($customer_id, TRUE);
				if($customer_info->override_default_tax)
				{
					$this->load->model('Customer_taxes');
					
					if ($customer_info->tax_class_id)
					{
						return $this->Tax_class->get_taxes($customer_info->tax_class_id);
					}
					
					return $this->Customer_taxes->get_info($customer_id);
				}
				
				if (!$customer_info->taxable)
				{
					return array();
				}
			}
		}
		
		$item_location_info = $this->Item_location->get_info($item_id, $location_id_to_use, true);
		if($item_location_info->override_default_tax)
		{
			if ($item_location_info->tax_class_id)
			{
				return $this->Tax_class->get_taxes($item_location_info->tax_class_id);
			}
			
			
			return $this->Item_location_taxes->get_info($item_id);
		}
						
		$item_info = $this->Item->get_info($item_id);
		
		if($transaction_type == 'sale')
		{
			$CI->load->helper('sale');
			if ($CI && property_exists($CI,'cart') && is_ebt_sale_not_ebt_cash($this->cart) && $this->config->item('enable_ebt_payments') && $item_info->is_ebt_item)
			{
				return array();
			}
		}

		if($item_info->override_default_tax)
		{
			if ($item_info->tax_class_id)
			{
				return $this->Tax_class->get_taxes($item_info->tax_class_id);
			}
			
			return $this->Item_taxes->get_info($item_id);
		}
		
		$location_tax_class = $this->Location->get_info_for_key('tax_class_id',$location_id_to_use);
		
		if ($location_tax_class)
		{
			return $this->Tax_class->get_taxes($location_tax_class);
		}
				
		$return = array();
		
		//Location Config
		$default_tax_1_rate = $this->Location->get_info_for_key('default_tax_1_rate',$location_id_to_use);
		$default_tax_1_name = $this->Location->get_info_for_key('default_tax_1_name',$location_id_to_use);
				
		$default_tax_2_rate = $this->Location->get_info_for_key('default_tax_2_rate',$location_id_to_use);
		$default_tax_2_name = $this->Location->get_info_for_key('default_tax_2_name',$location_id_to_use);
		$default_tax_2_cumulative = $this->Location->get_info_for_key('default_tax_2_cumulative',$location_id_to_use) ? $this->Location->get_info_for_key('default_tax_2_cumulative',$location_id_to_use) : 0;
		
		$default_tax_3_rate = $this->Location->get_info_for_key('default_tax_3_rate',$location_id_to_use);
		$default_tax_3_name = $this->Location->get_info_for_key('default_tax_3_name',$location_id_to_use);
		
		$default_tax_4_rate = $this->Location->get_info_for_key('default_tax_4_rate',$location_id_to_use);
		$default_tax_4_name = $this->Location->get_info_for_key('default_tax_4_name',$location_id_to_use);
		
		$default_tax_5_rate = $this->Location->get_info_for_key('default_tax_5_rate',$location_id_to_use);
		$default_tax_5_name = $this->Location->get_info_for_key('default_tax_5_name',$location_id_to_use);
		
		if ($default_tax_1_rate && is_numeric($default_tax_1_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_1_name,
				'percent' => $default_tax_1_rate,
				'cumulative' => 0
			);
		}
		
		if ($default_tax_2_rate && is_numeric($default_tax_2_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_2_name,
				'percent' => $default_tax_2_rate,
				'cumulative' => $default_tax_2_cumulative
			);
		}

		if ($default_tax_3_rate && is_numeric($default_tax_3_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_3_name,
				'percent' => $default_tax_3_rate,
				'cumulative' => 0
			);
		}


		if ($default_tax_4_rate && is_numeric($default_tax_4_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_4_name,
				'percent' => $default_tax_4_rate,
				'cumulative' => 0
			);
		}


		if ($default_tax_5_rate && is_numeric($default_tax_5_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_5_name,
				'percent' => $default_tax_5_rate,
				'cumulative' => 0
			);
		}
		
		if (!empty($return))
		{
			return $return;
		}
		
		$store_config_tax_class = $this->config->item('tax_class_id');
		
		if ($store_config_tax_class)
		{
			return $this->Tax_class->get_taxes($store_config_tax_class);
		}
		
		//Global Store Config
		$default_tax_1_rate = $this->config->item('default_tax_1_rate');
		$default_tax_1_name = $this->config->item('default_tax_1_name');
				
		$default_tax_2_rate = $this->config->item('default_tax_2_rate');
		$default_tax_2_name = $this->config->item('default_tax_2_name');
		$default_tax_2_cumulative = $this->config->item('default_tax_2_cumulative') ? $this->config->item('default_tax_2_cumulative') : 0;
		
		$default_tax_3_rate = $this->config->item('default_tax_3_rate');
		$default_tax_3_name = $this->config->item('default_tax_3_name');
		
		$default_tax_4_rate = $this->config->item('default_tax_4_rate');
		$default_tax_4_name = $this->config->item('default_tax_4_name');
		
		$default_tax_5_rate = $this->config->item('default_tax_5_rate');
		$default_tax_5_name = $this->config->item('default_tax_5_name');
		
		$return = array();
		
		if ($default_tax_1_rate && is_numeric($default_tax_1_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_1_name,
				'percent' => $default_tax_1_rate,
				'cumulative' => 0
			);
		}
		
		if ($default_tax_2_rate && is_numeric($default_tax_2_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_2_name,
				'percent' => $default_tax_2_rate,
				'cumulative' => $default_tax_2_cumulative
			);
		}

		if ($default_tax_3_rate && is_numeric($default_tax_3_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_3_name,
				'percent' => $default_tax_3_rate,
				'cumulative' => 0
			);
		}

		if ($default_tax_4_rate && is_numeric($default_tax_4_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_4_name,
				'percent' => $default_tax_4_rate,
				'cumulative' => 0
			);
		}

		if ($default_tax_5_rate && is_numeric($default_tax_5_rate))
		{
			$return[] = array(
				'id' => -1,
				'item_id' => $item_id,
				'name' => $default_tax_5_name,
				'percent' => $default_tax_5_rate,
				'cumulative' => 0
			);
		}
		
				
		return $return;
	}
}
?>