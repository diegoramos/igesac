
<?php 

//header('content-type: application/pdf');
//readfile('http://localhost/facturador1/consulta/verPdf/10434679341-03-B002-2');

 ?>


<?php $this->load->view("partial/header"); ?>

<?php
	$this->load->helper('sale');
	$return_policy = ($loc_return_policy = $this->Location->get_info_for_key('return_policy', isset($override_location_id) ? $override_location_id : FALSE)) ? $loc_return_policy : $this->config->item('return_policy');
	$company = ($company = $this->Location->get_info_for_key('company', isset($override_location_id) ? $override_location_id : FALSE)) ? $company : $this->config->item('company');
	$website = ($website = $this->Location->get_info_for_key('website', isset($override_location_id) ? $override_location_id : FALSE)) ? $website : $this->config->item('website');
	$company_logo = ($company_logo = $this->Location->get_info_for_key('company_logo', isset($override_location_id) ? $override_location_id : FALSE)) ? $company_logo : $this->config->item('company_logo');
	
	$is_integrated_credit_sale = is_sale_integrated_cc_processing($cart);
	$is_sale_integrated_ebt_sale = is_sale_integrated_ebt_sale($cart);
	$is_credit_card_sale = is_credit_card_sale($cart);
	
	$signature_needed = $this->config->item('capture_sig_for_all_payments') || (($is_credit_card_sale && !$is_integrated_credit_sale) ||  is_store_account_sale($cart));
	
	//Check for EMV signature for non pin verified
	if (!$signature_needed && $is_integrated_credit_sale)
	{
		foreach($payments as $payment_id=>$payment)
		{
			if ($payment->cvm != 'PIN VERIFIED')
			{
				$signature_needed = TRUE;
				break;
			}
		}
	}
	
	if (isset($error_message))
	{
		echo '<h1 style="text-align: center;">'.$error_message.'</h1>';
		exit;
	}
?>

<div class="manage_buttons hidden-print">
	<div class="row">
		<div class="col-md-6">
			<div class="hidden-print search no-left-border">
				<ul class="list-inline print-buttons">
					<li></li>
					
						<li>
							<?php 
							 if ($sale_id_raw != lang('sales_test_mode_transaction') && !$store_account_payment && !$is_ecommerce && $this->Employee->has_module_action_permission('sales', 'edit_sale', $this->Employee->get_logged_in_employee_info()->person_id)){

						   		$edit_sale_url = (isset($sale_type) && ($sale_type == ($this->config->item('user_configured_layaway_name') ? $this->config->item('user_configured_layaway_name') : lang('common_layaway')) || $sale_type == lang('common_estimate'))) ? 'unsuspend' : 'change_sale';
								echo form_open("sales/$edit_sale_url/".$sale_id_raw,array('id'=>'sales_change_form')); ?>
								<button class="btn btn-primary btn-lg hidden-print" id="edit_sale"> <?php echo lang('sales_edit'); ?> </button>

							<?php }	?>
							</form>		
						</li>
						
					<?php 
					if ($sale_id_raw != lang('sales_test_mode_transaction')){
					?>	
						<li>
							<button class="btn btn-primary btn-lg hidden-print" id="fufillment_sheet_button" onclick="window.open('<?php echo site_url("sales/fulfillment/$sale_id_raw"); ?>', 'blank');" > <?php echo lang('sales_fulfillment_sheet'); ?></button>
						</li>
					<?php } ?>
					
					<li>
						<button class="btn btn-primary btn-lg hidden-print gift_receipt" id="gift_receipt_button" onclick="toggle_gift_receipt()" > <?php echo lang('sales_gift_receipt'); ?> </button>
					</li>
						<?php if ($sale_id_raw != lang('sales_test_mode_transaction') && !empty($customer_email)) { ?>
							<li>
									<?php echo anchor('sales/email_receipt/'.$sale_id_raw, lang('common_email_receipt'), array('id' => 'email_receipt','class' => 'btn btn-primary btn-lg hidden-print'));?>
							</li>
						<?php }?>
					
					<?php if ($sale_id_raw != lang('sales_test_mode_transaction')) { ?>
						<li>
							<button class="btn btn-primary btn-lg hidden-print" id="fufillment_sheet_button" onclick="window.open('<?php echo site_url("sales/create_po/$sale_id_raw"); ?>', 'blank');" > <?php echo lang('common_create_po'); ?></button>
						</li>
						<?php } ?>					
				</ul>
			</div>
		</div>
		<div class="col-md-6">	
			<div class="buttons-list">
				<div class="pull-right-btn">
					<ul class="list-inline print-buttons">
						<li>
							<?php
							echo form_checkbox(array(
								'name'        => 'print_duplicate_receipt',
								'id'          => 'print_duplicate_receipt',
								'value'       => '1',
							)).'&nbsp;<label for="print_duplicate_receipt"><span></span>'.lang('sales_duplicate_receipt').'</label>';
								?>		
						</li>
						<li>
							<button class="btn btn-primary btn-lg hidden-print" id="print_button" onclick="print_receipt()" > <?php echo lang('common_print'); ?> </button>		
						</li>
						<li>
							<?php echo anchor_popup(site_url('sales/open_drawer'), '<i class="ion-android-open"></i> '.lang('common_pop_open_cash_drawer'),array('class'=>'btn btn-primary btn-lg hidden-print', 'target' => '_blank')); ?>
						</li>
						<li>
							<button class="btn btn-primary btn-lg hidden-print" id="new_sale_button_1" onclick="window.location='<?php echo site_url('sales'); ?>'" > <?php echo lang('sales_new_sale'); ?> </button>	
						</li>
					</ul>
				</div>
			</div>				
		</div>
	</div>
