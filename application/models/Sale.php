<?php
class Sale extends CI_Model
{
	public function __construct()
	{
      parent::__construct();
			$this->load->model('Inventory');
			$this->load->model('Number_invoice');	
	}
	public function get_info($sale_id)
	{
		$this->db->from('sales');
		$this->db->where('sale_id',$sale_id);
		return $this->db->get();
	}
	
	public function get_store_account_info($sale_id)
	{
		$this->db->from('store_accounts');
		$this->db->where('sale_id',$sale_id);
		return $this->db->get();
	}
	
	function has_coupons_for_today()
	{
		$this->load->model('Price_rule');	
		return $this->Price_rule->has_coupons_for_today();
	}
	
	function get_payment_sales_total_for_shift($shift_start, $shift_end,$payment_type)
  {
		$sales_totals = $this->get_sales_totaled_by_id($shift_start, $shift_end);
		$register_id = $this->Employee->get_logged_in_employee_current_register_id();
        
		$this->db->select('sales_payments.sale_id, sales_payments.payment_type, payment_amount, payment_id', false);
		$this->db->from('sales_payments');
		$this->db->join('sales','sales_payments.sale_id=sales.sale_id');
		$this->db->where('sales_payments.payment_date >=', $shift_start);
		$this->db->where('sales_payments.payment_date <=', $shift_end);
		$this->db->where('register_id', $register_id);
		$this->db->where($this->db->dbprefix('sales').'.deleted', 0);
		$this->db->order_by('payment_date');
		
		$payments_by_sale = array();
		$sales_payments = $this->db->get()->result_array();
		
		foreach($sales_payments as $row)
		{
        $payments_by_sale[$row['sale_id']][] = $row;
		}
				
		$payment_data = $this->Sale->get_payment_data($payments_by_sale,$sales_totals);
		
		foreach($payment_data as $payment_type_data=>$value)
		{
			if (strpos($payment_type_data,':') !== FALSE)
			{
				$key = substr($payment_type_data, 0, strpos($payment_type_data, ":"));
				
				if (!isset($payment_data[$key]))
				{
					$payment_data[$key] = array('payment_type' => $key,'payment_amount' => 0);
				}
				$payment_data[$key]['payment_amount'] += $value['payment_amount'];
				
			}
		}
				
		if (isset($payment_data[lang($payment_type)]))
		{
			return $payment_data[lang($payment_type)]['payment_amount'];
		}
		
		return 0.00;
  }
	
	function get_payment_data($payments_by_sale,$sales_totals)
	{
		static $foreign_language_to_cur_language = array();
		
		if (!$foreign_language_to_cur_language)
		{
			$this->load->helper('directory');
			$language_folder = directory_map(APPPATH.'language',1);
		
			$languages = array();
				
			foreach($language_folder as $language_folder)
			{
				$languages[] = substr($language_folder,0,strlen($language_folder)-1);
			}	
			
			$cur_lang = array();
			foreach($this->get_payment_options_with_language_keys() as $cur_lang_value => $lang_key)
			{
				$cur_lang[$lang_key] = $cur_lang_value;
			}
		
		
			foreach($languages as $language)
			{
				$this->lang->load('common', $language);
				
				foreach($this->get_payment_options_with_language_keys() as $cur_lang_value => $lang_key)
				{
					if (strpos($lang_key,'common') !== FALSE)
					{
						$foreign_language_to_cur_language[lang($lang_key)] = $cur_lang[$lang_key];
					}		
					else
					{
						$foreign_language_to_cur_language[$cur_lang_value] = $cur_lang_value;						
					}			
				}
			}
				
			//Switch back
			$this->lang->switch_to($this->config->item('language'));
		}
		$payment_data = array();
		
		$sale_ids = array_keys($payments_by_sale);
		$all_payments_for_sales = $this->_get_all_sale_payments($sale_ids);
		
		foreach($all_payments_for_sales as $sale_id => $payment_rows)
		{
			if (isset($sales_totals[$sale_id]))
			{
				$total_sale_balance = $sales_totals[$sale_id];		
				foreach($payment_rows as $payment_row)
				{
					//Postive sale total, positive payment
					if ($sales_totals[$sale_id] >= 0 && $payment_row['payment_amount'] >=0)
					{
						$payment_amount = $payment_row['payment_amount'] <= $total_sale_balance ? $payment_row['payment_amount'] : $total_sale_balance;
					}//Negative sale total negative payment
					elseif ($sales_totals[$sale_id] < 0 && $payment_row['payment_amount']  < 0)
					{
						$payment_amount = $payment_row['payment_amount'] >= $total_sale_balance ? $payment_row['payment_amount'] : $total_sale_balance;
					}//Positive Sale total negative payment
					elseif($sales_totals[$sale_id] >= 0 && $payment_row['payment_amount']  < 0)
					{
						$payment_amount = $total_sale_balance != 0 ? $payment_row['payment_amount'] : 0;
					}//Negtive sale total postive payment
					elseif($sales_totals[$sale_id] < 0 && $payment_row['payment_amount']  >= 0)
					{
						$payment_amount = $total_sale_balance != 0 ? $payment_row['payment_amount'] : 0;
					}
					
					if (!isset($foreign_language_to_cur_language[$payment_row['payment_type']]) || !isset($payment_data[$foreign_language_to_cur_language[$payment_row['payment_type']]]))
					{
						$payment_key = NULL;
						
						//Gift card
						if (strpos($payment_row['payment_type'],':') !== FALSE && !isset($foreign_language_to_cur_language[$payment_row['payment_type']]))
						{
			   	     list($giftcard_translation, $giftcard_number) = explode(":",$payment_row['payment_type']);
							 $foreign_language_to_cur_language[$payment_row['payment_type']] = $foreign_language_to_cur_language[$giftcard_translation].':'.$giftcard_number;
							
							if (!isset($payment_data[$foreign_language_to_cur_language[$payment_row['payment_type']]]))
							{
								$payment_data[$foreign_language_to_cur_language[$payment_row['payment_type']]] = array('payment_type' => $foreign_language_to_cur_language[$payment_row['payment_type']], 'payment_amount' => 0 );							
							}
							$payment_key = $foreign_language_to_cur_language[$payment_row['payment_type']];
						}
						elseif(isset($foreign_language_to_cur_language[$payment_row['payment_type']]))
						{
							if (!isset($payment_data[$foreign_language_to_cur_language[$payment_row['payment_type']]]))
							{
								$payment_data[$foreign_language_to_cur_language[$payment_row['payment_type']]] = array('payment_type' => $foreign_language_to_cur_language[$payment_row['payment_type']], 'payment_amount' => 0 );
							}
							
							$payment_key = $foreign_language_to_cur_language[$payment_row['payment_type']];
						}
						else
						{
							if (!isset($payment_data[$payment_row['payment_type']]))
							{
								$payment_data[$payment_row['payment_type']] = array('payment_type' => $payment_row['payment_type'], 'payment_amount' => 0 );
							}
							
							$payment_key = $payment_row['payment_type']; 
						}
					}
					else
					{
						$payment_key = $foreign_language_to_cur_language[$payment_row['payment_type']];
					}
					
					$exists = $this->_does_payment_exist_in_array($payment_row['payment_id'], $payments_by_sale[$sale_id]);
					
					
					if (($total_sale_balance != 0 || 
						($sales_totals[$sale_id] >= 0 && $payment_row['payment_amount']  < 0) ||
						($sales_totals[$sale_id] < 0 && $payment_row['payment_amount']  >= 0)) && $exists)
					{
						$payment_data[$payment_key]['payment_amount'] += $payment_amount;
					}

					$total_sale_balance-=$payment_amount;					
				}
			}
		}
		
		return $payment_data;
	}
	
