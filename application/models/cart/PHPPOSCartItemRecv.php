<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('PHPPOSCartItem.php');

class PHPPOSCartItemRecv extends PHPPOSCartItem
{
	public $selling_price;
	public $quantity_received;
	public $expire_date;
	public $cost_price_preview;
	
	public function __construct(array $params = array())
	{		
		$params['type'] = 'receiving';
		parent::__construct($params);
	}
	
	function get_price_exclusive_of_tax()
	{
		$CI =& get_instance();

		$sale_id = $this->cart->get_previous_receipt_id();
		
		$price_to_use = $this->unit_price;
		
		$item_info = $CI->Item->get_info($this->get_id());
		if($item_info->tax_included)
		{
			if ($sale_id && !$this->cart->is_editing_previous)
			{
				$CI->load->helper('items');
				$price_to_use = get_price_for_item_excluding_taxes($this->line, $this->unit_price, $sale_id);
			}
			else
			{
				$CI->load->helper('items');
				$price_to_use = get_price_for_item_excluding_taxes($this->item_id, $this->unit_price);
			}
		}
		
		return $price_to_use;
	}
}