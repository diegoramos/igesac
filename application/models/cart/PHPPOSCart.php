<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

abstract class PHPPOSCart
{	
	private $cart_id;
		
	private $cart_items;
	private $payments;
	private $excluded_taxes;
	private $mode;
	private $paid_store_account_ids;
	
	//MAKE SURE YOU NEVER set location_id, employee_id, or register_id anywhere except API
	//This is because this will overwrite whatever we actual have for our context
	//Setting these properties are just for the API
	public $employee_id;
	public $location_id;
	public $register_id;
	
	public $comment;
	public $selected_payment;
	public $type_comprobante;
	public $email_receipt;
	public $suspended;
	public $change_date_enable;
	public $change_cart_date;
		
	public $is_editing_previous;
	
	public $custom_field_1_value;
	public $custom_field_2_value;
	public $custom_field_3_value;
	public $custom_field_4_value;
	public $custom_field_5_value;
	public $custom_field_6_value;
	public $custom_field_7_value;
	public $custom_field_8_value;
	public $custom_field_9_value;
	public $custom_field_10_value;
	
	public function __construct(array $params = array())
	{		
		self::setup_defaults();
		
		//params that get overwritten if any passed in
		foreach($params as $name=>$value)
		{
	 		if (property_exists($this,$name))
	 		{
	 	 	 	$this->$name = $value;
			}
		}
	}
	
		
	public function setup_defaults()
	{
		$CI =& get_instance();
		if (!isset($CI->view_data))
		{
			$CI->view_data = array();
		}
		
		$this->employee_id = NULL;
		$this->location_id = NULL;
		$this->register_id = NULL;
		$this->cart_items = array();
		$this->payments = array();
		$this->excluded_taxes = array();
		$this->mode = NULL;
		$this->paid_store_account_ids = array();
		$this->comment = '';
		$this->selected_payment = '';
		$this->type_comprobante = '';
		$this->email_receipt = FALSE;
		$this->suspended = 0;
		$this->change_date_enable = FALSE;
		$this->change_cart_date = NULL;
		
		$this->is_editing_previous = FALSE;
		
		$this->custom_field_1_value = NULL;
		$this->custom_field_2_value = NULL;
		$this->custom_field_3_value = NULL;
		$this->custom_field_4_value = NULL;
		$this->custom_field_5_value = NULL;
		$this->custom_field_6_value = NULL;
		$this->custom_field_7_value = NULL;
		$this->custom_field_8_value = NULL;
		$this->custom_field_9_value = NULL;
		$this->custom_field_10_value = NULL;
		
	}
	
	public function get_cart_id()
	{
		return $this->cart_id ? $this->cart_id : '';
	}
	
	function get_mode()
	{
		return $this->mode;
	}

	function set_mode($mode)
	{
		$this->mode = $mode;
	}
	
	function remove_mode()
	{
		$this->mode = NULL;
	}
	
	//This method prevents properties from being set that don't exist
	public function __set($property, $value)
	{
	    //Checking for non-existing properties
	    if (!property_exists($this, $property)) 
	    {
	        throw new Exception("Property {$property} does not exist");
	    }
	    $this->$property = $value;
	}

	function has_cash_payment()
	{
		foreach($this->get_payments() as $payment)
		{
			if($payment->payment_type ==  lang('common_cash'))
			{
				return true;
			}
		}
		
		return false;
	}
	
	public function get_payments_by_type($type)
	{
		$payments = array();
		foreach($this->get_payments() as $payment)
		{
			if ($payment->payment_type == $type)
			{
				$payments[] = $payment;
			}
		}
		
		return $payments;
	}

	//Gets total payment amount for a given type
	public function get_payment_amount($type)
	{
		$total = 0;
		
		foreach($this->get_payments_by_type($type) as $payment)
		{
			$total+=$payment->payment_amount;
		}
		
		return $total;
	}
	public function get_item($index)
	{		
		$items = $this->get_items();
		if (isset($items[$index]))
		{
			return $items[$index];
		}
		
		return FALSE;
	}	
	
	function get_item_index($item)
	{
		$items = $this->get_items();
		for($k=0;$k<count($items);$k++)
		{
			if ($items[$k] == $item)
			{
				return $k;
			}
		}
		return FALSE;
	}
	