</div>
<div class="row manage-table receipt_<?php echo $this->config->item('receipt_text_size') ? $this->config->item('receipt_text_size') : 'small';?>" id="receipt_wrapper">
	<div class="col-md-12" id="receipt_wrapper_inner">
		<div class="panel panel-piluku">
			<div class="panel-body panel-pad">
				<div style="width: 100%;padding-top: 30px;">
					<table width="100%">
						<tbody>
							<tr>
								<td>
									<table style="width: 100%;">
										<tr>
											<td align="center">
												<strong>
												<?php echo strtoupper(H($company)); ?>
												<?php if ($this->Location->count_all() > 1) { ?>
													<li><?php echo H($this->Location->get_info_for_key('name', isset($override_location_id) ? $override_location_id : FALSE)); ?></li>
												<?php } ?>
												</strong>
											</td>
										</tr>
										<tr>
											<td align="center">
												<?php echo nl2br(H($this->Location->get_info_for_key('address', isset($override_location_id) ? $override_location_id : FALSE))); ?>
											</td>
										</tr>
										<tr>
											<td align="center">
												<?php echo H($this->Location->get_info_for_key('phone', isset($override_location_id) ? $override_location_id : FALSE)); ?>
											</td>
										</tr>
										<tr>
											<td>
												&nbsp;
											</td>
										</tr>
										<tr>
											<td align="center">
												<strong>NOTA DE VENTA Nro.: <?php echo H($sale_id); ?></strong>
											</td>
										</tr>
									</table>
								</td>
							</tr>

							<tr>
								<td style="border: 0px solid black; border-radius: 25px" >
									<table width="100%">
										<tbody>												
											<tr>
												<td style="padding-top: 1px;padding-left: 10px;" width="40%"><strong><font color="black">Se??ores:</font></strong></td>
												<td><font color="black"><?php if (!$this->config->item('remove_customer_name_from_receipt')) { ?>
												<?php echo explode('-',H($customer))[0]; ?><?php } ?></font></td>
											</tr>
											<tr>
												<td style="padding-top: 1px;padding-left: 10px;"><strong><font color="black" >Fecha de emisi??n:</font></strong></td>
												<td><font color="black"><?php echo H($transaction_time) ?></font></td>
											</tr>
											<tr>
												<td style="padding-top: 1px;padding-left: 10px;"><strong><font color="black" >Empleado:</font></strong></td>
												<td><font color="black"><?php echo H($employee); ?></font></td>
											</tr> 
											
										</tbody>
									</table>
								</td>
							</tr>
							<tr><td>&nbsp;</td></tr>
							<tr>
								<td>
															<table width="100%" style="border: 0px solid black; border-radius: 25px">
																						<tr>
																							<td style="padding-top: 2px;padding-left: 10px;"><strong><font color="black"><?php echo lang('common_quantity'); ?></font></strong></td>
																							<td style="padding-top: 2px;padding-left: 10px;"><strong><font color="black"><?php echo lang('common_item_name'); ?></font></strong></td>
																							<td align="right" style="padding-top: 2px;padding-left: 10px;"><strong><font color="black"><?php echo lang('common_price'); ?></font></strong></td>
																							<td align="right" style="padding-top: 2px;padding-left: 10px;"><strong><font><?php echo lang('common_total'); ?></font></strong></td>
																						</tr>
																						<?php
																							if ($discount_item_line = $cart->get_index_for_flat_discount_item())
																							{
																								$discount_item = $cart->get_item($discount_item_line);
																								$cart->delete_item($discount_item_line);
																								$cart->add_item($discount_item,false);
																								
																							}
																	 
																							$number_of_items_sold = 0;
																							$number_of_items_returned = 0;
																		
																	foreach(array_reverse($cart_items, true) as $line=>$item)
																	{
																		if ($item->tax_included)
																		{
																			if (get_class($item) == 'PHPPOSCartItemSale')
																			{
																				if ($item->tax_included)
																				{
																					$this->load->helper('items');
																					$unit_price = to_currency_no_money(get_price_for_item_including_taxes($item->item_id, $item->unit_price));
																				}
																			}
																			else
																			{
																				if ($item->tax_included)
																				{
																					$this->load->helper('item_kits');
																					$unit_price = to_currency_no_money(get_price_for_item_kit_including_taxes($item->item_kit_id, $item->unit_price));
																				}
																			}
																		}
																		else
																		{
																			$unit_price = $item->unit_price;
																		}
																		 if ($item->quantity > 0 && $item->name != lang('common_store_account_payment') && $item->name != lang('common_discount') && $item->name != lang('common_refund') && $item->name != lang('common_fee'))
																		 {
																	 		 $number_of_items_sold = $number_of_items_sold + $item->quantity;
																		 }
																		 elseif ($item->quantity < 0 && $item->name != lang('common_store_account_payment') && $item->name != lang('common_discount') && $item->name != lang('common_refund') && $item->name != lang('common_fee'))
																		 {
																	 		 $number_of_items_returned = $number_of_items_returned + abs($item->quantity);
																		 }
																		 
																		$item_number_for_receipt = false;
																		
																		if ($this->config->item('show_item_id_on_receipt'))
																		{
																			switch($this->config->item('id_to_show_on_sale_interface'))
																			{
																				case 'number':
																				$item_number_for_receipt = array_key_exists('item_number', $item) ? H($item->item_number) : H($item->item_kit_number);
																				break;
																			
																				case 'product_id':
																				$item_number_for_receipt = array_key_exists('product_id', $item) ? H($item->product_id) : ''; 
																				break;
																			
																				case 'id':
																				$item_number_for_receipt = array_key_exists('item_id', $item) ? H($item->item_id) : 'KIT '.H($item->item_kit_id); 
																				break;
																			
																				default:
																				$item_number_for_receipt = array_key_exists('item_number', $item) ? H($item->item_number) : H($item->item_kit_number);
																				break;
																			}
																		}
																		//esto dbes eliminar si es que uiqeres uqe funcione jajajajaj
																	?>
																				<tr>
																					<td align="right" style="padding-left: 10px;">
																						<?php 
																								if ($this->config->item('number_of_decimals_for_quantity_on_receipt') && floor($item->quantity) != $item->quantity)
																								{
																									echo to_currency_no_money($item->quantity,$this->config->item('number_of_decimals_for_quantity_on_receipt')); 
																								}
																								else
																								{
																									echo to_quantity($item->quantity); 
																								}
																								?>
																					</td>
																					<td style="padding-left: 10px;">
																						<div class="invoice-content-heading"><?php echo H($item->name); ?><?php if ($item_number_for_receipt){ ?> - <?php echo $item_number_for_receipt; ?><?php } ?><?php if ($item->size){ ?> (<?php echo H($item->size); ?>)<?php } ?>
																						</div>
																						<div class="invoice-desc">
																							<?php 
																								echo isset($item->variation_name) && $item->variation_name ? H($item->variation_name) : '';
																							?>
																						</div>

															                 			 <div class="invoice-desc">
																	                    <?php 
																							if(isset($item->serialnumber) && $item->serialnumber !="")
																												{
																													echo H($item->serialnumber); 
																												}
																												
																											?>
																						</div>
																							<?php
																								if(isset($item->rule['type']))
																									{	
																										
																										echo '<br class="gift_receipt_element"><i class="gift_receipt_element">'.H($item->rule['name']).'</i>';
																										if(isset($item->rule['rule_discount']))
																										{
																											echo '<br class="gift_receipt_element"><i class="gift_receipt_element"><u class="gift_receipt_element">'.lang('common_discount'). '</u>: ' .to_currency($item->rule['rule_discount']) . '</i>';
																										}																	
																									}
																										
																									?>
																					</td>
																					<td align="right" style="padding-left: 10px;">
																						<?php if ($this->config->item('show_orig_price_if_marked_down_on_receipt') && $item->regular_price > $unit_price) { ?>
																						<span class="strikethrough"><?php echo to_currency($item->regular_price,10);?></span>
																						<?php } ?>
																						<?php echo str_replace('S/.', '', to_currency($unit_price,10)); ?>
																					</td>

																					<td align="right" style="padding-left: 10px;">
																						<?php if ($this->config->item('indicate_taxable_on_receipt') && $item->taxable && !empty($taxes))
																							{
																								echo '<small>*'.lang('common_taxable').'</small>';
																							}
																							?>
																						<?php echo str_replace('S/.', '', to_currency($unit_price*$item->quantity-$unit_price*$item->quantity*$item->discount/100,10)); ?>
																					</td>
																				</tr>

																			<?php } ?>
															</table>
								</td>
							</tr>
							<tr><td>&nbsp;</td></tr>

						</tbody>
					</table>

						<table>
							<tbody>

							<?php if ($this->config->item('group_all_taxes_on_receipt')) { ?>
								<?php 
								$total_tax = 0;
								foreach($taxes as $name=>$value) 
								{
									$total_tax+=$value;
							 	}
								?>	
								<tr>
									<td></td>
									<td><?php echo lang('common_tax'); ?></td>
									<td>
										<?php if (isset($exchange_name) && $exchange_name) { 
										echo to_currency_as_exchange($cart,$total_tax*$exchange_rate);					
										?>
										<?php } else {  ?>
										<?php echo to_currency($total_tax*$exchange_rate); ?>				
										<?php
										}
										?>
									</td>
								</tr>
						<?php }else {?>
							<?php foreach($taxes as $name=>$value) { ?>
								<tr>
									<td></td>
									<td><?php echo H($name); ?></td>
									<td>
										<?php if (isset($exchange_name) && $exchange_name) { 
											echo to_currency_as_exchange($cart,$value*$exchange_rate);					
										?>
										<?php } else {  ?>
										<?php echo to_currency($value); ?>				
										<?php
										}
										?>
									</td>
								</tr>
							<?php }; ?>
						<?php } ?>
						<tr>
							<td>&nbsp;</td>
							<td><?php echo lang('common_total'); ?></td>
							<td>
								<?php if (isset($exchange_name) && $exchange_name) { 
									?>
									<?php echo $this->config->item('round_cash_on_sales') && $is_sale_cash_payment ?  to_currency_as_exchange($cart,round_to_nearest_05($total)) : to_currency_as_exchange($cart,$total); ?>				
								<?php } else {  ?>
								<?php echo $this->config->item('round_cash_on_sales') && $is_sale_cash_payment ?  to_currency(round_to_nearest_05($total)) : to_currency($total);  ?>				
								<?php
								}
								?>
							</td>
						</tr>
						<?php if ($number_of_items_sold) {  ?>
						<tr>
							<td></td>
							<td><?php echo lang('common_items_sold'); ?></td>
							<td>
								<?php echo to_quantity($number_of_items_sold); ?>
							</td>
						</tr>
						<?php } ?>
						<?php if ($number_of_items_returned) { ?>
						<tr>
							<td></td>
							<td><?php echo lang('common_items_returned'); ?></td>
							<td><?php echo to_quantity($number_of_items_returned); ?>
							</td>
						</tr>
						<?php }  ?>

						<?php
							foreach($payments as $payment_id=>$payment)
							{ 
						?>
						<tr>
							<td width="20%">&nbsp;</td>
							<td width="70%">
								<?php if (($is_integrated_credit_sale || sale_has_partial_credit_card_payment($cart) || $is_sale_integrated_ebt_sale || sale_has_partial_ebt_payment($cart)) && ($payment->payment_type == lang('common_credit') ||  $payment->payment_type == lang('sales_partial_credit') || $payment->payment_type == lang('common_ebt') || $payment->payment_type == lang('common_partial_ebt') ||  $payment->payment_type == lang('common_ebt_cash') ||  $payment->payment_type == lang('common_partial_ebt_cash'))) { ?>
							<div class="invoice-footer-value"><?php echo $is_sale_integrated_ebt_sale ? 'EBT ' : '';?><?php echo H($payment->card_issuer. ': '.$payment->truncated_card); ?></div>
						<?php } else { ?>
							<div class="invoice-footer-value"><?php $splitpayment=explode(':',$payment->payment_type); echo H($splitpayment[0]); ?></div>																				
						<?php } ?>	

							</td>
							<td>
								<?php 
						
						if (isset($exchange_name) && $exchange_name) { 
							?>
							<?php echo $this->config->item('round_cash_on_sales') && $payment->payment_type == lang('common_cash') ?  to_currency_as_exchange($cart,round_to_nearest_05($payment->payment_amount)) : to_currency_as_exchange($cart,$payment->payment_amount); ?>				
						<?php } else {  ?>
						<?php echo $this->config->item('round_cash_on_sales') && $payment->payment_type == lang('common_cash') ?  to_currency(round_to_nearest_05($payment->payment_amount)) : to_currency($payment->payment_amount); ?>				
						<?php
						}
						?>
							</td>
						</tr>
						<?php
							} //yony
						?>
						<?php foreach($payments as $payment) {?>
							<?php if (strpos($payment->payment_type, lang('common_giftcard'))!== FALSE) {?>
								<?php $giftcard_payment_row = explode(':', $payment->payment_type); ?>
						<tr>
							<td></td>
							<td><?php echo H($payment->payment_type);?></td>
							<td><?php echo to_currency($this->Giftcard->get_giftcard_value(end($giftcard_payment_row))); ?></td>
						</tr>
							<?php }?>
						<?php }?> 
						<?php if ($amount_change >= 0) {//yony cambio moneda ?>
						<tr>
							<td></td>
							<td><?php echo lang('common_change_due'); ?></td>
							<td>
								<?php if (isset($exchange_name) && $exchange_name) { 
											$amount_change_default_currency = $amount_change*pow($exchange_rate,-1);
											
											?>
											
											<?php
												
											if ($amount_change_default_currency != $amount_change) {
											?>
											<?php echo $this->config->item('round_cash_on_sales')  && $is_sale_cash_payment ?  to_currency_as_exchange($cart,round_to_nearest_05($amount_change)) : to_currency_as_exchange($cart,$amount_change); ?>
											<br /><?php echo lang('common_or');?><br />
											<?php
										}
											?>
											<?php echo $this->config->item('round_cash_on_sales')  && $is_sale_cash_payment ?  to_currency(round_to_nearest_05($amount_change_default_currency)) : to_currency($amount_change_default_currency); ?>				
											
										<?php } else {  ?>
										<?php echo $this->config->item('round_cash_on_sales')  && $is_sale_cash_payment ?  to_currency(round_to_nearest_05($amount_change)) : to_currency($amount_change); ?>				
										<?php
										}
										?>
							</td>
						</tr>
						<?php
						}
						else
						{
						?>
							<?php if (!$is_ecommerce) { ?>
						<tr>
							<td></td>
							<td><?php echo lang('common_amount_due'); ?></td>
							<td>
								<?php if (isset($exchange_name) && $exchange_name) { 
									?>
								<?php echo $this->config->item('round_cash_on_sales')  && $is_sale_cash_payment ?  to_currency_as_exchange($cart,round_to_nearest_05($amount_change * -1)) : to_currency_as_exchange($cart,$amount_change * -1); ?>
								<?php } else {  ?>
								<?php echo $this->config->item('round_cash_on_sales')  && $is_sale_cash_payment ?  to_currency(round_to_nearest_05($amount_change * -1)) : to_currency($amount_change * -1); ?>
								<?php
								}
								?>
							</td>
						</tr>
						<?php
							} 
						}
							?> 
						<?php if (isset($ebt_balance) && ($ebt_balance) !== FALSE) {?>
						<tr>
							<td></td>
							<td><?php echo lang('sales_ebt_balance_amount'); ?></td>
							<td><?php echo to_currency($ebt_balance); ?></td>
						</tr>
						<?php
							}
							?>
						<?php if (!$disable_loyalty && $this->config->item('enable_customer_loyalty_system') && isset($sales_until_discount) && !$this->config->item('hide_sales_to_discount_on_receipt') && $this->config->item('loyalty_option') == 'simple') {?>
						<tr>
							<td></td>
							<td><?php echo lang('common_sales_until_discount'); ?></td>
							<td><?php echo $sales_until_discount <= 0 ? lang('sales_redeem_discount_for_next_sale') : to_quantity($sales_until_discount); ?></td>
						</tr>
						<?php
							}
							?>
						<?php if (!$disable_loyalty && $this->config->item('enable_customer_loyalty_system') && isset($customer_points) && !$this->config->item('hide_points_on_receipt') && $this->config->item('loyalty_option') == 'advanced') {?>
						<tr>
							<td></td>
							<td><?php echo lang('common_points'); ?></td>
							<td><?php echo to_quantity($customer_points); ?></td>
						</tr>
						<?php
						}
						?>
						<?php
						if ($ref_no)
						{
						?>
						<tr>
							<td></td>
							<td><?php echo lang('sales_ref_no'); ?></td>
							<td><?php echo H($ref_no); ?></td>
						</tr>
						<?php
						}
						if (isset($auth_code) && $auth_code)
						{
						?>
						<tr>
							<td></td>
							<td><?php echo lang('sales_auth_code'); ?></td>
							<td><?php echo H($auth_code); ?></td>
						</tr>
						<?php
						}
						?>


							</tbody>
						</table>

				</div>
			    <div class="row">

			    <!-- invoice footer-->						 
			    <div class="row">
			        <div class="col-md-12 col-sm-12 col-xs-12">



						
						<?php 
						$this->load->model('Price_rule');
						$coupons = $this->Price_rule->get_coupons_for_receipt();
						if (count($coupons) > 0)
						{
							?>
							
					    <div class="row">
					        <div class="col-md-12 col-sm-12 col-xs-12">
					            <div class="invoice-policy">
									<h3 class='text-center'><?php echo lang('common_coupons');?></h3>
												
					            </div>
									</div>
							</div>
							<?php
								
						
							foreach($coupons as $coupon)
							{
								?>
								<div class="invoice-policy coupon">
									<?php
									$coupon_text = H($coupon['name'].' - '.$coupon['description']);
									$coupon_barcode = H($coupon['coupon_code']);
									$begins = date(get_date_format(),strtotime($coupon['start_date']));
									$expires = date(get_date_format(),strtotime($coupon['end_date']));
									?>
									<div><?php echo H($coupon_text);?></div>
									<?php echo "<img src='".site_url('barcode/index/svg')."?barcode=$coupon_barcode' alt=''/>"; ?>
									<div><?php echo lang('common_coupon_code').': '.H($coupon_barcode);?></div>
									<div><?php echo lang('common_begins').': '.H($begins);?></div>
									<div><?php echo lang('common_expires').': '.H($expires);?></div>
								</div><br />
								
								<?php
							}
						?>
							
						<?php
						}?>

							<?php if ($signature_needed && !$this->config->item('hide_signature')) {?>
								<button class="btn btn-primary text-white hidden-print" id="capture_digital_sig_button"> <?php echo lang('sales_capture_digital_signature'); ?> </button>
								<br />
							<?php
							}
							?>
			      </div>
					
					<?php if(!$this->config->item('hide_signature')) { ?>
			        <div class="col-md-6 col-sm-6 col-md-offset-3 col-sm-offset-3">
						<div id="signature">
								<?php if ($signature_needed) {?>
									
									<div id="digital_sig_holder">
										<canvas id="sig_cnv" name="sig_cnv" class="signature" width="500" height="100"></canvas>
										<div id="sig_actions_container" class="pull-right">
											<?php
											if ($this->agent->is_mobile()) //Display done button first
											{
											?>
												<button class="btn btn-primary btn-radius btn-lg hidden-print" id="capture_digital_sig_done_button"> <?php echo lang('sales_done_capturing_sig'); ?> </button>
												<button class="btn btn-primary btn-radius btn-lg hidden-print" id="capture_digital_sig_clear_button"> <?php echo lang('sales_clear_signature'); ?> </button>
											<?php
											}
											else  //Display done button 2nd
											{
											?>
												<button class="btn btn-primary btn-radius btn-lg hidden-print" id="capture_digital_sig_clear_button"> <?php echo lang('sales_clear_signature'); ?> </button>
												<button class="btn btn-primary btn-radius btn-lg hidden-print" id="capture_digital_sig_done_button"> <?php echo lang('sales_done_capturing_sig'); ?> </button>
											<?php	
											}
											?>
										</div>
									</div>
								<?php } ?>
								

								
								<?php 
								$this->load->helper('sale');
								if ($is_credit_card_sale)
								{	
									echo lang('sales_card_statement');
								}
								?>
								
						</div>
			        </div>
			        <?php } ?>
			    </div>
			</div>
			<!--container-->
		</div>		
	</div>
