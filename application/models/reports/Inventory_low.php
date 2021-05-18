<?php
require_once ("Report.php");
class Inventory_low extends Report
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Category');
	}
	
	public function getInputData()
	{
		$input_data = Report::get_common_report_input_data(TRUE);
				
		$this->load->model('Category');
		$this->load->model('Supplier');
		
		$supplier_entity_data = array();
		$supplier_entity_data['specific_input_name'] = 'supplier';
		$supplier_entity_data['specific_input_label'] = lang('reports_supplier');
		$supplier_entity_data['view'] = 'specific_entity';

		$suppliers = array();
		
		$suppliers[] = lang('common_all');
		foreach($this->Supplier->get_all()->result() as $supplier)
		{
			$suppliers[$supplier->person_id] = $supplier->company_name. ' ('.$supplier->first_name .' '.$supplier->last_name.')';
		}
		
		$supplier_entity_data['specific_input_data'] = $suppliers;
		
		$category_entity_data = array();
		$category_entity_data['specific_input_name'] = 'category_id';
		$category_entity_data['specific_input_label'] = lang('reports_category');
		$category_entity_data['view'] = 'specific_entity';
		
		$categories = array();
		$categories[] =lang('common_all');
		
		$categories_phppos= $this->Category->sort_categories_and_sub_categories($this->Category->get_all_categories_and_sub_categories());
		
		foreach($categories_phppos as $key=>$value)
		{
			$name = str_repeat('&nbsp;&nbsp;', $value['depth']).$value['name'];
			$categories[$key] = $name;
		}
		
		$category_entity_data['specific_input_data'] = $categories;
		
		$specific_entity_data['specific_input_name'] = 'customer_id';
		$specific_entity_data['specific_input_label'] = lang('reports_customer');
		$specific_entity_data['search_suggestion_url'] = site_url('reports/customer_search/1');
		$specific_entity_data['view'] = 'specific_entity';
		
		
		if ($this->settings['display'] == 'tabular')
		{
			$input_params = array();
			$input_params[] = $supplier_entity_data;
			$input_params[] = $category_entity_data;
			$input_params[] = array('view' => 'dropdown','dropdown_label' =>lang('common_inventory'),'dropdown_name' => 'inventory','dropdown_options' =>array('all' => lang('common_all'), 'in_stock' => lang('reports_in_stock'), 'out_of_stock' => lang('reports_out_of_stock')),'dropdown_selected_value' => '');
			$input_params[] = array('view' => 'checkbox','checkbox_label' =>lang('reports_show_only_reorder') ,'checkbox_name' => 'reorder_only');
			$input_params[] = array('view' => 'locations', 'can_view_inventory_at_all_locations' => $this->Employee->has_module_action_permission('reports','view_inventory_at_all_locations', $this->Employee->get_logged_in_employee_info()->person_id));
			$input_params[] = array('view' => 'excel_export');
			$input_params[] = array('view' => 'submit');
		}
		
		$input_data['input_report_title'] = lang('reports_report_options');
		$input_data['input_params'] = $input_params;
		return $input_data;
	}
	
	public function getOutputData()
	{
		$this->lang->load('error');
		
		$this->setupDefaultPagination();
		$summary_data = array();
		$variation_quantity_summary_data = array();
		$report_data = $this->getData();
		
		$details_data = array();
		$details_quantity_data = array();
		foreach ($report_data['details'] as $drow)
		{
			$details_data_row = array();

			$details_data_row[] = array('data'=>$drow['variation_id'], 'align' => 'left');			
			$details_data_row[] = array('data'=>$drow['name'], 'align' => 'left');
			$details_data_row[] = array('data'=>$drow['item_number'], 'align'=> 'left');
			$details_data_row[] = array('data'=>to_quantity($drow['quantity']), 'align'=> 'left');
			$details_data_row[] = array('data'=>to_quantity($drow['reorder_level']), 'align'=> 'left');
			$details_data_row[] = array('data'=>to_quantity($drow['replenish_level']), 'align'=> 'left');

			if ($drow['replenish_level'])
			{
				$details_data_row[] = array('data'=>to_quantity($drow['replenish_level'] - $drow['quantity']), 'align'=> 'left');
			}
			else
			{
				$details_data_row[] = array('data'=>lang('error_unknown'), 'align'=> 'left');
			}
			$details_data[$drow['item_id'].'|'.$drow['location_id']][] = $details_data_row;
			
			$details_quantity_data[$drow['item_id'].'|'.$drow['location_id']][] = array(
				'quantity' => $drow['quantity'],
				'reorder_level' => $drow['reorder_level'],
				'replenish_level' => $drow['replenish_level'],
			);
		}
		
		foreach(array_keys($details_data) as $item_id_location_key)
		{
			$item_quantity = 0;
			$item_reorder_level = 0;
			$item_replenish_level = 0;
			
			for($k=0;$k<count($details_data[$item_id_location_key]);$k++)
			{
				$item_quantity+=$details_quantity_data[$item_id_location_key][$k]['quantity'];
				$item_reorder_level+=$details_quantity_data[$item_id_location_key][$k]['reorder_level'];
				$item_replenish_level+=$details_quantity_data[$item_id_location_key][$k]['replenish_level'];
			}
			
			$variation_quantity_summary_data[$item_id_location_key] = array(
				'quantity' => $item_quantity,
				'reorder_level' => $item_reorder_level,
				'replenish_level' => $item_replenish_level,
			);
			
		}

		$location_count = count(Report::get_selected_location_ids());
				
		foreach($report_data['summary'] as $row)
		{
			$data_row = array();
			if ($location_count > 1)
			{
				$data_row[] = array('data'=>$row['location_name'], 'align' => 'left');
			}
			$data_row[] = array('data'=>$row['item_id'], 'align' => 'left');
			$data_row[] = array('data'=>$row['name'], 'align' => 'left');
			$data_row[] = array('data'=>$this->Category->get_full_path($row['category_id']), 'align'=> 'left');
			$data_row[] = array('data'=>$row['company_name'], 'align'=> 'left');
			$data_row[] = array('data'=>$row['item_number'], 'align'=> 'left');
			$data_row[] = array('data'=>$row['product_id'], 'align'=> 'left');
			if (!$this->config->item('hide_item_descriptions_in_reports') || (isset($this->params['export_excel']) && $this->params['export_excel']))
			{
				$data_row[] = array('data'=>$row['description'], 'align'=> 'left');
			}
			$data_row[] = array('data'=>$row['size'], 'align'=> 'left');
			$data_row[] = array('data'=>$row['location'], 'align'=> 'left');
			
			if($this->has_cost_price_permission)
			{
				$data_row[] = array('data'=>to_currency($row['cost_price']), 'align'=> 'right');
			}
			$data_row[] = array('data'=>to_currency($row['unit_price']), 'align'=> 'right');
			$data_row[] = array('data'=>to_quantity(isset($variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['quantity']) ? $variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['quantity'] : $row['quantity']), 'align'=> 'left');
			$data_row[] = array('data'=>to_quantity(isset($variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['reorder_level']) ? $variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['reorder_level'] : $row['reorder_level']), 'align'=> 'left');
			$data_row[] = array('data'=>to_quantity(isset($variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['replenish_level']) ? $variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['replenish_level'] : $row['replenish_level']), 'align'=> 'left');
			
			$quantity = isset($variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['quantity']) ? $variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['quantity'] : $row['quantity'];
			$replenish_level = isset($variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['replenish_level']) ? $variation_quantity_summary_data[$row['item_id'].'|'.$row['location_id']]['replenish_level'] : $row['replenish_level'];
			if ($replenish_level && ($replenish_level - $quantity) > 0)
			{
				$data_row[] = array('data'=>to_quantity($replenish_level - $quantity), 'align'=> 'right');				
			}
			else
			{
				$data_row[] = array('data'=>lang('error_unknown'), 'align'=> 'right');				
			}
			
			
			$summary_data[$row['item_id'].'|'.$row['location_id']] = $data_row;				
			
		}
		

		$data = array(
			"view" =>'tabular_details',
			"title" => lang('reports_low_inventory_report'),
			"subtitle" => '',
			"headers" => $this->getDataColumns(),
			"summary_data" => $summary_data,
			"overall_summary_data" => $this->getSummaryData(),
			"export_excel" => $this->params['export_excel'],
			"pagination" => $this->pagination->create_links(),
		);
		isset($details_data) && !empty($details_data) ? $data["details_data"]=$details_data: '' ;

		return $data;
		
	}
	
	
	public function getDataColumns()
	{
		
		$columns = array();
		
		$location_count = count(self::get_selected_location_ids());
		
		if ($location_count > 1)
		{
			$columns['summary'][] = array('data'=>lang('common_location'), 'align'=> 'left');
		}
		
		$columns['summary'][] = array('data'=>lang('common_item_id'), 'align'=> 'left');
		$columns['summary'][] = array('data'=>lang('reports_item_name'), 'align'=> 'left');
		$columns['summary'][] = array('data'=>lang('common_category'), 'align'=> 'right');
		$columns['summary'][] = array('data'=>lang('common_supplier'), 'align'=> 'right');
		$columns['summary'][] = array('data'=>lang('common_item_number'), 'align'=> 'right');
		$columns['summary'][] = array('data'=>lang('common_product_id'), 'align'=> 'right');
		if (!$this->config->item('hide_item_descriptions_in_reports') || (isset($this->params['export_excel']) && $this->params['export_excel']))
		{
			$columns['summary'][] = array('data'=>lang('reports_description'), 'align'=> 'right');
		}
		
		$columns['summary'][] = array('data'=>lang('common_size'), 'align'=> 'right');
		$columns['summary'][] = array('data'=>lang('common_location'), 'align'=> 'right');

		if($this->has_cost_price_permission)
		{
			$columns['summary'][] = array('data'=>lang('common_cost_price'), 'align'=> 'right');
		}

		$columns['summary'][] = array('data'=>lang('common_unit_price'), 'align'=> 'left');
		$columns['summary'][] = array('data'=>lang('common_count'), 'align'=> 'left');
		$columns['summary'][] = array('data'=>lang('reports_reorder_level'), 'align'=> 'left');
		$columns['summary'][] = array('data'=>lang('common_replenish_level'), 'align'=> 'left');
		$columns['summary'][] = array('data'=>lang('reports_order_amount'), 'align'=> 'left');

		$columns['details'][] = array('data'=>lang('common_variation_id'), 'align'=> 'left');
		$columns['details'][] = array('data'=>lang('common_variation'), 'align'=> 'left');
		$columns['details'][] = array('data'=>lang('common_item_number'), 'align'=> 'right');
		$columns['details'][] = array('data'=>lang('common_count'), 'align'=> 'left');
		$columns['details'][] = array('data'=>lang('reports_reorder_level'), 'align'=> 'left');
		$columns['details'][] = array('data'=>lang('common_replenish_level'), 'align'=> 'left');
		$columns['details'][] = array('data'=>lang('reports_order_amount'), 'align'=> 'left');
		
		return $columns;
	}
		
	public function getData()
	{
		$item_ids = array();
		
		$location_ids = self::get_selected_location_ids();
		$location_ids_string = implode(',',$location_ids);
		
		$this->dataQuery();
		//If we are exporting NOT exporting to excel make sure to use offset and limit
		if (isset($this->params['export_excel']) && !$this->params['export_excel'])
		{
			$this->db->limit($this->report_limit);
			if (isset($this->params['offset']))
			{
				$this->db->offset($this->params['offset']);
			}
		}
		
		$summary_data = $this->db->get()->result_array();
		
		for($k=0;$k<count($summary_data);$k++)
		{
			$item_ids[] = $summary_data[$k]['item_id'];
		}
		
		$sum_query = 'SUM(DISTINCT '.$this->db->dbprefix('location_item_variations').'.quantity)';

		$this->db->select('item_variations.id as variation_id, location_item_variations.location_id as location_id, items.item_id, GROUP_CONCAT(DISTINCT '.$this->db->dbprefix('attributes').'.name,": ",'.$this->db->dbprefix('attribute_values').'.name SEPARATOR ", ") as name, categories.id as category_id,categories.name as category,location, company_name, item_variations.item_number,size, product_id,
		IFNULL('.$this->db->dbprefix('location_items').'.cost_price, '.$this->db->dbprefix('items').'.cost_price) as cost_price,
		IFNULL('.$this->db->dbprefix('location_items').'.unit_price, '.$this->db->dbprefix('items').'.unit_price) as unit_price,
		'.$sum_query.' as quantity,
		COALESCE('.$this->db->dbprefix('location_item_variations').'.reorder_level,'.$this->db->dbprefix('item_variations').'.reorder_level,'.$this->db->dbprefix('location_items').'.reorder_level, '.$this->db->dbprefix('items').'.reorder_level) as reorder_level,
		COALESCE('.$this->db->dbprefix('location_item_variations').'.replenish_level,'.$this->db->dbprefix('item_variations').'.replenish_level,'.$this->db->dbprefix('location_items').'.replenish_level, '.$this->db->dbprefix('items').'.replenish_level) as replenish_level,
		description', FALSE);
		$this->db->from('item_variations');
		$this->db->join('item_variation_attribute_values', 'item_variations.id = item_variation_attribute_values.item_variation_id');
		$this->db->join('attribute_values', 'attribute_values.id = item_variation_attribute_values.attribute_value_id');
		$this->db->join('attributes', 'attributes.id = attribute_values.attribute_id');
		$this->db->join('items','items.item_id=item_variations.item_id');
		$this->db->join('suppliers', 'items.supplier_id = suppliers.person_id', 'left outer');
		$this->db->join('categories', 'items.category_id = categories.id', 'left outer');
		$this->db->join('location_items', 'location_items.item_id = items.item_id and location_items.location_id IN('.$location_ids_string.')', 'left');
		$this->db->join('location_item_variations', 'location_item_variations.item_variation_id = item_variations.id and location_item_variations.location_id IN('.$location_ids_string.')', 'left');
		$this->db->group_by('item_variations.id');
		if (isset($this->params['reorder_only']))
		{
			$this->db->where('((COALESCE('.$this->db->dbprefix('location_item_variations').'.quantity,'.$this->db->dbprefix('location_items').'.quantity) <= COALESCE('.$this->db->dbprefix('location_item_variations').'.reorder_level,'.$this->db->dbprefix('item_variations').'.reorder_level,'.$this->db->dbprefix('location_items').'.reorder_level, '.$this->db->dbprefix('items').'.reorder_level))) and '.$this->db->dbprefix('items').'.deleted=0');
		}
		else
		{
			$this->db->where('(COALESCE('.$this->db->dbprefix('location_item_variations').'.quantity,'.$this->db->dbprefix('location_items').'.quantity) <= 0 or (COALESCE('.$this->db->dbprefix('location_item_variations').'.quantity,'.$this->db->dbprefix('location_items').'.quantity) <= COALESCE('.$this->db->dbprefix('location_item_variations').'.reorder_level,'.$this->db->dbprefix('item_variations').'.reorder_level,'.$this->db->dbprefix('location_items').'.reorder_level, '.$this->db->dbprefix('items').'.reorder_level))) and '.$this->db->dbprefix('items').'.deleted=0');
		}
		
		if (!empty($item_ids))
		{
			$this->db->group_start();
			$item_ids_chunk = array_chunk($item_ids,25);
			foreach($item_ids_chunk as $item_ids)
			{
				$this->db->or_where_in('items.item_id',$item_ids);
			}
			$this->db->group_end();
		}
		else
		{
			$this->db->where('1', '2', FALSE);
		}
			
		$this->db->order_by('items.name');
		$this->db->where('item_variations.deleted',0);
		$details_data = $this->db->get()->result_array();
		
		
		return array('summary' => $summary_data, 'details' => $details_data);
		
	}
	
	private function dataQuery()
	{
		if ($this->params['category_id'])
		{
			if ($this->config->item('include_child_categories_when_searching_or_reporting'))
			{	
				$category_ids = $this->Category->get_category_id_and_children_category_ids_for_category_id($this->params['category_id']);			
			}
			else
			{
				$category_ids = array($this->params['category_id']);
			}
		}		
		
		$location_ids = self::get_selected_location_ids();
		$location_ids_string = implode(',',$location_ids);

		$sum_query = 'SUM(COALESCE('.$this->db->dbprefix('location_item_variations').'.quantity,'.$this->db->dbprefix('location_items').'.quantity,0))';

		$this->db->select('locations.name as location_name, COALESCE('.$this->db->dbprefix('locations').'.location_id,'.$this->db->dbprefix('location_items').'.location_id,'.$this->db->dbprefix('location_item_variations').'.location_id) as location_id, items.item_id, items.name, categories.id as category_id,categories.name as category,location, company_name, items.item_number,size, product_id,
		IFNULL('.$this->db->dbprefix('location_items').'.cost_price, '.$this->db->dbprefix('items').'.cost_price) as cost_price,
		IFNULL('.$this->db->dbprefix('location_items').'.unit_price, '.$this->db->dbprefix('items').'.unit_price) as unit_price,
		'.$sum_query.' as quantity,
		COALESCE('.$this->db->dbprefix('location_item_variations').'.reorder_level,'.$this->db->dbprefix('item_variations').'.reorder_level,'.$this->db->dbprefix('location_items').'.reorder_level,'.$this->db->dbprefix('location_items').'.reorder_level, '.$this->db->dbprefix('items').'.reorder_level) as reorder_level,
		COALESCE('.$this->db->dbprefix('location_item_variations').'.replenish_level,'.$this->db->dbprefix('item_variations').'.replenish_level,'.$this->db->dbprefix('location_items').'.replenish_level,'.$this->db->dbprefix('location_items').'.replenish_level, '.$this->db->dbprefix('items').'.replenish_level) as replenish_level,
		description', FALSE);
		$this->db->from('items');
		$this->db->join('item_variations','items.item_id=item_variations.item_id and item_variations.deleted = 0','left');
		$this->db->join('suppliers', 'items.supplier_id = suppliers.person_id', 'left outer');
		$this->db->join('categories', 'items.category_id = categories.id', 'left outer');
		$this->db->join('location_items', 'location_items.item_id = items.item_id and location_id IN('.$location_ids_string.')', 'left');
		$this->db->join('location_item_variations', 'location_item_variations.item_variation_id = item_variations.id and location_item_variations.location_id IN('.$location_ids_string.')', 'left');
		$this->db->join('locations', 'locations.location_id = location_items.location_id','left');
		
		if (isset($this->params['reorder_only']))
		{
			$this->db->where('((COALESCE('.$this->db->dbprefix('location_item_variations').'.quantity,'.$this->db->dbprefix('location_items').'.quantity) <= COALESCE('.$this->db->dbprefix('location_item_variations').'.reorder_level,'.$this->db->dbprefix('item_variations').'.reorder_level,'.$this->db->dbprefix('location_items').'.reorder_level, '.$this->db->dbprefix('items').'.reorder_level))) and '.$this->db->dbprefix('items').'.deleted=0');
		}
		else
		{
			$this->db->where('(COALESCE('.$this->db->dbprefix('location_item_variations').'.quantity,'.$this->db->dbprefix('location_items').'.quantity) <= 0 or (COALESCE('.$this->db->dbprefix('location_item_variations').'.quantity,'.$this->db->dbprefix('location_items').'.quantity) <= COALESCE('.$this->db->dbprefix('location_item_variations').'.reorder_level,'.$this->db->dbprefix('item_variations').'.reorder_level,'.$this->db->dbprefix('location_items').'.reorder_level, '.$this->db->dbprefix('items').'.reorder_level))) and '.$this->db->dbprefix('items').'.deleted=0');
		}
		
		$this->db->where('items.system_item',0);
		$this->db->group_by('items.item_id');
		
		if ($this->params['supplier'])
		{
			$this->db->where('suppliers.person_id', $this->params['supplier']);
		}
		
		if ($this->params['category_id'])
		{
			$this->db->where_in('categories.id', $category_ids);
		}
		
		if ($this->params['inventory'] == 'in_stock')
		{
			$this->db->having('quantity > 0');
		}

		if ($this->params['inventory'] == 'out_of_stock')
		{
			$this->db->having('quantity <= 0');
		}
				
	}
	
	function getTotalRows()
	{	
		$this->dataQuery();
		return $this->db->count_all_results();
	}
	
	public function getSummaryData()
	{
		return array();
	}
}
?>