	function sort_items($sort_receipt_column = 'name')
	{
		if ($sort_receipt_column == 'name')
		{
			usort($this->cart_items, array($this, "compare_by_name"));
		}
		elseif($sort_receipt_column == 'item_number')
		{
			usort($this->cart_items, array($this, "compare_by_item_number"));			
		}
		elseif($sort_receipt_column == 'product_id')
		{
			usort($this->cart_items, array($this, "compare_by_product_id"));			
		}
	}
	
	function compare_by_name($a, $b)
	{
	    return strcmp($b->name, $a->name);
	}

	function compare_by_item_number($a, $b)
	{
	    return strcmp($b->item_number, $a->item_number);
	}

	function compare_by_product_id($a, $b)
	{
	    return strcmp($b->product_id, $a->product_id);
	}
	
	public function get_items($class=NULL)
	{
		if ($class===NULL)
		{
			return $this->cart_items;
		}
		
		return array_filter($this->cart_items, function($value) use ($class)
		{
			return get_class($value) == $class;
		});
	}
	
	public function empty_items()
	{
		$this->cart_items = array();
	}
	
	public abstract function add_item_kit(PHPPOSCartItemKit $item_to_add,$options = array());
	
	public function add_item(PHPPOSCartItemBase $item_to_add,$add_to_end = TRUE)
	{
		$item_to_add->cart = $this;
		
		if($add_to_end)
		{
			$this->cart_items[] = $item_to_add;
		}
		else
		{
			array_unshift($this->cart_items,$item_to_add);
		}
		return TRUE;
	}

	public function replace($index, PHPPOSCartItemBase $item_replace)
	{
		$item_replace->cart = $this;
		$this->cart_items[$index] = $item_replace;
		return TRUE;
	}
	
	//Finds exsting items by comparing IDS and variation if they have property; they must also be in the same class to find a similar item
	public function find_similiar_item($item_to_search_for,$exclude = array())
	{		
		foreach($this->get_items(get_class($item_to_search_for)) as $item)
		{
			if (!in_array($item,$exclude) && $item->get_id() == $item_to_search_for->get_id() && (!property_exists($item,'variation_id') || $item->variation_id == $item_to_search_for->variation_id))
			{
					return $item;
			}
		}
		
		return FALSE;
	}
	