</div>
</div>

<div id="duplicate_receipt_holder" style="display: none;">
	
</div>

<?php if ($this->config->item('print_after_sale') && $this->uri->segment(2) == 'complete')
{
?>
<script type="text/javascript">
$(window).bind("load", function() {
	print_receipt();
});
</script>
<?php }  ?>

<script type="text/javascript">

$(document).ready(function(){
	
	$("#edit_sale").click(function(e)
	{
		e.preventDefault();
		bootbox.confirm(<?php echo json_encode(lang('sales_sale_edit_confirm')); ?>,function(result)
		{
			if (result)
			{
				$("#sales_change_form").submit();
			}
		});
	});
	$("#email_receipt").click(function()
	{
		$.get($(this).attr('href'), function()
		{
			show_feedback('success', <?php echo json_encode(lang('common_receipt_sent')); ?>, <?php echo json_encode(lang('common_success')); ?>);
			
		});
		
		return false;
	});
});

$('#print_duplicate_receipt').click(function()
{
	if ($('#print_duplicate_receipt').prop('checked'))
	{
	   var receipt = $('#receipt_wrapper').clone();
	   $('#duplicate_receipt_holder').html(receipt);
		$("#duplicate_receipt_holder").addClass('visible-print-block');
		$("#duplicate_receipt_holder .receipt_type_label").text(<?php echo json_encode(lang('sales_duplicate_receipt')); ?>);
		$(".receipt_type_label").show();		
		$(".receipt_type_label").addClass('show_receipt_labels');		
	}
	else
	{
		$("#duplicate_receipt_holder").empty();
		$("#duplicate_receipt_holder").removeClass('visible-print-block');
		$(".receipt_type_label").hide();
		$(".receipt_type_label").removeClass('show_receipt_labels');	
	}
});

