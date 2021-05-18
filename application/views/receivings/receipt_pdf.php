<?php
if (isset($error_message))
{
	echo '<h1 style="text-align: center;">'.$error_message.'</h1>';
	exit;
}

$company = ($company = $this->Location->get_info_for_key('company', isset($override_location_id) ? $override_location_id : FALSE)) ? $company : $this->config->item('company');
$company_logo = ($company_logo = $this->Location->get_info_for_key('company_logo', isset($override_location_id) ? $override_location_id : FALSE)) ? $company_logo : $this->config->item('company_logo');
$ruc_company = $this->config->item('ruc_company');
$item_custom_fields_to_display = array();
$supplier_custom_fields_to_display = array();
$receiving_custom_fields_to_display = array();

for($k=1;$k<=NUMBER_OF_PEOPLE_CUSTOM_FIELDS;$k++) 
{
 $item_custom_field = $this->Item->get_custom_field($k,'show_on_receipt');
 $supplier_custom_field = $this->Supplier->get_custom_field($k,'show_on_receipt');
 $recv_custom_field = $this->Receiving->get_custom_field($k,'show_on_receipt');
 
 if ($recv_custom_field)
 {
 	$receiving_custom_fields_to_display[] = $k;
 }
 
 if ($item_custom_field)
 {
 	$item_custom_fields_to_display[] = $k;
 }
 
 if ($supplier_custom_field)
 {
 	 $supplier_custom_fields_to_display[] = $k;
 }
}
// Code to get accounting id of current reciept
$currentReceivingAccoutingId = '';
$receiving_info = $this->Receiving->get_info($receiving_id_raw)->result_array();
if (($receiving_info) && ($receiving_info['0'])) {
	$currentReceivingAccoutingId = $receiving_info['0']['accounting_id'];
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<!--[if !mso]><!-->
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<!--<![endif]-->
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title></title>
	<!--[if (gte mso 9)|(IE)]>
	<style type="text/css">
		table {border-collapse: collapse !important;}
	</style>
	<![endif]-->
	<?php 
	$this->load->helper('assets');
	foreach(get_css_files() as $css_file) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url().$css_file['path'].'?'.ASSET_TIMESTAMP;?>" />
	<?php } ?>
	<?php foreach(get_js_files() as $js_file) { ?>
		<script src="<?php echo base_url().$js_file['path'].'?'.ASSET_TIMESTAMP;?>" type="text/javascript" charset="UTF-8"></script>
	<?php } ?>
