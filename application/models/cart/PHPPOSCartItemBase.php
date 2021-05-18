<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

abstract class PHPPOSCartItemBase
{
	public $cart;
	public $type;
	
	public $scan;
	public $existed_previously = FALSE;
	public $line = NULL; //This is used when we pull back for previous sales; new sales don't have this until saved
	public $name;
	public $quantity = 1;
	public $unit_price;
	public $cost_price;
	public $discount = 0;
	public $description;
	public $serialnumber;
	public $size;
	public $item_number;
	public $is_service;
	public $product_id;
	public $allow_alt_description;
	public $is_serialized;
	public $taxable;
	public $system_item;
	public $has_edit_price;
	public $allow_price_override_regardless_of_permissions;
	public $only_integer;
	public $is_series_package;
	public $series_quantity;
	public $series_days_to_use_within;
	public $category_id;
	public $image_id;
	public $unity;
	public function __construct(array $params = array())
	{	
		foreach($params as $name=>$value)
		{
	 		if (property_exists($this,$name))
	 		{
	 	 	 $this->$name = $value;
			}
		}
				
		if (!in_array($this->type, array('sale','receiving')))
		{
      throw new Exception("Cart Item type is set to a valid value (sale or receiving)");
		}
		
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
	public function get_taxes()
	{
		$taxes = array();
		
    $CI =& get_instance();
		
		//Use previous taxes in part if the item existed previoulsy, we have an id and we are NOT doing an edit on previous sale
		if ($this->existed_previously && $this->cart->get_previous_receipt_id() && !$this->cart->is_editing_previous)
		{
			return $this->get_taxes_for_cart_item_saved_previously();
		}
		else
		{
			return $this->get_taxes_for_cart_item_not_saved_yet();
		}	
	}
	
	//This funcions will key all the taxes by line id. This is for performance
	private function key_taxes_by_line_id($taxes)
	{
		$return = array();
		
		foreach($taxes as $tax)
		{
			$return[$tax['line']][] = $tax;
		}
		
		return $return;
	}
	
	//This gets taxes for an item that was part of a past cart. This was determined by the existed_previously instance variable 
	//This uses extensive caching to only hit database as little as possible
	public function get_taxes_for_cart_item_saved_previously()
	{
		//cache this so we don't need to hit database everytime
		static $taxes_for_past = array();
		
		$cache_key = $this->cart->get_cart_id().'|'.$this->cart->get_previous_receipt_id();
    $CI =& get_instance();
		
		if(!isset($taxes_for_past[$cache_key]))
		{			
			$taxes_for_past[$cache_key] = $this->type == 'receiving' ? $CI->Receiving->get_receiving_items_taxes($this->cart->get_previous_receipt_id()) : array_merge($CI->Sale->get_sale_items_taxes($this->cart->get_previous_receipt_id()), $CI->Sale->get_sale_item_kits_taxes($this->cart->get_previous_receipt_id()));
			$taxes_for_past[$cache_key] = $this->key_taxes_by_line_id($taxes_for_past[$cache_key]);			
		}
		
		$taxes = array();
				
		if (isset($taxes_for_past[$cache_key][$this->line]))
		{
			foreach($taxes_for_past[$cache_key][$this->line] as $key=>$tax_item)
			{
				$name = $tax_item['percent'].'% ' . $tax_item['name'];
		
				if ($tax_item['cumulative'])
				{
					$prev_tax = ($tax_item['price']*$tax_item['quantity']-$tax_item['price']*$tax_item['quantity']*$tax_item['discount']/100)*(($taxes_for_past[$cache_key][$this->line][$key-1]['percent'])/100);
					$tax_amount=(($tax_item['price']*$tax_item['quantity']-$tax_item['price']*$tax_item['quantity']*$tax_item['discount']/100) + $prev_tax)*(($tax_item['percent'])/100);					
				}
				else
				{
					$tax_amount=($tax_item['price']*$tax_item['quantity']-$tax_item['price']*$tax_item['quantity']*$tax_item['discount']/100)*(($tax_item['percent'])/100);
				}

				if (!isset($taxes[$name]))
				{
					$taxes[$name] = 0;
				}
				$taxes[$name] += $tax_amount;
			}
		}		
		return $taxes;
		
	}
	
	//This gets taxes for an item for a cart that hasn't been saved before. This means it doesn't have get_previous_receipt_id and is staging in session
	public function get_taxes_for_cart_item_not_saved_yet()
	{
    $CI =& get_instance();
		
		if ($this->type == 'receiving' && !$CI->config->item('charge_tax_on_recv'))
		{
			return array();
		}
		
		if($this->type == 'sale')
		{
			$customer_id = $this->cart->customer_id;
			$customer = $CI->Customer->get_info($customer_id, true);

			//Do not charge sales tax if we have a customer that is not taxable
			if (!$customer->taxable && $customer_id)
			{
			   return array();
			}
		}		
		
		$taxes = array();
		
		if (is_subclass_of($this,'PHPPOSCartItem'))
		{
			$tax_info = $CI->Item_taxes_finder->get_info($this->item_id,$this->type);
		}
		else
		{
			$tax_info = $CI->Item_kit_taxes_finder->get_info($this->item_kit_id,$this->type);
		}
		
		foreach($tax_info as $key=>$tax)
		{
			$price_to_use = $this->type == 'sale' ? $this->get_price_exclusive_of_tax() : $this->unit_price;	
			
			$name = $tax['percent'].'% ' . $tax['name'];
		
			if ($tax['cumulative'])
			{
				$prev_tax = ($price_to_use*$this->quantity-$price_to_use*$this->quantity*$this->discount/100)*(($tax_info[$key-1]['percent'])/100);
				$tax_amount=(($price_to_use*$this->quantity-$price_to_use*$this->quantity*$this->discount/100) + $prev_tax)*(($tax['percent'])/100);					
			}
			else
			{
				$tax_amount=($price_to_use*$this->quantity-$price_to_use*$this->quantity*$this->discount/100)*(($tax['percent'])/100);
			}
			
			if (!in_array($name, $this->cart->get_excluded_taxes()))
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
	
	public function get_subtotal()
	{
		return to_currency_no_money($this->unit_price*$this->quantity-$this->unit_price*$this->quantity*$this->discount/100);
	}	
	
	public function get_total()
	{
		$taxes_total = 0;
		
		foreach(array_values($this->get_taxes()) as $tax_amount)
		{
			$taxes_total+=$tax_amount;
		}
		return $this->get_subtotal() + $taxes_total;
	}
	
	public function get_profit()
	{
		$CI =& get_instance();			
		
		$store_account_item_id = $CI->Item->get_store_account_item_id();
		
		if ($this->get_id() != $store_account_item_id)
		{
			$item_cost_price = $this->cost_price;
		}
		else // Set cost price = price so we have no profit
		{
			$item_cost_price = $this->unit_price;
		}
	  return to_currency_no_money(($this->unit_price*$this->quantity-$this->unit_price*$this->quantity*$this->discount/100) - ($item_cost_price*$this->quantity));
		
	}
	
	function below_cost_price()
	{
			$price = $this->unit_price;				
			$discount = $this->discount;	
			$cost_price = $this->cost_price;
			$total_for_one = $price-$price*$discount/100;
			return $total_for_one < $cost_price;		
	}
	
	
	public abstract function get_id();
	public abstract function validate();
}