<?php
$this->load->helper('sale');
if ($this->config->item('always_print_duplicate_receipt_all') || ($this->config->item('automatically_print_duplicate_receipt_for_cc_transactions') && $is_credit_card_sale))
{
?>
	$("#print_duplicate_receipt").trigger('click');
<?php
}
?>

function print_receipt()
 {
 	window.print();
 	<?php
 	if ($this->config->item('redirect_to_sale_or_recv_screen_after_printing_receipt'))
 	{
 	?>
 	window.location = '<?php echo site_url('sales'); ?>';
 	<?php
 	}
 	?>
 }
 
 function toggle_gift_receipt()
 {
	 var gift_receipt_text = <?php echo json_encode(lang('sales_gift_receipt')); ?>;
	 var regular_receipt_text = <?php echo json_encode(lang('sales_regular_receipt')); ?>;
	 
	 if ($("#gift_receipt_button").hasClass('regular_receipt'))
	 {
		 $('#gift_receipt_button').addClass('gift_receipt');	 	
		 $('#gift_receipt_button').removeClass('regular_receipt');
		 $("#gift_receipt_button").text(gift_receipt_text);	
		 $('.gift_receipt_element').show();	
	 }
	 else
	 {
		 $('#gift_receipt_button').removeClass('gift_receipt');	 	
		 $('#gift_receipt_button').addClass('regular_receipt');
		 $("#gift_receipt_button").text(regular_receipt_text);
		 $('.gift_receipt_element').hide();	
	 }
 	
 }
 
