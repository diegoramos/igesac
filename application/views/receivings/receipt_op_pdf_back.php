<?php
$company = ($company = $this->Location->get_info_for_key('company', isset($override_location_id) ? $override_location_id : FALSE)) ? $company : $this->config->item('company');
$company_logo = ($company_logo = $this->Location->get_info_for_key('company_logo', isset($override_location_id) ? $override_location_id : FALSE)) ? $company_logo : $this->config->item('company_logo');
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

</head>
<body>
	<center class="wrapper" style="width:100%;table-layout:fixed;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;" >
		<div class="webkit" style="max-width:600px;background-color:#FFFFFF;margin-top:30px;border-radius:6px;border-width:1px;border-style:solid;border-color:#DCE0E6;" >
			<!--[if (gte mso 9)|(IE)]>
			<table width="600" align="center" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
			<tr>
			<td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
			<![endif]-->
			<table class="outer" align="center" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;Margin:0 auto;width:100%;max-width:600px;" >
				<tr>
					<td class="one-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
						<table class="outer" align="center" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;Margin:0 auto;width:100%;max-width:600px;" >
							<tr>
								<td class="one-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
									<table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
										<tr>
											<td class="inner contents receipt-header" align="center" style="padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;width:100%;height:48px;background-color:#2196F3;color:#FFFFFF;border-top-left-radius:6px;border-top-right-radius:6px;text-align:center !important;" >
													<?php if (!isset($transfer_to_location)) {?>
												<?php echo $is_po ? lang('receivings_purchase_order') : H($receipt_title); ?> #<?php echo $is_po ? H($receiving_id_raw) : H($receiving_id); ?>
														<?php } else { 
															?>
															<?php echo lang('receivings_transfer_id')?> #<?php echo H($receiving_id_raw);?>
														<?php
														} ?>
												<br />
												<div id="sale_time"><?php echo H($transaction_time); ?></div>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="two-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;text-align:center;font-size:0;border-bottom-width:1px;border-bottom-style:solid;border-bottom-color:#DCE0E6;" >
									<!--[if (gte mso 9)|(IE)]>
									</td><td width="50%" valign="top" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
									<![endif]-->
									<div class="column border-left" style="border-left-width:1px;border-left-style:solid;border-left-color:#DCE0E6;width:100%; height:100%;max-width:299px;display:inline-block;vertical-align:top;" >
										<table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
											<tr>
												<td class="inner" style="padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;" >
													<table class="contents" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;width:100%;font-size:13px;text-align:left;" >
														<tr>
															<td class="text" style="padding-bottom:0;padding-right:0;padding-left:0;padding-top:0px;" >
																<?php		
																	if ($company_logo)
																	{
																		$this->load->helper('file');
																		$file = $this->Appfile->get($company_logo);
																		$base64_file_data = base64_encode($file->file_data);
																		$mime = get_mime_by_extension($file->file_name);
																	?>
																	<img style="width:30%;" src="data:<?php echo $mime ?>;base64,<?php echo $base64_file_data ?>" />
																<?php } ?>
															</td>
															<td>

															</td>
															<td>
																<b><?php echo H($company); ?></b>
																<br />
																<?php echo nl2br(H($this->Location->get_info_for_key('address',isset($override_location_id) ? $override_location_id : FALSE))); ?>
																<br />
																<?php echo H($this->Location->get_info_for_key('phone',isset($override_location_id) ? $override_location_id : FALSE)); ?>
							  			          				<?php if($this->config->item('website')) { ?>
																	<br />
																	<a href="<?php echo H(prep_url($this->config->item('website'))); ?>" class="primary-color" style="text-decoration:underline;color:#2196F3;"><?php echo H($this->config->item('website')); ?></a>
																<?php } ?>
																<br />
																<?php echo "<b>".lang('common_contact').":</b> ".H($employee); ?>
																<br>
																<?php echo "<b>".lang('common_email').":</b> ".H($email); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</div>
									<!--[if (gte mso 9)|(IE)]>
									</td>
									</tr>
									</table>
									<![endif]-->
								</td>
							</tr>
						</table>
						<!--[if (gte mso 9)|(IE)]>
						</td>
						</tr>
						</table>
						<![endif]-->
					</td>
				</tr>
				<tr>
					<td class="one-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
						<table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
							<tr>
								<td class="inner no-padding" style="padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;" >
									<table width="100%" class="items-table" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;padding-top:10px !important;" >
										<tr border="1">
											<?php
												$column_width = "75px";
												$total_columns = 6;
												
												if (!$this->config->item('hide_size_field'))
												{
													$total_columns++;
												}
											 ?>

											<th width="300px" style="background-color:#F5F5F5;height:32px;" ><?php echo lang('common_item'); ?></th>
											<?php
											if (!$this->config->item('hide_size_field'))
											{
											?>
											<th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;" ><?php echo lang('common_size'); ?></th>
											<?php
											}
											?>
											<th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;" ><?php echo lang('common_unit'); ?></th>
											<th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;" ><?php echo lang('common_price'); ?></th>
											<th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;" ><?php echo lang('common_quantity'); ?></th>
											<th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;" ><?php echo lang('common_discount_percent'); ?></th>
											<th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;" ><?php echo lang('common_total'); ?></th>
										</tr>
										
										<?php
										$number_of_items_sold = 0;
										$number_of_items_returned = 0;
											foreach(array_reverse($cart_items, true) as $line=>$item)
											{
							 				 if ($item->quantity > 0 && $item->name != lang('common_store_account_payment') && $item->name != lang('common_discount') && $item->name != lang('common_refund') && $item->name != lang('common_fee'))
							 				 {
							 			 		 $number_of_items_sold = $number_of_items_sold + $item->quantity;
							 				 }
							 				 elseif ($item->quantity < 0 && $item->name != lang('common_store_account_payment') && $item->name != lang('common_discount') && $item->name != lang('common_refund') && $item->name != lang('common_fee'))
							 				 {
							 			 		 $number_of_items_returned = $number_of_items_returned + abs($item->quantity);
							 				 }
												
												?>
												
												<?php
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
												

												<tr class="text-center item-row">
													<td style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
														<?php echo $item->name.($item->variation_name ? '- '.H($item->variation_name) : '' ); ?><?php if ($item_number_for_receipt){ ?> - <?php echo $item_number_for_receipt; ?><?php } ?>
													</td>
													
													<?php
													if (!$this->config->item('hide_size_field'))
													{
													?>
													<td align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
														<?php echo $item->size; ?>
													</td>
													<?php
													}
													?>
													<td align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
														<?php echo to_currency($item->unity,10); ?>
													</td>
													<td align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
														<?php echo to_currency($item->unit_price,10); ?>
													</td>
													<td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
														<?php echo to_quantity($item->quantity);?>
													</td>
													<td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
														<?php echo to_quantity($item->discount); ?>
													</td>
										
													<td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
														<?php echo to_currency($item->unit_price*$item->quantity-$item->unit_price*$item->quantity*$item->discount/100,10); ?>
													</td>
												</tr>
												
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
															<tr class="text-center item-row"><td colspan="6">
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
														</td></tr>
														<?php
														}
													}
												?>
												<?php
												}
												?>
												
										<?php } ?>

										<?php
										foreach($receiving_custom_fields_to_display as $custom_field_id)
										{
											if($this->Receiving->get_custom_field($custom_field_id) !== false && $this->Receiving->get_custom_field($custom_field_id) !== false)
											{											
													if ($cart->{"custom_field_${custom_field_id}_value"})
													{
													?>						
													<?php

													if ($this->Receiving->get_custom_field($custom_field_id,'type') == 'checkbox')
													{
														$format_function = 'boolean_as_string';
													}
													elseif($this->Receiving->get_custom_field($custom_field_id,'type') == 'date')
													{
														$format_function = 'date_as_display_date';				
													}
													elseif($this->Receiving->get_custom_field($custom_field_id,'type') == 'email')
													{
														$format_function = 'strsame';					
													}
													elseif($this->Receiving->get_custom_field($custom_field_id,'type') == 'url')
													{
														$format_function = 'strsame';					
													}
													elseif($this->Receiving->get_custom_field($custom_field_id,'type') == 'phone')
													{
														$format_function = 'strsame';					
													}
													else
													{
														$format_function = 'strsame';
													}
													?>
													
													<tr class="text-center item-row">
														<td colspan="1000" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
															<?php echo $this->Receiving->get_custom_field($custom_field_id,'name'); ?><br />
															<?php echo $format_function($cart->{"custom_field_${custom_field_id}_value"}); ?>
					
														</td>
													</tr>
													
													<?php
												}
											}
										}
										?>
										<tr class="text-center item-row">
											<td colspan="<?php echo $total_columns-1; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
												<?php echo lang('common_sub_total'); ?>
											</td>
											<td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
												<?php echo to_currency($subtotal); ?>
											</td>
										</tr>
									
										<?php foreach($taxes as $name=>$value) { ?>
											<tr class="text-center item-row">
												<td colspan="<?php echo $total_columns-1; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
													<?php echo $name; ?>:
												</td>
												<td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
													<?php echo to_currency($value); ?>
												</td>
											</tr>
										<?php }; ?>

										<tr class="text-center item-row">
											<td colspan="<?php echo $total_columns-1; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
												<b><?php echo lang('common_total'); ?></b>
											</td>
											<td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
												<b> <?php echo to_currency($total); ?></b>
											</td>
										</tr>

									  	<tr><td colspan="<?php echo $total_columns; ?>">&nbsp;</td></tr>

									    <?php
											$amount_due = 0;
											 
											foreach($payments as $payment_id=>$payment) { 
												
												if ($payment->payment_type == lang('common_store_account'))
												{
													$amount_due+=$payment->payment_amount;
												}
												
												?>
										<!-- <tr class="text-center item-row">
												<td colspan="<?php echo $total_columns-2; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
													<?php echo (isset($show_payment_times) && $show_payment_times) ?  date(get_date_format().' '.get_time_format(), strtotime($payment->payment_date)) : lang('common_payment'); ?>
												</td>

												<td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php $splitpayment=explode(':',$payment->payment_type); echo $splitpayment[0]; ?> </td>

												<td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
													<?php echo to_currency($payment->payment_amount); ?>
												</td>
											</tr> -->
										<?php } ?>
										
										<?php if (isset($supplier_balance_for_sale) && (double)$supplier_balance_for_sale && !$this->config->item('hide_store_account_balance_on_receipt')) {?>
											<td colspan="<?php echo $total_columns-1; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
												<b><?php echo lang('receivings_supplier_account_balance'); ?></b>
											</td>
											<td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
												<b><?php echo to_currency($supplier_balance_for_sale); ?></b>
											</td>											
										<?php
										}
										?>
										
										<?php
										if ($this->config->item('paypal_me')) 
										{ 					
											if (isset($amount_due) && $amount_due) 
											{
												$this->lang->load('reports');
											?>
												<tr class="text-center item-row">
													<td  colspan="<?php echo $total_columns; ?>"align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
														<h2><?php echo anchor('https://paypal.me/'.$this->config->item('paypal_me').'/'.to_currency_no_money($amount_due),lang('reports_pay_with_paypal'));?></h2>
													</td>
												</tr>
						
												<?php
											}
										}
					
										?>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			
				<?php if ($number_of_items_returned) { ?>
				
				<tr>
					<td class="one-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
						<table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
							<tr>
								<td class="inner contents" style="padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;width:100%;text-align:left;" >
									<p style="Margin:0;font-size:13px;Margin-bottom:10px;" >
										<?php 
										echo lang('common_items_returned').": ". to_quantity($number_of_items_returned); 
										?>
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				
				<?php } ?>
				
				<tr>
					<td class="one-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
						<table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
							<tr>
								<td class="inner contents" style="padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;width:100%;text-align:left;" >
									<p style="Margin:0;font-size:13px;Margin-bottom:10px;" >
										<?php
											if (empty($comment)) {
												echo lang('common_comments').": ". 'Ninguno';
											} else {
												echo lang('common_comments').": ". H($comment);
											}
										?>
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				
				</table>
			</td>
		</tr>
		</table>

		<table>
			<tr>
				<td class="one-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
					<table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
						<tr>
							<td class="inner contents" style="padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;width:100%;text-align:left;" >
								<p style="Margin:0;font-size:13px;Margin-bottom:10px;" >
									<?php echo nl2br($this->config->item('announcement_oc')) ?>
								</p>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="one-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
					<table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
						<tr>
							<td class="inner contents" style="padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;width:100%;text-align:left;" >
								<p style="Margin:0;font-size:13px;Margin-bottom:10px;color: red;" >
									<strong><?php echo nl2br(H($this->config->item('cancel_policy'))); ?></strong>
								</p>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

			<!--[if (gte mso 9)|(IE)]>
			</td>
			</tr>
			</table>
			<![endif]-->
		</div>
	</center>
</body>
</html>