	function _does_payment_exist_in_array($payment_id, $payments)
	{
		foreach($payments as $payment)
		{
			if($payment['payment_id'] == $payment_id)
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
		
	function _get_all_sale_payments($sale_ids)
	{
		$this->load->helper('text');
		$return = array();
		
		if (count($sale_ids) > 0)
		{
			$this->db->select('sales_payments.*, sales.sale_time');
		      	$this->db->from('sales_payments');
		      	$this->db->join('sales', 'sales.sale_id=sales_payments.sale_id');
			
			$this->db->group_start();
			$sale_ids_chunk = array_chunk($sale_ids,25);
			foreach($sale_ids_chunk as $sale_ids)
			{
				$this->db->or_where_in('sales_payments.sale_id', $sale_ids);
			}
			$this->db->group_end();
			
			$this->lang->load('sales');
			
			$store_account_payment_types = implode(',', array_map('add_quotes_and_escape', get_all_language_values_for_key('common_store_account')));
			$points_payment_types = implode(',', array_map('add_quotes_and_escape', get_all_language_values_for_key('common_points')));
			$giftcard_payment_types = implode(',', array_map('add_quotes_and_escape', get_all_language_values_for_key('common_giftcard')));
			$check_payment_types = implode(',', array_map('add_quotes_and_escape', get_all_language_values_for_key('common_check')));
			$credit_payment_types = implode(',', array_map('add_quotes_and_escape', get_all_language_values_for_key('common_credit')));
			$partial_credit_payment_types = implode(',', array_map('add_quotes_and_escape', get_all_language_values_for_key('sales_partial_credit')));
			$custom_payment_types = $this->Appconfig->get_additional_payment_types();
			if (empty($custom_payment_types))
			{
				$custom_payment_types[] = lang('common_none');
			}
			$custom_payment_types = implode(',', array_map('add_quotes_and_escape', $custom_payment_types));
			$cash_payment_types = implode(',', array_map('add_quotes_and_escape', get_all_language_values_for_key('common_cash')));
			
			$debit_payment_types = implode(',', array_map('add_quotes_and_escape', get_all_language_values_for_key('common_debit')));

			$this->db->order_by("(".$this->db->dbprefix("sales_payments").".payment_type IN ($store_account_payment_types)) DESC,(".$this->db->dbprefix("sales_payments").".payment_type IN ($points_payment_types)) DESC, (SUBSTRING_INDEX(".$this->db->dbprefix("sales_payments").".payment_type,':',1) IN ($giftcard_payment_types)) DESC,"."(".$this->db->dbprefix("sales_payments").".payment_type IN ($check_payment_types)) DESC,"."(".$this->db->dbprefix("sales_payments").".payment_type IN ($credit_payment_types)) DESC,"."(".$this->db->dbprefix("sales_payments").".payment_type IN ($partial_credit_payment_types)) DESC,"."(".$this->db->dbprefix("sales_payments").".payment_type IN ($custom_payment_types)) DESC,"."(".$this->db->dbprefix("sales_payments").".payment_type IN ($cash_payment_types)) DESC,"."(".$this->db->dbprefix("sales_payments").".payment_type IN ($debit_payment_types)) DESC,payment_date");
			
			$result = $this->db->get()->result_array();
			foreach($result as $row)
			{
				$return[$row['sale_id']][] = $row;
			}
		}
		return $return;
	}
	
	function get_payment_data_grouped_by_sale($payments_by_sale,$sales_totals)
	{
		static $foreign_language_to_cur_language = array();
		
		if (!$foreign_language_to_cur_language)
		{
		$this->load->helper('directory');
			$language_folder = directory_map(APPPATH.'language',1);
		
			$languages = array();
				
			foreach($language_folder as $language_folder)
			{
				$languages[] = substr($language_folder,0,strlen($language_folder)-1);
			}
		
			$cur_lang = array();
			foreach($this->get_payment_options_with_language_keys() as $cur_lang_value => $lang_key)
			{
				$cur_lang[$lang_key] = $cur_lang_value;
			}
		
		
			foreach($languages as $language)
			{
				$this->lang->load('common', $language);
			
				foreach($this->get_payment_options_with_language_keys() as $cur_lang_value => $lang_key)
				{
					if (strpos($lang_key,'common') !== FALSE)
					{
						$foreign_language_to_cur_language[lang($lang_key)] = $cur_lang[$lang_key];
					}		
					else
					{
						$foreign_language_to_cur_language[$cur_lang_value] = $cur_lang_value;						
					}			
				}
			}
				
			//Switch back
			$this->lang->switch_to($this->config->item('language'));
		}
		
		$payment_data = array();
		
		$sale_ids = array_keys($payments_by_sale);
		$all_payments_for_sales = $this->_get_all_sale_payments($sale_ids);
		
		foreach($all_payments_for_sales as $sale_id => $payment_rows)
		{
			if (isset($sales_totals[$sale_id]))
			{
				$total_sale_balance = $sales_totals[$sale_id];
			
				foreach($payment_rows as $payment_row)
				{
					//Postive sale total, positive payment
					if ($sales_totals[$sale_id] >= 0 && $payment_row['payment_amount'] >=0)
					{
						$payment_amount = $payment_row['payment_amount'] <= $total_sale_balance ? $payment_row['payment_amount'] : $total_sale_balance;
					}//Negative sale total negative payment
					elseif ($sales_totals[$sale_id] < 0 && $payment_row['payment_amount']  < 0)
					{
						$payment_amount = $payment_row['payment_amount'] >= $total_sale_balance ? $payment_row['payment_amount'] : $total_sale_balance;
					}//Positive Sale total negative payment
					elseif($sales_totals[$sale_id] >= 0 && $payment_row['payment_amount']  < 0)
					{
						$payment_amount = $total_sale_balance != 0 ? $payment_row['payment_amount'] : 0;
					}//Negtive sale total postive payment
					elseif($sales_totals[$sale_id] < 0 && $payment_row['payment_amount']  >= 0)
					{
						$payment_amount = $total_sale_balance != 0 ? $payment_row['payment_amount'] : 0;
					}			
			
			
					if (!isset($foreign_language_to_cur_language[$payment_row['payment_type']]) || !isset($payment_data[$sale_id][$foreign_language_to_cur_language[$payment_row['payment_type']]]))
					{
						$payment_key = NULL;
						
						//Gift card
						if (strpos($payment_row['payment_type'],':') !== FALSE && !isset($foreign_language_to_cur_language[$payment_row['payment_type']]))
						{
			   	   	list($giftcard_translation, $giftcard_number) = explode(":",$payment_row['payment_type']);
							$foreign_language_to_cur_language[$payment_row['payment_type']] = $foreign_language_to_cur_language[$giftcard_translation].':'.$giftcard_number;							
							
							
							if (!isset($payment_data[$sale_id][$foreign_language_to_cur_language[$payment_row['payment_type']]]))
							{
								$payment_data[$sale_id][$foreign_language_to_cur_language[$payment_row['payment_type']]] = array('sale_id' => $sale_id,'payment_type' => $foreign_language_to_cur_language[$payment_row['payment_type']], 'payment_amount' => 0,'payment_date' => $payment_row['payment_date'], 'sale_time' => $payment_row['sale_time'] );
							}
							$payment_key = $foreign_language_to_cur_language[$payment_row['payment_type']];
							
						}
						elseif(isset($foreign_language_to_cur_language[$payment_row['payment_type']]))
						{
							if (!isset($payment_data[$sale_id][$foreign_language_to_cur_language[$payment_row['payment_type']]]))
							{
								$payment_data[$sale_id][$foreign_language_to_cur_language[$payment_row['payment_type']]] = array('sale_id' => $sale_id,'payment_type' => $foreign_language_to_cur_language[$payment_row['payment_type']], 'payment_amount' => 0,'payment_date' => $payment_row['payment_date'], 'sale_time' => $payment_row['sale_time'] );
							}
							$payment_key = $foreign_language_to_cur_language[$payment_row['payment_type']];
							
						}
						else
						{
							if (!isset($payment_data[$sale_id][$payment_row['payment_type']]))
							{
								$payment_data[$sale_id][$payment_row['payment_type']] = array('sale_id' => $sale_id,'payment_type' => $payment_row['payment_type'], 'payment_amount' => 0,'payment_date' => $payment_row['payment_date'], 'sale_time' => $payment_row['sale_time'] );
							}
							
							$payment_key = $payment_row['payment_type']; 
							
						}
					}
					else
					{
						$payment_key = $foreign_language_to_cur_language[$payment_row['payment_type']];
					}
					
					
					$exists = $this->_does_payment_exist_in_array($payment_row['payment_id'], $payments_by_sale[$sale_id]);
				
					if (($total_sale_balance != 0 || 
						($sales_totals[$sale_id] >= 0 && $payment_row['payment_amount']  < 0) ||
						($sales_totals[$sale_id] < 0 && $payment_row['payment_amount']  >= 0)) && $exists)
					{
						$payment_data[$sale_id][$payment_key]['payment_amount'] += $payment_amount;
					}
				
					$total_sale_balance-=$payment_amount;
				}
			}
		}
		
		return $payment_data;
	}
	
	
	function get_sales_totaled_by_id($shift_start, $shift_end)
	{
		$register_id = $this->Employee->get_logged_in_employee_current_register_id();
		
		$this->db->select('sales.sale_id', false);
      	$this->db->from('sales');
      	$this->db->join('sales_payments','sales_payments.sale_id=sales.sale_id');
		$this->db->where('sales_payments.payment_date >=', $shift_start);
		$this->db->where('sales_payments.payment_date <=', $shift_end);
		$this->db->where('register_id', $register_id);
		$this->db->where($this->db->dbprefix('sales').'.deleted', 0);
		
		$sale_ids = array();
		$result = $this->db->get()->result();
		foreach($result as $row)
		{
			$sale_ids[] = $row->sale_id;
		}
		
		$sales_totals = array();
		
		if (count($sale_ids) > 0)
		{
			$this->db->select('sale_id, total');
			$this->db->from('sales');
			$this->db->group_start();
			$sale_ids_chunk = array_chunk($sale_ids,25);
			
			foreach($sale_ids_chunk as $sale_id_chunk)
			{
				$this->db->or_where($this->db->dbprefix('sales').'.sale_id IN('.implode(',',$sale_id_chunk).')');
			}
			$this->db->group_end();
			foreach($this->db->get()->result_array() as $sale_total_row)
			{
				$sales_totals[$sale_total_row['sale_id']] = $sale_total_row['total'];
			}
		}
		
		return $sales_totals;
	}
	 
	function exists($sale_id)
	{
		$this->db->from('sales');
		$this->db->where('sale_id',$sale_id);
		$query = $this->db->get();

		return ($query->num_rows()==1);
	}
	
	function update($sale_data, $sale_id)
	{
		$this->db->where('sale_id', $sale_id);
		$success = $this->db->update('sales',$sale_data);
		
		return $success;
	}

	function save($cart)
	{	
		$series_to_add = array();
		
		$exchange_rate = $cart->get_exchange_rate() ? $cart->get_exchange_rate() : 1;
		$exchange_name = $cart->get_exchange_name() ? $cart->get_exchange_name() : '';
		$exchange_currency_symbol = $cart->get_exchange_currency_symbol() ? $cart->get_exchange_currency_symbol() : '';
		$exchange_currency_symbol_location = $cart->get_exchange_currency_symbol_location() ? $cart->get_exchange_currency_symbol_location() : '';
		$exchange_number_of_decimals = ($cart->get_exchange_currency_number_of_decimals() !== '' && $cart->get_exchange_currency_number_of_decimals() !== NULL ) ? $cart->get_exchange_currency_number_of_decimals() : '';
		$exchange_thousands_separator = $cart->get_exchange_currency_thousands_separator() ? $cart->get_exchange_currency_thousands_separator() : '';
		$exchange_decimal_point = $cart->get_exchange_currency_decimal_point() ? $cart->get_exchange_currency_decimal_point() : '';
	
		$items = $cart->get_items();
		$customer_id = $cart->customer_id;
		$employee_id=$cart->employee_id ? $cart->employee_id : $this->Employee->get_logged_in_employee_info()->person_id;
		$sold_by_employee_id=$cart->sold_by_employee_id ? $cart->sold_by_employee_id : $employee_id;
		$comment = $cart->comment ? $cart->comment : '';
		$discount_reason = $cart->discount_reason ? $cart->discount_reason : '';
		$show_comment_on_receipt = $cart->show_comment_on_receipt ? 1 : 0;
		$coupons = $cart->get_coupons();
		$payments = $cart->get_payments();
		$sale_id= $cart->sale_id;
		$sale_id_exist= $cart->sale_id; //Important for numeration
		$store_account_payment = $cart->get_mode() == 'store_account_payment' ? 1 : 0;
		$is_purchase_points = $cart->get_mode() == 'purchase_points' ? 1 : 0;
		$suspended = $cart->suspended ? $cart->suspended : ($cart->get_mode() == 'estimate' ? 2 : 0);
			
		$store_account_in_all_languages = get_all_language_values_for_key('common_store_account','common');
	
		$balance = 0;
		//Add up balances for all languages
		foreach($store_account_in_all_languages as $store_account_lang)
		{
				//Thanks Mike for math help on how to convert exchange rate back to get correct balance
				$balance+= $cart->get_payment_amount($store_account_lang)*pow($exchange_rate,-1);
		}
			
		
		//Do this before we clear_exchange_details so we have a string with the exchanged currencies
		$payment_types='';
		foreach($payments as $payment_id=>$payment)
		{
			$payment_types=$payment_types.$payment->payment_type.': '.($exchange_rate == 1 ? to_currency($payment->payment_amount) : to_currency_as_exchange($cart,$payment->payment_amount)).'<br />';
		}
		
		
		
		//Clear currency exchange so it is saved right values for totals
		$cart->clear_exchange_details();
		//Reset payments back to regular default currency
		
		for($k=0;$k<count($payments);$k++)
		{
			$payments[$k]->payment_amount = $payments[$k]->payment_amount*pow($exchange_rate,-1);
		}
		if ($this->config->item('test_mode'))
		{
			$cart->destroy();
			$cart->save();
			return lang('sales_test_mode_transaction');
		}
		
		$is_new_sale = $sale_id ? false : true;
		$this->load->model('Item_serial_number');
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();
			
		$global_weighted_average_cost = FALSE;
		
		if ($this->config->item('always_use_average_cost_method'))
		{
			$global_weighted_average_cost=  $this->get_global_weighted_average_cost();
			$global_weighted_average_cost = to_currency_no_money($global_weighted_average_cost, 10);
		}
		
		if ($sale_id)
		{
			$before_save_sale_info = $this->get_info($sale_id)->row();
		}
		else
		{
			$before_save_sale_info = FALSE;
		}
		
		if(count($items)==0)
			return -1;
		
		$tier_id = $cart->selected_tier_id;
		$deleted_taxes = $cart->get_excluded_taxes();
		
		if (!$tier_id)
		{
			$tier_id = NULL;
		}
		
		$sale_total_qty = $cart->get_total_quantity(); 
		$sale_subtotal = $cart->get_subtotal();
		$sale_total = $cart->get_total();
		$sale_tax = $sale_total - $sale_subtotal;
		$sales_data = array(
			'customer_id'=> $customer_id > 0 ? $customer_id : null,
			'employee_id'=>$employee_id,
			'sold_by_employee_id' => $sold_by_employee_id,
			'payment_type'=>$payment_types,
			'type_comprobante' =>$cart->type_comprobante,
			'comment'=>$comment,
			'discount_reason'=>$discount_reason,
			'show_comment_on_receipt'=> $show_comment_on_receipt ?  $show_comment_on_receipt : 0,
			'suspended'=>$suspended,
			'deleted' => 0,
			'deleted_by' => NULL,
			'cc_ref_no' => $before_save_sale_info ? $before_save_sale_info->cc_ref_no : '',//Legacy for old payments; set new payments to empty
			'auth_code' => $before_save_sale_info ? $before_save_sale_info->auth_code : '',//Legacy for old payments; set new payments to empty
			'location_id' => $cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id(),
			'register_id' => $cart->register_id ? $cart->register_id : $this->Employee->get_logged_in_employee_current_register_id(),
			'store_account_payment' => $store_account_payment,
			'is_purchase_points' => $is_purchase_points,
			'tier_id' => $tier_id ? $tier_id : NULL,
			'deleted_taxes' =>  $deleted_taxes? serialize($deleted_taxes) : NULL,
			'total_quantity_purchased' => $sale_total_qty,
			'subtotal' => $sale_subtotal,
			'total' => $sale_total,
			'tax' => $sale_tax,
			'profit' =>0,//Will update when sale complete
			'rule_id' => $cart->get_spending_price_rule_id(),
			'rule_discount' => $cart->get_spending_price_rule_discount(),
			'exchange_rate' => $exchange_rate,
			'exchange_name' => $exchange_name,
			'exchange_currency_symbol' => $exchange_currency_symbol,
			'exchange_currency_symbol_location' => $exchange_currency_symbol_location,
			'exchange_number_of_decimals' => $exchange_number_of_decimals,
			'exchange_thousands_separator' => $exchange_thousands_separator,
			'exchange_decimal_point' => $exchange_decimal_point,
			'last_modified' => $sale_id ? date('Y-m-d H:i:s') : NULL,
 		);
				
		for($k=1;$k<=NUMBER_OF_PEOPLE_CUSTOM_FIELDS;$k++) 
		{
			$sales_data["custom_field_${k}_value"] = $this->cart->{"custom_field_${k}_value"};
		}
				
		$sale_profit = 0;
		$sale_commission = 0;
		
		if ($suspended == 1) //Layaway
		{
			$sales_data['was_layaway'] = 1;
		}
		elseif ($suspended == 2) //estimate
		{
			$sales_data['was_estimate'] = 1;				
		}
		
		if($sale_id)
		{
			$old_data=$this->get_info($sale_id)->row_array();
			$sales_data['sale_time']=$old_data['sale_time'];
		}
		else
		{
			$sales_data['sale_time'] = date('Y-m-d H:i:s');
		}
						
		$change_cart_date = $cart->change_date_enable ?  $cart->change_cart_date : false;
		
		if($change_cart_date) 
		{
			$sale_time = strtotime($change_cart_date);
			if($sale_time !== FALSE)
			{
				$sales_data['sale_time']=date('Y-m-d H:i:s', strtotime($change_cart_date));
			}
		}
		
		if ($sale_id)
		{
			//If we are NOT a suspended sale and wasn't a layaway/estimate
			if (!$cart->suspended && !$old_data['was_layaway'] && !$old_data['was_estimate'])
			{
				$override_payment_time = $sales_data['sale_time'];
			}
		}
		elseif($cart->change_date_enable)
		{
			if (!$cart->get_previous_receipt_id() && !$cart->suspended)
			{
				$override_payment_time = $sales_data['sale_time'];
			}
			
		}
		
		$store_account_payment_amount = 0;
		
		if ($store_account_payment)
		{
			$store_account_payment_amount = $cart->get_total();
		}
		
		//Only update balance + store account payments if we are NOT an estimate (suspended < 2)
		if (!$cart->is_ecommerce && $suspended < 2)
		{
	   	  //Update customer store account balance
			  if($customer_id > 0 && $balance)
			  {
				  $this->db->set('balance','balance+'.$balance,false);
				  $this->db->where('person_id', $customer_id);
				  $this->db->update('customers');
			  }
			  
		     //Update customer store account if payment made
			if($customer_id > 0 && $store_account_payment_amount)
			{
				$this->db->set('balance','balance-'.$store_account_payment_amount,false);
				$this->db->where('person_id', $customer_id);
				$this->db->update('customers');
			 }
		 }
		 		 
		 $previous_store_account_amount = 0;

		 //If we have a previous sale but it wasn't an estimate
		 if ($sale_id !== FALSE && $before_save_sale_info && $before_save_sale_info->suspended != 2)
		 {
			 $previous_store_account_amount = $this->get_store_account_payment_total($sale_id);
		 }
		 
		if ($sale_id)
		{
			//Delete previoulsy sale so we can overwrite data
			$this->delete($sale_id, true);
			
			$this->db->where('sale_id', $sale_id);
			$this->db->update('sales', $sales_data);
		}
		else
		{
			$this->db->insert('sales',$sales_data);
			$sale_id = $this->db->insert_id();
		}
		//Esto se agrego recientemente
		$location_id_num=$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id();


		if ($cart->type_comprobante==4) {
			
		}else{
			//Activar facturación electronica
			if ($this->config->item('active_facturacion')) {
					///////////////////////FACTURACIÓN ELECTRONICA//////////////////////////////
					//////////////////////////CLIENTE INFO//////////////////////////////////////
					$sunat = FALSE;
					$cust_info          = $this->Customer->get_info($customer_id);

	                $nombre_compania    = $cust_info->company_name;
	                $direccion_cliente  = $cust_info->address_1;
	                $direccion_cliente2 = $cust_info->address_2;
	                $cliente_ciudad   	= $cust_info->city;
	                $provincia_cliente  = $cust_info->state;
	                $cod_postal_clie    = $cust_info->zip;
	                $pais_cliente       = $cust_info->country;
	                $telefono_cliente   = $cust_info->phone_number;
	                $correo_cliente     = $cust_info->email;
	                $cliente = $cust_info->first_name . ' ' . $cust_info->last_name;
	                if ($cust_info->account_number != '') {
	                    $num_doc = $cust_info->account_number;
	                    if (strlen($cust_info->account_number) == 8) {
	                        $tipo_documento = 1;
	                    } else if (strlen($cust_info->account_number) == 11) {
	                        $tipo_documento = 6;
	                    }
	                }else{
	                	$tipo_documento = 0;
	                	$num_doc = "00000000";
	                }

	                ///////////////////////ITEMS////////////////
	               	$sales_items_data = array();
	               	$count=0;
	               	$descuento_item_total=0;
	               	$descuento_global=0;
	                foreach ($items as $index => $item) {
	      
						if ($item->quantity==-1) {
							$descuento_global=$item->unit_price+$descuento_global;
						}else if($item->quantity>=1){
							$count=$count+1;
		                	$sale_item_subtotal = $item->get_subtotal();
							$sale_item_total = $item->get_total();
							$sale_item_tax = $sale_item_total - $sale_item_subtotal;
							$descuento_item_total = $descuento_item_total+to_currency_no_money($sale_item_subtotal*($item->discount/100));
							$precio_sin_igv=($item->tax_included) ? to_currency_no_money(($item->unit_price)/1.18):$item->unit_price;
							$item_s = array
							(
								//'line'=>$line,
								'txtITEM' => $count,
								'txtDESCRIPCION_DET'=> ($item->description!='') ? $item->description : $item->name,
								'txtUNIDAD_MEDIDA_DET'=> $item->unity,
								'txtCODIGO_DET'     => $item->item_number,
								'txtCANTIDAD_DET'=> $item->quantity,
								'txtPORCENTAJE_DESCUENTO_ITEM'=> to_currency_no_money($item->discount/100),
								'txtDESCUENTO_ITEM'=>  to_currency_no_money($sale_item_subtotal*($item->discount/100)),
								'txtBASE_DESCUENTO_ITEM'=> to_currency_no_money($sale_item_subtotal),
								'txtPRECIO_SIN_IGV_DET'=> $precio_sin_igv,
								'txtSUB_TOTAL_DET' => to_currency_no_money($sale_item_subtotal),
								'txtIMPORTE_DET' => to_currency_no_money($sale_item_subtotal),
								'txtPRECIO_DET' => to_currency_no_money($sale_item_total),
								'txtIGV' => to_currency_no_money($sale_item_tax),
								'txtISC' => 0,
								'txtCOD_TIPO_OPERACION' => "10",
								'txtPRECIO_TIPO_CODIGO' => "01",
								'txtCODIGO_PROD_SUNAT' => ''
							);
							$sales_items_data[] = $item_s;
						}
						//echo to_currency_no_money($sale_item_tax);
						
	                }

	                $this->load->helper('number');
					$discount_reason = $cart->discount_reason ? $cart->discount_reason : '';
					$show_comment_on_receipt = $cart->show_comment_on_receipt ? 1 : 0;
					$coupons = $cart->get_coupons();
					$payments = $cart->get_payments();

					$moneda = '';
		            if ($this->cart->get_exchange_details() == '') {
		                $moneda = 'PEN';
		            } else {
		                $many   = explode('|', $this->cart->get_exchange_details());
		                $moneda = $many[1];
		            }

		            if ($cart->type_comprobante==1) {
						$sales_data_sunat = array(
							'tipo_de_comprobante' 	=> '1',
							'tipo_operacion'        => "0101", //si
		                    "total_inafecta"        => "0", //si
			                "total_exoneradas"      => "0", //si
			                "total_gratuitas"       => "0", //si por que no te quedas programando dijiste y juraste que ese dia era el ultmo
			                "total_exportacion"     => "0", //si
			                'total_descuento'=>/*(($discount_reason!='')?$discount_reason:0)+*/$descuento_item_total/*+$descuento_global*/,

			                "porcentaje_descuento_total" => 0.00,
			                "descuento_global" => 0,//$descuento_global,
			                "base_descuento_global" => 0,

			                "porcentaje_igv"       	=> "18.00", //si
			                "total_letras"          => num_to_letras($sale_total), //si
			                "nro_guia_remision"     => "",//si
			                "cod_guia_remision"     => "",//si
			                "nro_otr_comprobante"   => "",//si
			                //'serie'                 => '00' . $current_location,
		                    //'tipo_comprobante'      => '0' . $this->cart->type_comprobante,
		                    "fecha_comprobante"     => date('Y-m-d'),
		                	"fecha_vto_comprobante" => date('Y-m-d'),
		                	"cod_tipo_documento"    => '0' . $cart->type_comprobante,
		                	"cod_moneda"            => ($exchange_name!='')?$exchange_name:$moneda,//si
		                	//-------------------------
							//'deleted_taxes' =>  $deleted_taxes? serialize($deleted_taxes) : NULL,
							//'total_quantity_purchased' => $sale_total_qty,
							////cliente
		                	'cliente_numerodocumento' => $num_doc,//si
		                    'cliente_nombre'          => $cliente,//si
		                    'cliente_tipodocumento'   => $tipo_documento,//si
		                    'cliente_direccion'     => $direccion_cliente,//si
		                    //'direccion_cliente2'    => $direccion_cliente2,
		                    "cliente_pais"           => "PE" /*$pais_cliente*/,
		                	"cliente_ciudad"         => $cliente_ciudad,
			                "cliente_codigoubigeo"   => "",
			                "cliente_departamento"   => "",
			                "cliente_provincia"      => $provincia_cliente,
			                "cliente_distrito"       => "",

							'sub_total' 			=> $sale_subtotal,
							'total_gravadas' 		=> $sale_subtotal,
							'total' 				=> $sale_total,
							'total_igv' 			=> $sale_tax,
							"total_isc"             => "0", //si
			                "total_otr_imp"         => "0", //si
			                'detalle'               => $sales_items_data,
			                "usuariosol"			=> '',
							"clavesol"				=> ''//
				 		);
					}else if($cart->type_comprobante==3){
						$sales_data_sunat = array(
							'tipo_de_comprobante' 			=> '3',
							"tipo"							=> "nuevo",
							"codigo"						=> "RC",
							"serie"							=> date('Ymd'),
						    //"secuencia"             		=> 1,
						    "fecha_referencia"             	=> date('Y-m-d'),
							"fecha_documento"          		=> date('Y-m-d'),
							"usuariosol"			=> '',
							"clavesol"				=> '',
							//items
						    "detalle" => array( 
						                    array(
												"ITEM"					=>	"1",
												"TIPO_COMPROBANTE"		=>	"03",
												//"NRO_COMPROBANTE"		=>	"B001-43",
												"NRO_DOCUMENTO"			=>	$num_doc,
												"TIPO_DOCUMENTO"		=>	$tipo_documento,
												"NRO_COMPROBANTE_REF"	=>	"0",
												"TIPO_COMPROBANTE_REF"	=>	"0",
												"STATUS"				=>	"1",
												"COD_MONEDA"			=>	($exchange_name!='')?$exchange_name:$moneda,
												"TOTAL"					=>	$sale_total,
												"GRAVADA"				=>	$sale_subtotal,
												"EXONERADO"				=>	"0",
												"INAFECTO"				=>	"0",
												"EXPORTACION"			=>	"0",
												"GRATUITAS"				=>	"0",
												"MONTO_CARGO_X_ASIG"	=>	"0",
												"CARGO_X_ASIGNACION"	=>	"0",
												"ISC"					=>	"0",
												"IGV"					=>	$sale_tax,
												"OTROS"					=>	"0"
						                    )
							)

						);
					}

					if (!$sale_id_exist) {
						
						//Sale prefix
						if ($suspended > 0) {
							$prefix=$this->Number_invoice->get_numeration('cotizacion',$location_id_num);
							$data_numeration = array(
								'numeracion' => ((int)$prefix->numeracion+1)
								 );
							$data_sunat_re = array(
								'serie' 	=> $prefix->prefijo./*$location_id_num.*/"-".$prefix->numeracion,
								'sale_id'	=> $sale_id,
								'prefix_id'	=> $prefix->prefix_id,
								'suspended'	=> 1
								 );
							$this->Number_invoice->save_sunat_serie($data_sunat_re);
							$this->Number_invoice->update_numeration($prefix->prefix_id,$data_numeration);

						}
						if ($suspended == 0 && $cart->type_comprobante==1) {
							$prefix=$this->Number_invoice->get_numeration('factura',$location_id_num);
							$serie=$prefix->prefijo./*$location_id_num.*/"-".$prefix->numeracion;
							$data_numeration = array(
								'numeracion' => ((int)$prefix->numeracion+1)
								 );
							$sales_data_sunat['numero_comprobante']=$prefix->numeracion;
							$sales_data_sunat['serie_comprobante']=$prefix->prefijo/*.$location_id_num*/;
							$data_sunat_re = array(
								'serie' 	=> $prefix->prefijo./*$location_id_num.*/"-".$prefix->numeracion,
								'sale_id'	=> $sale_id,
								'prefix_id'	=> $prefix->prefix_id
								 );
							$sunat = TRUE;
							$this->Number_invoice->save_sunat_serie($data_sunat_re);
							$this->Number_invoice->update_numeration($prefix->prefix_id,$data_numeration);
						}
						if ($suspended == 0 && $cart->type_comprobante==3) {
							//error_reporting(E_ALL);
							//ini_set('display_errors', TRUE);
							//ini_set('display_startup_errors', TRUE);
							$prefix=$this->Number_invoice->get_numeration('boleta',$location_id_num);

							$serie=$prefix->prefijo./*$location_id_num.*/"-".$prefix->numeracion;

							$data_numeration = array(

								'numeracion' => ((int)$prefix->numeracion+1)

								 );

							$sales_data_sunat['detalle'][0]['NRO_COMPROBANTE']=$prefix->prefijo/*.$location_id_num*/."-".$prefix->numeracion;

							$data_sunat_re = array(
								'serie' 	=> $prefix->prefijo./*$location_id_num.*/"-".$prefix->numeracion,
								'sale_id'	=> $sale_id,
								'prefix_id'	=> $prefix->prefix_id
								 );

							$sunat = TRUE;

							$this->Number_invoice->save_sunat_serie($data_sunat_re);

							$this->Number_invoice->update_numeration($prefix->prefix_id,$data_numeration);
							/*** save and update numeracion ***/
							$tipo_comprobante="03";
							$serie_fecha = date('Ymd');
							$resumen=$this->Number_invoice->get_numeration_resumen_diario($serie_fecha);

							if ($resumen==null) {
								$sales_data_sunat['secuencia']=1;
								$data_numeration_b = array('prefijo'=>'RC','numeracion' => 2,'nombre'=>$serie_fecha);
								$this->Number_invoice->save_numeration_resumen_diario($data_numeration_b);
							}else{
								$sales_data_sunat['secuencia']=$resumen->numeracion;
								$data_numeration_b = array('numeracion' => ($resumen->numeracion+1));
								$this->Number_invoice->update_numeration_resumen($resumen->nombre,$data_numeration_b);
							}
						}
					}else{
						if ($suspended == 0 && $cart->type_comprobante==1) {
							$prefix=$this->Number_invoice->get_numeration('factura',$location_id_num);
							$serie=$prefix->prefijo./*$location_id_num.*/"-".$prefix->numeracion;
							$data_numeration = array(
								'numeracion' => ((int)$prefix->numeracion+1)
								 );
							$sales_data_sunat['numero_comprobante']=$prefix->numeracion;
							$sales_data_sunat['serie_comprobante']=$prefix->prefijo/*.$location_id_num*/;
							$data_sunat_re = array(
								'serie' 	=> $prefix->prefijo./*$location_id_num.*/"-".$prefix->numeracion,
								'sale_id'	=> $sale_id,
								'prefix_id'	=> $prefix->prefix_id
								 );
							$sunat = TRUE;
							$this->Number_invoice->save_sunat_serie($data_sunat_re);
							$this->Number_invoice->update_numeration($prefix->prefix_id,$data_numeration);
						}
						if ($suspended == 0 && $cart->type_comprobante==3) {

							$prefix=$this->Number_invoice->get_numeration('boleta',$location_id_num);

							$serie=$prefix->prefijo./*$location_id_num.*/"-".$prefix->numeracion;

							$data_numeration = array(
								'numeracion' => ((int)$prefix->numeracion+1)
								 );

							$sales_data_sunat['detalle'][0]['NRO_COMPROBANTE']=$prefix->prefijo/*.$location_id_num*/."-".$prefix->numeracion;

							$data_sunat_re = array(
								'serie' 	=> $prefix->prefijo/*.$location_id_num*/."-".$prefix->numeracion,
								'sale_id'	=> $sale_id,
								'prefix_id'	=> $prefix->prefix_id
								 );

							$sunat = TRUE;

							$this->Number_invoice->save_sunat_serie($data_sunat_re);

							$this->Number_invoice->update_numeration($prefix->prefix_id,$data_numeration);

							/*** save and update numeracion ***/
							$tipo_comprobante="03";
							$serie_fecha = date('Ymd');
							$resumen=$this->Number_invoice->get_numeration_resumen_diario($serie_fecha);
							if ($resumen==null) {
								$sales_data_sunat['secuencia']=1;
								$data_numeration_b = array('prefijo'=>'RC','numeracion' => 2,'nombre'=>$serie_fecha);
								$this->Number_invoice->save_numeration_resumen_diario($data_numeration_b);
							}else{
								$sales_data_sunat['secuencia']=$resumen->numeracion;
								$data_numeration_b = array('numeracion' => ($resumen->numeracion+1));
								$this->Number_invoice->update_numeration_resumen($resumen->nombre,$data_numeration_b);
							}
						}
					}
					if ($sunat && $this->config->item('ruta_sunat')!='') {

						$ruta =  $this->config->item('ruta_sunat');

		                //Invocamos el servicio
						$token = $this->config->item('token'); //en caso quieras utilizar algún token generado desde tu sistema

						//codificamos la data
						$data_json = json_encode($sales_data_sunat);

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $ruta);
						curl_setopt(
							$ch, CURLOPT_HTTPHEADER, array(
							///'Authorization: Token token="'.$token.'"',
							'x-api-key:'.$token,
							'Content-Type: application/json',
							)
						);
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						$respuesta  = curl_exec($ch);
						curl_close($ch);
						//$response = json_decode($respuesta, true);
						//$fp = fopen('/var/www/html/jigeneral/data.txt', 'w');
						//fwrite($fp, $respuesta);
						//fclose($fp);
					}
			}else{
				//creo que no vale jijiji
				if (!$sale_id_exist) {
					//Sale prefix
					if ($suspended > 0) {
						$prefix=$this->Number_invoice->get_numeration('cotizacion',$location_id_num);
						$data_numeration = array(
							'numeracion' => ((int)$prefix->numeracion+1)
							 );
						$data_sunat_re = array(
							'serie' 	=> $prefix->prefijo/*.$location_id_num*/."-".$prefix->numeracion,
							'sale_id'	=> $sale_id,
							'prefix_id'	=> $prefix->prefix_id,
							'suspended'	=> 1
							 );
						$this->Number_invoice->save_sunat_serie($data_sunat_re);
						$this->Number_invoice->update_numeration($prefix->prefix_id,$data_numeration);

					}
				}
			}
		}
		
		//store_accounts_paid_sales
		$paid_sales = $cart->get_paid_store_account_ids();
		if (!empty($paid_sales))
		{
			foreach(array_keys($cart->get_paid_store_account_ids()) as $sale_id_paid)
			{
				$this->db->insert('store_accounts_paid_sales',array('sale_id' => $sale_id_paid,'store_account_payment_sale_id' => $sale_id));
			}
		}
		
		//Loyalty systems
		 if (!$cart->is_ecommerce && $suspended < 2 && $customer_id > 0 && $this->config->item('enable_customer_loyalty_system'))
		 {
		   $sales_data_loy = array();	 
		   $customer_info = $this->Customer->get_info($customer_id);
 		
		if(!$customer_info->disable_loyalty)
		{
			if ($this->config->item('loyalty_option') == 'simple')
			{
				if (!$store_account_payment)
				{
					if ($cart->redeem_discount)
					{
						$this->db->where('person_id', $customer_id);
						$this->db->set('current_sales_for_discount','current_sales_for_discount -'.$this->config->item('number_of_sales_for_discount'),false);
						$this->db->update('customers');
						$sales_data_loy['did_redeem_discount'] = 1;				
					}
					else
					{
						$this->db->where('person_id', $customer_id);
						$this->db->set('current_sales_for_discount','current_sales_for_discount +1',false);
						$this->db->update('customers');
					}
				}
			}//End simple
			else
			{
				$current_points = $customer_info->points;
				$current_spend_for_points = $customer_info->current_spend_for_points;
			
				//This is duplicated below; but this is ok so we don't break anything else
				$giftcard_payments_amount = 0;
				foreach($payments as $payment_id=>$payment)
				{
					if ( substr( $payment->payment_type, 0, strlen( lang('common_giftcard') ) ) == lang('common_giftcard') )
					{
						$giftcard_payments_amount+=$payment->payment_amount;
					}
				}
			
				//Don't count points or gift cards or excluded items
				$excluded_items_total = $this->get_excluded_loyalty_items_total(FALSE,$cart);
				
				$sale_total_with_or_without_tax = $this->config->item('loyalty_points_without_tax') ? $cart->get_subtotal() : $cart->get_total();
				$total_spend_for_sale = $sale_total_with_or_without_tax - $cart->get_payment_amount(lang('common_points')) - $giftcard_payments_amount - $excluded_items_total;
	         	
				list($spend_amount_for_points, $points_to_earn) = explode(":",$this->config->item('spend_to_point_ratio'),2);
		
				if (!$store_account_payment && $total_spend_for_sale != 0)
				{
					//If we earn any points
					if ($current_spend_for_points + abs($total_spend_for_sale) >= $spend_amount_for_points)
					{
						$total_amount_towards_points = $current_spend_for_points + abs($total_spend_for_sale);
						$new_points = (((($total_amount_towards_points)-fmod(($total_amount_towards_points), $spend_amount_for_points))/$spend_amount_for_points) * $points_to_earn);
						
						if ($total_spend_for_sale >= 0)
						{
							$new_point_value = $current_points + $new_points;					
						}
						else
						{
							$new_point_value = $current_points - $new_points;							
						}
						
						$new_current_spend_for_points = fmod(($current_spend_for_points + $total_spend_for_sale),$spend_amount_for_points);
					}
					else
					{
						$new_current_spend_for_points = $current_spend_for_points + $total_spend_for_sale;
						$new_point_value = $current_points;
					}
			
					$sales_data_loy['points_gained'] = (int)($new_point_value -  $current_points); 
				}
				else //Don't change any values for store account payment
				{
					$new_current_spend_for_points = $current_spend_for_points;
					$new_point_value = $current_points;
				}
		
				//Redeem points
				if ($payment_amount_points = $cart->get_payment_amount(lang('common_points')))
				{
					$points_used = ceil(to_currency_no_money($payment_amount_points / $this->config->item('point_value')));
					$new_point_value -= $points_used;
					$sales_data_loy['points_used'] = (int)$points_used;
			
				}
				else
				{
					$sales_data_loy['points_used'] = 0;
				}
		
				$new_point_value = (int) round(to_currency_no_money($new_point_value));
				$new_current_spend_for_points = to_currency_no_money($new_current_spend_for_points);
		
				$this->db->where('person_id', $customer_id);
				$this->db->update('customers', array('points' => $new_point_value, 'current_spend_for_points' => $new_current_spend_for_points));				
			 }
		 	
			if(!empty($sales_data_loy))
			{
				$this->db->where('sale_id', $sale_id);
				$this->db->update('sales', $sales_data_loy);
			}
		 }
	 }//End loyalty
 
		 				
		//Only update store account payments if we are NOT an estimate (suspended = 2)
		if (!$cart->is_ecommerce && $suspended < 2)
		{
			// Our customer switched from before; add special logic
			if ($balance && $before_save_sale_info && $before_save_sale_info->customer_id && $before_save_sale_info->customer_id != $customer_id)
			{
				$store_account_transaction = array(
				   'customer_id'=>$customer_id,
				   'sale_id'=>$sale_id,
					'comment'=>$comment,
				   'transaction_amount'=>$balance,
					'balance'=>$this->Customer->get_info($customer_id)->balance,
					'date' => date('Y-m-d H:i:s')
				);

				$this->db->insert('store_accounts',$store_account_transaction);
				
				
				$store_account_transaction = array(
				   'customer_id'=>$before_save_sale_info->customer_id,
				   'sale_id'=>$sale_id,
					'comment'=>$comment,
				   'transaction_amount'=>-$previous_store_account_amount,
					'balance'=>$this->Customer->get_info($before_save_sale_info->customer_id)->balance,
					'date' => date('Y-m-d H:i:s')
				);

				$this->db->insert('store_accounts',$store_account_transaction);
				
			}
			elseif($customer_id > 0 && $balance)
			{
			 	$store_account_transaction = array(
			      'customer_id'=>$customer_id,
			      'sale_id'=>$sale_id,
					'comment'=>$comment,
			      'transaction_amount'=>$balance - $previous_store_account_amount,
					'balance'=>$this->Customer->get_info($customer_id)->balance,
					'date' => date('Y-m-d H:i:s')
				);
				
				if ($balance - $previous_store_account_amount)
				{
					$this->db->insert('store_accounts',$store_account_transaction);
				}
			 } 
			 elseif ($customer_id > 0 && $previous_store_account_amount) //We had a store account payment before has one...We need to log this
			 {
 			 	$store_account_transaction = array(
 			      'customer_id'=>$customer_id,
 			      'sale_id'=>$sale_id,
 					'comment'=>$comment,
 			      'transaction_amount'=> -$previous_store_account_amount,
 					'balance'=>$this->Customer->get_info($customer_id)->balance,
 					'date' => date('Y-m-d H:i:s')
 				);

 				$this->db->insert('store_accounts',$store_account_transaction);
				
			 } //We switched customers for a sale
			 //insert store account payment transaction 
			if($customer_id > 0 && $store_account_payment)
			{
			 	$store_account_transaction = array(
			        'customer_id'=>$customer_id,
			        'sale_id'=>$sale_id,
					'comment'=>$comment,
			       	'transaction_amount'=> -$store_account_payment_amount,
					'balance'=>$this->Customer->get_info($customer_id)->balance,
					'date' => date('Y-m-d H:i:s')
				);

				$this->db->insert('store_accounts',$store_account_transaction);
			 }
		 }
		 
		$total_giftcard_payments = 0;

		foreach($payments as $payment_id=>$payment)
		{
			//Only update giftcard payments if we are NOT an estimate (suspended = 2)
			if (!$cart->is_ecommerce && $suspended < 2)
			{
				if ( substr( $payment->payment_type, 0, strlen( lang('common_giftcard') ) ) == lang('common_giftcard') )
				{
					/* We have a gift card and we have to deduct the used value from the total value of the card. */
					$splitpayment = explode( ':', $payment->payment_type );
					$cur_giftcard_value = $this->Giftcard->get_giftcard_value( $splitpayment[1] );
	
					$this->Giftcard->update_giftcard_value( $splitpayment[1], $cur_giftcard_value - $payment->payment_amount );
					$total_giftcard_payments+=$payment->payment_amount;
					
					$this->Giftcard->log_modification(array('sale_id' => $sale_id, "number" => $splitpayment[1], "person" => lang('common_customer'), "old_value" => $cur_giftcard_value, "new_value" => $cur_giftcard_value - $payment->payment_amount, "type" => 'sale'));
					
				}
			}

			$sales_payments_data = array
			(
				'sale_id'=>$sale_id,
				'payment_type'=>$payment->payment_type,
				'payment_amount'=>$payment->payment_amount,
				'payment_date' => isset($override_payment_time) ? $override_payment_time: $payment->payment_date,
				'truncated_card' => $payment->truncated_card,
				'card_issuer' => $payment->card_issuer,
				'auth_code' => $payment->auth_code,
				'ref_no' => $payment->ref_no,
				'cc_token' => $payment->cc_token,
				'acq_ref_data' => $payment->acq_ref_data,
				'process_data' => $payment->process_data,
				'entry_method' => $payment->entry_method,
				'aid' => $payment->aid,
				'tvr' => $payment->tvr,
				'iad' => $payment->iad,
				'tsi' => $payment->tsi,
				'arc' => $payment->arc,
				'cvm' => $payment->cvm,
				'tran_type' => $payment->tran_type,
				'application_label' => $payment->application_label,
				'ebt_voucher_no' => $payment->ebt_voucher_no,
				'ebt_auth_code' => $payment->ebt_auth_code,
			);
			
			$this->db->insert('sales_payments',$sales_payments_data);
		}
	
		$has_added_giftcard_value_to_cost_price = $total_giftcard_payments > 0 ? false : true;
		$has_added_points_value_to_cost_price = $cart->get_payment_amount(lang('common_points')) > 0 ? false : true;
		
		$store_account_item_id = $this->Item->get_store_account_item_id();
		
		foreach($items as $line=>$item)
		{			
			$sale_item_subtotal = $item->get_subtotal();
			
			$sale_item_total = $item->get_total();
			$sale_item_tax = $sale_item_total - $sale_item_subtotal;
			
			if (property_exists($item,'item_id'))
			{
				
				if ($item->is_series_package)
				{
					$this->load->model('Customer');
					
					for($k=1;$k<=floor($item->quantity);$k++)
					{
						$series_to_add[$line.'|'.$k] = array(
						 	'item_id' =>$item->item_id,
						  'expire_date' =>date('Y-m-d',strtotime('+ '.$item->series_days_to_use_within.' days')),
						  'quantity_remaining' => $item->series_quantity,
						  'customer_id' => $customer_id,
						);

					}
				}
				
				$cur_item_info = $this->Item->get_info($item->item_id);
				$cur_item_location_info = $this->Item_location->get_info($item->item_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
				//Redeem profit when giftcard is used; so we set cost price to item price
				if ($item->name==lang('common_giftcard') && !$this->Giftcard->get_giftcard_id($item->description) && $this->config->item('calculate_profit_for_giftcard_when') == 'redeeming_giftcard')
				{
					$cost_price = $item->unit_price;					
				}
				elseif(($this->config->item('remove_points_from_profit') && !$has_added_points_value_to_cost_price) || ($this->config->item('remove_points_from_profit') && !$is_new_sale) || ($this->config->item('calculate_profit_for_giftcard_when') == 'selling_giftcard' && !$has_added_giftcard_value_to_cost_price && !$is_new_sale))
				{					
					$cost_price = $cur_item_location_info->cost_price ? $cur_item_location_info->cost_price : $cur_item_info->cost_price;
				}
				elseif ($item->item_id != $store_account_item_id)
				{
					$cost_price = $item->cost_price;
				}
				else // Set cost price = price so we have no profit
				{
					$cost_price = $item->unit_price;
				}
				
				
				if ($this->config->item('calculate_profit_for_giftcard_when') == 'selling_giftcard')
				{
					//Add to the cost price if we are using a giftcard as we have already recorded profit for sale of giftcard
					if (!$has_added_giftcard_value_to_cost_price)
					{
						$cost_price+= $total_giftcard_payments / $item->quantity;
						$has_added_giftcard_value_to_cost_price = true;
					}
				}
				
				if($this->config->item('remove_points_from_profit') && !$has_added_points_value_to_cost_price || ($this->config->item('remove_points_from_profit') && !$is_new_sale))
				{
					$cost_price += $cart->get_payment_amount(lang('common_points')) / $item->quantity;
					$has_added_points_value_to_cost_price = true;
				}
				
				if ($item->variation_id)
				{
					$cur_item_variation_info = $this->Item_variations->get_info($item->variation_id);
					$cur_item_variation_location_info = $this->Item_variation_location->get_info($item->variation_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
					$reorder_level = ($cur_item_variation_location_info && $cur_item_variation_location_info->reorder_level) ? $cur_item_variation_location_info->reorder_level : $cur_item_variation_info->reorder_level;
				}
				else
				{
					$reorder_level = ($cur_item_location_info && $cur_item_location_info->reorder_level) ? $cur_item_location_info->reorder_level : $cur_item_info->reorder_level;
				}
				if ($cur_item_info->tax_included)
				{
					$this->load->helper('items');
					$item->unit_price = get_price_for_item_excluding_taxes($item->item_id, $item->unit_price);
				}
				
				$item->cost_price = $cost_price;
				$sale_item_profit = $item->get_profit();
				$sale_profit+=$sale_item_profit;
				$this->load->helper('items');
				
				$line_item_commission = get_commission_for_item($cart,$item->item_id,$item->unit_price,to_currency_no_money($cost_price,10), $item->quantity, $item->discount);
				$sale_commission+=$line_item_commission;
				$sales_items_data = array
				(
					'sale_id'=>$sale_id,
					'item_id'=>$item->item_id,
					'item_variation_id' => $item->variation_id ? $item->variation_id : NULL,
					'line'=>$line,
					'description'=>$item->description,
					'serialnumber'=>$item->serialnumber,
					'quantity_purchased'=>$item->quantity,
					'discount_percent'=>$item->discount,
					'item_cost_price' =>  $global_weighted_average_cost === FALSE ? to_currency_no_money($cost_price,10) : $global_weighted_average_cost,
					'item_unit_price'=>$item->unit_price,
					'regular_item_unit_price_at_time_of_sale' =>$item->regular_price,
					'commission' => $line_item_commission,
					'subtotal' => $sale_item_subtotal,
					'total' => $sale_item_total,
					'tax' => $sale_item_tax,
					'profit' =>$sale_item_profit,
					'tier_id' => $item->tier_id ? $item->tier_id : NULL,
				);
				
				
				
				if ($item->serialnumber)
				{
					$this->Item_serial_number->delete_serial($item->item_id, $item->serialnumber);
				}
				
				if (isset($item->rule['rule_id']) && isset($item->rule['rule_discount']))
				{
					$sales_items_data['rule_id'] = $item->rule['rule_id'];
					$sales_items_data['rule_discount'] = $item->rule['rule_discount'];
				}
				//Save to tick last modifed for sync
				$empty_data = array();
				$this->Item->save($empty_data,$item->item_id);
				
				$this->db->insert('sales_items',$sales_items_data);
				
				//Only update giftcard payments if we are NOT an estimate (suspended = 2)
				if (!$cart->is_ecommerce && $suspended < 2)
				{
					
					//create points from sale
					if ($item->name==lang('common_purchase_points'))
					{
					  $this->db->set('points','points+'.$item->quantity,false);
					  $this->db->where('person_id', $customer_id);
					  $this->db->update('customers');
					}
					
					//create giftcard from sales 
					if($item->name==lang('common_giftcard') && !$this->Giftcard->get_giftcard_id($item->description)) 
					{ 
						$giftcard_data = array(
							'giftcard_number'=>$item->description,
							'value'=>$item->unit_price,
							'description' => $comment,
							'customer_id'=>$customer_id > 0 ? $customer_id : null,
						);
												
						$this->Giftcard->save($giftcard_data);
						
						$employee_info = $cart->employee_id ? $this->Employee->get_info($cart->employee_id) : $this->Employee->get_logged_in_employee_info();
						$this->Giftcard->log_modification(array('sale_id' => $sale_id, "number" => $item->description, "person"=>$employee_info->first_name . " " . $employee_info->last_name, "new_value" => $item->unit_price, 'old_value' => 0, "type" => 'create'));
					}
				}
				
				//Only do stock check + inventory update if we are NOT an estimate
				if (!$cart->is_ecommerce && $suspended < 2)
				{
					$stock_recorder_check=false;
					$out_of_stock_check=false;
					$email=false;
					$message = '';
					
					if ($item->variation_id)
					{	
						//checks if the quantity is greater than reorder level
						if(!$cur_item_info->is_service && $cur_item_variation_location_info->quantity > $reorder_level)
						{
							$stock_recorder_check=true;
						}
				
						//checks if the quantity is greater than 0
						if(!$cur_item_info->is_service && $cur_item_variation_location_info->quantity > 0)
						{
							$out_of_stock_check=true;
						}
				
						//Update stock quantity IF not a service 
						if (!$cur_item_info->is_service)
						{
							$cur_item_variation_location_info->quantity = $cur_item_variation_location_info->quantity !== '' ? $cur_item_variation_location_info->quantity : 0;
							$this->Item_variation_location->save_quantity($cur_item_variation_location_info->quantity - $item->quantity, $item->variation_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
						}
				
						//Re-init $cur_item_variation_location_info after updating quantity
						$cur_item_variation_location_info = $this->Item_variation_location->get_info($item->variation_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
				
						//checks if the quantity is out of stock
						if($out_of_stock_check && $cur_item_variation_location_info->quantity <= 0)
						{
							$message= $cur_item_info->name.' '.$this->Item_variations->get_variation_name($item->variation_id).' '.lang('sales_is_out_stock').' '.to_quantity($cur_item_variation_location_info->quantity);
							if ($cur_item_info->item_id)
							{
								$message.="\n";
								$message.= lang('common_item_id').": ".$cur_item_info->item_id;
							}

							if ($cur_item_info->item_number)
							{
								$message.="\n";
								$message.= lang('common_item_number').": ".$cur_item_info->item_number;
							}

							if ($cur_item_info->product_id)
							{
								$message.="\n";
								$message.= lang('common_product_id').": ".$cur_item_info->product_id;
							}
							
							if ($cur_item_info->description)
							{
								$message.="\n";
								$message.= lang('common_description').": ".$cur_item_info->description;
							}
						
							$email=true;
					
						}	
						//checks if the quantity hits reorder level 
						else if($stock_recorder_check && ($cur_item_variation_location_info->quantity <= $reorder_level))
						{
							$message= $cur_item_info->name.' '.$this->Item_variations->get_variation_name($item->variation_id).' '.lang('sales_hits_reorder_level').' '.to_quantity($cur_item_variation_location_info->quantity);
							if ($cur_item_info->item_id)
							{
								$message.="\n";
								$message.= lang('common_item_id').": ".$cur_item_info->item_id;
							}

							if ($cur_item_info->item_number)
							{
								$message.="\n";
								$message.= lang('common_item_number').": ".$cur_item_info->item_number;
							}

							if ($cur_item_info->product_id)
							{
								$message.="\n";
								$message.= lang('common_product_id').": ".$cur_item_info->product_id;
							}

							if ($cur_item_info->description)
							{
								$message.="\n";
								$message.= lang('common_description').": ".$cur_item_info->description;
							}
							
						
							$email=true;
						}
				
						//send email 
						if($this->Location->get_info_for_key('receive_stock_alert',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) && $email)
						{			
							$this->load->library('email');
							$config = array();
							$config['mailtype'] = 'text';				
							$this->email->initialize($config);
							$this->email->from($this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) ? $this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) : 'no-reply@mg.phppointofsale.com', $this->config->item('company'));
							$this->email->to($this->Location->get_info_for_key('stock_alert_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) ? $this->Location->get_info_for_key('stock_alert_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) : $this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id())); 
							
							if($this->Location->get_info_for_key('cc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()))
							{
								$this->email->cc($this->Location->get_info_for_key('cc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()));
							}
				
							if($this->Location->get_info_for_key('bcc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()))
							{
								$this->email->bcc($this->Location->get_info_for_key('bcc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()));
							}
							
							if ($this->Location->count_all() > 1)
							{
								$message.="\n\n".lang("common_location").': '.$this->Location->get_info_for_key('name',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
							}
						
							$this->email->subject(lang('sales_stock_alert_item_name').' '.$this->Item->get_info($item->item_id)->name.' '.$this->Item_variations->get_variation_name($item->variation_id));
							$this->email->message($message);	
							$this->email->send();
						}
					
						if (!$cur_item_info->is_service)
						{
							$qty_buy = -$item->quantity;
							$sale_remarks =$this->config->item('sale_prefix').' '.$sale_id;

							$inv_data = array
							(
								'trans_date'=>date('Y-m-d H:i:s'),
								'trans_items'=>$item->item_id,
								'trans_user'=>$employee_id,
								'trans_comment'=>$sale_remarks,
								'trans_inventory'=>$qty_buy,
								'location_id' => $cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id(),
								'item_variation_id' => $item->variation_id,
								'trans_current_quantity' => $cur_item_variation_location_info->quantity,
							);
							$this->Inventory->insert($inv_data);
						}
					}
					else
					{
						//checks if the quantity is greater than reorder level
						if(!$cur_item_info->is_service && $cur_item_location_info->quantity > $reorder_level)
						{
							$stock_recorder_check=true;
						}
				
						//checks if the quantity is greater than 0
						if(!$cur_item_info->is_service && $cur_item_location_info->quantity > 0)
						{
							$out_of_stock_check=true;
						}
				
						//Update stock quantity IF not a service 
						if (!$cur_item_info->is_service)
						{
							$cur_item_location_info->quantity = $cur_item_location_info->quantity !== '' ? $cur_item_location_info->quantity : 0;
							$this->Item_location->save_quantity($cur_item_location_info->quantity - $item->quantity, $item->item_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
						}
				
						//Re-init $cur_item_location_info after updating quantity
						$cur_item_location_info = $this->Item_location->get_info($item->item_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
				
						//checks if the quantity is out of stock
						if($out_of_stock_check && $cur_item_location_info->quantity <= 0)
						{
							$message= $cur_item_info->name.' '.lang('sales_is_out_stock').' '.to_quantity($cur_item_location_info->quantity);
							if ($cur_item_info->item_id)
							{
								$message.="\n";
								$message.= lang('common_item_id').": ".$cur_item_info->item_id;
							}

							if ($cur_item_info->item_number)
							{
								$message.="\n";
								$message.= lang('common_item_number').": ".$cur_item_info->item_number;
							}

							if ($cur_item_info->product_id)
							{
								$message.="\n";
								$message.= lang('common_product_id').": ".$cur_item_info->product_id;
							}
							
							if ($cur_item_info->description)
							{
								$message.="\n";
								$message.= lang('common_description').": ".$cur_item_info->description;
							}
							
						
							$email=true;
					
						}	
						//checks if the quantity hits reorder level 
						else if($stock_recorder_check && ($cur_item_location_info->quantity <= $reorder_level))
						{
							$message= $cur_item_info->name.' '.lang('sales_hits_reorder_level').' '.to_quantity($cur_item_location_info->quantity);
							if ($cur_item_info->item_id)
							{
								$message.="\n";
								$message.= lang('common_item_id').": ".$cur_item_info->item_id;
							}

							if ($cur_item_info->item_number)
							{
								$message.="\n";
								$message.= lang('common_item_number').": ".$cur_item_info->item_number;
							}

							if ($cur_item_info->product_id)
							{
								$message.="\n";
								$message.= lang('common_product_id').": ".$cur_item_info->product_id;
							}
						
							if ($cur_item_info->description)
							{
								$message.="\n";
								$message.= lang('common_description').": ".$cur_item_info->description;
							}
						
						
							$email=true;
						}
				
						//send email 
						if($this->Location->get_info_for_key('receive_stock_alert') && $email)
						{			
							$this->load->library('email');
							$config = array();
							$config['mailtype'] = 'text';				
							$this->email->initialize($config);
							$this->email->from($this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) ? $this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) : 'no-reply@mg.phppointofsale.com', $this->config->item('company'));
							$this->email->to($this->Location->get_info_for_key('stock_alert_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) ? $this->Location->get_info_for_key('stock_alert_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) : $this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id())); 
							
							if($this->Location->get_info_for_key('cc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()))
							{
								$this->email->cc($this->Location->get_info_for_key('cc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()));
							}
				
							if($this->Location->get_info_for_key('bcc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()))
							{
								$this->email->bcc($this->Location->get_info_for_key('bcc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()));
							}
							
							if ($this->Location->count_all() > 1)
							{
								$message.="\n\n".lang("common_location").': '.$this->Location->get_info_for_key('name');
							}
						
							$this->email->subject(lang('sales_stock_alert_item_name').$this->Item->get_info($item->item_id)->name);
							$this->email->message($message);	
							$this->email->send();
						}
					
						if (!$cur_item_info->is_service)
						{
							$qty_buy = -$item->quantity;
							$sale_remarks =$this->config->item('sale_prefix').' '.$sale_id;

							$inv_data = array
							(
								'trans_date'=>date('Y-m-d H:i:s'),
								'trans_items'=>$item->item_id,
								'trans_user'=>$employee_id,
								'trans_comment'=>$sale_remarks,
								'trans_inventory'=>$qty_buy,
								'location_id' => $cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id(),
								'trans_current_quantity' => $cur_item_location_info->quantity, 
							);
							$this->Inventory->insert($inv_data);
						}
					}
				}
			}
			else
			{
				$cur_item_kit_info = $this->Item_kit->get_info($item->item_kit_id);
				$cur_item_kit_location_info = $this->Item_kit_location->get_info($item->item_kit_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());


				if(($this->config->item('remove_points_from_profit') && !$has_added_points_value_to_cost_price) || ($this->config->item('remove_points_from_profit') && !$is_new_sale) || ($this->config->item('calculate_profit_for_giftcard_when') == 'selling_giftcard' && !$has_added_giftcard_value_to_cost_price && !$is_new_sale))
				{
					$cost_price = $cur_item_kit_location_info->cost_price ? $cur_item_kit_location_info->cost_price : $cur_item_kit_info->cost_price;
				}
				else
				{
					$cost_price = $item->cost_price;					
				}
				
				if ($this->config->item('calculate_profit_for_giftcard_when') == 'selling_giftcard')
				{
					//Add to the cost price if we are using a giftcard as we have already recorded profit for sale of giftcard
					if (!$has_added_giftcard_value_to_cost_price)
					{
						$cost_price+= $total_giftcard_payments / $item->quantity;
						$has_added_giftcard_value_to_cost_price = true;
					}
				}
				
				if ($this->config->item('remove_points_from_profit') && !$has_added_points_value_to_cost_price)
				{
					$cost_price += $cart->get_payment_amount(lang('common_points')) / $item->quantity;
					$has_added_points_value_to_cost_price = true;
				}
				
				
				if ($cur_item_kit_info->tax_included)
				{
					$this->load->helper('item_kits');
					$item->unit_price = get_price_for_item_kit_excluding_taxes($item->item_kit_id, $item->unit_price);
				}
				
				$item->cost_price = $cost_price;
				$sale_item_profit = $item->get_profit();
				$sale_profit+=$sale_item_profit;
				
				
				$this->load->helper('item_kits');
				
				$line_item_commission = get_commission_for_item_kit($cart,$item->item_kit_id,$item->unit_price,$cost_price === NULL ? 0.00 : to_currency_no_money($cost_price,10), $item->quantity, $item->discount);
				$sale_commission+=$line_item_commission;
				
				$sales_item_kits_data = array
				(
					'sale_id'=>$sale_id,
					'item_kit_id'=>$item->item_kit_id,
					'line'=>$line,
					'description'=>$item->description,
					'quantity_purchased'=>$item->quantity,
					'discount_percent'=>$item->discount,
					'item_kit_cost_price' => $global_weighted_average_cost === FALSE ? ($cost_price === NULL ? 0.00 : to_currency_no_money($cost_price,10)) : $global_weighted_average_cost,
					'item_kit_unit_price'=>$item->unit_price,
					'regular_item_kit_unit_price_at_time_of_sale' =>$item->regular_price,
					'commission' => $line_item_commission,
					'subtotal' => $sale_item_subtotal,
					'total' => $sale_item_total,
					'tax' => $sale_item_tax,
					'profit' =>$sale_item_profit,	
					'tier_id' => $item->tier_id ? $item->tier_id : NULL,
				);


				if (isset($item->rule['rule_id']))
				{
					$sales_item_kits_data['rule_id'] = $item->rule['rule_id'];
					$sales_item_kits_data['rule_discount'] = $item->rule['rule_discount'];
				}
				$this->db->insert('sales_item_kits',$sales_item_kits_data);
				
				foreach($this->Item_kit_items->get_info($item->item_kit_id) as $item_kit_item)
				{
					$cur_item_info = $this->Item->get_info($item_kit_item->item_id);
					$cur_item_location_info = $this->Item_location->get_info($item_kit_item->item_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
					
					if ($item_kit_item->item_variation_id)
					{
						
						$cur_item_variation_info = $this->Item_variations->get_info($item_kit_item->item_variation_id);
						$cur_item_variation_location_info = $this->Item_variation_location->get_info($item_kit_item->item_variation_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
						$reorder_level = ($cur_item_variation_location_info && $cur_item_variation_location_info->reorder_level) ? $cur_item_variation_location_info->reorder_level : $cur_item_variation_info->reorder_level;
						
						$stock_recorder_check=false;
						$out_of_stock_check=false;
						$email=false;
						$message = '';
						
						
						//checks if the quantity is greater than reorder level
						if(!$cur_item_info->is_service && $cur_item_variation_location_info->quantity > $reorder_level)
						{
							$stock_recorder_check=true;
						}
				
						//checks if the quantity is greater than 0
						if(!$cur_item_info->is_service && $cur_item_variation_location_info->quantity > 0)
						{
							$out_of_stock_check=true;
						}
				
						//Update stock quantity IF not a service 
						if (!$cur_item_info->is_service)
						{
							$cur_item_variation_location_info->quantity = $cur_item_variation_location_info->quantity !== '' ? $cur_item_variation_location_info->quantity : 0;
							$this->Item_variation_location->save_quantity($cur_item_variation_location_info->quantity - $item->quantity * $item_kit_item->quantity, $item_kit_item->item_variation_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
						}
				
						//Re-init $cur_item_variation_location_info after updating quantity
						$cur_item_variation_location_info = $this->Item_variation_location->get_info($item_kit_item->item_variation_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
				
						//checks if the quantity is out of stock
						if($out_of_stock_check && $cur_item_variation_location_info->quantity <= 0)
						{
							$message= $cur_item_info->name.' '.$this->Item_variations->get_variation_name($item_kit_item->item_variation_id).' '.lang('sales_is_out_stock').' '.to_quantity($cur_item_variation_location_info->quantity);
							if ($cur_item_info->item_id)
							{
								$message.="\n";
								$message.= lang('common_item_id').": ".$cur_item_info->item_id;
							}

							if ($cur_item_info->item_number)
							{
								$message.="\n";
								$message.= lang('common_item_number').": ".$cur_item_info->item_number;
							}

							if ($cur_item_info->product_id)
							{
								$message.="\n";
								$message.= lang('common_product_id').": ".$cur_item_info->product_id;
							}
							
							if ($cur_item_info->description)
							{
								$message.="\n";
								$message.= lang('common_description').": ".$cur_item_info->description;
							}
							
						
							$email=true;
					
						}	
						//checks if the quantity hits reorder level 
						else if($stock_recorder_check && ($cur_item_variation_location_info->quantity <= $reorder_level))
						{
							$message= $cur_item_info->name.' '.$this->Item_variations->get_variation_name($item_kit_item->item_variation_id).' '.lang('sales_hits_reorder_level').' '.to_quantity($cur_item_variation_location_info->quantity);
							if ($cur_item_info->item_id)
							{
								$message.="\n";
								$message.= lang('common_item_id').": ".$cur_item_info->item_id;
							}

							if ($cur_item_info->item_number)
							{
								$message.="\n";
								$message.= lang('common_item_number').": ".$cur_item_info->item_number;
							}

							if ($cur_item_info->product_id)
							{
								$message.="\n";
								$message.= lang('common_product_id').": ".$cur_item_info->product_id;
							}
							
							if ($cur_item_info->description)
							{
								$message.="\n";
								$message.= lang('common_description').": ".$cur_item_info->description;
							}
							
						
							$email=true;
						}
				
						//send email 
						if($this->Location->get_info_for_key('receive_stock_alert') && $email)
						{			
							$this->load->library('email');
							$config = array();
							$config['mailtype'] = 'text';				
							$this->email->initialize($config);
							$this->email->from($this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) ? $this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) : 'no-reply@mg.phppointofsale.com', $this->config->item('company'));
							$this->email->to($this->Location->get_info_for_key('stock_alert_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) ? $this->Location->get_info_for_key('stock_alert_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) : $this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id())); 
							
							if($this->Location->get_info_for_key('cc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()))
							{
								$this->email->cc($this->Location->get_info_for_key('cc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()));
							}
				
							if($this->Location->get_info_for_key('bcc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()))
							{
								$this->email->bcc($this->Location->get_info_for_key('bcc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()));
							}
							
							if ($this->Location->count_all() > 1)
							{
								$message.="\n\n".lang("common_location").': '.$this->Location->get_info_for_key('name',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
							}
						
							$this->email->subject(lang('sales_stock_alert_item_name').' '.$this->Item->get_info($item_kit_item->item_id)->name.' '.$this->Item_variations->get_variation_name($item_kit_item->item_variation_id));
							$this->email->message($message);	
							$this->email->send();
						}
					
						if (!$cur_item_info->is_service)
						{
							$qty_buy = -$item->quantity * $item_kit_item->quantity;
							$sale_remarks =$this->config->item('sale_prefix').' '.$sale_id;

							$inv_data = array
							(
								'trans_date'=>date('Y-m-d H:i:s'),
								'trans_items'=>$item_kit_item->item_id,
								'trans_user'=>$employee_id,
								'trans_comment'=>$sale_remarks,
								'trans_inventory'=>$qty_buy,
								'location_id' => $cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id(),
								'item_variation_id' => $item_kit_item->item_variation_id,
								'trans_current_quantity' => $cur_item_variation_location_info->quantity, 
							);
							$this->Inventory->insert($inv_data);
						}

					}
					else
					{
						$reorder_level = ($cur_item_location_info && $cur_item_location_info->reorder_level !== NULL) ? $cur_item_location_info->reorder_level : $cur_item_info->reorder_level;
					
						//Only do stock check + inventory update if we are NOT an estimate
						if (!$cart->is_ecommerce && $suspended < 2)
						{
							$stock_recorder_check=false;
							$out_of_stock_check=false;
							$email=false;
							$message = '';


							//checks if the quantity is greater than reorder level
							if(!$cur_item_info->is_service && $cur_item_location_info->quantity > $reorder_level)
							{
								$stock_recorder_check=true;
							}

							//checks if the quantity is greater than 0
							if(!$cur_item_info->is_service && $cur_item_location_info->quantity > 0)
							{
								$out_of_stock_check=true;
							}

							//Update stock quantity IF not a service item and the quantity for item is NOT NULL
							if (!$cur_item_info->is_service)
							{
								$cur_item_location_info->quantity = $cur_item_location_info->quantity !== '' ? $cur_item_location_info->quantity : 0;
								
								$this->Item_location->save_quantity($cur_item_location_info->quantity - ($item->quantity * $item_kit_item->quantity),$item_kit_item->item_id,$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
							}
					
							//Re-init $cur_item_location_info after updating quantity
							$cur_item_location_info = $this->Item_location->get_info($item_kit_item->item_id);
				
							//checks if the quantity is out of stock
							if($out_of_stock_check && !$cur_item_info->is_service && $cur_item_location_info->quantity <= 0)
							{
								$message= $cur_item_info->name.' '.lang('sales_is_out_stock').' '.to_quantity($cur_item_location_info->quantity);
								if ($cur_item_info->item_id)
								{
									$message.="\n";
									$message.= lang('common_item_id').": ".$cur_item_info->item_id;
								}

								if ($cur_item_info->item_number)
								{
									$message.="\n";
									$message.= lang('common_item_number').": ".$cur_item_info->item_number;
								}

								if ($cur_item_info->product_id)
								{
									$message.="\n";
									$message.= lang('common_product_id').": ".$cur_item_info->product_id;
								}
								
								if ($cur_item_info->description)
								{
									$message.="\n";
									$message.= lang('common_description').": ".$cur_item_info->description;
								}
								
								$email=true;

							}	
							//checks if the quantity hits reorder level 
							else if($stock_recorder_check && ($cur_item_location_info->quantity <= $reorder_level))
							{
								$message= $cur_item_info->name.' '.lang('sales_hits_reorder_level').' '.to_quantity($cur_item_location_info->quantity);
								if ($cur_item_info->item_id)
								{
									$message.="\n";
									$message.= lang('common_item_id').": ".$cur_item_info->item_id;
								}

								if ($cur_item_info->item_number)
								{
									$message.="\n";
									$message.= lang('common_item_number').": ".$cur_item_info->item_number;
								}

								if ($cur_item_info->product_id)
								{
									$message.="\n";
									$message.= lang('common_product_id').": ".$cur_item_info->product_id;
								}
								
								if ($cur_item_info->description)
								{
									$message.="\n";
									$message.= lang('common_description').": ".$cur_item_info->description;
								}
								
							
								$email=true;
							}

							//send email 
							if($this->Location->get_info_for_key('receive_stock_alert',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) && $email)
							{			
								$this->load->library('email');
								$config = array();
								$config['mailtype'] = 'text';				
								$this->email->initialize($config);
								$this->email->from($this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) ? $this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) : 'no-reply@mg.phppointofsale.com', $this->config->item('company'));
								$this->email->to($this->Location->get_info_for_key('stock_alert_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) ? $this->Location->get_info_for_key('stock_alert_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()) : $this->Location->get_info_for_key('email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id())); 
								
								if($this->Location->get_info_for_key('cc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()))
								{
									$this->email->cc($this->Location->get_info_for_key('cc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()));
								}
				
								if($this->Location->get_info_for_key('bcc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()))
								{
									$this->email->bcc($this->Location->get_info_for_key('bcc_email',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id()));
								}
								
								if ($this->Location->count_all() > 1)
								{
									$message.="\n\n".lang("common_location").': '.$this->Location->get_info_for_key('name',$cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id());
								}
								$this->email->subject(lang('sales_stock_alert_item_name').$cur_item_info->name);
								$this->email->message($message);	
								$this->email->send();
							}

							if (!$cur_item_info->is_service)
							{
								$qty_buy = -$item->quantity * $item_kit_item->quantity;
								$sale_remarks =$this->config->item('sale_prefix').' '.$sale_id;

								$inv_data = array
								(
									'trans_date'=>date('Y-m-d H:i:s'),
									'trans_items'=>$item_kit_item->item_id,
									'trans_user'=>$employee_id,
									'trans_comment'=>$sale_remarks,
									'trans_inventory'=>$qty_buy,
									'location_id' => $cart->location_id ? $cart->location_id : $this->Employee->get_logged_in_employee_current_location_id(),
									'trans_current_quantity' => $cur_item_location_info->quantity, 
									
								);
								$this->Inventory->insert($inv_data);
							}
						}
					}
				}
			}
			
			$customer = $this->Customer->get_info($customer_id);
 			if (!$customer_id || $customer->taxable)
 			{
				if (property_exists($item,'item_id'))
				{
					foreach($this->Item_taxes_finder->get_info($item->item_id) as $row)
					{
						$tax_name = $row['percent'].'% ' . $row['name'];
				
						//Only save sale if the tax has NOT been deleted
						if (!in_array($tax_name, $cart->get_excluded_taxes()))
						{	
							 $this->db->insert('sales_items_taxes', array(
								'sale_id' 	=>$sale_id,
								'item_id' 	=>$item->item_id,
								'line'      =>$line,
								'name'		=>$row['name'],
								'percent' 	=>$row['percent'],
								'cumulative'=>$row['cumulative']
							));
						}
					}
				}
				else
				{
					foreach($this->Item_kit_taxes_finder->get_info($item->item_kit_id) as $row)
					{
						$tax_name = $row['percent'].'% ' . $row['name'];
				
						//Only save sale if the tax has NOT been deleted
						if (!in_array($tax_name, $cart->get_excluded_taxes()))
						{
							$this->db->insert('sales_item_kits_taxes', array(
								'sale_id' 		=>$sale_id,
								'item_kit_id'	=>$item->item_kit_id,
								'line'      	=>$line,
								'name'			=>$row['name'],
								'percent' 		=>$row['percent'],
								'cumulative'	=>$row['cumulative']
							));
						}
					}					
				}
			}
		}
		//aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
		if ($this->config->item('remove_commission_from_profit_in_reports'))
		{
			$sale_profit-=$sale_commission;
		}
		
		$this->update(array('profit'=> $sale_profit),$sale_id);
				
		if ($coupons != NULL && !empty($coupons))
		{
			foreach($coupons as $coupon)
			{
				$coupon_data = array(
					'rule_id'=> $coupon['value'],
					'sale_id' => $sale_id,
				);
			
				$this->db->insert('sales_coupons', $coupon_data);
			}
		}
		
		if($cart->get_has_delivery())
		{
			$this->load->model('Person');
			$this->load->model('Delivery');
			
			$delivery_person_info = $cart->get_delivery_person_info();
			$delivery_info = $cart->get_delivery_info();
			
			$person_id = FALSE;
			if (isset($delivery_person_info['person_id']))
			{
				$person_id = $delivery_person_info['person_id'];
				unset($delivery_person_info['person_id']);
			}
			if($this->Person->save($delivery_person_info,$person_id))
			{
				$delivery_info['sale_id'] = $sale_id;
				$delivery_info['shipping_address_person_id'] = $person_id ? $person_id : $delivery_person_info['person_id'];
				
				if (!$delivery_info['shipping_address_person_id'])
				{
					$delivery_info['shipping_address_person_id'] = 1;
				}
				$delivery_info['tax_class_id'] = $cart->get_delivery_tax_group_id() ? $cart->get_delivery_tax_group_id() : NULL;
				
				if (!isset($delivery_info['shipping_method_id']) || !$delivery_info['shipping_method_id'])
				{
					$delivery_info['shipping_method_id'] = NULL;
				}

				if (!isset($delivery_info['shipping_zone_id']) || !$delivery_info['shipping_zone_id'])
				{
					$delivery_info['shipping_zone_id'] = NULL;
				}
				if((isset($delivery_info['estimated_shipping_date']) && $delivery_info['estimated_shipping_date']) || (isset($delivery_info['estimated_delivery_date']) && $delivery_info['estimated_delivery_date']))
				{
					$delivery_info['status'] = 'scheduled';
				}
				else
				{
					$delivery_info['status'] = 'not_scheduled';
				}
								
				$this->Delivery->save($delivery_info);
			}
		
		}
		
		
		$this->db->trans_complete();
		
		if ($this->db->trans_status() === FALSE)
		{
			return -1;
		}
			
		$this->Customer->delete_series_by_sale_id($sale_id);	
		foreach($series_to_add as $line_qty=>$series)
		{
			$series['sale_id'] = $sale_id;
			list($line,$qty) = explode('|',$line_qty);
			$series_id = $this->Customer->add_new_series($series);
			$this->db->where('sale_id',$sale_id);
			$this->db->where('line',$line);
			$this->db->where('item_id',$series['item_id']);
			$this->db->update('sales_items',array('series_id' => $series_id));
		}
		
		return $sale_id;				
	}
	
	function update_store_account($sale_id,$undelete=0)
	{
		//update if Store account payment exists
		$this->db->from('sales_payments');
		$store_account_in_all_languages = get_all_language_values_for_key('common_store_account','common');
		$this->db->where_in('payment_type', $store_account_in_all_languages);
		$this->db->where('sale_id',$sale_id);
		$to_be_paid_result = $this->db->get();
		
		$customer_id=$this->get_customer($sale_id)->person_id;
		
		
		if($to_be_paid_result->num_rows() >=1)
		{
			foreach($to_be_paid_result->result() as $to_be_paid)
			{
				if($to_be_paid->payment_amount) 
				{
					//update customer balance
					if($undelete==0)
					{
						$this->db->set('balance','balance-'.$to_be_paid->payment_amount,false);
					}
					else
					{
						$this->db->set('balance','balance+'.$to_be_paid->payment_amount,false);
					}
					$this->db->where('person_id', $customer_id);
					$this->db->update('customers'); 
				
				}
			}			
		}
	}
	
	function update_giftcard_balance($sale_id,$undelete=0)
	{
		//if gift card payment exists add the amount to giftcard balance
			$this->db->from('sales_payments');
			$this->db->like('payment_type',lang('common_giftcard'));
			$this->db->where('sale_id',$sale_id);
			$sales_payment = $this->db->get();
			
			if($sales_payment->num_rows() >=1)
			{
				foreach($sales_payment->result() as $row)
				{
					$giftcard_number=str_ireplace(lang('common_giftcard').':','',$row->payment_type);
					$cur_giftcard_value = $this->Giftcard->get_giftcard_value($giftcard_number);
					$value=$row->payment_amount;
					
					$value_to_add_subtract = 0;
					if($undelete==0)
					{
						$this->db->set('value','value+'.$value,false);
						$value_to_add_subtract = $value;		
					}
					else
					{
						$this->db->set('value','value-'.$value,false);
						$value_to_add_subtract = -$value;		
					}
					$this->db->where('giftcard_number', $giftcard_number);
					$this->db->update('giftcards'); 
					$this->Giftcard->log_modification(array('sale_id' => $sale_id, "number" => $giftcard_number, "old_value" => $cur_giftcard_value, "new_value" => $cur_giftcard_value + $value_to_add_subtract, "type" => $undelete ? 'sale_undelete' : 'sale_delete'));
				}
			}
	}
	
	function update_loyalty_simple_count($sale_id, $undelete=0)
	{
		$sale_info = $this->get_info($sale_id)->row_array();
		$store_account_payment = $sale_info['store_account_payment'];
		$customer_id = $sale_info['customer_id'];
		$suspended = $sale_info['suspended'];
		
	  $customer_info = $this->Customer->get_info($customer_id);
		
		if($customer_info->disable_loyalty)
		{
			return false;
		}
		
		
	 	if (!$store_account_payment && $suspended < 2 && $customer_id > 0 && $this->config->item('enable_customer_loyalty_system') && $this->config->item('loyalty_option') == 'simple')
		{
			if ($sale_info['did_redeem_discount'])
			{
				$this->db->where('person_id', $customer_id);
				$this->db->set('current_sales_for_discount','current_sales_for_discount'.($undelete ? ' - ' : ' + ').$this->config->item('number_of_sales_for_discount'),false);
				$this->db->update('customers');				
			}
			else
			{
				$this->db->where('person_id', $customer_id);
				$this->db->set('current_sales_for_discount','current_sales_for_discount'.($undelete ? ' + ' : ' - ').'1',false);
				$this->db->update('customers');				
			}
		}
	}
	function update_points($sale_id, $undelete=0)
	{
		$sale_info = $this->get_info($sale_id)->row_array();
		$store_account_payment = $sale_info['store_account_payment'];
		$customer_id = $sale_info['customer_id'];
		$suspended = $sale_info['suspended'];
	  $customer_info = $this->Customer->get_info($customer_id);
		
		if($customer_info->disable_loyalty)
		{
			return false;
		}
				
		 //Update points information if we have NOT a store account payment and not an estimate and we have a customer and we have loyalty enabled
		 if (!$store_account_payment && $suspended < 2 && $customer_id > 0 && $this->config->item('enable_customer_loyalty_system') && $this->config->item('loyalty_option') == 'advanced')
		 {
		   $customer_info = $this->Customer->get_info($customer_id);
			$current_points = $customer_info->points;
			$current_spend_for_points = $customer_info->current_spend_for_points;
			$total_spend_for_sale = $this->get_sale_total($sale_id,$this->config->item('loyalty_points_without_tax'));
			
			
			//Remove giftcard from spend
			$this->db->from('sales_payments');
			$this->db->like('payment_type',lang('common_giftcard'));
			$this->db->where('sale_id',$sale_id);
			$sales_payment = $this->db->get();
			
			if($sales_payment->num_rows() >=1)
			{
				foreach($sales_payment->result() as $row)
				{
					$total_spend_for_sale-=$row->payment_amount;
				}
			}
			
			$excluded_items_total = $this->get_excluded_loyalty_items_total($sale_id);

			//remove excluded_items amount
			$total_spend_for_sale-=$excluded_items_total;
			
			//update if Store account payment exists
			$this->db->from('sales_payments');
			$this->db->where('payment_type',lang('common_points'));
			$this->db->where('sale_id',$sale_id);
			$points_payment = $this->db->get()->row_array();
			
			$points_payment =	isset($points_payment['payment_amount']) ? $points_payment['payment_amount'] : 0;
			
			//We should NOT count point payments for adding/removing points as we will do this later (at the end of this function)
			$total_spend_for_sale-=$points_payment;
			
		   list($spend_amount_for_points, $points_to_earn) = explode(":",$this->config->item('spend_to_point_ratio'),2);
			
			if($undelete) //Put points back
			{
				//If we earn any points
				if ($current_spend_for_points + abs($total_spend_for_sale) >= $spend_amount_for_points)
				{
					$total_amount_towards_points = $current_spend_for_points + abs($total_spend_for_sale);
					$new_points = (((($total_amount_towards_points)-fmod(($total_amount_towards_points), $spend_amount_for_points))/$spend_amount_for_points) * $points_to_earn);
					
					if ($total_spend_for_sale >= 0)
					{
						$new_point_value = $current_points + $new_points;					
					}
					else
					{
						$new_point_value = $current_points - $new_points;							
					}
					
					$new_current_spend_for_points = fmod(($current_spend_for_points + $total_spend_for_sale),$spend_amount_for_points);
				}
				else
				{
					$new_current_spend_for_points = $current_spend_for_points + $total_spend_for_sale;
					$new_point_value = $current_points;
				}
				
				$this->db->where('person_id', $customer_id);
				$this->db->update('customers', array('points' => $new_point_value, 'current_spend_for_points' => $new_current_spend_for_points));
				
				//If we are undeleting a sale; any points used should be removed back
				if ($sale_info['points_used'])
				{
 				  $this->db->set('points','points-'.$sale_info['points_used'],false);
 				  $this->db->where('person_id', $customer_id);
 				  $this->db->update('customers');
				}
				
		 }
		 else //Take points away
		 {
			if ($current_spend_for_points - abs($total_spend_for_sale) >=0) //Just need to remove current spend
			{
				$new_point_value = $current_points;
				$new_current_spend_for_points = $current_spend_for_points - $total_spend_for_sale;
			}
			else
			{
				
				$total_amount_towards_points = $current_spend_for_points + abs($total_spend_for_sale);
				$new_points =  (((($total_amount_towards_points)-fmod(($total_amount_towards_points), $spend_amount_for_points))/$spend_amount_for_points) * $points_to_earn);
				
				if ($total_spend_for_sale >= 0)
				{
					$new_point_value = $current_points - $new_points;					
				}
				else
				{
					$new_point_value = $current_points + $new_points;							
				}
				
				$new_current_spend_for_points = fmod(($current_spend_for_points - $total_spend_for_sale),$spend_amount_for_points);
			}
			
			$new_point_value = (int) round(to_currency_no_money($new_point_value));
			$new_current_spend_for_points = to_currency_no_money($new_current_spend_for_points);
			
			$this->db->where('person_id', $customer_id);
			$this->db->update('customers', array('points' => $new_point_value, 'current_spend_for_points' => $new_current_spend_for_points));
		 	
			
			//If we are deleting a sale; any points used shouold be added back
			if ($sale_info['points_used'])
			{
			  $this->db->set('points','points+'.$sale_info['points_used'],false);
			  $this->db->where('person_id', $customer_id);
			  $this->db->update('customers');
			}
		 }
	  }
	}
	
	function get_sale_total($sale_id,$subtotal = false)
	{		
		$row = $this->get_info($sale_id)->row_array();
		if (isset($row['total']) && !$subtotal)
		{
			return $row['total'];
		}
		elseif(isset($row['subtotal']) && $subtotal)
		{
			return $row['subtotal'];
		}
		
		return 0;
	}
	
	function delete($sale_id, $all_data = false,$messaje_sunat='')
	{
		$sale_info = $this->get_info($sale_id)->row_array();
		$this->load->model('Customer');
		$this->Customer->delete_series_by_sale_id($sale_id);
		if($sale_info['store_account_payment'])
		{
			$store_account_payment_amount = $sale_info['total'];
			$store_account_info = $this->get_store_account_info($sale_id)->row_array();
			$sno = $store_account_info['sno'];
			
			$this->db->where('sno > '.$sno);
			$this->db->where('customer_id',$sale_info['customer_id']);
			$this->db->set('balance', "balance + $store_account_payment_amount", FALSE);
			$this->db->update('store_accounts');
			$this->db->delete('store_accounts',array('sno' => $sno));
			
		  $this->db->set('balance','balance+'.$store_account_payment_amount,false);
		  $this->db->where('person_id', $sale_info['customer_id']);
		  $this->db->update('customers');
		}
		
		$suspended = $sale_info['suspended'];
		$employee_id=$this->Employee->get_logged_in_employee_info()->person_id ? $this->Employee->get_logged_in_employee_info()->person_id : 1;
		//Only update stock quantity if we are NOT an estimate ($suspendd = 2)
		if ($suspended < 2)
		{
			$this->db->select('serialnumber, sales.location_id, item_id, quantity_purchased,item_variation_id');
			$this->db->from('sales_items');
			$this->db->join('sales', 'sales.sale_id = sales_items.sale_id');
			$this->db->where('sales_items.sale_id', $sale_id);
		
			foreach($this->db->get()->result_array() as $sale_item_row)
			{
				$sale_location_id = $sale_item_row['location_id'];
				$cur_item_info = $this->Item->get_info($sale_item_row['item_id']);	
				$cur_item_location_info = $this->Item_location->get_info($sale_item_row['item_id'], $sale_location_id);
			
			
				if (!$cur_item_info->is_service)
				{
					if ($sale_item_row['item_variation_id'])
					{
						$cur_item_quantity = $this->Item_variation_location->get_location_quantity($sale_item_row['item_variation_id'], $sale_location_id);
						//Update stock quantity
						$this->Item_variation_location->save_quantity($cur_item_quantity + $sale_item_row['quantity_purchased'],$sale_item_row['item_variation_id'], $sale_location_id);
					}
					else
					{
						$cur_item_quantity = $this->Item_location->get_location_quantity($sale_item_row['item_id'], $sale_location_id);
						//Update stock quantity
						$this->Item_location->save_quantity($cur_item_quantity + $sale_item_row['quantity_purchased'],$sale_item_row['item_id'], $sale_location_id);
					}

					$sale_remarks =$this->config->item('sale_prefix').' '.$sale_id;
						$inv_data = array
					(
						'location_id' => $sale_location_id,
						'trans_date'=>date('Y-m-d H:i:s'),
						'trans_items'=>$sale_item_row['item_id'],
						'trans_user'=>$employee_id,
						'trans_comment'=>$sale_remarks,
						'trans_inventory'=>$sale_item_row['quantity_purchased'],
						'item_variation_id' => $sale_item_row['item_variation_id'] ? $sale_item_row['item_variation_id'] : NULL,
						'trans_current_quantity' => $cur_item_quantity + $sale_item_row['quantity_purchased']);
							
					$this->Inventory->insert($inv_data);
				}
				
				if ($sale_item_row['serialnumber'])
				{
					$this->load->model('Item_serial_number');
					$this->Item_serial_number->add_serial($sale_item_row['item_id'], $sale_item_row['serialnumber']);
				}
				
			}
		}

		//Only update stock quantity + store accounts + giftcard balance if we are NOT an estimate ($suspended = 2)
		if ($suspended < 2)
		{		
			$this->db->select('sales.location_id, item_kit_id, quantity_purchased');
			$this->db->from('sales_item_kits');
			$this->db->join('sales', 'sales.sale_id = sales_item_kits.sale_id');
			$this->db->where('sales_item_kits.sale_id', $sale_id);
		
			foreach($this->db->get()->result_array() as $sale_item_kit_row)
			{
				foreach($this->Item_kit_items->get_info($sale_item_kit_row['item_kit_id']) as $item_kit_item)
				{
					$sale_location_id = $sale_item_kit_row['location_id'];
					$cur_item_info = $this->Item->get_info($item_kit_item->item_id);
					$cur_item_location_info = $this->Item_location->get_info($item_kit_item->item_id, $sale_location_id);

					if (!$cur_item_info->is_service)
					{
						$cur_item_location_info->quantity = $cur_item_location_info->quantity !== '' ? $cur_item_location_info->quantity : 0;
					
						$this->Item_location->save_quantity($cur_item_location_info->quantity + ($sale_item_kit_row['quantity_purchased'] * $item_kit_item->quantity),$item_kit_item->item_id, $sale_location_id);

						$sale_remarks =$this->config->item('sale_prefix').' '.$sale_id;
						$inv_data = array
						(
							'location_id' => $sale_location_id,
							'trans_date'=>date('Y-m-d H:i:s'),
							'trans_items'=>$item_kit_item->item_id,
							'trans_user'=>$employee_id,
							'trans_comment'=>$sale_remarks,
							'trans_inventory'=>$sale_item_kit_row['quantity_purchased'] * $item_kit_item->quantity,
							'trans_current_quantity' => $cur_item_location_info->quantity + ($sale_item_kit_row['quantity_purchased'] * $item_kit_item->quantity),
		
						);
						$this->Inventory->insert($inv_data);
					}				
				}
			}

			$this->update_store_account($sale_id);
			$this->update_giftcard_balance($sale_id);
			$this->update_points($sale_id);
			$this->update_loyalty_simple_count($sale_id);
			
			//Only insert store account transaction if we aren't deleting the whole sale.
			//When deleting the whole sale save() takes care of this
			if (!$all_data)
			{
		 		$previous_store_account_amount = $this->get_store_account_payment_total($sale_id);
			
				if ($previous_store_account_amount)
				{	
					$store_account_transaction = array(
			   		'customer_id'=>$sale_info['customer_id'],
			      'sale_id'=>$sale_id,
						'comment'=>$sale_info['comment'],
			      'transaction_amount'=>-$previous_store_account_amount,
						'balance'=>$this->Customer->get_info($sale_info['customer_id'])->balance,
						'date' => date('Y-m-d H:i:s')
					);
					$this->db->insert('store_accounts',$store_account_transaction);
				}
			}
		}
		
		if ($all_data)
		{
			$this->db->delete('sales_payments', array('sale_id' => $sale_id)); 
			$this->db->delete('sales_items_taxes', array('sale_id' => $sale_id)); 
			$this->db->delete('sales_items', array('sale_id' => $sale_id)); 
			$this->db->delete('sales_item_kits_taxes', array('sale_id' => $sale_id)); 
			$this->db->delete('sales_item_kits', array('sale_id' => $sale_id)); 
			$this->db->delete('sales_coupons', array('sale_id' => $sale_id)); 
			$this->db->delete('sales_deliveries', array('sale_id' => $sale_id)); 
		}
		$sunat_baja=FALSE;
		$is_boleta=FALSE;
		$numeracion_actual=0;
		if ($suspended==0) {

			$retur_num=$this->Number_invoice->save_sunat_serie_by_id_sale($sale_id,0);
			$serie_dat = explode('-', $retur_num->serie);
			$serie = $serie_dat[0];
			$numeracion = $serie_dat[1];
			if ($retur_num->nombre=='factura') {

				$tipo_comprobante="01";
				$sunat_baja=TRUE;

				$serie_fecha = date('Ymd');
				$num_baja=$this->Number_invoice->get_numeration_baja($serie_fecha);

				if ($num_baja==null) {
					$numeracion_actual=1;
					$data_numeration_b = array('prefijo'=>'RA','numeracion' => 1,'nombre'=>$serie_fecha);
					$this->Number_invoice->save_numeration_baja($data_numeration_b);
				}else{
					$numeracion_actual=$num_baja->numeracion;
				}

				$data_numeration_b = array('numeracion' => ($numeracion_actual+1));
				$this->Number_invoice->update_numeration_baja($serie_fecha,$data_numeration_b);
				/*
				$numeracion_actual = $num_baja->numeracion;
				if ($num_baja->prefijo) {
					$data_numeration = array('numeracion' => ($num_baja->numeracion+1), );
				}
				*/
				//$this->Number_invoice->update_numeration($num_baja->prefix_id,$data_numeration);

			}else if($retur_num->nombre=='boleta'){

				$tipo_comprobante="03";
				$sunat_baja=TRUE;
				$is_boleta=TRUE;
				//$resumen=$this->Number_invoice->get_numeration_resumen_diario(); //$numeracion_actual = $resumen->numeracion;
				$serie_fecha = date('Ymd');
				$resumen=$this->Number_invoice->get_numeration_resumen_diario($serie_fecha);

				if ($resumen==null) {
					$numeracion_actual=1;
					$data_numeration_b = array('prefijo'=>'RC','numeracion' => 1,'nombre'=>$serie_fecha);
					$this->Number_invoice->save_numeration_resumen_diario($data_numeration_b);
				}else{
					$numeracion_actual=$resumen->numeracion;
				}

				$data_numeration_b = array('numeracion' => ($numeracion_actual+1));
				$this->Number_invoice->update_numeration_resumen($serie_fecha,$data_numeration_b);
			}
		}
		if ($sunat_baja && !$is_boleta) {
			$ruta =  $this->config->item('ruta_sunat');
			$data_baja_sunat = array(
				//Cabecera del documento
				"tipo_de_comprobante"			=> "a1",
				"codigo"						=> "RA",
				"serie"							=> date('Ymd'),
			    "secuencia"             		=> $numeracion_actual,
			    "fecha_referencia"             	=> date('Y-m-d'),
				"fecha_baja"            		=> date('Y-m-d'),
				"usuariosol"					=> $this->config->item('usuariosol'),
				"clavesol"						=> $this->config->item('clavesol'),
				//items
			    "detalle" => array( 
			                    array(
			                        "ITEM"          	=> "1",
			                        "TIPO_COMPROBANTE"  => $tipo_comprobante,
			                        "SERIE"           	=> $serie,
			                        "NUMERO"            => $numeracion,
			                        "MOTIVO"          	=> $messaje_sunat,//$this->input->post('motivo')
			                    )
				)
			);
			//Invocamos el servicio
			$token = $this->config->item('token'); //en caso quieras utilizar algún token generado desde tu sistema
			//codificamos la data
			$data_json = json_encode($data_baja_sunat);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $ruta);
			curl_setopt(
				$ch, CURLOPT_HTTPHEADER, array(
				//'Authorization: Token token="'.$token.'"',
				'x-api-key:'.$token,
				'Content-Type: application/json',
				)
			);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$respuesta  = curl_exec($ch);
			curl_close($ch);

			//$response = json_decode($respuesta, true);
			//echo $respuesta;
			//exit();
		}
		if ($sunat_baja && $is_boleta) {
			$ruta =  $this->config->item('ruta_sunat');
			$data_baja_sunat = array(
				//Cabecera del documento
				"tipo_de_comprobante"			=> "3",
				"tipo"							=> "anulacion",
				"codigo"						=> "RC",
				"serie"							=> date('Ymd'),
			    "secuencia"             		=> $numeracion_actual,
			    "fecha_referencia"             	=> date('Y-m-d'),
				"fecha_documento"            		=> date('Y-m-d'),
				"usuariosol"					=> $this->config->item('usuariosol'),
				"clavesol"						=> $this->config->item('clavesol'),

				//items
			    "detalle" => array( 
			                    array(
			                        "ITEM"          	=> "1",
			                        "TIPO_COMPROBANTE"  => $tipo_comprobante,
			                        "SERIE"           	=> $serie,
			                        "STATUS"           	=> "3",
			                        "NUMERO"            => $numeracion
			                    )
				)
			);
			//Invocamos el servicio
			$token = $this->config->item('token'); //en caso quieras utilizar algún token generado desde tu sistema
			//codificamos la data
			$data_json = json_encode($data_baja_sunat);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $ruta);
			curl_setopt(
				$ch, CURLOPT_HTTPHEADER, array(
				//'Authorization: Token token="'.$token.'"',
				'x-api-key:'.$token,
				'Content-Type: application/json',
				)
			);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$respuesta  = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			//echo $respuesta;
			//exit();
			/*** save and update numeracion ***/
			//$resumen=$this->Number_invoice->get_numeration_resumen_diario($serie_fecha);
			
			//$data_numeration = array('numeracion' => ($resumen->numeracion+1));
			//$this->Number_invoice->update_numeration($resumen->prefix_id,$data_numeration);

		}


		$this->db->where('sale_id', $sale_id);
		return $this->db->update('sales', array('deleted' => 1,'deleted_by'=>$employee_id, 'last_modified' => date('Y-m-d H:i:s')));
	}
	
	function undelete($sale_id)
	{
		$sale_info = $this->get_info($sale_id)->row_array();
		$suspended = $sale_info['suspended'];
		$employee_id=$this->Employee->get_logged_in_employee_info()->person_id;
	
		//Only update stock quantity + store accounts + giftcard balance if we are NOT an estimate ($suspended = 2)
		if ($suspended < 2)
		{		
			$this->db->select('serialnumber,sales.location_id, item_id, quantity_purchased,item_variation_id');
			$this->db->from('sales_items');
			$this->db->join('sales', 'sales.sale_id = sales_items.sale_id');
			$this->db->where('sales_items.sale_id', $sale_id);
		
			foreach($this->db->get()->result_array() as $sale_item_row)
			{
				$sale_location_id = $sale_item_row['location_id'];
				$cur_item_info = $this->Item->get_info($sale_item_row['item_id']);	
				$cur_item_location_info = $this->Item_location->get_info($sale_item_row['item_id'], $sale_location_id);

				if (!$cur_item_info->is_service)
				{
					if ($sale_item_row['item_variation_id'])
					{
						$cur_item_quantity = $this->Item_variation_location->get_location_quantity($sale_item_row['item_variation_id'], $sale_location_id);
						//Update stock quantity
						$this->Item_variation_location->save_quantity($cur_item_quantity - $sale_item_row['quantity_purchased'],$sale_item_row['item_variation_id'], $sale_location_id);
					}
					else
					{
						$cur_item_quantity = $this->Item_location->get_location_quantity($sale_item_row['item_id'], $sale_location_id);
						//Update stock quantity
						$this->Item_location->save_quantity($cur_item_quantity - $sale_item_row['quantity_purchased'],$sale_item_row['item_id'], $sale_location_id);
					}
					
					$sale_remarks =$this->config->item('sale_prefix').' '.$sale_id;
						$inv_data = array
					(
						'location_id' => $sale_location_id,
						'trans_date'=>date('Y-m-d H:i:s'),
						'trans_items'=>$sale_item_row['item_id'],
						'trans_user'=>$employee_id,
						'trans_comment'=>$sale_remarks,
						'trans_inventory'=>-$sale_item_row['quantity_purchased'],
						'item_variation_id' => $sale_item_row['item_variation_id'] ? $sale_item_row['item_variation_id'] : NULL,
						'trans_current_quantity' => $cur_item_quantity - $sale_item_row['quantity_purchased'],
						
					);
					$this->Inventory->insert($inv_data);
				}
				
				if ($sale_item_row['serialnumber'])
				{
					$this->load->model('Item_serial_number');
					$this->Item_serial_number->delete_serial($sale_item_row['item_id'], $sale_item_row['serialnumber']);
				}
			}
		
			$this->update_store_account($sale_id,1);
			$this->update_giftcard_balance($sale_id,1);
			$this->update_points($sale_id,1);
			$this->update_loyalty_simple_count($sale_id,1);
			
		 	$previous_store_account_amount = $this->get_store_account_payment_total($sale_id);
			
			if ($previous_store_account_amount)
			{	
			 	$store_account_transaction = array(
			      'customer_id'=>$sale_info['customer_id'],
			      'sale_id'=>$sale_id,
					'comment'=>$sale_info['comment'],
			      'transaction_amount'=>$previous_store_account_amount,
					'balance'=>$this->Customer->get_info($sale_info['customer_id'])->balance,
					'date' => date('Y-m-d H:i:s')
				);
				$this->db->insert('store_accounts',$store_account_transaction);
			}
			
			
			$this->db->select('sales.location_id, item_kit_id, quantity_purchased');
			$this->db->from('sales_item_kits');
			$this->db->join('sales', 'sales.sale_id = sales_item_kits.sale_id');
			$this->db->where('sales_item_kits.sale_id', $sale_id);
		
			foreach($this->db->get()->result_array() as $sale_item_kit_row)
			{
				foreach($this->Item_kit_items->get_info($sale_item_kit_row['item_kit_id']) as $item_kit_item)
				{
					$sale_location_id = $sale_item_kit_row['location_id'];
					$cur_item_info = $this->Item->get_info($item_kit_item->item_id);
					$cur_item_location_info = $this->Item_location->get_info($item_kit_item->item_id, $sale_location_id);
					if (!$cur_item_info->is_service && $cur_item_location_info->quantity !== NULL)
					{
						$this->Item_location->save_quantity($cur_item_location_info->quantity - ($sale_item_kit_row['quantity_purchased'] * $item_kit_item->quantity),$item_kit_item->item_id, $sale_location_id);

						$sale_remarks =$this->config->item('sale_prefix').' '.$sale_id;
						$inv_data = array
						(
							'location_id' => $sale_location_id,
							'trans_date'=>date('Y-m-d H:i:s'),
							'trans_items'=>$item_kit_item->item_id,
							'trans_user'=>$employee_id,
							'trans_comment'=>$sale_remarks,
							'trans_inventory'=>-$sale_item_kit_row['quantity_purchased'] * $item_kit_item->quantity,
							'trans_current_quantity' => $cur_item_location_info->quantity - ($sale_item_kit_row['quantity_purchased'] * $item_kit_item->quantity),
	
						);
						$this->Inventory->insert($inv_data);					
					}
				}
			}	
		}
		
		$this->db->where('sale_id', $sale_id);
		return $this->db->update('sales', array('deleted' => 0, 'deleted_by' => NULL,'last_modified' => date('Y-m-d H:i:s')));
	}

	function get_sale_items($sale_id)
	{
		$this->db->from('sales_items');
		$this->db->where('sale_id',$sale_id);
		$this->db->order_by('line');
		return $this->db->get();
	}
	
	function get_sale_coupons($sale_id)
	{
		$this->db->from('sales_coupons');
		$this->db->join('price_rules','price_rules.id=sales_coupons.rule_id');
		$this->db->where('sale_id',$sale_id);
		$this->db->order_by('sales_coupons.id');
		return $this->db->get();
	}
	
	
	function get_sale_items_ordered_by_category($sale_id)
	{
		$this->db->select('items.*, sales_items.*, categories.name as category, categories.id as category_id, sales_items.description as sales_items_description');
		$this->db->from('sales_items');
		$this->db->join('items', 'items.item_id = sales_items.item_id');
		$this->db->join('categories', 'categories.id = items.category_id');
		$this->db->where('sale_id',$sale_id);
		$this->db->order_by('categories.name, items.name');
		return $this->db->get();		
	}

	function get_sale_item_kits($sale_id)
	{
		$this->db->from('sales_item_kits');
		$this->db->where('sale_id',$sale_id);
		$this->db->order_by('line');
		return $this->db->get();
	}
	
	function get_sale_item_kits_ordered_by_category($sale_id)
	{
		$this->db->select('item_kits.*, sales_item_kits.*, categories.name as category,categories.id as category_id');
		$this->db->from('sales_item_kits');
		$this->db->join('item_kits', 'item_kits.item_kit_id = sales_item_kits.item_kit_id');
		$this->db->join('categories', 'categories.id = item_kits.category_id');
		$this->db->where('sale_id',$sale_id);
		$this->db->order_by('categories.name, item_kits.name');
		return $this->db->get();		
	}
	
	function get_sale_items_taxes($sale_id, $line = FALSE)
	{
		$item_where = '';
		
		//This is sort of hacky but 0 needs to work but anything else fasly shouldn't work
		if ($line!== FALSE && $line!=='' && $line !== NULL)
		{
			$item_where = 'and '.$this->db->dbprefix('sales_items').'.line = '.$line;
		}

		$query = $this->db->query('SELECT name, line, percent, cumulative, item_unit_price as price, quantity_purchased as quantity, discount_percent as discount '.
		'FROM '. $this->db->dbprefix('sales_items_taxes'). ' JOIN '.
		$this->db->dbprefix('sales_items'). ' USING (sale_id, item_id, line) '.
		'WHERE '.$this->db->dbprefix('sales_items_taxes').".sale_id = $sale_id".' '.$item_where.' '.
		'ORDER BY '.$this->db->dbprefix('sales_items').'.line,'.$this->db->dbprefix('sales_items').'.item_id,cumulative,name,percent');
		return $query->result_array();
	}
	
	function get_sale_item_kits_taxes($sale_id, $line = FALSE)
	{
		$item_kit_where = '';
		
		//This is sort of hacky but 0 needs to work but anything else fasly shouldn't work
		if ($line!== FALSE && $line!=='' && $line !== NULL)
		{
			$item_kit_where = 'and '.$this->db->dbprefix('sales_item_kits').'.line = '.$line;
		}
		
		$query = $this->db->query('SELECT name, line, percent, cumulative, item_kit_unit_price as price, quantity_purchased as quantity, discount_percent as discount '.
		'FROM '. $this->db->dbprefix('sales_item_kits_taxes'). ' JOIN '.
		$this->db->dbprefix('sales_item_kits'). ' USING (sale_id, item_kit_id, line) '.
		'WHERE '.$this->db->dbprefix('sales_item_kits_taxes').".sale_id = $sale_id".' '.$item_kit_where.' '.
		'ORDER BY '.$this->db->dbprefix('sales_item_kits').'.line,'.$this->db->dbprefix('sales_item_kits').'.item_kit_id,cumulative,name,percent');
		return $query->result_array();	
	}

	function get_sale_payments($sale_id)
	{
		$this->db->from('sales_payments');
		$this->db->where('sale_id',$sale_id);
		return $this->db->get();
	}

	function get_customer($sale_id)
	{
		$this->db->from('sales');
		$this->db->where('sale_id',$sale_id);
		return $this->Customer->get_info($this->db->get()->row()->customer_id);
	}
	
	function get_comment($sale_id)
	{
		$this->db->from('sales');
		$this->db->where('sale_id',$sale_id);
		return $this->db->get()->row()->comment;
	}
		
	function get_tier_id($sale_id)
	{
		$this->db->from('sales');
		$this->db->where('sale_id',$sale_id);
		return $this->db->get()->row()->tier_id;		
	}
	
	function get_comment_on_receipt($sale_id)
	{
		$this->db->from('sales');
		$this->db->where('sale_id',$sale_id);
		return $this->db->get()->row()->show_comment_on_receipt;
	}
		
	function get_sold_by_employee_id($sale_id)
	{
		$this->db->from('sales');
		$this->db->where('sale_id',$sale_id);
		return $this->db->get()->row()->sold_by_employee_id;
	}
	
	public function get_giftcard_value( $giftcardNumber )
	{
		if ( !$this->Giftcard->exists( $this->Giftcard->get_giftcard_id($giftcardNumber)))
			return 0;
		
		$this->db->from('giftcards');
		$this->db->where('giftcard_number',$giftcardNumber);
		return $this->db->get()->row()->value;
	}
	
	function get_all_suspended($suspended_types = NULL)
	{				
		
		if ($suspended_types === NULL)
		{
			$suspended_types = array(1,2);
			$this->load->model('Sale_types');
			
			foreach($this->Sale_types->get_all()->result_array() as $row)
			{
				$suspended_types[] = $row['id'];
			}
		}
		error_reporting(E_ALL);
		ini_set('display_errors', TRUE);
		ini_set('display_startup_errors', TRUE);
		
		$location_id = $this->Employee->get_logged_in_employee_current_location_id();		
		//La nueva agregada
		$this->db->select('sales.*,customers.*,people.*,sale_types.name as sale_type_name,sales_sunat.serie');
		$this->db->from('sales');
		$this->db->join('sale_types', 'sale_types.id = sales.suspended', 'left');
		$this->db->join('customers', 'sales.customer_id = customers.person_id', 'left');
		$this->db->join('people', 'customers.person_id = people.person_id', 'left');
		$this->db->join('sales_sunat', 'sales_sunat.sale_id = sales.sale_id', 'left');
		if (!(boolean)$this->session->userdata('isadmin')) {
			$this->db->join('people peo', 'peo.person_id = sales.sold_by_employee_id');
			$this->db->where('sales.sold_by_employee_id', $this->session->userdata('person_id'));
		}
		$this->db->where('sales_sunat.suspended', 1);
		$this->db->where('sales.deleted', 0);
		$this->db->where_in('sales.suspended', $suspended_types);
		$this->db->where('sales.location_id', $location_id);
		$this->db->order_by('sale_id');
		
		$sales = $this->db->get()->result_array();
	
		$sale_ids = array();
		
		foreach($sales as $sale)
		{
			$sale_ids[] = $sale['sale_id'];
		}
		
		$all_payments_for_sales = $this->_get_all_sale_payments($sale_ids);	
				
		for($k=0;$k<count($sales);$k++)
		{
			$item_names = array();
			$this->db->select('name');
			$this->db->from('items');
			$this->db->join('sales_items', 'sales_items.item_id = items.item_id');
			$this->db->where('sale_id', $sales[$k]['sale_id']);
		
			foreach($this->db->get()->result_array() as $row)
			{
				$item_names[] = $row['name'];
			}
			
			$this->db->select('name');
			$this->db->from('item_kits');
			$this->db->join('sales_item_kits', 'sales_item_kits.item_kit_id = item_kits.item_kit_id');
			$this->db->where('sale_id', $sales[$k]['sale_id']);
		
			foreach($this->db->get()->result_array() as $row)
			{
				$item_names[] = $row['name'];
			}
			
			$sales[$k]['items'] = implode(', ', $item_names);

			$sales[$k]['last_payment_date'] = lang('common_none');			
			$sale_total = $this->get_sale_total($sales[$k]['sale_id']);		
			$amount_paid = 0;
			$sale_id = $sales[$k]['sale_id'];
						
			$payment_data = array();
			
			if (isset($all_payments_for_sales[$sale_id]))
			{
				$total_sale_balance = $sale_total;		
				
				foreach($all_payments_for_sales[$sale_id] as $payment_row)
				{
					//Postive sale total, positive payment
					if ($sale_total >= 0 && $payment_row['payment_amount'] >=0)
					{
						$payment_amount = $payment_row['payment_amount'] <= $total_sale_balance ? $payment_row['payment_amount'] : $total_sale_balance;
					}//Negative sale total negative payment
					elseif ($sale_total < 0 && $payment_row['payment_amount']  < 0)
					{
						$payment_amount = $payment_row['payment_amount'] >= $total_sale_balance ? $payment_row['payment_amount'] : $total_sale_balance;
					}//Positive Sale total negative payment
					elseif($sale_total >= 0 && $payment_row['payment_amount']  < 0)
					{
						$payment_amount = $total_sale_balance != 0 ? $payment_row['payment_amount'] : 0;
					}//Negtive sale total postive payment
					elseif($sale_total < 0 && $payment_row['payment_amount']  >= 0)
					{
						$payment_amount = $total_sale_balance != 0 ? $payment_row['payment_amount'] : 0;
					}				
			
					$total_sale_balance-=$payment_amount;	
					$amount_paid+=	$payment_amount;	
					
					
					$sales[$k]['last_payment_date'] = date(get_date_format().' '.get_time_format(), strtotime($payment_row['payment_date']));		
				}
			}
			
			$sales[$k]['sale_total'] = $sale_total;
			$sales[$k]['amount_due'] = $sale_total - $amount_paid;
			$sales[$k]['amount_paid'] = $amount_paid;
		}
		
		return $sales;
		
	}
	
	function count_all()
	{
		$this->db->from('sales');
		$this->db->where('deleted',0);
		
		if ($this->config->item('hide_store_account_payments_in_reports'))
		{
			$this->db->where('store_account_payment',0);
		}
		
		return $this->db->count_all_results();
	}
	
	function get_recent_sales_for_customer($customer_id)
	{
		$return = array();
		
		$sales_items = $this->db->dbprefix('sales_items');
		$sales_item_kits = $this->db->dbprefix('sales_item_kits');
		$this->db->select("sales.*, SUM(COALESCE($sales_items.quantity_purchased,0)) + SUM(COALESCE($sales_item_kits.quantity_purchased,0)) as items_purchased");
		$this->db->from('sales');
		$this->db->join('sales_items', 'sales.sale_id = sales_items.sale_id','left');
		$this->db->join('sales_item_kits', 'sales.sale_id = sales_item_kits.sale_id','left');
		$this->db->where('customer_id', $customer_id);
		$this->db->where('deleted', 0);
		$this->db->order_by('sale_time DESC');
		$this->db->group_by('sales.sale_id');
		$this->db->limit($this->config->item('number_of_recent_sales') ? $this->config->item('number_of_recent_sales') : 10);
		
		foreach($this->db->get()->result_array() as $row)
		{
			$return[] = $row;
		}

		return $return;
	}
	
	function get_store_account_payment_total($sale_id)
	{
		$this->db->select('SUM(payment_amount) as store_account_payment_total', false);
		$this->db->from('sales_payments');
		$this->db->where('sale_id', $sale_id);
		$store_account_in_all_languages = get_all_language_values_for_key('common_store_account','common');
		$this->db->where_in('payment_type', $store_account_in_all_languages);
		
		$sales_payments = $this->db->get()->row_array();	
		
		return $sales_payments['store_account_payment_total'] ? $sales_payments['store_account_payment_total'] : 0;
	}
	
	function get_deleted_taxes($sale_id)
	{
		$this->db->from('sales');
		$this->db->where('sale_id',$sale_id);
		$deleted_taxes = $this->db->get()->row()->deleted_taxes;
		return $deleted_taxes ? unserialize($deleted_taxes) : array();
	}
	
	function get_sale_ids_for_range($start_date, $end_date)
	{
		$this->db->select('sale_id');
		$this->db->from('sales');
		$this->db->where('sale_time BETWEEN '.$this->db->escape($start_date).' and '.$this->db->escape($end_date));
		
		$sale_ids = array();
		foreach($this->db->get()->result_array() as $row)
		{
			$sale_ids[] = $row['sale_id'];
		}
		
		return $sale_ids;
	}
	
	
	function get_sales_amount_for_range($start_date, $end_date)
	{
		$this->load->model('Sale');
		$this->load->model('reports/Summary_sales');
		$model = $this->Summary_sales;
		$model->setParams(array('start_date'=>$start_date, 'end_date'=>$end_date, 'sale_type' => 'sales', 'offset' => 0, 'export_excel' => '1'));
		
		$report_order = $this->config->item('report_sort_order') ? $this->config->item('report_sort_order') : 'asc';
		$data = $model->getData();
		$report_data = array();
		
		foreach($data as $row)
		{
			$report_data[] = array('sale_date' => $row['sale_date'], 'sale_amount' => to_currency_no_money($row['total']));
		}
		
		if ($report_order == 'desc')
		{
			$report_data = array_reverse($report_data); 
		}	
		
		return $report_data;
		
	}
	
	function get_quantity_sold_for_item_in_sale($sale_id, $item_id)
	{
		$this->db->select('quantity_purchased');
		$this->db->from('sales_items');
		$this->db->where('sale_id',$sale_id);
		$this->db->where('item_id',$item_id);
		$row = $this->db->get()->row_array();
		
		return empty($row) ? 0 : $row['quantity_purchased'];
	}
	
	function get_quantity_sold_for_item_kit_in_sale($sale_id, $item_kit_id)
	{
		$this->db->select('quantity_purchased');
		$this->db->from('sales_item_kits');
		$this->db->where('sale_id',$sale_id);
		$this->db->where('item_kit_id',$item_kit_id);
		$row = $this->db->get()->row_array();
		
		return empty($row) ? 0 : $row['quantity_purchased'];
		
	}
	
	function can_void_cc_sale($sale_id)
	{
		$processor = false;
		
		if ($this->Location->get_info_for_key('credit_card_processor') == 'mercury' || !$this->Location->get_info_for_key('credit_card_processor'))
		{
			$processor = 'mercury';
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'heartland')
		{
			$processor = 'heartland';			
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'worldpay')
		{
			$processor = 'worldpay';			
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'firstdata')
		{
			$processor = 'firstdata';			
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'evo')
		{
			$processor = 'evo';			
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'stripe')
		{
			$processor = 'stripe';
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'braintree')
		{
			$processor = 'braintree';
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'other_usb')
		{
			$processor = 'other_usb';			
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'square')
		{
			$processor = 'square';			
		}
		
		$this->db->from('sales_payments');
		$this->db->where('sale_id',$sale_id);
		$this->db->where_in('payment_type', array(lang('common_credit'),lang('sales_partial_credit')));
		
		$result = $this->db->get()->result_array();
		
		if (empty($result))
		{
			return FALSE;
		}

		foreach($result as $row)
		{
			if ($processor == 'mercury' || $processor == 'heartland' || $processor == 'evo' || $processor == 'worldpay' || $processor == 'firstdata' || $processor == 'other_usb')
			{
				if(!($row['auth_code'] && $row['ref_no'] && $row['cc_token'] && $row['acq_ref_data'] && $row['payment_amount'] > 0))
				{
					return FALSE;
				}
			}
			elseif($processor == 'stripe' || $processor == 'braintree')
			{
				if (!$row['ref_no'])
				{
					return FALSE;
				}
			}
			elseif($processor == 'square')
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	function can_void_cc_return($sale_id)
	{
		$processor = false;
		
		
		if ($this->Location->get_info_for_key('credit_card_processor') == 'mercury' || !$this->Location->get_info_for_key('credit_card_processor'))
		{
			$processor = 'mercury';
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'heartland')
		{
			$processor = 'heartland';			
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'worldpay')
		{
			$processor = 'worldpay';			
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'firstdata')
		{
			$processor = 'firstdata';			
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'evo')
		{
			$processor = 'evo';			
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'stripe')
		{
			$processor = 'stripe';
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'braintree')
		{
			$processor = 'braintree';
		}
		elseif($this->Location->get_info_for_key('credit_card_processor') == 'square')
		{
			$processor = 'square';			
		}
		
		$this->db->from('sales_payments');
		$this->db->where('sale_id',$sale_id);
		$this->db->where_in('payment_type', array(lang('common_credit'),lang('sales_partial_credit')));
		
		$result = $this->db->get()->result_array();
		
		if (empty($result))
		{
			return FALSE;
		}

		foreach($result as $row)
		{
			if ($processor == 'mercury' || $processor == 'heartland' || $processor == 'evo' || $processor == 'worldpay' || $processor == 'firstdata' || $processor == 'other_usb')
			{				
				if(!($row['ref_no'] && $row['cc_token'] && $row['payment_amount'] < 0))
				{
					return FALSE;
				}
				
			}
			elseif($processor == 'stripe' || $processor == 'braintree')
			{
				return FALSE;
			}
			elseif($processor == 'square')
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	function get_item_ids_sold_for_date_range($start_date, $end_date, $supplier_id, $location_id = FALSE)
	{
		if ($location_id === FALSE)
		{
			$location_id = $this->Employee->get_logged_in_employee_current_location_id();
		}
		
		$this->db->select('sales_items.item_id,sales_items.item_variation_id');
		$this->db->from('sales_items');
		$this->db->join('items', 'sales_items.item_id = items.item_id');
		$this->db->join('sales', 'sales.sale_id = sales_items.sale_id');
		$this->db->where('sale_time BETWEEN '.$this->db->escape($start_date).' and '.$this->db->escape($end_date).' and sales.deleted = 0');
		$this->db->where('supplier_id', $supplier_id);
		$this->db->where('location_id', $location_id);
		$this->db->where('items.deleted',0);
		$item_ids = array();
		
		foreach($this->db->get()->result_array() as $row)
		{
			$item_ids[$row['item_id'].($row['item_variation_id'] ? '#'.$row['item_variation_id'] : '')] = $row['item_id'].($row['item_variation_id'] ? '#'.$row['item_variation_id'] : '');
		}
		
		return array_values($item_ids);
	}
	
	function get_last_sale_id($location_id = FALSE)
	{
		if ($location_id === FALSE)
		{
			$location_id = $this->Employee->get_logged_in_employee_current_location_id();
		}
		
		$this->db->select('sale_id');
		$this->db->from('sales');
		$this->db->where('deleted', 0);
		$this->db->where('location_id', $location_id);
		$this->db->order_by('sale_id DESC');
		$this->db->limit(1);
		$query = $this->db->get();
		
		if ($row = $query->row_array())
		{
			return $row['sale_id'];
		}
		
		return FALSE;
		
	}
	
	function get_global_weighted_average_cost()
	{
		$current_location=$this->Employee->get_logged_in_employee_current_location_id();
		
		$this->db->select('sum(IFNULL('.$this->db->dbprefix('location_items').'.cost_price, '.$this->db->dbprefix('items').'.cost_price) * quantity) / sum(quantity) as weighted_cost', FALSE);
		$this->db->from('items');
		$this->db->join('location_items', 'location_items.item_id = items.item_id and location_id = '.$current_location, 'left');
		$this->db->where('is_service !=', 1);
		$this->db->where('items.deleted', 0);
		
		$row = $this->db->get()->row_array();
		
		return $row['weighted_cost'];
		
	}
	
	function get_payment_options_with_language_keys()
	{		
		
		$payment_options=array(
		lang('common_cash') => 'common_cash',
		lang('common_check') => 'common_check',
		lang('common_giftcard') => 'common_giftcard',
		lang('common_debit') => 'common_debit',
		lang('common_credit') => 'common_credit',
		lang('common_store_account') => 'common_store_account',
		lang('common_points') => 'common_points',
		);
		
		foreach($this->Appconfig->get_additional_payment_types() as $additional_payment_type)
		{
			$payment_options[$additional_payment_type] = $additional_payment_type;
		}
		
		return $payment_options;
	}
	
	function get_payment_options($cart)
	{
		$payment_options = array();
		
		$customer_id=$cart->customer_id;
		
		if ($customer_id)
		{
			$cust_info=$this->Customer->get_info($customer_id);
		}
		
		
		if ($this->Location->get_info_for_key('enable_credit_card_processing'))
		{
			$payment_options=array(
				lang('common_cash') => lang('common_cash'),
				lang('common_check') => lang('common_check'),
				lang('common_credit') => lang('common_credit'),
				lang('common_giftcard') => lang('common_giftcard'));
				
				if($this->config->item('customers_store_accounts') && $cart->get_mode() != 'store_account_payment') 
				{
					$payment_options=array_merge($payment_options,	array(lang('common_store_account') => lang('common_store_account')		
					));
				}
				
				
				if (isset($cust_info) && !$cust_info->disable_loyalty)
				{
					if ($this->config->item('enable_customer_loyalty_system') && $this->config->item('loyalty_option') == 'advanced' && count(explode(":",$this->config->item('spend_to_point_ratio'),2)) == 2 &&  isset($cust_info) && $cust_info->points >=1 && $cart->get_payment_amount(lang('common_points')) <=0)
					{
						$payment_options=array_merge($payment_options,	array(lang('common_points') => lang('common_points')));		
					}
				}
				
				if ($this->Location->get_info_for_key('integrated_gift_cards'))
				{
					$payment_options=array_merge($payment_options,	array(lang('common_integrated_gift_card') => lang('common_integrated_gift_card')));		
				}
				
				if($this->config->item('enable_ebt_payments')) 
				{
					$payment_options=array_merge($payment_options,	array(lang('common_ebt') => lang('common_ebt'),lang('common_ebt_cash') => lang('common_ebt_cash')));
				}
				
				if ($this->config->item('enable_wic'))
				{
					$payment_options=array_merge($payment_options,	array(lang('common_wic') => lang('common_wic')));					
				}
		}
		else
		{
			$payment_options=array(
				lang('common_cash') => lang('common_cash'),
				lang('common_check') => lang('common_check'),
				lang('common_giftcard') => lang('common_giftcard'),
				lang('common_debit') => lang('common_debit'),
				lang('common_credit') => lang('common_credit')
				);
				
				if($this->config->item('customers_store_accounts') && $cart->get_mode() != 'store_account_payment') 
				{
					$payment_options=array_merge($payment_options,	array(lang('common_store_account') => lang('common_store_account')		
					));
				}
				if (isset($cust_info) && !$cust_info->disable_loyalty)
				{
					if ($this->config->item('enable_customer_loyalty_system') && $this->config->item('loyalty_option') == 'advanced' && count(explode(":",$this->config->item('spend_to_point_ratio'),2)) == 2 &&  isset($cust_info) && $cust_info->points >=1 && $cart->get_payment_amount(lang('common_points')) <=0)
					{
						$payment_options=array_merge($payment_options,	array(lang('common_points') => lang('common_points')));		
					}
				}
				
				if($this->config->item('enable_ebt_payments')) 
				{
					$payment_options=array_merge($payment_options,	array(lang('common_ebt') => lang('common_ebt'),lang('common_ebt_cash') => lang('common_ebt_cash')));
				}
				
				if ($this->config->item('enable_wic'))
				{
					$payment_options=array_merge($payment_options,	array(lang('common_wic') => lang('common_wic')));					
				}
		}
		
		foreach($this->Appconfig->get_additional_payment_types() as $additional_payment_type)
		{
			$payment_options[$additional_payment_type] = $additional_payment_type;
		}
		
		$deleted_payment_types = $this->config->item('deleted_payment_types');
		$deleted_payment_types = explode(',',$deleted_payment_types);
		
		foreach($deleted_payment_types as $deleted_payment_type)
		{
			foreach($payment_options as $payment_option)
			{
				if ($payment_option == $deleted_payment_type)
				{
					unset($payment_options[$payment_option]);
				}
			}
		}
		return $payment_options;
	}
	
	function get_unpaid_store_account_sales($sale_ids)
	{
		$store_account_in_all_languages = get_all_language_values_for_key('common_store_account','common');
		
		$this->db->select('sales.sale_id, sale_time, SUM(payment_amount) as payment_amount,sales.comment', false);
		$this->db->from('sales');
		$this->db->join('sales_payments', 'sales.sale_id = sales_payments.sale_id');
		$this->db->where_in('sales_payments.payment_type', $store_account_in_all_languages);
		$this->db->where('sales.deleted',0);
		if (!empty($sale_ids))
		{
			$this->db->where_in('sales.sale_id', $sale_ids);
		}
		else
		{
			$this->db->where_in('sales.sale_id', array(0));				
		}
		$this->db->order_by('sale_time');
		$this->db->group_by('sale_id');
		return $this->db->get()->result_array();
	}
	
	function get_unpaid_store_account_sale_ids($customer_id,$limit = 1000)
	{
		$store_account_in_all_languages = get_all_language_values_for_key('common_store_account','common');
		
		$this->db->select('store_accounts.sale_id');
		$this->db->from('store_accounts');
		$this->db->join('sales_payments', 'store_accounts.sale_id = sales_payments.sale_id');
		$this->db->where('store_accounts.customer_id',$customer_id);
		$this->db->where('store_accounts.sale_id IS NOT NULL');
		$this->db->where('store_accounts.sale_id NOT IN (SELECT sale_id FROM '.$this->db->dbprefix('store_accounts_paid_sales').' WHERE sale_id IS NOT NULL)');
		$this->db->where_in('sales_payments.payment_type', $store_account_in_all_languages);
		$this->db->order_by('date');
		
		
		$sale_ids = array();
		
		foreach($this->db->get()->result_array() as $row)
		{
			$sale_ids[] = $row['sale_id'];
		}
			
		return $sale_ids;
	}
	
	function mark_all_unpaid_sales_paid($customer_id = '')
	{
		$this->db->select('store_accounts.sale_id');
		$this->db->from('store_accounts');
		if ($customer_id)
		{
			$this->db->where('customer_id',$customer_id);
		}
		
		$this->db->where('sale_id is NOT NULL and sale_id NOT IN (SELECT sale_id FROM '.$this->db->dbprefix('store_accounts_paid_sales').' WHERE sale_id is NOT NULL)');
		$this->db->order_by('date');
	
		foreach($this->db->get()->result_array() as $row)
		{
			$this->db->insert('store_accounts_paid_sales',array('sale_id' => $row['sale_id'],'store_account_payment_sale_id' => NULL));
	
		}
	}
	
	function get_discount_reason($sale_id)
	{
	       $this->db->from('sales');
	       $this->db->where('sale_id',$sale_id);
	       return $this->db->get()->row()->discount_reason;
	}
	
	function get_exchange_details($sale_id)
	{
    $this->db->from('sales');
    $this->db->where('sale_id',$sale_id);
    $row = $this->db->get()->row();
					
		return $row->exchange_rate.'|'.$row->exchange_name.'|'.$row->exchange_currency_symbol.'|'.$row->exchange_currency_symbol_location.'|'.$row->exchange_number_of_decimals.'|'.$row->exchange_thousands_separator.'|'.$row->exchange_decimal_point;
		
	}
	
	function get_excluded_loyalty_items_total($sale_id = false,$cart = NULL)
	{
		if (!$sale_id)
		{
			$items = $cart->get_items();
		}
		else
		{
			$items_for_sale = $this->get_sale_items($sale_id)->result_array(); 
			$item_kits_for_sale = $this->get_sale_item_kits($sale_id)->result_array();
			
			$items = array();
			
			foreach($items_for_sale as $item)
			{
				$item_obj = new stdClass();
				$item_obj->item_id = $item['item_id'];
				$item_obj->unit_price = $item['item_unit_price'];
				$item_obj->quantity = $item['quantity_purchased'];
				$item_obj->discount = $item['discount_percent'];
				$items[] = $item_obj;
				
			}
			
			foreach($item_kits_for_sale as $item)
			{
				$item_obj = new stdClass();
				$item_obj->item_kit_id = $item['item_kit_id'];
				$item_obj->unit_price = $item['item_kit_unit_price'];
				$item_obj->quantity = $item['quantity_purchased'];
				$item_obj->discount = $item['discount_percent'];
				$items[] = $item_obj;
			}
			
		}
				
		$total = 0;
		
		foreach($items as $item)
		{
			if (property_exists($item,'item_id'))
			{
				$this->load->helper('items');
				$info = $this->Item->get_info($item->item_id);
				$item_id_or_line = property_exists($item,'line') ? $item->line : $item->item_id;
				
				if ($this->config->item('loyalty_points_without_tax'))
				{
					if (!$info->tax_included)
					{
						$price = $item->unit_price;
					}
					else
					{
						$price = get_price_for_item_excluding_taxes($item_id_or_line,$item->unit_price, $sale_id);
					}					
				}
				else
				{
					if (!$info->tax_included)
					{
						$price = get_price_for_item_including_taxes($item_id_or_line,$item->unit_price, $sale_id);
					}
					else
					{
						$price = $item->unit_price;
					}
				}
			}
			else
			{				
				$this->load->helper('item_kits');
				$info = $this->Item_kit->get_info($item->item_kit_id);
				$item_kit_id_or_line = property_exists($item,'line') ? $item->line : $item->item_kit_id;
				
				if ($this->config->item('loyalty_points_without_tax'))
				{
					if (!$info->tax_included)
					{
						$price = $item->unit_price;
					}
					else
					{
						$price = get_price_for_item_kit_excluding_taxes($item_id_or_line,$item->unit_price, $sale_id);
					}
				}
				else
				{				
					if (!$info->tax_included)
					{
						$price = get_price_for_item_kit_including_taxes($item_kit_id_or_line,$item->unit_price, $sale_id);
					}
					else
					{
						$price = $item->unit_price;
					}
				}
			}
			
			if ($info->disable_loyalty)
			{
				$total+=to_currency_no_money($price*$item->quantity-$price*$item->quantity*$item->discount/100,10);
			}
		}
		
		return to_currency_no_money($total);
	}
	
	function get_store_accounts_paid_sales($store_account_payment_sale_id)
	{
		$this->db->select('sale_id');
		$this->db->from('store_accounts_paid_sales');
		$this->db->where('store_account_payment_sale_id',$store_account_payment_sale_id);
		
		$return = array();
		
		foreach($this->db->get()->result_array() as $row)
		{
			$return[] = $row['sale_id'];
		}
		
		return $return;
	}
	
	function get_sales_not_in_quickbooks_since_last_sync()
	{
		$qb_setup_date  = $this->config->item('qb_setup_date');
		$sales=$this->db->dbprefix('sales');
		$data=$this->db->query("SELECT * 
						FROM ".$sales."
						WHERE (deleted =0 and accounting_id IS NULL and sale_time > '$qb_setup_date' and suspended = 0)");		
						
		return $data;
	}
	
	function get_sales_in_quickbooks_that_need_to_be_deleted()
	{
		$sales=$this->db->dbprefix('sales');
		$data=$this->db->query("SELECT * 
						FROM ".$sales."
						WHERE (deleted =1 and accounting_id IS NOT NULL and suspended = 0)");		
						
		return $data;
	}
	
	function link_qb_sale($sale_id, $qb_sale_id)
	{
		$this->db->where('sale_id', $sale_id);
		return $this->db->update('sales', array('accounting_id' => $qb_sale_id));
	}
	
	function get_payments($sale_id)
	{
		$this->db->from('sales_payments');
		$this->db->where('sale_id',$sale_id);
		
		return $this->db->get()->result_array();
	}
	
	//gets next sale id (approx)
	function get_next_sale_id()
	{
		$this->db->select('MAX(sale_id) as max');
		$this->db->from('sales');
		$row = $this->db->get()->row_array();
		
		if (!isset($row['max']) || !$row['max'])
		{
			$row['max'] = 0;
		}
		return $row['max'] + 1;
	}
	
	function get_payments_for_sale($sale_id)
	{		
		$this->db->select('sale_id, SUM(total) as total', false);
		$this->db->from('sales');
		$this->db->where('sale_id', $sale_id);
		$sale_total_row = $this->db->get()->row_array();
		$sales_totals[$sale_total_row['sale_id']] = to_currency_no_money($sale_total_row['total'], 2);
		
		$this->db->select('sales_payments.sale_id, sales_payments.payment_type, payment_amount, payment_id', false);
		$this->db->from('sales_payments');
		$this->db->join('sales', 'sales.sale_id=sales_payments.sale_id');
		$this->db->where('sales_payments.sale_id', $sale_id);
		
		$sales_payments = $this->db->get()->result_array();
		
		$payments_by_sale = array();
		foreach($sales_payments as $row)
		{
        	$payments_by_sale[$row['sale_id']][] = $row;
		}
		
		$this->load->model('Sale');
		$payment_data = $this->Sale->get_payment_data($payments_by_sale,$sales_totals);
		
		$return = array();
		foreach($payment_data as $payment_type=>$row)
		{
			$return[$payment_type] = $row['payment_amount'];
		}
		
		return $return;
	}

	function get_custom_field($number,$key="name")
	{
		static $config_data;
		
		if (!$config_data)
		{
			$config_data = unserialize($this->config->item('sale_custom_field_prefs'));
		}
		
		return isset($config_data["custom_field_${number}_${key}"]) && $config_data["custom_field_${number}_${key}"] ? $config_data["custom_field_${number}_${key}"] : FALSE;
	}
}
?>