//timer for sig refresh
var refresh_timer;
var sig_canvas = document.getElementById('sig_cnv');

<?php
//Only use Sig touch on mobile
if ($this->agent->is_mobile())
{
?>
	var signaturePad = new SignaturePad(sig_canvas);
<?php
}
?>
$("#capture_digital_sig_button").click(function()
{	
	<?php
	//Only use Sig touch on mobile
	if ($this->agent->is_mobile())
	{
	?>
		signaturePad.clear();
	<?php
	}
	else
	{
	?>
		try
		{
			if (TabletConnectQuery()==0)
			{
				bootbox.alert(<?php echo json_encode(lang('sales_unable_to_connect_to_signature_pad')); ?>);
				return;
			}	
		}
		catch(exception) 
		{
			bootbox.alert(<?php echo json_encode(lang('sales_unable_to_connect_to_signature_pad')); ?>);
			return;			
		}
		
	   var ctx = document.getElementById('sig_cnv').getContext('2d');
	   SigWebSetDisplayTarget(ctx);
	   SetDisplayXSize( 500 );
	   SetDisplayYSize( 100 );
	   SetJustifyMode(0);
	   refresh_timer = SetTabletState(1,ctx,50);
	   KeyPadClearHotSpotList();
	   ClearSigWindow(1);
	   ClearTablet();
	<?php
	}
	?>
	
	$("#capture_digital_sig_button").hide();
	$("#digital_sig_holder").show();
});