</head>
<body>

	<div class="row manage-table receipt_<?php echo $this->config->item('receipt_text_size') ? $this->config->item('receipt_text_size') : 'small';?>" id="receipt_wrapper">
		<div class="wrapper">
			<section class="invoice">
			    <div class="row">
			      <div class="col-xs-12" style="margin-top:20px;">
			      </div>
			    </div>
				<div class="panel panel-piluku">
					<div class="panel-body panel-pad">
						<div class="row invoice-info" style="margin-bottom:10px;">
							<div class="col-sm-4 invoice-col" align="center">
					            <ul class="list-unstyled invoice-address" style="margin-bottom:1px;">
					                <?php if($company_logo) {?>
					                	<li class="invoice-logo">
											<?php echo img(array('src' => $this->Appfile->get_url_for_file($company_logo))); ?>
					                	</li>
					                <?php } else { ?>
					                	<li class="company-title"><?php echo H($company); ?></li>
									<?php } ?>
					            </ul>
							</div>
							<!-- /.col -->
							<div class="col-sm-4 invoice-col" align="center">
					            <ul class="list-unstyled invoice-address" style="margin-bottom:2px;">
					                <li class="company-title"><?php echo H($company); ?></li>
									<?php if ($this->Location->count_all() > 1) { ?>
										<li><?php echo H($this->Location->get_info_for_key('name', isset($override_location_id) ? $override_location_id : FALSE)); ?></li>
									<?php } ?>
					                <li><?php echo nl2br(H($this->Location->get_info_for_key('address', isset($override_location_id) ? $override_location_id : FALSE))); ?></li>
					                <li><?php echo H($this->Location->get_info_for_key('phone', isset($override_location_id) ? $override_location_id : FALSE)); ?></li>
									<li><?php echo H($this->Location->get_info_for_key('website', isset($override_location_id) ? $override_location_id : FALSE)); ?></li>
									<li><?php echo H($this->Location->get_info_for_key('email', isset($override_location_id) ? $override_location_id : FALSE)); ?></li>
					            </ul>
							</div>
							<!-- /.col -->
							<div class="col-sm-4 invoice-col" align="center">
					            <ul class="list-unstyled invoice-detail" style="margin-bottom:1px; border: 1px solid #9398a0">
									<li>
										 <?php echo H($receipt_title); ?><?php echo ($total) < 0 ? ' ('.lang('sales_return').')': '';?>
										 <br>
										 <strong>RUC : <?php echo $ruc_company; ?><br></strong>
										 <strong><?php echo H($transaction_time) ?></strong>
									</li>
									<?php if ($ruc_company!='') { ?>
					            	<?php } ?>

									<?php if (!isset($transfer_to_location)) {?>
									<li id="sale_id"><span><?php echo $is_po ? lang('receivings_purchase_order') : lang('receivings_id').": "; ?></span><?php echo $is_po ? H($receiving_id) : H($receiving_id); ?></li>
									<?php } else { 
										?>
									<li id="sale_id"><span><?php echo lang('receivings_transfer_id').": "; ?></span><?php echo H($receiving_id_raw); ?></li>
									<?php
									} ?>

									<?php if (isset($deleted) && $deleted) {?>
					            	<li><span class="text-danger" style="color: #df6c6e;"><strong><?php echo lang('sales_deleted_voided'); ?></strong></span></li>
									<?php } ?>
									<?php if (isset($sale_type)) { ?>
										<li><?php echo H($sale_type); ?></li>
									<?php } ?>
									
									<?php if ($is_ecommerce) { ?>
										<li><?php echo lang('common_ecommerce'); ?></li>
									<?php } ?>
									
									<?php
									if ($this->Register->count_all(isset($override_location_id) ? $override_location_id : FALSE) > 1 && $register_name)
									{
									?>
										<li><span><?php echo lang('common_register_name').':'; ?></span><?php echo H($register_name); ?></li>		
									<?php
									}
									?>				
									
									<?php
									if ($tier)
									{
									?>
										<li><span><?php echo $this->config->item('override_tier_name') ? $this->config->item('override_tier_name') : lang('common_tier_name').':'; ?></span><?php echo H($tier); ?></li>		
									<?php
									}
									?>
									
									<?php if (!$this->config->item('remove_employee_from_receipt')) { ?>
									<li><span><?php echo lang('common_contact').":"; ?></span><?php echo H($employee); ?></li>
									<?php
									foreach($employee_custom_fields_to_display as $custom_field_id)
									{
									?>
											<?php 
											
												$employee_info = $this->Employee->get_info($sold_by_employee_id);
												
												if ($employee_info->{"custom_field_${custom_field_id}_value"})
												{
												?>
				          					<div class="invoice-desc">
												<?php

												if ($this->Employee->get_custom_field($custom_field_id,'type') == 'checkbox')
												{
													$format_function = 'boolean_as_string';
												}
												elseif($this->Employee->get_custom_field($custom_field_id,'type') == 'date')
												{
													$format_function = 'date_as_display_date';				
												}
												elseif($this->Employee->get_custom_field($custom_field_id,'type') == 'email')
												{
													$format_function = 'strsame';					
												}
												elseif($this->Employee->get_custom_field($custom_field_id,'type') == 'url')
												{
													$format_function = 'strsame';					
												}
												elseif($this->Employee->get_custom_field($custom_field_id,'type') == 'phone')
												{
													$format_function = 'strsame';					
												}
												else
												{
													$format_function = 'strsame';
												}
												
												echo '<li><span>'.lang('common_employee').' '.($this->Employee->get_custom_field($custom_field_id,'hide_field_label') ? '' : $this->Employee->get_custom_field($custom_field_id,'name').':').'</span> '.$format_function($employee_info->{"custom_field_${custom_field_id}_value"}).'</li>';
												?>
											</div>
											<?php
										}
									}
									?>
									<?php } ?>
									<?php 
									if(H($this->Location->get_info_for_key('enable_credit_card_processing',isset($override_location_id) ? $override_location_id : FALSE)))
									{
										echo '<li id="merchant_id"><span>'.lang('common_merchant_id').':</span> '.H($this->Location->get_merchant_id(isset($override_location_id) ? $override_location_id : FALSE)).'</li>';
									}
									?>
					            </ul>
							</div>
						      <!-- /.col -->
						</div>

						<div class="invoice-lista" style="margin-bottom:5px;">
						    <!-- /.row -->
						    <div class="row">
								<!-- to address-->
								        <?php if(isset($supplier) || isset($transfer_to_location)) { ?>
								        <div class="col-md-4 col-sm-4 col-xs-12">
											<ul class="list-unstyled invoice-address invoiceto">
												<?php if(isset($supplier)) { ?>
													<li id="supplier"><?php echo lang('common_supplier').": ".H($supplier); ?></li>
													<?php if(!empty($account_number)){ ?><li><?php echo lang('common_ruc_number'); ?> : <?php echo H($account_number); ?></li><?php } ?>
													<?php if(!empty($supplier_address_1)){ ?><li><?php echo lang('common_address'); ?> : <?php echo H($supplier_address_1. ', '.$supplier_address_2); ?><?php } ?>
													<?php if (!empty($supplier_city)) { echo ' '.H($supplier_city.', '.$supplier_state.' '.$supplier_zip).'</li>';} ?>
													<?php if (!empty($supplier_country)) { echo '<li>'.H($supplier_country).'</li>';} ?>		
													<?php if(!empty($supplier_phone)){ ?><li><?php echo lang('common_phone_number'); ?> : <?php echo H($supplier_phone); ?></li><?php } ?>
													<?php if(!empty($supplier_email)){ ?><li><?php echo lang('common_email'); ?> : <?php echo H($supplier_email); ?></li><?php } ?>
													
													<?php
													foreach($supplier_custom_fields_to_display as $custom_field_id)
													{
													?>
															<?php 
														
																$supplier_info = $this->Supplier->get_info($supplier_id);
															
																if ($supplier_info->{"custom_field_${custom_field_id}_value"})
																{
																?>
						                  			<div class="invoice-desc">
																<?php

																if ($this->Supplier->get_custom_field($custom_field_id,'type') == 'checkbox')
																{
																	$format_function = 'boolean_as_string';
																}
																elseif($this->Supplier->get_custom_field($custom_field_id,'type') == 'date')
																{
																	$format_function = 'date_as_display_date';				
																}
																elseif($this->Supplier->get_custom_field($custom_field_id,'type') == 'email')
																{
																	$format_function = 'strsame';					
																}
																elseif($this->Supplier->get_custom_field($custom_field_id,'type') == 'url')
																{
																	$format_function = 'strsame';					
																}
																elseif($this->Supplier->get_custom_field($custom_field_id,'type') == 'phone')
																{
																	$format_function = 'strsame';					
																}
																else
																{
																	$format_function = 'strsame';
																}
															
																echo '<li><span>'.lang('common_supplier').' '.($this->Supplier->get_custom_field($custom_field_id,'hide_field_label') ? '' : $this->Supplier->get_custom_field($custom_field_id,'name').':').'</span> '.$format_function($supplier_info->{"custom_field_${custom_field_id}_value"}).'</li>';
																?>
													</div>
															<?php
														}
													}
													?>

												<?php } ?>
												<?php if(isset($transfer_to_location)) { ?>
													<li id="transfer_from"><span><?php echo lang('receivings_transfer_from').': ' ?></span><?php echo H($transfer_from_location); ?></li>
													<li id="transfer_to"><span><?php echo lang('receivings_transfer_to').': ' ?></span><?php echo H($transfer_to_location); ?></li>
												<?php } ?>
											</ul>
								        </div>
								        <?php } ?>
						    </div>
						</div>

						<?php
			    		$x_col = 4;
			    		$xs_col = 2;
			    		if($discount_exists)
			    		{
			    			$x_col = 4;
			    			$xs_col = 2;

								if($this->config->item('wide_printer_receipt_format'))
								{
					    		$x_col = 2;
									$xs_col = 2;
								}
			    		}
							else
							{
								if($this->config->item('wide_printer_receipt_format'))
								{
					    		$x_col = 4;
									$xs_col = 2;
								}
							}
						?>
					    <!-- invoice heading-->
					    <div class="invoice-table" style="margin-bottom:5px; border: 1px solid #9398a0">
					        <div class="row">
					            <div class="<?php echo $this->config->item('wide_printer_receipt_format') ? 'col-md-'.$x_col . ' col-sm-' .$x_col . ' col-xs-'.$x_col : 'col-md-12 col-sm-12 col-xs-12' ?>">
					                <div class="invoice-head item-name"><?php echo lang('common_item_name'); ?></div>
					            </div>
					            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?> gift_receipt_element">
					                <div class="invoice-head text-right item-price"><?php echo lang('common_unit'); ?></div>
					            </div>
					            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?> gift_receipt_element">
					                <div class="invoice-head text-right item-price"><?php echo lang('common_price'); ?></div>
					            </div>
					            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?>">
					                <div class="invoice-head text-right item-qty"><?php echo lang('common_quantity'); ?></div>
					            </div>
								<?php if($discount_exists) { ?>
						            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?> gift_receipt_element">
						                <div class="invoice-head text-right item-discount"><?php echo lang('common_discount_percent'); ?></div>
						            </div>
					            <?php } ?>
					            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?>">
					                <div class="invoice-head pull-right item-total gift_receipt_element"><?php echo lang('common_total'); ?></div>
					            </div>
					        </div>
					    </div>
					    <?php 
							$number_of_items_sold = 0;
							$number_of_items_returned = 0;
							foreach(array_reverse($cart_items, true) as $line=>$item) { ?>
							
							<?php
							
							if ($item->quantity > 0 && $item->name != lang('common_store_account_payment') && $item->name != lang('common_discount') && $item->name != lang('common_refund') && $item->name != lang('common_fee'))
							{
								 $number_of_items_sold = $number_of_items_sold + $item->quantity;
							}
							elseif ($item->quantity < 0 && $item->name != lang('common_store_account_payment') && $item->name != lang('common_discount') && $item->name != lang('common_refund') && $item->name != lang('common_fee'))
							{
								 $number_of_items_returned = $number_of_items_returned + abs($item->quantity);
							}

							$item_number_for_receipt = false;
							
							if ($this->config->item('show_item_id_on_recv_receipt'))
							{
								switch($this->config->item('id_to_show_on_sale_interface'))
								{
									case 'number':
									$item_number_for_receipt = array_key_exists('item_number', $item) ? H($item->item_number) : '';
									break;
								
									case 'product_id':
									$item_number_for_receipt = array_key_exists('product_id', $item) ? H($item->product_id) : ''; 
									break;
								
									case 'id':
									$item_number_for_receipt = array_key_exists('item_id', $item) ? H($item->item_id) : ''; 
									break;
								
									default:
									$item_number_for_receipt = array_key_exists('item_number', $item) ? H($item->item_number) : '';
									break;
								}
							}
							?>
						    <!-- invoice items-->
						    <div class="invoice-table-content">
						        <div class="row">
					            	<div class="<?php echo $this->config->item('wide_printer_receipt_format') ? 'col-md-'.$x_col . ' col-sm-' .$x_col . ' col-xs-'.$x_col : 'col-md-12 col-sm-12 col-xs-12' ?>">
						                <div class="invoice-content invoice-con">
						                    <div class="invoice-content-heading"><?php echo H($item->name); ?><?php if ($item->size){ ?> (<?php echo H($item->size); ?>)<?php } ?></div>
																
											<?php if ($item_number_for_receipt){ ?>
											<div class="invoice-desc">
												<?php 
													echo $item_number_for_receipt;
												?>
											</div>
												
											<?php } ?>
											
											<div class="invoice-desc">
												<?php 
													echo $item->variation_name ? H($item->variation_name) : '';
												?>
											</div>
											<?php if (!$this->config->item('hide_desc_on_receipt') && !$item->description=="" ) {?>
						                    	<div class="invoice-desc"><?php echo H($item->description); ?></div>
						                    <?php } ?>
											<?php if (isset($item->serialnumber) && $item->serialnumber !="") { ?>
						                    	<div class="invoice-desc"><?php echo H($item->serialnumber); ?></div>
											<?php } ?>
											
											<?php
											foreach($item_custom_fields_to_display as $custom_field_id)
											{
											?>
													<?php 
													if(get_class($item) == 'PHPPOSCartItemRecv' && $this->Item->get_custom_field($custom_field_id) !== false)
													{
														$item_info = $this->Item->get_info($item->item_id);
														
														if ($item_info->{"custom_field_${custom_field_id}_value"})
														{
														?>
			                    				<div class="invoice-desc">
														<?php

														if ($this->Item->get_custom_field($custom_field_id,'type') == 'checkbox')
														{
															$format_function = 'boolean_as_string';
														}
														elseif($this->Item->get_custom_field($custom_field_id,'type') == 'date')
														{
															$format_function = 'date_as_display_date';				
														}
														elseif($this->Item->get_custom_field($custom_field_id,'type') == 'email')
														{
															$format_function = 'strsame';					
														}
														elseif($this->Item->get_custom_field($custom_field_id,'type') == 'url')
														{
															$format_function = 'strsame';					
														}
														elseif($this->Item->get_custom_field($custom_field_id,'type') == 'phone')
														{
															$format_function = 'strsame';					
														}
														else
														{
															$format_function = 'strsame';
														}
														
														echo ($this->Item->get_custom_field($custom_field_id,'hide_field_label') ? '' : $this->Item->get_custom_field($custom_field_id,'name').':').' '.$format_function($item_info->{"custom_field_${custom_field_id}_value"});
														?>
												</div>
													<?php
													}
												}
											}?>
						                </div>
						            </div>
					            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?> gift_receipt_element">
						            <div class="invoice-content item-price text-right"><?php echo $item->unity; ?></div>
						        </div>
					            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?> gift_receipt_element">
						            <div class="invoice-content item-price text-right"><?php echo to_currency($item->unit_price,10); ?></div>
						        </div>
					            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?> gift_receipt_element">
						            <div class="invoice-content item-qty text-right"><?php echo to_quantity($item->quantity); ?></div>
						        </div>
									<?php if($discount_exists) { ?>
					            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?> gift_receipt_element">
							        <div class="invoice-content item-discount text-right"><?php echo to_quantity($item->discount); ?></div>
							    </div>							
									<?php } ?>
					            <div class="col-md-<?php echo $xs_col; ?> col-sm-<?php echo $xs_col; ?> col-xs-<?php echo $xs_col; ?> gift_receipt_element">
					                	<div class="invoice-content item-total pull-right">
											<?php if ($this->config->item('indicate_taxable_on_receipt') && $item->taxable && !empty($taxes))
											{
												echo '<small>*'.lang('common_taxable').'</small>';
											}
											?>
											<?php echo to_currency($item->unit_price*$item->quantity-$item->unit_price*$item->quantity*$item->discount/100,10); ?>	
										</div>
					            	</div>
						        </div>					
						    </div>
					    <?php } ?>

					    </div>
						 
					    <div class="invoice-footer panel-pad">
					    	<?php if ($exchange_name) { ?>
							
								<div class="row">
						            <div class="col-md-offset-4 col-sm-offset-4 col-md-6 col-sm-6 col-xs-8">
						                <div class="invoice-footer-heading"><?php echo lang('common_exchange_to').' '.H($exchange_name); ?></div>
						            </div>
						            <div class="col-md-2 col-sm-2 col-xs-4">
						                <div class="invoice-footer-value">x <?php echo to_currency_no_money(1/$exchange_rate); ?></div>
						            </div>
						        </div>

							<?php } ?>
					    	<?php if (!empty($taxes)) {?>
						        <div class="row">
						            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-4 col-md-2 col-sm-2 col-xs-4">
						                <div class="invoice-footer-heading sub-total-heading"><?php echo lang('common_sub_total'); ?></div>
						            </div>
						            <div class="col-md-2 col-sm-2 col-xs-4">
						                <div class="invoice-footer-value"><?php //echo to_currency($subtotal); ?>
						                	<?php if (isset($exchange_name) && $exchange_name) { 
												echo to_currency_as_exchange($cart,$subtotal);
											?>
											<?php } else {  ?>
											<?php echo to_currency($subtotal); ?>
											<?php
											}
											?>
						                </div>
						            </div>
						        </div>
						        <?php if ($this->config->item('group_all_taxes_on_receipt')) { ?>
									<?php 
										$total_tax = 0;
										foreach($taxes as $name=>$value) 
										{
											$total_tax+=$value;
									 	}
									?>	
										<div class="row">
								            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-4 col-md-2 col-sm-2 col-xs-4">
								                <div class="invoice-footer-heading tax-heading"><?php echo lang('common_tax'); ?></div>
								            </div>
								            <div class="col-md-2 col-sm-2 col-xs-4">
								                <div class="invoice-footer-value"><?php //echo to_currency($total_tax); ?>
								                	<?php if (isset($exchange_name) && $exchange_name) { 
														echo to_currency_as_exchange($cart,$total_tax*$exchange_rate);
													?>
													<?php } else {  ?>
													<?php echo to_currency($total_tax*$exchange_rate); ?>
													<?php
													}
													?>
								                </div>
								            </div>
								        </div>						
								<?php }else {?>
									<?php foreach($taxes as $name=>$value) { ?>
										<div class="row">
								            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-4 col-md-2 col-sm-2 col-xs-4">
								                <div class="invoice-footer-heading tax-heading"><?php echo H($name); ?></div>
								            </div>
								            <div class="col-md-2 col-sm-2 col-xs-4">
								                <div class="invoice-footer-value"><?php //echo to_currency($value); ?>
								                	<?php if (isset($exchange_name) && $exchange_name) { 
														echo to_currency_as_exchange($cart,$value*$exchange_rate);
													?>
													<?php } else {  ?>
													<?php echo to_currency($value*$exchange_rate); ?>
													<?php
													}
													?>
								                </div>
								            </div>
								        </div>
									<?php } ?>
								<?php } ?>
						    <?php } ?>
						    <div class="row">
					            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-4 col-md-2 col-sm-2 col-xs-4">
					                <div class="invoice-footer-heading total-heading"><?php echo lang('common_total'); ?></div>
					            </div>
					            <div class="col-md-2 col-sm-2 col-xs-4">
					                <div class="invoice-footer-value"><?php //echo to_currency($total); ?>
					                	<?php if (isset($exchange_name) && $exchange_name) { 
										?>
										<?php echo $this->config->item('round_cash_on_sales') && $is_sale_cash_payment ?  to_currency_as_exchange($cart,round_to_nearest_05($total)) : to_currency_as_exchange($cart,$total); ?>				
										<?php } else {  ?>
										<?php echo $this->config->item('round_cash_on_sales') && $is_sale_cash_payment ?  to_currency(round_to_nearest_05($total)) : to_currency($total); ?>				
										<?php
										}
										?>
					                </div>
					            </div>
					        </div>
							<div class="row">
						    	<div class="col-md-6 col-sm-6 col-xs-6">
						            <?php $this->load->helper('number'); ?>
									SON: <?php
									echo num_to_letras($total,"Y",letra_modena($exchange_name)); ?>
					            </div>
							</div>
					        <div class="row">
								<?php if ($number_of_items_returned) { ?>
								
						            <div class="col-md-offset-4 col-sm-offset-4 col-md-6 col-sm-6 col-xs-8">
						                <div class="invoice-footer-heading"><?php echo lang('common_items_returned'); ?></div>
						            </div>
						            <div class="col-md-2 col-sm-2 col-xs-4">
						                <div class="invoice-footer-value invoice-total"><?php echo to_quantity($number_of_items_returned); ?></div>
						            </div>
								<?php } ?>
					        </div>	
					        <?php
								foreach($payments as $payment_id=>$payment)
								{ 
							?>
							<!-- <div class="row">
						            <div class="col-md-offset-4 col-sm-offset-4 col-md-4 col-sm-4 col-xs-4">
						                <div class="invoice-footer-heading"><?php echo (isset($show_payment_times) && $show_payment_times) ?  date(get_date_format().' '.get_time_format(), strtotime($payment->payment_date)) : lang('common_payment'); ?></div>
						            </div>
						            <div class="col-md-2 col-sm-4 col-xs-4">
										<div class="invoice-footer-value"><?php $splitpayment=explode(':',$payment->payment_type); echo H($splitpayment[0]); ?></div>																				
						            </div>
									
						            <div class="col-md-2 col-sm-2 col-xs-4">
										<div class="invoice-footer-value invoice-payment"><?php echo to_currency($payment->payment_amount); ?></div>
						            </div>							
								</div> -->
							<?php
								}
							?>
												
					        <?php if(isset($amount_change)) { ?>
								<div class="row">
						            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-4 col-md-2 col-sm-2 col-xs-4">
						                <div class="invoice-footer-heading"><?php echo lang('common_amount_tendered'); ?></div>
						            </div>
						            <div class="col-md-2 col-sm-2 col-xs-4">
						                <div class="invoice-footer-value"><?php echo to_currency($amount_tendered); ?></div>
						            </div>
						        </div>
						        <div class="row">
						            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-4 col-md-2 col-sm-2 col-xs-4">
						                <div class="invoice-footer-heading"><?php echo lang('common_change_due'); ?></div>
						            </div>
						            <div class="col-md-2 col-sm-2 col-xs-4">
						                <div class="invoice-footer-value"><?php echo H($amount_change); ?></div>
						            </div>
						        </div>
							<?php } ?>
							
							<?php if (isset($supplier_balance_for_sale) && (double)$supplier_balance_for_sale && !$this->config->item('hide_store_account_balance_on_receipt')) {?>
							
								<div class="row">						
						            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-4 col-md-2 col-sm-2 col-xs-48">
						                <div class="invoice-footer-value"><?php echo lang('receivings_supplier_account_balance'); ?></div>
						            </div>
						            <div class="col-md-2 col-sm-2 col-xs-4">
						                <div class="invoice-footer-value invoice-payment"><?php echo to_currency($supplier_balance_for_sale); ?></div>
						            </div>
						        </div>
							<?php
							}
							?>
					    </div>
					    <!-- invoice footer -->
					    <div class="row">
					        <div class="col-md-12 col-sm-12 col-xs-12">
					            <?php if (!$this->config->item('hide_barcode_on_sales_and_recv_receipt')) {?>
						            <div class="invoice-policy" id="barcode">
						            	<?php echo "<img src='".site_url('barcode/index/svg')."?barcode=$receiving_id&text=$receiving_id' />"; ?>
						            </div>
						        <?php } ?>
					        </div>
					    </div>
						<div class="row">
				            <div class="col-md-12 col-sm-12 col-xs-12">
				                <div class="invoice-footer-value" style="padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;width:100%;text-align:left;">
									<?php
										if (empty($comment)) {
											echo lang('common_comments').": ". 'Ninguno';
										} else {
											echo lang('common_comments').": ". H($comment);
										}
									?>
				                </div>
				            </div>
				        </div>
					</div>
				</div>
			</section>
		</div>
	</div>

</body>
</html>