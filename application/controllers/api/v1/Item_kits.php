<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . 'libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Item_kits extends REST_Controller {
	
		protected $methods = [
        'index_get' => ['level' => 1, 'limit' => 20],
        'index_post' => ['level' => 2, 'limit' => 20],
        'index_delete' => ['level' => 2, 'limit' => 20],
        'batch_post' => ['level' => 2, 'limit' => 20],

      ];

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }
			
		private function _item_kit_result_to_array($item_kit)
		{
				$this->load->model('Category');
				$this->load->model('Manufacturer');
				$this->load->model('Tag');
				$manufacturers = array();
	
			 foreach($this->Manufacturer->get_all() as $id => $row)
			 {
					$manufacturers[$id] = $row['name'];
 
			 }

				$item_kit_return = array(
					'item_kit_id' => (int)$item_kit->item_kit_id,
					'name' => $item_kit->name,
					'item_kit_number' => $item_kit->item_kit_number,
					'product_id' => $item_kit->product_id,
					'category' => $this->Category->get_full_path($item_kit->category_id),
					'category_id' => $item_kit->category_id  ? (int) $item_kit->category_id : NULL,
					'manufacturer' => isset($manufacturers[$item_kit->manufacturer_id]) ? $manufacturers[$item_kit->manufacturer_id] : '',
					'manufacturer_id' => $item_kit->manufacturer_id ? $item_kit->manufacturer_id : NULL,
					'cost_price' => $item_kit->cost_price !== NULL ? to_currency_no_money($item_kit->cost_price) : NULL,
					'unit_price' => $item_kit->unit_price !== NULL ? to_currency_no_money($item_kit->unit_price) : NULL,
					'max_discount_percent' => $item_kit->max_discount_percent !== NULL ? to_quantity($item_kit->max_discount_percent,FALSE) : NULL,
					'max_edit_price' => $item_kit->max_edit_price !== NULL ? to_currency_no_money($item_kit->max_edit_price) : NULL,
					'min_edit_price' => $item_kit->min_edit_price !== NULL ? to_currency_no_money($item_kit->min_edit_price) : NULL,
					'description' => $item_kit->description,
					'disable_loyalty' => $item_kit->disable_loyalty ? TRUE : FALSE,
					'tax_included' => $item_kit->tax_included ? TRUE : FALSE,
					'change_cost_price' => $item_kit->change_cost_price ? TRUE : FALSE,
					'override_default_tax' => $item_kit->override_default_tax ? TRUE : FALSE,
					'tax_class_id' => $item_kit->tax_class_id ? $item_kit->tax_class_id : NULL,
					'tags' => $this->Tag->get_tags_for_item_kit($item_kit->item_kit_id),
					'commission_percent' => $item_kit->commission_percent !== NULL ? to_quantity($item_kit->commission_percent,false) : NULL,
					'commission_fixed' => $item_kit->commission_fixed !== NULL  ? to_currency_no_money($item_kit->commission_fixed) : NULL,
					'commission_percent_type' => $item_kit->commission_percent_type !== NULL ? $item_kit->commission_percent_type : NULL,
					'allow_price_override_regardless_of_permissions' => $item_kit->allow_price_override_regardless_of_permissions ? TRUE : FALSE,
					'only_integer' => $item_kit->only_integer ? TRUE : FALSE,
					'is_barcoded' => $item_kit->is_barcoded ? TRUE : FALSE,
					
				);
				
				$item_kit_return['items'] = array();
				$this->load->model('Item_kit_items');
				
				$item_kit_items = $this->Item_kit_items->get_info($item_kit->item_kit_id);
				
				foreach($item_kit_items as $item_kit_item)
				{
					$item_kit_return['items'][] = array('item_id' => $item_kit_item->item_id,'item_variation_id' => $item_kit_item->item_variation_id, 'quantity' => to_quantity($item_kit_item->quantity,FALSE));
				}
	
				for($k=1;$k<=NUMBER_OF_PEOPLE_CUSTOM_FIELDS;$k++)
				{
					if($this->Item_kit->get_custom_field($k) !== false)
					{
						$field = array();
						$field['label']= $this->Item_kit->get_custom_field($k);
						if($this->Item_kit->get_custom_field($k,'type') == 'date')
						{
							$field['value'] = date_as_display_date($item_kit->{"custom_field_{$k}_value"});
						}
						else
						{
							$field['value'] = $item_kit->{"custom_field_{$k}_value"};
						}
						
						$item_kit_return['custom_fields'][$field['label']] = $field['value'];
					}
	
				}
				
				$this->load->model('Tier');
				$item_kit_return['tier_pricing'] = array();
			  foreach($this->Tier->get_all()->result_array() as $tier)
			  {
					$tier_id = $tier['id'];
					$tier_name = $tier['name'];
					$tier_price_row = $this->Item_kit->get_tier_price_row($tier_id,$item_kit->item_kit_id);
					
					if ($tier_price_row)
					{
						if ($tier_price_row->unit_price !== NULL)
						{
							$tier_type = 'unit_price';
							$tier_value = to_currency_no_money($tier_price_row->unit_price,10);
						
						}
						elseif($tier_price_row->percent_off !== NULL)
						{
							$tier_type = 'percent_off';		
							$tier_value = to_quantity($tier_price_row->percent_off,false);						
													
						}
						elseif($tier_price_row->cost_plus_percent !== NULL)
						{
							$tier_type = 'cost_plus_percent';		
							$tier_value = to_quantity($tier_price_row->cost_plus_percent,false);						
																						
						}
						elseif($tier_price_row->cost_plus_fixed_amount !== NULL)
						{
							$tier_type = 'cost_plus_fixed_amount';
							$tier_value = to_currency_no_money($tier_price_row->cost_plus_fixed_amount,10);						
						}
						else
						{
							$tier_type = NULL;
						}
			
						if ($tier_type !== NULL)
						{
				  		$item_kit_return['tier_pricing'][] = array('name' => $tier_name, 'value' => $tier_value,'type' => $tier_type);
						}
					}
				}
				
				$item_kit_return['locations'] = array();
				
				$this->load->model('Location');
				$this->load->model('Item_kit_location');
				
				foreach($this->Location->get_all()->result_array() as $location_row)
				{
					$item_kit_loc_row = array();
					$item_kit_location_info = $this->Item_kit_location->get_info($item_kit->item_kit_id,$location_row['location_id']);
					$item_kit_loc_row['unit_price'] = to_currency_no_money($item_kit_location_info->unit_price);
					$item_kit_loc_row['cost_price'] = to_currency_no_money($item_kit_location_info->cost_price);
					$item_kit_loc_row['override_default_tax'] = $item_kit_location_info->override_default_tax ? TRUE : FALSE;
					$item_kit_loc_row['tax_class_id'] = $item_kit_location_info->tax_class_id ? $item_kit_location_info->tax_class_id : NULL;
					$item_kit_loc_row['tier_pricing'] = array();
				  foreach($this->Tier->get_all()->result_array() as $tier)
				  {
						$tier_id = $tier['id'];
						$tier_name = $tier['name'];
						$tier_price_row = $this->Item_kit_location->get_tier_price_row($tier_id,$item_kit->item_kit_id,$location_row['location_id']);
					
						if ($tier_price_row)
						{
							if ($tier_price_row->unit_price !== NULL)
							{
								$tier_type = 'unit_price';
								$tier_value = to_currency_no_money($tier_price_row->unit_price,10);
						
							}
							elseif($tier_price_row->percent_off !== NULL)
							{
								$tier_type = 'percent_off';		
								$tier_value = to_quantity($tier_price_row->percent_off,false);						
													
							}
							elseif($tier_price_row->cost_plus_percent !== NULL)
							{
								$tier_type = 'cost_plus_percent';		
								$tier_value = to_quantity($tier_price_row->cost_plus_percent,false);						
																						
							}
							elseif($tier_price_row->cost_plus_fixed_amount !== NULL)
							{
								$tier_type = 'cost_plus_fixed_amount';
								$tier_value = to_currency_no_money($tier_price_row->cost_plus_fixed_amount,10);						
							}
							else
							{
								$tier_type = NULL;
							}
			
							if ($tier_type !== NULL)
							{
					  		$item_kit_loc_row['tier_pricing'][] = array('name' => $tier_name, 'value' => $tier_value,'type' => $tier_type);
							}
						}
					}
					
					$item_kit_return['locations'][$location_row['location_id']] = $item_kit_loc_row;
				}
				
				
				return $item_kit_return;
		}

		public function index_delete($item_kit_id)
		{
			$this->load->model('Item_kit');

			if ($item_kit_id === NULL || !is_numeric($item_kit_id))
      {
      		$this->response(NULL, REST_Controller::HTTP_BAD_REQUEST);
			}
			  $item_kit = $this->Item_kit->get_info($item_kit_id);
      	if ($item_kit->item_kit_id && !$item_kit->deleted)
				{	
						$this->Item_kit->delete($item_kit_id);
				    $item_kit_return = $this->_item_kit_result_to_array($item_kit);
						$this->response($item_kit_return, REST_Controller::HTTP_OK);
				}
				else
				{
						$this->response(NULL, REST_Controller::HTTP_NOT_FOUND);
				}
			
		}
				
    public function index_get($item_kit_id = NULL)
    {
			$this->load->model('Item_kit');
			$this->load->helper('url');
			$this->load->helper('date');
			
			if ($item_kit_id === NULL)
      {
      	$search = $this->input->get('search');
      	$search_field = $this->input->get('search_field');
				$offset = $this->input->get('offset');
				$limit = $this->input->get('limit');
				
				if ($limit !== NULL && $limit > 100)
				{
					$limit = 100;
				}

				
				if ($search || $this->input->get('category_id'))
				{
					if ($search_field !== NULL)
					{
							$search_field_map = array(
							'item_kit_id' => $this->db->dbprefix('item_kits').'.item_kit_id',
							'item_kit_number' => $this->db->dbprefix('item_kits').'.item_kit_number',
							'product_id' => $this->db->dbprefix('item_kits').'.product_id',
							'name' => $this->db->dbprefix('item_kits').'.name',
							'description' => $this->db->dbprefix('item_kits').'.description',
							'cost_price' => $this->db->dbprefix('item_kits').'.cost_price',
							'unit_price' => $this->db->dbprefix('item_kits').'.unit_price',
							'manufacturer_name' => $this->db->dbprefix('manufacturers').'.name',
							'tag_name' => $this->db->dbprefix('tags').'.name',
							);

						
						$custom_fields_map = array();
			
						for($k=1;$k<=NUMBER_OF_PEOPLE_CUSTOM_FIELDS;$k++)
						{
							if($this->Item_kit->get_custom_field($k) !== false)
							{
								$custom_fields_map[$this->Item_kit->get_custom_field($k)] = $this->db->dbprefix('item_kits').".custom_field_${k}_value";
							}
						}
						
						if (isset($search_field_map[$search_field]))
						{
							$search_field = $search_field_map[$search_field];
						}
						if (isset($custom_fields_map[$search_field]))
						{
							$search_field = $custom_fields_map[$search_field];
						}
						elseif (strpos($search_field, 'custom_field') !== false)
						{
							$search_field = $this->db->dbprefix('item_kits').'.'.$search_field;
						}
					}
					
					$item_kits = $this->Item_kit->search($search, 0, $this->input->get('category_id') ? $this->input->get('category_id') : FALSE,$limit!==NULL ? $limit : 20, $offset!==NULL ? $offset : 0,'name','asc',$search_field ? $search_field : 'all')->result();
					$total_records = $this->Item_kit->search_count_all($search, 0,$this->input->get('category_id') ? $this->input->get('category_id') : FALSE,10000,$search_field ? $search_field : 'all');
				}
				else
				{
					$item_kits = $this->Item_kit->get_all(0,$limit!==NULL ? $limit : 20, $offset!==NULL ? $offset : 0)->result();
					$total_records = $this->Item_kit->count_all(0);
				}
				
				$item_kits_return = array();
				foreach($item_kits as $item_kit)
				{
						$item_kits_return[] = $this->_item_kit_result_to_array($item_kit);
				}
				
				header("x-total-records: $total_records");
				
				$this->response($item_kits_return, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
      }
      else
      {
      			if (!is_numeric($item_kit_id))
      			{
							$this->response(NULL, REST_Controller::HTTP_BAD_REQUEST);
      			}
      			
        		$item_kit = $this->Item_kit->get_info($item_kit_id);
        		
        		if ($item_kit->item_kit_id)
        		{
        			$item_kit_return = $this->_item_kit_result_to_array($item_kit);
							$this->response($item_kit_return, REST_Controller::HTTP_OK);
					}
					else
					{
							$this->response(NULL, REST_Controller::HTTP_NOT_FOUND);
					}			
      }
    }
    
    public function index_post($item_kit_id = NULL)
    {
			if ($item_kit_id!== NULL)
			{
				$this->_update($item_kit_id);
				return;
			}
			
    	$this->load->model('Item_kit');
			$item_kit_request = json_decode(file_get_contents('php://input'),TRUE);
			
			if ($item_kit_id = $this->_create_item_kit($item_kit_request))
			{
				$item_kit_return = $this->_item_kit_result_to_array($this->Item_kit->get_info($item_kit_id));
				$this->response($item_kit_return, REST_Controller::HTTP_OK);
			}
			
			$this->response(NULL, REST_Controller::HTTP_METHOD_NOT_ALLOWED);
			
    }
    
    private function _populate_custom_fields($item_kit_request,&$item_kit_data)
    {
    	$custom_fields_map = array();
			
			for($k=1;$k<=NUMBER_OF_PEOPLE_CUSTOM_FIELDS;$k++)
			{
				if($this->Item_kit->get_custom_field($k) !== false)
				{
					$custom_fields_map[$this->Item_kit->get_custom_field($k)] = array('index' => $k, 'type' => $this->Item_kit->get_custom_field($k,'type'));
				}

			}
			if (isset($item_kit_request['custom_fields']))
			{
				foreach($item_kit_request['custom_fields'] as $custom_field => $custom_field_value)
				{
					if(isset($custom_fields_map[$custom_field]))
					{
						$key = $custom_fields_map[$custom_field]['index'];
						$type = $custom_fields_map[$custom_field]['type'];
					
						if ($type == 'date')
						{
							$item_kit_data["custom_field_{$key}_value"] = strtotime($custom_field_value);
						}
						else
						{
							$item_kit_data["custom_field_{$key}_value"] = $custom_field_value;
						}
					}
				}
			}
			
			
    }
    
    private function _create_item_kit($item_kit_request)
    {
    	 $this->load->model('Item_kit');
    	 $this->load->model('Tag');

			$item_kit_data = array(
						'name'=>isset($item_kit_request['name']) ? $item_kit_request['name'] :  '',
						'description'=>isset($item_kit_request['description']) ? $item_kit_request['description'] :  '',
						'item_kit_number'=>isset($item_kit_request['item_kit_number']) ? $item_kit_request['item_kit_number'] :  NULL,
						'product_id'=>isset($item_kit_request['product_id']) ? $item_kit_request['product_id'] :  NULL,
						'category_id'=>isset($item_kit_request['category_id']) ? $item_kit_request['category_id'] :  NULL,
						'manufacturer_id'=>isset($item_kit_request['manufacturer_id']) ? $item_kit_request['manufacturer_id'] : NULL,
						'unit_price'=>isset($item_kit_request['unit_price']) ? $item_kit_request['unit_price'] : NULL,
						'cost_price'=>isset($item_kit_request['cost_price']) ? $item_kit_request['cost_price'] : NULL,
						'max_discount_percent'=>isset($item_kit_request['max_discount_percent']) ? $item_kit_request['max_discount_percent'] : NULL,
						'max_edit_price'=>isset($item_kit_request['max_edit_price']) ? $item_kit_request['max_edit_price'] : NULL,
						'min_edit_price'=>isset($item_kit_request['min_edit_price']) ? $item_kit_request['min_edit_price'] : NULL,
						'disable_loyalty'=>isset($item_kit_request['disable_loyalty']) && $item_kit_request['disable_loyalty'] ? 1 : 0,
						'tax_included'=>isset($item_kit_request['tax_included']) && $item_kit_request['tax_included'] ? 1 : 0,
						'change_cost_price'=>isset($item_kit_request['change_cost_price']) && $item_kit_request['change_cost_price'] ? 1 : 0,
						'override_default_tax'=>isset($item_kit_request['override_default_tax']) && $item_kit_request['override_default_tax'] ? 1 : 0,
						'tax_class_id'=>isset($item_kit_request['tax_class_id']) ? $item_kit_request['tax_class_id'] : NULL,
						'commission_percent' => isset($item_kit_request['commission_percent']) ? $item_kit_request['commission_percent'] : NULL,
						'commission_fixed' => isset($item_kit_request['commission_fixed']) ? $item_kit_request['commission_fixed'] : NULL,
						'commission_percent_type' => isset($item_kit_request['commission_percent_type']) ? $item_kit_request['commission_percent_type'] : NULL,
						'allow_price_override_regardless_of_permissions'=>isset($item_kit_request['allow_price_override_regardless_of_permissions']) && $item_kit_request['allow_price_override_regardless_of_permissions'] ? 1 : 0,
						'only_integer'=>isset($item_kit_request['only_integer']) && $item_kit_request['only_integer'] ? 1 : 0,
						'is_barcoded'=>isset($item_kit_request['is_barcoded']) && $item_kit_request['is_barcoded'] ? 1 : 0,
			);
		

			$this->_populate_custom_fields($item_kit_request,$item_kit_data);
			$this->Item_kit->save($item_kit_data);
			
			if (isset($item_kit_request['locations']))
			{
				$this->_save_item_kit_location_data($item_kit_request['locations'],$item_kit_data['item_kit_id']);
			}
			
			if (isset($item_kit_request['tags']) && $item_kit_request['tags'])
			{
				$this->Tag->save_tags_for_item_kit($item_kit_data['item_kit_id'] , implode(',',$item_kit_request['tags']));
			}
			
			if (isset($item_kit_request['items']) && $item_kit_request['items'])
			{
					$item_kit_items = array();
				foreach($item_kit_request['items'] as $item_kit_item)
				{
					$item_id = $item_kit_item['item_id'];
					$item_variation_id = isset($item_kit_item['item_variation_id']) ? $item_kit_item['item_variation_id'] : NULL; 
					$quantity = $item_kit_item['quantity'];

					$item_kit_items[] = array(
							'item_id' => $item_id,
							'item_variation_id' => $item_variation_id,
							'quantity' => $quantity
							);
				}
				$this->load->model('Item_kit_items');
				$this->Item_kit_items->save($item_kit_items, $item_kit_data['item_kit_id']);

			}
			
			if (isset($item_kit_request['tier_pricing']))
			{
				if (!empty($item_kit_request['tier_pricing']))
				{
					$this->load->model('Tier');
					foreach($item_kit_request['tier_pricing'] as $tier_data)
					{
						$tier_info = $this->Tier->get_info_by_name($tier_data['name']);
						
						//Couldn't find tier
						if (!$tier_info->id)
						{
							continue;
						}
						
						$price_or_percent = $tier_data['value'];
						$tier_type = $tier_data['type'];
						$tier_id = $tier_info->id;
						
						if ($price_or_percent)
						{				
							$tier_data=array('tier_id'=>$tier_id);
							$tier_data['item_kit_id'] = $item_kit_data['item_kit_id'];

							if ($tier_type == 'unit_price')
							{
								$tier_data['unit_price'] = $price_or_percent;
								$tier_data['percent_off'] = NULL;
								$tier_data['cost_plus_percent'] = NULL;
								$tier_data['cost_plus_fixed_amount'] = NULL;
							}
							elseif($tier_type == 'percent_off')
							{
								$tier_data['percent_off'] = (float)$price_or_percent;
								$tier_data['unit_price'] = NULL;
								$tier_data['cost_plus_percent'] = NULL;
								$tier_data['cost_plus_fixed_amount'] = NULL;
							}
							elseif($tier_type == 'cost_plus_percent')
							{
								$tier_data['percent_off'] = NULL;
								$tier_data['unit_price'] = NULL;
								$tier_data['cost_plus_percent'] = (float)$price_or_percent;
								$tier_data['cost_plus_fixed_amount'] = NULL;
							}
							elseif($tier_type == 'cost_plus_fixed_amount')
							{
								$tier_data['percent_off'] = NULL;
								$tier_data['unit_price'] = NULL;
								$tier_data['cost_plus_percent'] = NULL;
								$tier_data['cost_plus_fixed_amount'] = (float)$price_or_percent;
							}
					
							$this->Item_kit->save_item_tiers($tier_data,$item_kit_data['item_kit_id']);
						}
						else
						{
							$this->Item_kit->delete_tier_price($tier_id, $item_kit_data['item_kit_id']);
						}
					}
				}
				else
				{
					$this->Item_kit->delete_all_tier_prices($item_kit_data['item_kit_id']);
				}
			}
			
			return $item_kit_data['item_kit_id'];
    }
    
    private function _update_item_kit($item_kit_id,$item_kit_request)
    {
   	  $this->load->model('Item_kit');

			$item_kit_data = array();
    	foreach($item_kit_request as $key=>$value)
    	{
    			if ($key=="items")
    			{
    			  $item_kit_items = array();
						foreach($value as $item_kit_item)
						{
							$item_id = $item_kit_item['item_id'];
							$item_variation_id = isset($item_kit_item['item_variation_id']) ? $item_kit_item['item_variation_id'] : NULL; 
							$quantity = $item_kit_item['quantity'];

							$item_kit_items[] = array(
									'item_id' => $item_id,
									'item_variation_id' => $item_variation_id,
									'quantity' => $quantity
									);
						}
						$this->load->model('Item_kit_items');
						$this->Item_kit_items->save($item_kit_items, $item_kit_id);

    			}
    			elseif ($key=="tags")
    			{
    			  $this->load->model('Tag');
						$this->Tag->save_tags_for_item_kit($item_kit_id , implode(',',$value));
    			}
					elseif($key == 'tier_pricing')
					{
						if (!empty($value))
						{
							$this->load->model('Tier');
							foreach($value as $tier_data)
							{
								$tier_info = $this->Tier->get_info_by_name($tier_data['name']);
						
								//Couldn't find tier
								if (!$tier_info->id)
								{
									continue;
								}
						
								$price_or_percent = $tier_data['value'];
								$tier_type = $tier_data['type'];
								$tier_id = $tier_info->id;
						
								if ($price_or_percent)
								{				
									$tier_data=array('tier_id'=>$tier_id);
									$tier_data['item_kit_id'] = $item_kit_id;

									if ($tier_type == 'unit_price')
									{
										$tier_data['unit_price'] = $price_or_percent;
										$tier_data['percent_off'] = NULL;
										$tier_data['cost_plus_percent'] = NULL;
										$tier_data['cost_plus_fixed_amount'] = NULL;
									}
									elseif($tier_type == 'percent_off')
									{
										$tier_data['percent_off'] = (float)$price_or_percent;
										$tier_data['unit_price'] = NULL;
										$tier_data['cost_plus_percent'] = NULL;
										$tier_data['cost_plus_fixed_amount'] = NULL;
									}
									elseif($tier_type == 'cost_plus_percent')
									{
										$tier_data['percent_off'] = NULL;
										$tier_data['unit_price'] = NULL;
										$tier_data['cost_plus_percent'] = (float)$price_or_percent;
										$tier_data['cost_plus_fixed_amount'] = NULL;
									}
									elseif($tier_type == 'cost_plus_fixed_amount')
									{
										$tier_data['percent_off'] = NULL;
										$tier_data['unit_price'] = NULL;
										$tier_data['cost_plus_percent'] = NULL;
										$tier_data['cost_plus_fixed_amount'] = (float)$price_or_percent;
									}
					
									$this->Item_kit->save_item_tiers($tier_data,$item_kit_id);
								}
								else
								{
									$this->Item_kit->delete_tier_price($tier_id, $item_kit_id);
								}
							}
						}
						else
						{
							$this->Item_kit->delete_all_tier_prices($item_kit_id);
						}
					}
					elseif($key == 'locations')
					{
						$this->_save_item_kit_location_data($value,$item_kit_id);
					}
    			elseif($key!='custom_fields')
    			{
						$item_kit_data[$key] = $value;
    			}
    	}
    	
			$this->_populate_custom_fields($item_kit_request,$item_kit_data);
    	return $this->Item_kit->save($item_kit_data,$item_kit_id);
    }
    
    public function _update($item_kit_id)
    {
			$item_kit_request = json_decode(file_get_contents('php://input'),TRUE);
			
      if ($this->_update_item_kit($item_kit_id, $item_kit_request))
			{
				$item_kit_return = $this->_item_kit_result_to_array($this->Item_kit->get_info($item_kit_id));
				$this->response($item_kit_return, REST_Controller::HTTP_OK);
			}
			
			$this->response(NULL, REST_Controller::HTTP_METHOD_NOT_ALLOWED);
			
    }
    
        
    public function batch_post()
    {
       	$this->load->model('Item_kit');

    		$request = json_decode(file_get_contents('php://input'),TRUE);
    		$create = isset($request['create']) ? $request['create']:  array();
    		$update = isset($request['update']) ? $request['update'] : array();
    		$delete = isset($request['delete']) ? $request['delete'] : array();
    		
    		$response = array();
    		
    		if (!empty($create))
    		{
    			$response['create'] = array();
    			
    			foreach($create as $item_kit_request)
    			{
    				if ($item_kit_id = $this->_create_item_kit($item_kit_request))
						{
							$item_kit_return = $this->_item_kit_result_to_array($this->Item_kit->get_info($item_kit_id));
						}
						else
						{
							$item_kit_return = array('error' => TRUE);
						}
						$response['create'][] = $item_kit_return;

    			}
    		}

    		if (!empty($update))
    		{
    			$response['update'] = array();
    			
    				foreach($update as $item_kit_request)
    				{
    				  $item_kit_id = $item_kit_request['item_kit_id'];
							if ($this->_update_item_kit($item_kit_id,$item_kit_request))
							{
								$item_kit_return = $this->_item_kit_result_to_array($this->Item_kit->get_info($item_kit_id));
							}
							else
							{
								$item_kit_return = array('error' => TRUE);
							}
							$response['update'][] = $item_kit_return;
    				}

    		}

    		if (!empty($delete))
    		{
    			$response['delete'] = array();
    			
    			foreach($delete as $item_kit_id)
    			{
							if ($item_kit_id === NULL || !is_numeric($item_kit_id))
     				  {
								$response['delete'][] = array('error' => TRUE);
			      		break;
			      	}
			      	
			  			$item_kit = $this->Item_kit->get_info($item_kit_id);
							if ($item_kit->item_kit_id && !$item_kit->deleted)
							{	
									$this->Item_kit->delete($item_kit_id);
									$item_kit_return = $this->_item_kit_result_to_array($item_kit);
									$response['delete'][] = $item_kit_return;
							}
							else
							{
								$response['delete'][] = array('error' => TRUE);
							}
    			}
    		}
    		
				$this->response($response, REST_Controller::HTTP_OK);
    }
		
		
		
		
		public function _save_item_kit_location_data($location_data,$item_kit_id)
		{
			$this->load->model('Item_kit_location');
			foreach($location_data as $location_id=>$item_kit_location_info)
			{
				$item_kit_location_data = array();
				
				if (isset($item_kit_location_info['unit_price']))
				{
					$item_kit_location_data['unit_price'] = $item_kit_location_info['unit_price'];
				}

				if (isset($item_kit_location_info['cost_price']))
				{
					$item_kit_location_data['cost_price'] = $item_kit_location_info['cost_price'];
				}

				if (isset($item_kit_location_info['override_default_tax']))
				{
					$item_kit_location_data['override_default_tax'] = $item_kit_location_info['override_default_tax'] ? 1 : 0;
				}
				
				if (isset($item_kit_location_info['tax_class_id']))
				{
					$item_kit_location_data['tax_class_id'] = $item_kit_location_info['tax_class_id'];
				}
					
				$this->Item_kit_location->save($item_kit_location_data,$item_kit_id,$location_id);
						
				if (isset($item_kit_location_info['tier_pricing']))
				{
					$this->load->model('Tier');
					foreach($item_kit_location_info['tier_pricing'] as $tier_data)
					{
						$tier_info = $this->Tier->get_info_by_name($tier_data['name']);
				
						//Couldn't find tier
						if (!$tier_info->id)
						{
							continue;
						}
				
						$price_or_percent = $tier_data['value'];
						$tier_type = $tier_data['type'];
						$tier_id = $tier_info->id;
				
						if ($price_or_percent)
						{				
							$tier_data=array('tier_id'=>$tier_id);
							$tier_data['item_kit_id'] = $item_kit_id;

							if ($tier_type == 'unit_price')
							{
								$tier_data['unit_price'] = $price_or_percent;
								$tier_data['percent_off'] = NULL;
								$tier_data['cost_plus_percent'] = NULL;
								$tier_data['cost_plus_fixed_amount'] = NULL;
							}
							elseif($tier_type == 'percent_off')
							{
								$tier_data['percent_off'] = (float)$price_or_percent;
								$tier_data['unit_price'] = NULL;
								$tier_data['cost_plus_percent'] = NULL;
								$tier_data['cost_plus_fixed_amount'] = NULL;
							}
							elseif($tier_type == 'cost_plus_percent')
							{
								$tier_data['percent_off'] = NULL;
								$tier_data['unit_price'] = NULL;
								$tier_data['cost_plus_percent'] = (float)$price_or_percent;
								$tier_data['cost_plus_fixed_amount'] = NULL;
							}
							elseif($tier_type == 'cost_plus_fixed_amount')
							{
								$tier_data['percent_off'] = NULL;
								$tier_data['unit_price'] = NULL;
								$tier_data['cost_plus_percent'] = NULL;
								$tier_data['cost_plus_fixed_amount'] = (float)$price_or_percent;
							}
			
							$this->Item_kit_location->save_item_tiers($tier_data,$item_kit_id,$location_id);
						}
						else
						{
							$this->Item_kit_location->delete_tier_price($tier_id, $item_kit_id,$location_id);
						}
					}
				}
				else
				{
					$this->Item_kit_location->delete_all_tier_prices($item_kit_id,$location_id);
				}
			}
		}
		
}