$("#capture_digital_sig_clear_button").click(function()
{
	<?php
	//Only use Sig touch on mobile
	if ($this->agent->is_mobile())
	{
	?>
		signaturePad.clear();
	<?php
	}
	else
	{
	?>
   	ClearTablet();	
	<?php
	}
	?>
});

$("#capture_digital_sig_done_button").click(function()
{
	<?php
	//Only use Sig touch on mobile
	if ($this->agent->is_mobile())
	{
	?>
	   if(signaturePad.isEmpty())
	   {
	      bootbox.alert(<?php echo json_encode(lang('sales_no_sig_captured')); ?>);
	   }
	   else
	   {
			SigImageCallback(signaturePad.toDataURL().split(",")[1]);
			$("#capture_digital_sig_button").show();
	   }	
	<?php
	}
	else
	{
	?>
		if(NumberOfTabletPoints() == 0)
		{
		   bootbox.alert(<?php echo json_encode(lang('sales_no_sig_captured')); ?>);
		}
		else
		{
		   SetTabletState(0,refresh_timer);
		   //RETURN TOPAZ-FORMAT SIGSTRING
		   SetSigCompressionMode(1);
			var sig = GetSigString();

		   //RETURN BMP BYTE ARRAY CONVERTED TO BASE64 STRING
		   SetImageXSize(500);
		   SetImageYSize(100);
		   SetImagePenWidth(5);
		   GetSigImageB64(SigImageCallback);
			$("#capture_digital_sig_button").show();
		}
	<?php
	}
	?>
});

