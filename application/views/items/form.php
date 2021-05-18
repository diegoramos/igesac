<?php $this->load->view("partial/header"); ?>
<?php $this->load->view('partial/categories/category_modal', array('categories' => $categories));?>
<?php $this->load->view('partial/unit/unit_modal', array('units' => $units));?>

<?php $query = http_build_query(array('redirect' => $redirect, 'progression' => $progression ? 1 : null, 'quick_edit' => $quick_edit ? 1 : null)); ?>
<?php $manage_query = http_build_query(array('redirect' => uri_string().($query ? "?".$query : ""), 'progression' => $progression ? 1 : null, 'quick_edit' => $quick_edit ? 1 : null)); ?>
<?php
	// Check that if POS connected with QBO if yes then text length will be 100 if no then text length will be -1(unlimited)
    if ($this->config->item('quickbooks_access_token')) {
		$input_max_length = MAX_LENGTH_NAME_QB;
	} else {
		$input_max_length = '-1';
	}
?>
<div class="spinner" id="grid-loader" style="display:none;">
  <div class="rect1"></div>
  <div class="rect2"></div>
  <div class="rect3"></div>
</div>

<div class="manage_buttons">
	<div class="row">
		<div class="<?php echo isset($redirect) ? 'col-xs-9 col-sm-10 col-md-10 col-lg-10': 'col-xs-12 col-sm-12 col-md-12' ?> margin-top-10">
			<div class="modal-item-info padding-left-10">
				<div class="modal-item-details margin-bottom-10">
					<?php if(!$item_info->item_id) { ?>
			    <span class="modal-item-name new"><?php echo lang('items_new'); ?></span>
					<?php } else { ?>
		    	<span class="modal-item-name"><?php echo H($item_info->name); ?></span>
					<span class="modal-item-category"><?php echo H($category); ?></span>
					<?php } ?>
				</div>
			</div>	
		</div>
		<?php if(isset($redirect) && !$progression) { ?>
		<div class="col-xs-3 col-sm-2 col-md-2 col-lg-2 margin-top-10">
			<div class="buttons-list">
				<div class="pull-right-btn">
				<?php echo 
					anchor(site_url($redirect), ' ' . lang('common_done'), array('class'=>'outbound_link btn btn-primary btn-lg ion-android-exit', 'title'=>''));
				?>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>
</div>

<?php if(!$quick_edit) { ?>
<?php $this->load->view('partial/nav', array('progression' => $progression, 'query' => $query, 'item_info' => $item_info)); ?>
<?php } ?>

<?php echo form_open('items/save/'.(!isset($is_clone) ? $item_info->item_id : ''),array('id'=>'item_form','class'=>'form-horizontal')); ?>
<?php echo form_hidden('ecommerce_product_id', $item_info->ecommerce_product_id); ?>
	
<div class="row <?php echo $redirect ? 'manage-table' :''; ?>" id="form">
	<div class="col-md-12">
		
	

	<div class="panel panel-piluku">
		<div class="panel-heading">
	      <h3 class="panel-title"><i class="ion-information-circled"></i> <?php echo lang("common_item_information"); ?> <small>(<?php echo lang('common_fields_required_message'); ?>)</small></h3>
				
				<div class="panel-options custom pagination pagination-top hidden-print text-center" id="pagination_top">
					<?php
					if (isset($prev_item_id) && $prev_item_id)
					{
							echo anchor('items/view/'.$prev_item_id, '<span class="hidden-xs ion-chevron-left"> '.lang('items_prev_item').'</span>');
					}
					if (isset($next_item_id) && $next_item_id)
					{
							echo anchor('items/view/'.$next_item_id,'<span class="hidden-xs">'.lang('items_next_item').' <span class="ion-chevron-right"></span</span>');
					}
					?>
	  		</div>
		</div>

			<div class="panel-body">
				
				<div class="form-group">
					<?php echo form_label(lang('common_item_name').':', 'name',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label required wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(array(
							'name'=>'name',
							'id'=>'name',
							'maxlength'=>$input_max_length,
							'class'=>'form-control form-inps',
							'value'=>$item_info->name)
						);?>
					</div>
				</div>
								
				<div class="form-group">
					<?php echo form_label(lang('common_category').':', 'category_id',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label  required wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_dropdown('category_id', $categories,$item_info->category_id, 'class="form-control form-inps" id="category_id"');?>
						<?php if ($this->Employee->has_module_action_permission('items', 'manage_categories', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
								<div>
									<a href="javascript:void(0);" id="add_category"><?php echo lang('common_add_category'); ?></a>
								</div>
						<?php } ?>		
					</div>
				</div>
				<!-- Unidad de medida quispe1-->
				<div class="form-group">
					<?php echo form_label(lang('common_unit_measurement').':', 'unit_id',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label  required wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_dropdown('unit_id', $units,$item_info->unit_id, 'class="form-control form-inps" id="unit_id"');?>
						<?php if ($this->Employee->has_module_action_permission('items', 'manage_categories', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
								<div>
									<a href="javascript:void(0);" id="add_unit_measurement"><?php echo lang('common_add_measurement'); ?></a>
								</div>
						<?php } ?>		
					</div>
				</div>

				<div class="form-group">
					<?php echo form_label(lang('common_supplier').':', 'supplier_id',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide ')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_dropdown('supplier_id', $suppliers, $selected_supplier,'class="form-control" id="supplier_id"');?>
					</div>
				</div>
							
				<div class="form-group">
					<?php echo form_label(lang('common_item_number_expanded').':', 'item_number',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(array(
							'name'=>'item_number',
							'id'=>'item_number',
							'class'=>'form-control form-inps',
							'value'=>$item_info->item_number)
						);?>
					</div>
				</div>
               
        		<div class="form-group">
					<?php echo form_label(lang('common_product_id').':', 'product_id',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(array(
							'name'=>'product_id',
							'id'=>'product_id',
							'class'=>'form-control form-inps',
							'value'=>$item_info->product_id)
						);?>
					</div>
				</div>
				
				<div class="form-group">	
					<label class="col-sm-3 col-md-3 col-lg-2 control-label"><?php echo lang('common_additional_item_numbers') ?></label>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<table id="additional_item_numbers" class="table">
							<thead>
								<tr>
								<th><?php echo lang('common_item_number'); ?></th>
								<th><?php echo lang('common_delete'); ?></th>
								</tr>
							</thead>
							
							<tbody>
								<?php if (isset($additional_item_numbers) && $additional_item_numbers) {?>
									<?php foreach($additional_item_numbers->result() as $additional_item_number) { ?>
										<tr><td><input type="text" class="form-control form-inps" size="50" name="additional_item_numbers[]" value="<?php echo H($additional_item_number->item_number); ?>" /></td><td>
										<a class="delete_addtional_item_number" href="javascript:void(0);"><?php echo lang('common_delete'); ?></a>
									</td></tr>
									<?php } ?>
								<?php } ?>
							</tbody>
						</table>
					
						<a href="javascript:void(0);" id="add_addtional_item_number"><?php echo lang('items_add_item_number'); ?></a>
					</div>
				</div>
				
				<div class="form-group">
					<?php echo form_label(lang('common_tags').':', 'tags',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(array(
							'name'=>'tags',
							'id'=>'tags',
							'class'=>'form-control form-inps',
							'value' => $tags,
						));?>
						<?php if ($this->Employee->has_module_action_permission('items', 'manage_tags', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
								<div>
									<?php echo anchor("items/manage_tags".($manage_query ? '?'.$manage_query : ''),lang('items_manage_tags'),array('class'=> 'outbound_link', 'title'=>lang('items_manage_tags')));?>
								</div>
						<?php } ?>
					</div>
				</div>
				
				<?php if (!$this->config->item('hide_size_field')) { ?>
				<div class="form-group">
					<?php echo form_label(lang('common_size').':', 'size',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(array(
							'name'=>'size',
							'id'=>'size',
							'class'=>'form-control form-inps',
							'value'=>$item_info->size)
						);?>
					</div>
				</div>
				<?php }else {
					echo form_hidden('size','');
					
				} ?>
				<div class="form-group">
					<?php echo form_label(lang('common_manufacturer').':', 'manufacturer_id',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_dropdown('manufacturer_id', $manufacturers, $selected_manufacturer,'class="form-control" id="manufacturer_id"');?>
						
						<?php if ($this->Employee->has_module_action_permission('items', 'manage_manufacturers', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
						<div>
							<?php echo anchor("items/manage_manufacturers".($manage_query ? '?'.$manage_query : ''),lang('common_manage_manufacturers'),array('class'=> 'outbound_link', 'title'=>lang('common_manage_manufacturers')));?>
						</div>
						<?php } ?>
						
					</div>
				</div>
				
				<div class="form-group">
					<?php echo form_label(lang('common_description').':', 'description',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_textarea(array(
							'name'=>'description',
							'id'=>'description',
							'value'=>$item_info->description,
							'class'=>'form-control  text-area',
							'rows'=>'5',
							'cols'=>'17')
						);?>
					</div>
				</div>
				
				<div class="form-group">
					<?php echo form_label(lang('common_long_description').':', 'long_description',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_textarea(array(
							'name'=>'long_description',
							'id'=>'long_description',
							'value'=>$item_info->long_description,
							'class'=>'form-control  text-area',
							'rows'=>'5',
							'cols'=>'17')
						);?>
					</div>
				</div>
				
        <div class="form-group">
					<?php echo form_label(lang('items_weight').':', 'weight',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(array(
							'name'=>'weight',
							'id'=>'weight',
							'class'=>'form-control form-inps',
							'value'=>$item_info->weight ? to_quantity($item_info->weight, false) : '')
						);?>
					</div>
				</div>
				
        <div class="form-group">
					<?php echo form_label(lang('items_dimensions').':', 'dimensions',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(array(
							'name'=>'length',
							'id'=>'length',
							'placeholder' => lang('items_length'),
							'class'=>'form-control form-inps',
							'value'=>$item_info->length ? to_quantity($item_info->length, false) : '')
						);?><br />
						<?php echo form_input(array(
							'name'=>'width',
							'id'=>'width',
							'placeholder' => lang('items_width'),
							'class'=>'form-control form-inps',
							'value'=>$item_info->width ? to_quantity($item_info->width, false) : '')
						);?><br />
						<?php echo form_input(array(
							'name'=>'height',
							'id'=>'height',
							'placeholder' => lang('items_height'),
							'class'=>'form-control form-inps',
							'value'=>$item_info->height ? to_quantity($item_info->height, false) : '')
						);?>
						
					</div>
				</div>
				
				
				
						<div class="form-group">
					
						<?php echo form_label(lang('common_is_barcoded').':', '',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_checkbox(array(
							'name'=>'is_barcoded',
							'id'=>'is_barcoded',
							'class' => 'is_barcoded delete-checkbox',
							'value'=>1,
							'checked'=>(boolean)(($item_info->is_barcoded)) || !$item_info->item_id));
						?>
						<label for="is_barcoded"><span></span></label>
					</div>
				</div>
				
				
				<?php if ($this->config->item("ecommerce_platform")) { ?>
				<div class="form-group">
					<?php echo form_label(lang('items_ecommerce_shipping_class').':', 'ecommerce_shipping_class_id',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide ')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_dropdown('ecommerce_shipping_class_id', $ecommerce_shipping_classes, $item_info->ecommerce_shipping_class_id,'class="form-control" id="ecommerce_shipping_class_id"');?>
					</div>
				</div>
				<?php } ?>
				<?php
				if ($this->config->item('enable_ebt_payments')) { ?>
					<div class="form-group">
					
					<?php echo form_label(lang('common_is_ebt_item').':', '',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
					<?php echo form_checkbox(array(
						'name'=>'is_ebt_item',
						'id'=>'is_ebt_item',
						'class' => 'is_ebt_item delete-checkbox',
						'value'=>1,
						'checked'=>(boolean)(($item_info->is_ebt_item))));
					?>
					<label for="is_ebt_item"><span></span></label>
				</div>
			</div>
			<?php } ?>
			<div class="form-group">
				<?php echo form_label(lang('items_sold_in_a_series').':', 'is_series_package',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
				<div class="col-sm-9 col-md-9 col-lg-10">
					<?php echo form_checkbox(array(
						'name'=>'is_series_package',
						'id'=>'is_series_package',
							'class'=>'delete-checkbox',
						'value'=>1,
						'checked'=>($item_info->is_series_package)
					));?>
					<label for="is_series_package"><span></span></label>
				</div>
			</div>
			
			<div class="form-group <?php if (!$item_info->is_series_package){echo 'hidden';} ?>" id="series_package_options">
				
				<div class="form-group">	
					<?php echo form_label(lang('common_series_quantity').':', 'series_quantity',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide ')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(array(
						'class'=>'form-control form-inps',
						'name'=>'series_quantity',
						'id'=>'series_quantity',
						'value'=>$item_info->series_quantity));?>
					</div>
				</div>
				
				
				<div class="form-group">	
					<?php echo form_label(lang('common_series_days_to_use_within').':', 'series_days_to_use_within',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide ')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(array(
						'class'=>'form-control form-inps',
						'name'=>'series_days_to_use_within',
						'id'=>'series_days_to_use_within',
						'value'=>$item_info->series_days_to_use_within));?>
					</div>
				</div>
				
			</div>

				<div class="form-group">
					<?php echo form_label(lang('items_is_service').':', 'is_service',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_checkbox(array(
							'name'=>'is_service',
							'id'=>'is_service',
								'class'=>'delete-checkbox',
							'value'=>1,
							'checked'=>($item_info->is_service || (!$item_info->item_id && $this->config->item('default_new_items_to_service'))) ? 1 : 0)
						);?>
						<label for="is_service"><span></span></label>
					</div>
				</div>
				
				<?php if ($this->config->item("ecommerce_platform")) { ?>
				
				<div class="form-group">
					<?php echo form_label(lang('items_is_ecommerce').':', 'is_ecommerce',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_checkbox(array(
							'name'=>'is_ecommerce',
							'id'=>'is_ecommerce',
								'class'=>'delete-checkbox',
							'value'=>1,
							'checked'=>($item_info->is_ecommerce || (!$item_info->item_id && $this->config->item('new_items_are_ecommerce_by_default'))) ? 1 : 0)
						);?>
						<label for="is_ecommerce"><span></span></label>
					</div>
				</div>
				<?php } ?>
				<div class="form-group">
					<?php echo form_label(lang('items_allow_alt_desciption').':', 'allow_alt_description',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_checkbox(array(
							'name'=>'allow_alt_description',
							'id'=>'allow_alt_description',
							'class'=>'delete-checkbox',
							'value'=>1,
							'checked'=>($item_info->allow_alt_description)? 1  :0)
						);?>
						<label for="allow_alt_description"><span></span></label>
					</div>
				</div>
				
				<div class="form-group">
					<?php echo form_label(lang('items_is_serialized').':', 'is_serialized',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_checkbox(array(
							'name'=>'is_serialized',
							'id'=>'is_serialized',
								'class'=>'delete-checkbox',
							'value'=>1,
							'checked'=>($item_info->is_serialized)? 1 : 0)
						);?>
						<label for="is_serialized"><span></span></label>
					</div>
				</div>
				
				<div id="serial_container" class="form-group serial-input <?php if (!$item_info->is_serialized){echo 'hidden';} ?>">
					<label class="col-sm-3 col-md-3 col-lg-2 control-label"><?php echo lang('items_serial_numbers') ?></label>
					<div class="col-sm-9 col-md-9 col-lg-9">
				
					<table id="serial_numbers" class="table">
						<thead>
							<tr>
							<th><?php echo lang('items_serial_number'); ?></th>
							<th><?php echo lang('common_cost_price'); ?></th>
							<th><?php echo lang('common_price'); ?></th>
							<th><?php echo lang('common_delete'); ?></th>
							</tr>
						</thead>
						
						<tbody>
							<?php if (isset($serial_numbers) && $serial_numbers) {?>
								<?php foreach($serial_numbers->result() as $serial_item_number) { ?>
								<tr>
									<td><input type="text" class="form-control form-inps" size="40" name="serial_numbers[]" value="<?php echo H($serial_item_number->serial_number); ?>" /></td>
									<td><input type="text" class="form-control form-inps" size="20" name="serial_number_cost_prices[]" value="<?php echo H($serial_item_number->cost_price !== NULL ? to_currency_no_money($serial_item_number->cost_price) : ''); ?>" /></td>
									<td><input type="text" class="form-control form-inps" size="20" name="serial_number_prices[]" value="<?php echo H($serial_item_number->unit_price !== NULL ? to_currency_no_money($serial_item_number->unit_price) : ''); ?>" /></td>
									<td><a class="delete_serial_number" href="javascript:void(0);"><?php echo lang('common_delete'); ?></a></td>
								</tr>
								<?php } ?>
							<?php } ?>
						</tbody>
					</table>
				
					<a href="javascript:void(0);" id="add_serial_number"><?php echo lang('items_add_serial_number'); ?></a>
					
				</div>
			</div>
				
				<?php
				if ($this->config->item('enable_customer_loyalty_system') && $this->config->item('loyalty_option') == 'advanced')
				{
				?>
				
				<div class="form-group">
					<?php echo form_label(lang('common_disable_loyalty').':', 'disable_loyalty',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_checkbox(array(
							'name'=>'disable_loyalty',
							'id'=>'disable_loyalty',
								'class'=>'delete-checkbox',
							'value'=>1,
							'checked'=>($item_info->disable_loyalty)? 1 : 0)
						);?>
						<label for="disable_loyalty"><span></span></label>
					</div>
				</div>
				
				<?php
				}
				?>
				
				<?php if ($this->config->item('verify_age_for_products')) { ?>
				
					<div class="form-group">
						<?php echo form_label(lang('common_requires_age_verification').':', 'verify_age',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php echo form_checkbox(array(
								'name'=>'verify_age',
								'id'=>'verify_age',
									'class'=>'delete-checkbox',
								'value'=>1,
								'checked'=>($item_info->verify_age)? 1 : 0)
							);?>
							<label for="verify_age"><span></span></label>
						</div>
					</div>

					<div class="form-group <?php if (!$item_info->verify_age){echo 'hidden';} ?>" id="required_age_container">
						<?php echo form_label(lang('common_required_age').':', 'required_age',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php echo form_input(array(
								'name'=>'required_age',
								'id'=>'required_age',
								'class'=>'form-control form-inps',
								'value' => $item_info->item_id ? $item_info->required_age : $this->config->item('default_age_to_verify'),
							));?>
						</div>
					</div>
				
				<?php } ?>
			 <?php for($k=1;$k<=NUMBER_OF_PEOPLE_CUSTOM_FIELDS;$k++) { ?>
				<?php
				 $custom_field = $this->Item->get_custom_field($k);
				 if($custom_field !== FALSE)
				 { ?>
					 <div class="form-group">
					 <?php echo form_label($custom_field . ' :', "custom_field_${k}_value", array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
					 							
					 <div class="col-sm-9 col-md-9 col-lg-10">
							<?php if ($this->Item->get_custom_field($k,'type') == 'checkbox') { ?>
								
								<?php echo form_checkbox("custom_field_${k}_value", '1', (boolean)$item_info->{"custom_field_${k}_value"},"id='custom_field_${k}_value'");?>
								<label for="<?php echo "custom_field_${k}_value"; ?>"><span></span></label>
								
							<?php } elseif($this->Item->get_custom_field($k,'type') == 'date') { ?>
								
									<?php echo form_input(array(
									'name'=>"custom_field_${k}_value",
									'id'=>"custom_field_${k}_value",
									'class'=>"custom_field_${k}_value".' form-control',
									'value'=>is_numeric($item_info->{"custom_field_${k}_value"}) ? date(get_date_format(), $item_info->{"custom_field_${k}_value"}) : '')
									);?>									
									<script>
										var $field = <?php echo "\$('#custom_field_${k}_value')"; ?>;
								    $field.datetimepicker({format: JS_DATE_FORMAT, locale: LOCALE, ignoreReadonly: IS_MOBILE ? true : false});	
										
									</script>
										
							<?php } elseif($this->Item->get_custom_field($k,'type') == 'dropdown') { ?>
									
									<?php 
									$choices = explode('|',$this->Item->get_custom_field($k,'choices'));
									$select_options = array();
									foreach($choices as $choice)
									{
										$select_options[$choice] = $choice;
									}
									echo form_dropdown("custom_field_${k}_value", $select_options, $item_info->{"custom_field_${k}_value"}, 'class="form-control"');?>
									
							<?php } else {
							
									echo form_input(array(
									'name'=>"custom_field_${k}_value",
									'id'=>"custom_field_${k}_value",
									'class'=>"custom_field_${k}_value".' form-control',
									'value'=>$item_info->{"custom_field_${k}_value"})
									);?>									
							<?php } ?>
						</div>
					</div>
				<?php } //end if?>
				<?php } //end for loop?>
				
				
			</div><!--/panel-body -->
		</div><!-- /panel-piluku -->
		
		
	<?php echo form_hidden('redirect', isset($redirect) ? $redirect : ''); ?>
	<?php echo form_hidden('progression', isset($progression) ? $progression : ''); ?>
	<?php echo form_hidden('quick_edit', isset($quick_edit) ? $quick_edit : ''); ?>
	
		<div class="form-actions">
			<?php
			if (isset($redirect) && $redirect == 'sales')
			{
				echo form_button(array(
			    'name' => 'cancel',
			    'id' => 'cancel',
				 'class' => 'submit_button btn btn-lg btn-danger',
			    'value' => 'true',
			    'content' => lang('common_cancel')
				));
			}
			?>
			<?php
				echo form_submit(array(
					'name'=>'submitf',
					'id'=>'submitf',
					'value'=>lang('common_save'),
					'class'=>'submit_button floating-button btn btn-lg btn-primary')
				);
			?>
		</div>		
	</div>
</div>
		

<script type='text/javascript'>

<?php $this->load->view("partial/common_js"); ?>
	
function check_service_inputs()
{
	var $reorder_inputs = $(".is-service-toggle");
	
	if ($('#is_service').prop('checked'))
	{
		$reorder_inputs.addClass('hidden');
	}
	else
	{
		$reorder_inputs.removeClass('hidden');
	}
}


$(document).ready(function()
{		
	$("#is_serialized").change(function()
	{
		if ($(this).prop('checked'))
		{
			$("#serial_container").removeClass('hidden');
		}
		else
		{
			$("#serial_container").addClass('hidden');			
		}
	});
	
	$(".delete_serial_number").click(function()
	{
		$(this).parent().parent().remove();
	});
	
	$("#add_serial_number").click(function()
	{
		$("#serial_numbers tbody").append('<tr><td><input type="text" class="form-control form-inps" size="40" name="serial_number_cost_prices[]" value="" /></td><td><input type="text" class="form-control form-inps" size="40" name="serial_numbers[]" value="" /></td><td><input type="text" class="form-control form-inps" size="20" name="serial_number_prices[]" value="" /></td><td>&nbsp;</td></tr>');
	});
	
	$(".delete_addtional_item_number").click(function()
	{
		$(this).parent().parent().remove();
	});
	
	$("#add_addtional_item_number").click(function()
	{
		$("#additional_item_numbers tbody").append('<tr><td><input type="text" class="form-control form-inps" size="40" name="additional_item_numbers[]" value="" /></td><td>&nbsp;</td></tr>');
	});	
	
	$('#supplier_id').selectize();
		
	$('#category_id').selectize({
		create: true,
		render: {
	    item: function(item, escape) {
				var item = '<div class="item">'+ escape($('<div>').html(item.text).text()) +'</div>';
				return item;
	    },
	    option: function(item, escape) {
				var option = '<div class="option">'+ escape($('<div>').html(item.text).text()) +'</div>';
				return option;
	    },
      option_create: function(data, escape) {
			var add_new = <?php echo json_encode(lang('common_new_category')) ?>;
        return '<div class="create">'+escape(add_new)+' <strong>' + escape(data.input) + '</strong></div>';
      }
		}
	});

	$('#unit_id').selectize({
		create: true,
		render: {
	    item: function(item, escape) {
				var item = '<div class="item">'+ escape($('<div>').html(item.text).text()) +'</div>';
				return item;
	    },
	    option: function(item, escape) {
				var option = '<div class="option">'+ escape($('<div>').html(item.text).text()) +'</div>';
				return option;
	    },
      option_create: function(data, escape) {
			var add_new = <?php echo json_encode(lang('common_new_category')) ?>;
        return '<div class="create">'+escape(add_new)+' <strong>' + escape(data.input) + '</strong></div>';
      }
		}
	});

	$("#cancel").click(cancelItemAddingFromSaleOrRecv);
	
  setTimeout(function(){$(":input:visible:first","#item_form").focus();},100);
	
	$(document).on('change', '#is_service', check_service_inputs);

	$('#tags').selectize({
		delimiter: ',',
		loadThrottle : 215,
		persist: false,
		valueField: 'value',
		labelField: 'label',
		searchField: 'label',
		create: true,
		render: {
	      option_create: function(data, escape) {
				var add_new = <?php echo json_encode(lang('common_add_new_tag')) ?>;
	        return '<div class="create">'+escape(add_new)+' <strong>' + escape(data.input) + '</strong></div>';
	      }
		},
		load: function(query, callback) {
			if (!query.length) return callback();
			$.ajax({
				url:'<?php echo site_url("items/tags");?>'+'?term='+encodeURIComponent(query),
				type: 'GET',
				error: function() {
					callback();
				},
				success: function(res) {
					res = $.parseJSON(res);
					callback(res);
				}
			});
		}
	});
	
	$('#item_form').validate({
		ignore: ':hidden:not([class~=selectized]),:hidden > .selectized, .selectize-control .selectize-input input',
		submitHandler:function(form)
		{			
			var args = {
				next: {
					label: <?php echo json_encode(lang('common_edit').' '.lang('items_variations')) ?>,
					url: <?php echo json_encode(site_url("items/variations/".($item_info->item_id ? $item_info->item_id : -1)."?$query")); ?>,
				}
			};
			
			$.post('<?php echo site_url("items/check_duplicate");?>', {term: $('#name').val()},function(data) {
			<?php if(!$item_info->item_id) {  ?>
				if(data.duplicate)
				{
					bootbox.confirm(<?php echo json_encode(lang('common_items_duplicate_exists'));?>, function(result)
					{
						if(result)
						{
							doItemSubmit(form, args);
						}
					});
				}
				else
				{
					doItemSubmit(form, args);
				}
				<?php } else { ?>
					doItemSubmit(form, args);
				<?php } ?>
				} , "json");
		},
		errorClass: "text-danger",
		errorElement: "span",
		highlight:function(element, errorClass, validClass) {
			$(element).parents('.form-group').removeClass('has-success').addClass('has-error');
		},
		unhighlight: function(element, errorClass, validClass) {
			$(element).parents('.form-group').removeClass('has-error').addClass('has-success');
		},
		rules:
		{
		<?php if(!$item_info->item_id) {  ?>
			item_number:
			{
				remote: 
		    { 
					url: "<?php echo site_url('items/item_number_exists');?>", 
					type: "post"
		    } 
			},
			product_id:
			{
				remote: 
		    { 
					url: "<?php echo site_url('items/product_id_exists');?>", 
					type: "post"
		    } 
			},
		<?php } ?>
			name:"required",
			category_id:"required",
			reorder_level:
			{
				number:true
			}
		},
		messages:
		{			
			<?php if(!$item_info->item_id) {  ?>
			item_number:
			{
				remote: function()
				{
					var link = '<a id="item_number_validation_link" target="_blank" href="#"><?php echo lang('common_item_info'); ?></a>';
					
					$.post(<?php echo json_encode(site_url('items/find_item_info')); ?>,{scan_item_number: $("#item_number").val()}, function(response)
					{
						$("#item_number_validation_link").attr('href',response.link);
					},'json');
					return <?php echo json_encode(lang('items_item_number_exists')); ?>+' '+link;
				}
				   
			},
			product_id:
			{
				remote: function()
				{
					var link = '<a id="product_id_validation_link" target="_blank" href="#"><?php echo lang('common_item_info'); ?></a>';
					
					$.post(<?php echo json_encode(site_url('items/find_item_info')); ?>,{scan_item_number: $("#product_id").val()}, function(response)
					{
						$("#product_id_validation_link").attr('href',response.link);
					},'json');
					return <?php echo json_encode(lang('items_product_id_exists')); ?>+' '+link;
				}
			},
			<?php } ?>
			
			<?php foreach($tiers as $tier) { ?>
				"<?php echo 'item_tier['.$tier->id.']'; ?>":
				{
					number: <?php echo json_encode(lang('common_this_field_must_be_a_number')); ?>
				},
			<?php } ?>
			
			<?php foreach($locations as $location) { ?>
				"<?php echo 'locations['.$location->location_id.'][quantity]'; ?>":
				{
					number: <?php echo json_encode(lang('common_this_field_must_be_a_number')); ?>
				},
				"<?php echo 'locations['.$location->location_id.'][reorder_level]'; ?>":
				{
					number: <?php echo json_encode(lang('common_this_field_must_be_a_number')); ?>
				},
				"<?php echo 'locations['.$location->location_id.'][cost_price]'; ?>":
				{
					number: <?php echo json_encode(lang('common_this_field_must_be_a_number')); ?>
				},
				"<?php echo 'locations['.$location->location_id.'][unit_price]'; ?>":
				{
					number: <?php echo json_encode(lang('common_this_field_must_be_a_number')); ?>
				},			
				"<?php echo 'locations['.$location->location_id.'][promo_price]'; ?>":
				{
					number: <?php echo json_encode(lang('common_this_field_must_be_a_number')); ?>
				},			
				<?php foreach($tiers as $tier) { ?>
					"<?php echo 'locations['.$location->location_id.'][item_tier]['.$tier->id.']'; ?>":
					{
						number: <?php echo json_encode(lang('common_this_field_must_be_a_number')); ?>
					},
				<?php } ?>				
			<?php } ?>
			
			name:<?php echo json_encode(lang('common_item_name_required')); ?>,
			category_id:<?php echo json_encode(lang('common_category_required')); ?>,
			cost_price:
			{
				required:<?php echo json_encode(lang('items_cost_price_required')); ?>,
				number:<?php echo json_encode(lang('common_cost_price_number')); ?>
			},
			unit_price:
			{
				required:<?php echo json_encode(lang('items_unit_price_required')); ?>,
				number:<?php echo json_encode(lang('common_unit_price_number')); ?>
			},
			promo_price:
			{
				number: <?php echo json_encode(lang('common_this_field_must_be_a_number')); ?>
			}
		}
	});
});

function cancelItemAddingFromSaleOrRecv()
{
	bootbox.confirm(<?php echo json_encode(lang('items_are_you_sure_cancel')); ?>, function(result)
	{
		if (result)
		{
			<?php if (isset($sale_or_receiving) && $sale_or_receiving == 'sale') {?>
				window.location = <?php echo json_encode(site_url('sales')); ?>;
			<?php } else { ?>
				window.location = <?php echo json_encode(site_url('receivings')); ?>;
			<?php } ?>
		}
	});
}

$("#verify_age").click(function()
{
	if ($('#verify_age').prop('checked'))
	{
		$("#required_age_container").removeClass('hidden');	
	}
	else
	{
		$("#required_age_container").addClass('hidden');
	}
	
});

$("#is_series_package").click(function()
{
	if ($('#is_series_package').prop('checked'))
	{
		$("#series_package_options").removeClass('hidden');	
	}
	else
	{
		$("#series_package_options").addClass('hidden');
	}
	
});

$(document).on('click', "#add_category",function()
{
	$("#categoryModalDialogTitle").html(<?php echo json_encode(lang('common_add_category')); ?>);
	var parent_id = $("#category_id").val();
	
	$parent_id_select = $('#parent_id');
	$parent_id_select[0].selectize.setValue(parent_id, false);
	
	$("#categories_form").attr('action',SITE_URL+'/items/save_category');
	
	//Clear form
	$(":file").filestyle('clear');
	$("#categories_form").find('#category_name').val("");
	$("#categories_form").find('#category_color').val("");
	$('#category_color').colorpicker('setValue', '');
	$("#categories_form").find('#category_image').val("");
	$("#categories_form").find('#image-preview').attr('src','');
	$('#del_image').prop('checked',false);
	$('#preview-section').hide();
	
	//show
	$("#category-input-data").modal('show');
});

$("#categories_form").submit(function(event)
{
	event.preventDefault();

	$(this).ajaxSubmit({ 
		success: function(response, statusText, xhr, $form){
			show_feedback(response.success ? 'success' : 'error', response.message, response.success ? <?php echo json_encode(lang('common_success')); ?> : <?php echo json_encode(lang('common_error')); ?>);
			if(response.success)
			{
				$("#category-input-data").modal('hide');
				
				var category_id_selectize = $("#category_id")[0].selectize
				category_id_selectize.clearOptions();
				category_id_selectize.addOption(response.categories);		
				category_id_selectize.addItem(response.selected, true);			
			}		
		},
		dataType:'json',
	});
});

//// ADD NEW UNIDAD DE MEDIDA yony 
$(document).on('click', "#add_unit_measurement",function()
{
	$("#unit_measurement").html("Añadir nueva unidad de medida");
	var parent_id = $("#unit_id").val();

	$("#unit_form").attr('action',SITE_URL+'/items/save_unit');
	
	//Clear form
	$(":file").filestyle('clear');
	$("#unit_form").find('#name').val("");
	$("#unit_form").find('#abbreviation').val("");
	//show
	$("#unit-input-data").modal('show');
});

$("#unit_form").submit(function(event)
{
	event.preventDefault();

	$(this).ajaxSubmit({ 
		success: function(response, statusText, xhr, $form){
			show_feedback(response.success ? 'success' : 'error', response.message, response.success ? <?php echo json_encode(lang('common_success')); ?> : <?php echo json_encode(lang('common_error')); ?>);
			if(response.success)
			{
				$("#unit-input-data").modal('hide');
				
				var category_id_selectize = $("#unit_id")[0].selectize
				category_id_selectize.clearOptions();
				category_id_selectize.addOption(response.units);		
				category_id_selectize.addItem(response.selected, true);			
			}		
		},
		dataType:'json',
	});
});

<?php if ($this->session->flashdata('manage_success_message')) { ?>
	show_feedback('success', <?php echo json_encode($this->session->flashdata('manage_success_message')); ?>, <?php echo json_encode(lang('common_success')); ?>);
<?php } ?>

</script>
<?php echo form_close(); ?>
</div>
<?php $this->load->view('partial/footer'); ?>