	public function merge_item($item_merge_from, $item_merge_into)
	{
		foreach($this->get_items(get_class($item_merge_into)) as $item)
		{
			if ($item === $item_merge_into)
			{
				$item_merge_into->quantity+=$item_merge_from->quantity;				
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	public function delete_item($index)
	{
		if (isset($this->cart_items[$index]))
		{
			unset($this->cart_items[$index]);
			$this->cart_items = array_values($this->cart_items);
		}
	}
			
	//Adds a payment to the cart
	public function add_payment(PHPPOSCartPaymentBase $payment)
	{
		$this->payments[] = $payment;
		return TRUE;
	}
	
	public function edit_payment($index,$data)
	{
		if (isset($this->payments[$index]))
		{
			foreach($data as $prop=>$value)
			{
				$this->payments[$index]->$prop = $value;
			}
		}
	}
	
	public function get_payment_ids($payment_type)
	{
		$payment_ids = array();
		
		$payments=$this->get_payments();
		
		for($k=0;$k<count($payments);$k++)
		{
			if ($payments[$k]->payment_type == $payment_type)
			{
				$payment_ids[] = $k;
			}
		}
		
		return $payment_ids;
	}
	
	public function delete_payment($payments)
	{
		if (is_array($payments))
		{
			foreach($payments as $payment_id)
			{
				unset($this->payments[$payment_id]);
			}
		}
		elseif (isset($this->payments[$payments]))
		{
			unset($this->payments[$payments]);
		}
		
		if (empty($this->payments))
		{
			$this->payments = array();
		}
		else
		{
			$this->payments = array_values($this->payments);
		}
	}
	
	public function set_payments(array $payments)
	{
		$this->payments = $payments;
	}
	
	//Gets payments for the cart
	public function get_payments()
	{
		return $this->payments;
	}
		
	function get_excluded_taxes() 
	{
		return $this->excluded_taxes;
	}

	function add_excluded_tax($name) 
	{
		if (!in_array($name, $this->excluded_taxes))
		{
			$this->excluded_taxes[] = $name;
			return TRUE;
		}
		return FALSE;
	}
	
	function delete_excluded_tax($name) 
	{
		if (in_array($name, $this->excluded_taxes))
		{
			unset($this->excluded_taxes[array_search($name,$this->excluded_taxes)]);
			$this->excluded_taxes = array_values($this->excluded_taxes);
			return TRUE;
		}
		return FALSE;
	}
	
	
	function get_paid_store_account_ids() 
	{
		return $this->paid_store_account_ids;
	}

	function add_paid_store_account_payment_id($id) 
	{
		$this->paid_store_account_ids[$id] = TRUE;
		return TRUE;
	}
	
	function delete_paid_store_account_id($id) 
	{
		unset($this->paid_store_account_ids[$id]);
		return TRUE;
	}
	
	function delete_all_paid_store_account_payment_ids()
	{
		$this->paid_store_account_ids = array();
	}
	
	function set_excluded_taxes(array $recv_excluded_taxes)
	{
		$this->excluded_taxes = $recv_excluded_taxes;
	}
	
	public function destroy()
	{
		self::setup_defaults();
	}
	
	public function get_subtotal()
	{
		$subtotal = 0;
		foreach($this->get_items() as $item)
		{
			$subtotal+=$item->get_subtotal();
		}
		
		return to_currency_no_money($subtotal);
	}

	function get_payments_total()
	{
		$payments_total = 0;
		foreach($this->get_payments() as $payment)
		{
			$payments_total+=$payment->payment_amount;
		}
		
		return to_currency_no_money($payments_total);
	}
	

	public function get_amount_due()
	{
		return to_currency_no_money($this->get_total() - $this->get_payments_total());
	}
		
	public function do_payments_cover_total()
	{
		$total_payments = 0;

		foreach($this->get_payments() as $payment)
		{
			$total_payments += $payment->payment_amount;
		}
		/* Changed the conditional to account for floating point rounding */
		if ( ( $this->get_mode() == 'sale' || $this->get_mode() == 'receive' || $this->get_mode() == 'purchase_order' || $this->get_mode() == 'store_account_payment' ) && (( to_currency_no_money( $this->get_total() ) - $total_payments ) > 1e-6 ) )
		{
			return false;
		}
	
		return true;
	}
	
	function get_total()
	{
		$total = 0;
		foreach($this->get_items() as $item)
		{
			$total+=$item->get_subtotal();
		}

		foreach(array_values($this->get_taxes()) as $tax)
		{
			$total+=$tax;
		}
		
		return to_currency_no_money($total);
	}
	
	function get_total_quantity()
	{
		$cart_count = 0;
		$CI =& get_instance();
		
		$CI->load->helper('language');
		
		$giftcard_langs = get_all_language_values_for_key('common_giftcard','giftcards');
		
		foreach($this->get_items() as $item)
	  { 
			if (!$item->system_item || in_array($item->product_id,$giftcard_langs))
			{
	 	 		$cart_count = $cart_count + $item->quantity;
			}
		}
	 
	 return $cart_count;
	}
	
	
	public function get_taxes()
	{
		$taxes = array();
		foreach($this->get_items() as $line=>$item)
		{
			$item_taxes = $item->get_taxes();
			
			foreach($item_taxes as $name => $tax_amount)
			{
				if (!isset($taxes[$name]))
				{
					$taxes[$name] = 0;
				}
				
				$taxes[$name] += $tax_amount;
			}	
		}
		
		return $taxes;
	}
	
	function get_tax_total_amount()
	{
		$taxes = $this->get_taxes();
		$total_tax = 0;
		foreach($taxes as $name=>$value) 
		{
			$total_tax+=$value;
	 	}
		
		return to_currency_no_money($total_tax);
	}
	
	public abstract function is_valid_receipt($receipt_id);
	
	//Any string could be a valid item as long as not empty or null
	function is_valid_item($item)
	{
		return $item !='' && $item!== NULL;
	}
	
	public function is_valid_item_kit($item_kit_id)
	{
		$CI =& get_instance();
	
		//KIT #
		$pieces = explode(' ',$item_kit_id);

		if(count($pieces)==2 && strtolower($pieces[0]) == 'kit')
		{
			return $CI->Item_kit->exists($pieces[1]);
		}
		else
		{
			return $CI->Item_kit->get_item_kit_id($item_kit_id) !== FALSE;
		}
	}
	
	function get_valid_item_kit_id($item_kit_id)
	{
		$CI =& get_instance();
		
		//KIT #
		$pieces = explode(' ',$item_kit_id);

		if(count($pieces)==2 && strtolower($pieces[0]) == 'kit')
		{
			return $pieces[1];
		}
		else
		{
			return $CI->Item_kit->get_item_kit_id($item_kit_id);
		}
	}
	
	
	public abstract function return_order($id);
	
	function does_discount_exists()
	{
		foreach($this->cart_items as $item)
		{
			if($item->discount!=0)
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	
	//Saves the state of the cart to session
	public function save()
	{		
		if ($this->cart_id !== NULL)
		{
			$CI =& get_instance();	
			$CI->session->set_userdata($this->cart_id, serialize($this));
		}
	}
	
	function return_cart_items($cart_items)
	{
		$this->cart_items = $cart_items;
		
		foreach($this->get_items() as $item)
		{
			$item->quantity=-$item->quantity;
		}
	}
	
	public function to_array()
	{
		$data = array();
		$data['cart']=$this;
		$data['cart_items']=$this->get_items();
		$data['items_in_cart'] = count($data['cart_items']);
		$data['subtotal']=$this->get_subtotal();
		$data['payments']=$this->get_payments();
		$data['taxes']=$this->get_taxes();
		$data['total']=$this->get_total();
		$data['amount_due'] = $this->get_amount_due();
		$data['comment'] = $this->comment;
		$data['discount_exists'] = $this->does_discount_exists();
		$data['mode'] = $this->mode;
		$data['selected_payment'] = $this->selected_payment;
		$data['type_comprobante'] =$this->type_comprobante;
		$data['payments_cover_total'] = $this->do_payments_cover_total();
		$data['email_receipt'] = $this->email_receipt;
		$data['store_account_payment'] = $this->get_mode() == 'store_account_payment' ? 1 : 0;
		$data['change_date_enable'] = $this->change_date_enable;
		$data['suspended'] = $this->suspended;
		$data['paid_store_account_ids'] = $this->paid_store_account_ids;
		$data['change_cart_date'] = $this->change_cart_date;
		// echo "<pre>";
		// print_r ($data);
		// echo "</pre>";exit();
		return $data;
	}

	function validate_payment($payment_type,$payment_amount,$payment_date = false)
	{
		$payment_date = $payment_date !== FALSE ? $payment_date : date('Y-m-d H:i:s');
		
		foreach($this->get_payments() as $payment)
		{
			if ($payment_type == $payment->payment_type && $payment->payment_amount == $payment_amount)
			{
				//Do a check based on timestamp to be a little more relaxed
				
				//If payment amount is within 5 seconds deny it
				$seconds_diff = strtotime($payment_date) - strtotime($payment->payment_date);
				if ($seconds_diff < 5)
				{
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}
	
	function can_convert_cart_from_sale_to_return()
	{
		$cart = $this->get_items();
		
		if (!$cart || count($cart) == 0)
		{
			return FALSE;
		}
		
		foreach($cart as $cart_item)
		{	
			if ($cart_item->quantity < 0)
			{
				return false;
			}
		}
		unset($cart_item);
		
		return TRUE;
	}
	
	function do_convert_cart_from_sale_to_return()
	{
		$cart = $this->get_items();
		
		foreach($cart as $cart_item)
		{
			$cart_item->quantity = -1 * abs($cart_item->quantity);
		}
	}
	
	function can_convert_cart_from_return_to_sale()
	{
		$cart = $this->get_items();
		
		if (!$cart || count($cart) == 0)
		{
			return FALSE;
		}
		
		foreach($cart as $cart_item)
		{	
			if ($cart_item->quantity > 0)
			{
				return false;
			}
		}
		unset($cart_item);
		
		return TRUE;
	}
	
	function do_convert_cart_from_return_to_sale()
	{
		$cart = $this->get_items();
		
		foreach($cart as $cart_item)
		{
			$cart_item->quantity = 1 * abs($cart_item->quantity);
		}
	}
	
	function get_last_item_added_price()
	{
		$items = $this->get_items();
		
		if (!empty($items))
		{
			//Get last element then reset pointer so nothing gets messed
			$last_item = end($items);
			reset($items);
			return $last_item->unit_price;
		}		
	
		return FALSE;
	}
	
	function do_all_variation_items_have_variation_selected()
	{
		foreach($this->get_items() as $cart_item)
		{
			//If we have variation choices but don't have a variation id
			if (!empty($cart_item->variation_choices) && !$cart_item->variation_id)
			{
				return FALSE;
			}
		}
		return true;
	}
		
	abstract function get_previous_receipt_id();
	abstract function process_barcode_scan($barcode_scan_data,$options = array());
}