function SigImageCallback( str )
{
 $("#digital_sig_holder").hide();
 $.post('<?php echo site_url('sales/sig_save'); ?>', {sale_id: <?php echo json_encode($sale_id_raw); ?>, image: str}, function(response)
 {
	 $("#signature_holder").empty();
	 $("#signature_holder").append('<img src="'+SITE_URL+'/app_files/view/'+response.file_id+'?timestamp='+response.file_timestamp+'" width="250" />');
 }, 'json');

}
 
<?php
//EMV Usb Reset
if (isset($reset_params))
{
?>
 var data = {};
 <?php
 foreach($reset_params['post_data'] as $name=>$value)
 {
	 if ($name && $value)
	 {
	 ?>
	 data['<?php echo $name; ?>'] = '<?php echo $value; ?>';
 	 <?php 
	 }
 }
 ?>	

 mercury_emv_pad_reset(<?php echo json_encode($reset_params['post_host']); ?>, <?php echo $this->Location->get_info_for_key('listener_port'); ?>, data);
<?php
}
if (isset($trans_cloud_reset) && $trans_cloud_reset)
{
?>
	$.get(<?php echo json_encode(site_url('sales/reset_pin_pad')); ?>);
<?php
}
?>
</script>

<?php if(($is_integrated_credit_sale || $is_sale_integrated_ebt_sale) && $is_sale) { ?>
<script type="text/javascript">
show_feedback('success', <?php echo json_encode(lang('sales_credit_card_processing_success')); ?>, <?php echo json_encode(lang('common_success')); ?>);	
</script>
<?php } ?>

<!-- This is used for mobile apps to print receipt-->
<script type="text/print" id="print_output"><?php echo $company; ?>

<?php echo H($this->Location->get_info_for_key('address',isset($override_location_id) ? $override_location_id : FALSE)); ?>

<?php echo H($this->Location->get_info_for_key('phone',isset($override_location_id) ? $override_location_id : FALSE)); ?>

<?php if($website) { ?>
<?php echo H($website); ?>
	
<?php } ?>

<?php echo H($receipt_title); ?>

<?php echo H($transaction_time); ?>

<?php if(isset($customer))
{
?>
<?php echo lang('common_customer').": ".H($customer); ?>
<?php if (!$this->config->item('remove_customer_contact_info_from_receipt')) { ?>
	
<?php if(!empty($customer_address_1)){ ?><?php echo lang('common_address'); ?>: <?php echo H($customer_address_1. ' '.$customer_address_2); ?>
	
<?php } ?>
<?php if (!empty($customer_city)) { echo H($customer_city.' '.$customer_state.', '.$customer_zip); ?>

<?php } ?>
<?php if (!empty($customer_country)) { echo H($customer_country); ?>
	
<?php } ?>
<?php if(!empty($customer_phone)){ ?><?php echo lang('common_phone_number'); ?> : <?php echo H($customer_phone); ?>
	
<?php } ?>
<?php if(!empty($customer_email)){ ?><?php echo lang('common_email'); ?> : <?php echo H($customer_email); ?><?php } ?>

<?php
}
else
{
?>
	
<?php
}
}
?>
<?php echo lang('common_sale_id').": ".$sale_id; ?>
<?php if (isset($sale_type)) { ?>
<?php echo $sale_type; ?>
<?php } ?>
<?php if (!$this->config->item('remove_employee_from_receipt')) { ?>
<?php echo lang('common_employee').": ".$employee; ?>
<?php }?>
<?php 
if($this->Location->get_info_for_key('enable_credit_card_processing',isset($override_location_id) ? $override_location_id : FALSE))
{
	echo lang('common_merchant_id').': '.H($this->Location->get_merchant_id(isset($override_location_id) ? $override_location_id : FALSE));
}
?>

<?php echo lang('common_item'); ?>            <?php echo lang('common_price'); ?> <?php echo lang('common_quantity'); ?><?php if($discount_exists){echo ' '.lang('common_discount_percent');}?> <?php echo lang('common_total'); ?>

<?php
foreach(array_reverse($cart_items, true) as $line=>$item)
{
?>
<?php echo character_limiter(H($item->name), 14,'...'); ?><?php echo strlen($item->name) < 14 ? str_repeat(' ', 14 - strlen(H($item->name))) : ''; ?> <?php echo str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($item->unit_price,10)); ?> <?php echo to_quantity($item->quantity); ?><?php if($discount_exists){echo ' '.$item->discount;}?> <?php echo str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($item->unit_price*$item->quantity-$item->unit_price*$item->quantity*$item->discount/100,10)); ?>

  <?php echo H($item->description); ?>  <?php echo isset($item->serialnumber) ? H($item->serialnumber) : ''; ?>
	

<?php
}
?>

<?php echo lang('common_sub_total'); ?>: <?php echo str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($subtotal)); ?>


<?php foreach($taxes as $name=>$value) { ?>
<?php echo $name; ?>: <?php echo str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($value)); ?>

<?php }; ?>

<?php echo lang('common_total'); ?>: <?php echo $this->config->item('round_cash_on_sales') && $is_sale_cash_payment ?  str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency(round_to_nearest_05($total))) : str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($total)); ?>

<?php echo lang('common_items_sold'); ?>: <?php echo to_quantity($number_of_items_sold); ?>

<?php
	foreach($payments as $payment_id=>$payment)
{ ?>

<?php echo (isset($show_payment_times) && $show_payment_times) ?  date(get_date_format().' '.get_time_format(), strtotime($payment->payment_date)) : lang('common_payment'); ?>  <?php if (($is_integrated_credit_sale || sale_has_partial_credit_card_payment($cart) || sale_has_partial_ebt_payment($cart)) && ($payment->payment_type == lang('common_credit') ||  $payment->payment_type == lang('sales_partial_credit') || $payment->payment_type == lang('common_ebt') || $payment->payment_type == lang('common_partial_ebt') ||  $payment->payment_type == lang('common_ebt_cash') ||  $payment->payment_type == lang('common_partial_ebt_cash'))) { echo $payment->card_issuer. ': '.$payment->truncated_card; ?> <?php } else { ?><?php $splitpayment=explode(':',$payment->payment_type); echo $splitpayment[0]; ?> <?php } ?><?php echo $this->config->item('round_cash_on_sales') && $payment->payment_type == lang('common_cash') ?  str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency(round_to_nearest_05($payment->payment_amount))) : str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($payment->payment_amount)); ?>

<?php if ($payment->entry_method) { ?>
	
<?php echo lang('sales_entry_method'). ': '.H($payment->entry_method); ?>
	
<?php } ?>
<?php if ($payment->tran_type) { ?><?php echo lang('sales_transaction_type'). ': '.H($payment->tran_type); ?>
	
<?php } ?>
<?php if ($payment->application_label) { ?><?php echo lang('sales_application_label'). ': '.H($payment->application_label); ?>
	
<?php } ?>
<?php if ($payment->ref_no) { ?><?php echo lang('sales_ref_no'). ': '.H($payment->ref_no); ?>
	
<?php } ?>
<?php if ($payment->auth_code) { ?><?php echo lang('sales_auth_code'). ': '.H($payment->auth_code); ?>
	
<?php } ?>
<?php if ($payment->aid) { ?><?php echo 'AID: '.H($payment->aid); ?>
	
<?php } ?>
<?php if ($payment->tvr) { ?><?php echo 'TVR: '.H($payment->tvr); ?>

<?php } ?>
<?php if ($payment->tsi) { ?><?php echo 'TSI: '.H($payment->tsi); ?>
	
<?php } ?>
<?php if ($payment->arc) { ?><?php echo 'ARC: '.H($payment->arc); ?>
	
<?php } ?>
<?php if ($payment->cvm) { ?><?php echo 'CVM: '.H($payment->cvm); ?>
<?php } ?>
<?php
}
?>	
<?php foreach($payments as $payment) { $giftcard_payment_row = explode(':', $payment->payment_type);?>
<?php if (strpos($payment->payment_type, lang('common_giftcard'))!== FALSE) {?><?php echo lang('sales_giftcard_balance'); ?>  <?php echo $payment->payment_type;?>: <?php echo str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($this->Giftcard->get_giftcard_value(end($giftcard_payment_row)))); ?>
	<?php }?>
<?php }?>
<?php if ($amount_change >= 0) {?>
<?php echo lang('common_change_due'); ?>: <?php echo $this->config->item('round_cash_on_sales')  && $is_sale_cash_payment ?  str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency(round_to_nearest_05($amount_change))) : str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($amount_change)); ?>
<?php
}
else
{
?>
<?php echo lang('common_amount_due'); ?>: <?php echo $this->config->item('round_cash_on_sales')  && $is_sale_cash_payment ?  str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency(round_to_nearest_05($amount_change * -1))) : str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($amount_change * -1)); ?>
<?php
} 
?>
<?php if (!$disable_loyalty && $this->config->item('enable_customer_loyalty_system') && isset($customer_points) && !$this->config->item('hide_points_on_receipt')) {?>
	
<?php echo lang('common_points'); ?>: <?php echo to_currency_no_money($customer_points); ?>
<?php } ?>

<?php if (isset($customer_balance_for_sale) && (float)$customer_balance_for_sale && !$this->config->item('hide_store_account_balance_on_receipt')) {?>

<?php echo lang('sales_customer_account_balance'); ?>: <?php echo to_currency($customer_balance_for_sale); ?>
<?php
}
?>
<?php
if ($ref_no)
{
?>

<?php echo lang('sales_ref_no'); ?>: <?php echo $ref_no; ?>
<?php
}
if (isset($auth_code) && $auth_code)
{
?>

<?php echo lang('sales_auth_code'); ?>: <?php echo H($auth_code); ?>
<?php
}
?>
<?php if($show_comment_on_receipt==1){echo H($comment);} ?>

<?php if(!$this->config->item('hide_signature')) { ?>
<?php if ($signature_needed) {?>
			
<?php echo lang('sales_signature'); ?>: 

<?php 
if ($is_credit_card_sale)
{
	echo lang('sales_card_statement');
}
?><?php }?><?php } ?>
<?php  if ($return_policy) { echo wordwrap(H($return_policy),40);} ?></script>
<?php $this->load->view("partial/footer"); ?>
