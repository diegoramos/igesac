	<?php
		$this->load->helper('sale');
		$return_policy = ($loc_return_policy = $this->Location->get_info_for_key('return_policy', isset($override_location_id) ? $override_location_id : FALSE)) ? $loc_return_policy : $this->config->item('return_policy');
		$company = ($company = $this->Location->get_info_for_key('company', isset($override_location_id) ? $override_location_id : FALSE)) ? $company : $this->config->item('company');
		$website = ($website = $this->Location->get_info_for_key('website', isset($override_location_id) ? $override_location_id : FALSE)) ? $website : $this->config->item('website');
		$company_logo = ($company_logo = $this->Location->get_info_for_key('company_logo', isset($override_location_id) ? $override_location_id : FALSE)) ? $company_logo : $this->config->item('company_logo');
		$ruc_company = $this->config->item('ruc_company');
		$is_integrated_credit_sale = is_sale_integrated_cc_processing($cart);
		$is_sale_integrated_ebt_sale = is_sale_integrated_ebt_sale($cart);
		$is_credit_card_sale = is_credit_card_sale($cart);
		
		$signature_needed = $this->config->item('capture_sig_for_all_payments') || (($is_credit_card_sale && !$is_integrated_credit_sale) ||  is_store_account_sale($cart));
		$item_custom_fields_to_display = array();
		$sale_custom_fields_to_display = array();
		$item_kit_custom_fields_to_display = array();
		$customer_custom_fields_to_display = array();
		$employee_custom_fields_to_display = array();
		
		
	 for($k=1;$k<=NUMBER_OF_PEOPLE_CUSTOM_FIELDS;$k++) 
	 {
		 $item_custom_field = $this->Item->get_custom_field($k,'show_on_receipt');
		 $sale_custom_field = $this->Sale->get_custom_field($k,'show_on_receipt');
		 $item_kit_custom_field = $this->Item_kit->get_custom_field($k,'show_on_receipt');
		 $customer_custom_field = $this->Customer->get_custom_field($k,'show_on_receipt');
		 $employee_custom_field = $this->Employee->get_custom_field($k,'show_on_receipt');
		 
		 if ($item_custom_field)
		 {
		 	$item_custom_fields_to_display[] = $k;
		 }

		 if ($sale_custom_field)
		 {
		 	$sale_custom_fields_to_display[] = $k;
		 }
		 
		 if ($item_kit_custom_field)
		 {
	 	 	$item_kit_custom_fields_to_display[] = $k;
		 }
		 
		 if ($customer_custom_field)
		 {
	  	 	$customer_custom_fields_to_display[] = $k;
		 }
		 
		 if ($employee_custom_field)
		 {
		 		$employee_custom_fields_to_display[] = $k;
		 }
	 }
		
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

		// Code to get accounting id of current reciept
		$currentSaleAccoutingId = '';
		$sale_info = $this->Sale->get_info($sale_id_raw)->result_array();
		if (($sale_info) && ($sale_info['0'])) {
			$currentSaleAccoutingId = $sale_info['0']['accounting_id'];
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

<div class="wrapper">
  <!-- Main content -->
  <section class="invoice">
    <!-- Table row -->
    <div class="row">
      <div class="col-xs-12 table-responsive">
        <table width="100%" class="items-table" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;padding-top:5px !important;">
                <tr>
                  <td class="one-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
                    <table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:15px;">
                      <tr>
                        <td class="inner contents receipt-header" align="center" style="padding-top:0px;padding-bottom:0px;padding-right:10px;padding-left:10px;width:100%;height:48px;background-color:#2196F3;color:#FFFFFF;border-top-left-radius:6px;border-top-right-radius:6px;text-align:center !important;">
                          <strong><?php echo isset($sale_type) ? H($sale_type) : H($receipt_title); ?> #<?php echo H($sale_id); ?></strong>
                          <br/>
                          <?php if (isset($deleted) && $deleted) {?>
                                <span class="text-danger" style="color: #df6c6e;"><strong><?php echo lang('sales_deleted_voided'); ?></strong></span>
                          <br/>
                          <?php } ?>
                          <div id="sale_time"><?php echo H($transaction_time); ?></div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td class="three-column" style="padding-top:5px;padding-bottom:0;padding-right:0;padding-left:0;text-align:center;font-size:0;border-bottom-width:1px;border-bottom-style:solid;border-bottom-color:#DCE0E6;">
                    <table>
                      <tr>
                        <td>
                          <?php   
                            if ($company_logo)
                            {
                              $this->load->helper('file');
                              $file = $this->Appfile->get($company_logo);
                              $base64_file_data = base64_encode($file->file_data);
                              $mime = get_mime_by_extension($file->file_name);
                            ?>
                            <img style="width:28%;" src="data:<?php echo $mime ?>;base64,<?php echo $base64_file_data ?>" />
                          <?php } ?>
                        </td>
                        <td>
                          <?php
                            $homolog_url=base_url('assets/img/homologada.png');
                          ?>
                            <img style="width:28%;" src="<?php echo $homolog_url; ?>" />
                        </td>
                        <td>
                          <strong><?php echo H($company); ?></strong>
                          <br/>
                          <?php if ($ruc_company!='') { ?>
                          <strong>RUC : <?php echo $ruc_company; ?></strong>
                          <br />
                          <?php } ?>
                          <?php echo nl2br(H($this->Location->get_info_for_key('address',isset($override_location_id) ? $override_location_id : FALSE))); ?>
                          <br />
                            <?php if($this->config->item('website')) { ?>
                            <a href="<?php echo H(prep_url($this->config->item('website'))); ?>" class="primary-color" style="text-decoration:underline;color:#2196F3;"><?php echo H($this->config->item('website')); ?></a>
                          <?php } ?>
                          <br/>
                          <?php
                            $phone_all = H($this->Location->get_info_for_key('phone',isset($override_location_id) ? $override_location_id : FALSE));
                            $phone_num = explode("/", $phone_all);
                          ?>
                          <?php echo H($phone_num[0]).' / '; ?>
                          <?php echo $phone_number_employee; ?>
                          <br />
                          <?php 
                            if (isset($email_employee)) {
                              echo $email_employee;
                            } else {
                              echo H($this->Location->get_info_for_key('email',isset($override_location_id) ? $override_location_id : FALSE));
                            }
                          ?>
                          <br>
                          <?php echo "<b>".lang('common_contact').":</b> ".H($employee); ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td>
                    <table>
                      <tr>
                        <td>
                          <!--Customer-->
                          <div class="col-md-12 col-sm-12 col-xs-12">
                            <?php if(isset($customer)) { ?>
                              <?php if (!$this->config->item('remove_customer_name_from_receipt')) { ?>
                                <?php echo lang('common_customer').": ".H($customer); ?><br />
                              <?php } ?>
                              <?php if(!empty($customer_address_1) || !empty($customer_address_2)){ ?><?php echo lang('common_address'); ?> : <?php echo substr(H($customer_address_1. ' '.$customer_address_2),0,-7); ?><br /><?php } ?>
                              <?php if(!empty($customer_account)){ ?><?php echo lang('common_ruc_number'); ?> : <?php echo H($customer_account); ?><br /><?php } ?>
                              <?php if (!$this->config->item('remove_customer_company_from_receipt')) { ?>
                                <?php if(!empty($customer_company)) { ?><?php echo lang('common_company').": ".H($customer_company); ?><br /><?php } ?>
                              <?php } ?>
                                
                                <?php if (!$this->config->item('remove_customer_contact_info_from_receipt')) { ?>
                                  <?php if (!empty($customer_city)) { echo H($customer_city.' '.$customer_state.', '.$customer_zip).'<br/>';} ?>
                                  <?php if (!empty($customer_country)) { echo H($customer_country).'<br/>';} ?>     
                                  <?php if(!empty($customer_phone)){ ?><?php echo lang('common_phone_number'); ?> : <?php echo H($customer_phone); ?><br/><?php } ?>
                                  <?php if(!empty($customer_email)){ ?><?php echo lang('common_email'); ?> : <?php echo H($customer_email); ?><br/><?php } ?>
                                <?php } ?>
                                <?php
                                foreach($customer_custom_fields_to_display as $custom_field_id)
                                {
                                ?>
                                    <?php 
                                      $customer_info = $this->Customer->get_info($customer_id);
                                      
                                      if ($customer_info->{"custom_field_${custom_field_id}_value"})
                                      {
                                      ?>
                                    <div class="invoice-desc">
                                      <?php

                                      if ($this->Customer->get_custom_field($custom_field_id,'type') == 'checkbox')
                                      {
                                        $format_function = 'boolean_as_string';
                                      }
                                      elseif($this->Customer->get_custom_field($custom_field_id,'type') == 'date')
                                      {
                                        $format_function = 'date_as_display_date';        
                                      }
                                      elseif($this->Customer->get_custom_field($custom_field_id,'type') == 'email')
                                      {
                                        $format_function = 'strsame';         
                                      }
                                      elseif($this->Customer->get_custom_field($custom_field_id,'type') == 'url')
                                      {
                                        $format_function = 'strsame';         
                                      }
                                      elseif($this->Customer->get_custom_field($custom_field_id,'type') == 'phone')
                                      {
                                        $format_function = 'strsame';         
                                      }
                                      else
                                      {
                                        $format_function = 'strsame';
                                      }
                                      
                                      echo ($this->Customer->get_custom_field($custom_field_id,'hide_field_label') ? '' : $this->Customer->get_custom_field($custom_field_id,'name').':').' '.$format_function($customer_info->{"custom_field_${custom_field_id}_value"}).'<br/>';
                                      ?>
                                    </div>
                                    <?php
                                  }
                                }
                                ?>
                            <?php } ?>
                          </div>
                          <!-- delivery address-->
                          <div class="col-md-12 col-sm-12 col-xs-12">
                            <?php if(isset($delivery_person_info)) { ?>
                                <?php echo lang('deliveries_shipping_address');?>:<br/>
                                <?php echo lang('common_name').": ".H($delivery_person_info['first_name'].' '.$delivery_person_info['last_name']); ?><br/>
                                <?php if(!empty($delivery_person_info['address_1']) || !empty($delivery_person_info['address_2'])){ ?><?php echo lang('common_address'); ?> : <?php echo H($delivery_person_info['address_1']. ' '.$delivery_person_info['address_2']); ?><br/><?php } ?>
                                <?php if (!empty($delivery_person_info['city'])) { echo H($delivery_person_info['city'].' '.$delivery_person_info['state'].', '.$delivery_person_info['zip']).'<br/>';} ?>
                                <?php if (!empty($delivery_person_info['country'])) { echo H($delivery_person_info['country']).'<br/>';} ?>     
                                <?php if(!empty($delivery_person_info['phone'])){ ?><?php echo lang('common_phone_number'); ?> : <?php echo H($delivery_person_info['phone']); ?><br/><?php } ?>
                                <?php if(!empty($delivery_person_info['email'])){ ?><?php echo lang('common_email'); ?> : <?php echo H($delivery_person_info['email']); ?></br><?php } ?>
                            <?php } ?>
                            
                            <?php if(!empty($delivery_info['estimated_delivery_or_pickup_date']) || !empty($delivery_info['tracking_number']) ||  !empty($delivery_info['comment'])) {?>
                                <?php echo lang('deliveries_delivery_information');?>:<br/>
                                <?php if(!empty($delivery_info['estimated_delivery_or_pickup_date'])){ ?><?php echo lang('deliveries_estimated_delivery_or_pickup_date'); ?> : <?php echo date(get_date_format().' '.get_time_format(),strtotime($delivery_info['estimated_delivery_or_pickup_date'])); ?></br><?php } ?>
                                <?php if(!empty($delivery_info['tracking_number'])){ ?><?php echo lang('deliveries_tracking_number'); ?> : <?php echo H($delivery_info['tracking_number']); ?><br/><?php } ?>
                                <?php if(!empty($delivery_info['comment'])){ ?><?php echo lang('common_comment'); ?> : <?php echo H($delivery_info['comment']); ?><br/><?php } ?>
                            <?php } ?>
                          </div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
        </table>

        <table width="100%" class="items-table" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;padding-top:5px !important;">
          <tr>
            <?php
              $column_width = "100px";
              $total_columns = 6;
              if($discount_exists) { $column_width = "75px"; $total_columns = 7; } 
             ?>
            <th width="200px" style="background-color:#F5F5F5;height:32px;text-align: center; vertical-align: middle;" ><?php echo lang('common_item'); ?></th>
            <th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;text-align: center; vertical-align: middle;" ><?php echo lang('common_unit'); ?></th>
            <th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;text-align: center; vertical-align: middle;" ><?php echo lang('common_image'); ?></th>
            <th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;text-align: center; vertical-align: middle;" ><?php echo lang('common_price'); ?></th>
            <th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;text-align: center; vertical-align: middle;" ><?php echo lang('common_quantity'); ?></th>
            <?php if($discount_exists) { ?>
              <th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;text-align: center; vertical-align: middle;" ><?php echo lang('common_discount_percent'); ?></th>
            <?php } ?>

            <th width="<?php echo $column_width ?>" style="background-color:#F5F5F5;height:32px;text-align: center; vertical-align: middle;" ><?php echo lang('common_total'); ?></th>
          </tr>
          <?php
          if ($discount_item_line = $cart->get_index_for_flat_discount_item())
          {
            $discount_item = $cart->get_item($discount_item_line);
            $cart->delete_item($discount_item_line);
            $cart->add_item($discount_item,false);
            $cart_items = $cart->get_items();
          }
          
            foreach(array_reverse($cart_items, true) as $line=>$item)
            {
              
              if ($item->tax_included)
              {
                if (get_class($item) == 'PHPPOSCartItemSale')
                {
                  if ($item->tax_included)
                  {
                    $this->load->helper('items');
                    $unit_price = get_price_for_item_including_taxes($item->item_id, $item->unit_price);
                  }
                }
                else
                {
                  if ($item->tax_included)
                  {
                    $this->load->helper('item_kits');
                    $unit_price = get_price_for_item_kit_including_taxes($item->item_kit_id, $item->unit_price);
                  }
                }
              }
              else
              {
                $unit_price = $item->unit_price;
              }
              
              $item_number_for_receipt = false;
              
              if ($this->config->item('show_item_id_on_receipt'))
              {
                switch($this->config->item('id_to_show_on_sale_interface'))
                {
                  case 'number':
                  $item_number_for_receipt = $item->item_number;
                  break;
                
                  case 'product_id':
                  $item_number_for_receipt = $item->product_id;
                  break;
                
                  case 'id':
                  $item_number_for_receipt = $item->item_id;
                  break;
                
                  default:
                  $item_number_for_receipt = $item->item_number;
                  break;
                }
              }
            ?>
            <?php 
              $avatar_url=$item->image_id ?  app_file_url($item->image_id) : base_url('assets/assets/images/default.png');
            ?>
          <tr class="text-center item-row">
            <td style="padding-right:0;padding-top:0px !important;padding-left:0px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
              <?php echo H($item->name).((get_class($item) == 'PHPPOSCartItemSale' && $item->variation_name) ? '- '.H($item->variation_name) : '' ); ?><?php if ($item_number_for_receipt){ ?> - <?php echo H($item_number_for_receipt); ?><?php } ?><?php if (!$this->config->item('hide_desc_emailed_receipts') && $item->description){ ?> - <?php echo H($item->description); ?><?php } ?><?php if ($item->size){ ?> (<?php echo H($item->size); ?>)<?php } ?>
            </td>
            <td  align="center" style="padding-right:0;padding-top:0px !important;padding-left:0px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:0px;" >
              <?php echo $item->unity;?>
            </td>
            <td align="center" style="padding-right:0;padding-top:0px !important;padding-left:0px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:0px;" >
              <?php echo '<img src="'.$avatar_url.'" height="95" width="75">'; ?>
            </td>
            <td align="center" style="padding-right:0;padding-top:0px !important;padding-left:0px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:0px;" >
              <?php echo to_currency($unit_price,10); ?>
            </td>
            <td  align="center" style="padding-right:0;padding-top:0px !important;padding-left:0px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:0px;" >
              <?php echo to_quantity($item->quantity);?>
            </td>
            <?php if($discount_exists) { ?>
              <td  align="center" style="padding-right:0;padding-top:0px !important;padding-left:0px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:0px;" >
                <?php echo to_quantity($item->discount).'%'; ?>
              </td>
            <?php } ?>
          
            <td  align="center" style="padding-right:0;padding-top:0px !important;padding-left:0px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:0px;" >
              <?php echo to_currency($unit_price*$item->quantity-$item->unit_price*$item->quantity*$item->discount/100,10); ?>
            </td>
          </tr>

          <?php
          foreach($item_custom_fields_to_display as $custom_field_id)
          {
          ?>
              <?php 
              if(get_class($item) == 'PHPPOSCartItemSale' && $this->Item->get_custom_field($custom_field_id) !== false)
              {
                $item_info = $this->Item->get_info($item->item_id);
                
                if ($item_info->{"custom_field_${custom_field_id}_value"})
                {
                ?>
                <tr class="text-center item-row"><td colspan="5">
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
          }
            
          foreach($item_kit_custom_fields_to_display as $custom_field_id)
          {
            if(get_class($item) == 'PHPPOSCartItemKitSale' && $this->Item_kit->get_custom_field($custom_field_id) !== false && $this->Item_kit->get_custom_field($custom_field_id) !== false)
            {
                $item_info = $this->Item_kit->get_info($item->item_kit_id);
                
                if ($item_info->{"custom_field_${custom_field_id}_value"})
                {
                ?>
                <tr class="text-center item-row"><td colspan="5">
                <?php

                if ($this->Item_kit->get_custom_field($custom_field_id,'type') == 'checkbox')
                {
                  $format_function = 'boolean_as_string';
                }
                elseif($this->Item_kit->get_custom_field($custom_field_id,'type') == 'date')
                {
                  $format_function = 'date_as_display_date';        
                }
                elseif($this->Item_kit->get_custom_field($custom_field_id,'type') == 'email')
                {
                  $format_function = 'strsame';         
                }
                elseif($this->Item_kit->get_custom_field($custom_field_id,'type') == 'url')
                {
                  $format_function = 'strsame';         
                }
                elseif($this->Item_kit->get_custom_field($custom_field_id,'type') == 'phone')
                {
                  $format_function = 'strsame';         
                }
                else
                {
                  $format_function = 'strsame';
                }
                
                echo ($this->Item_kit->get_custom_field($custom_field_id,'hide_field_label') ? '' : $this->Item_kit->get_custom_field($custom_field_id,'name').':').' '.$format_function($item_info->{"custom_field_${custom_field_id}_value"});
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

          <?php if ($exchange_name) { ?>
          
          <tr class="text-center item-row">
            <td colspan="<?php echo $total_columns-1; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
              <?php echo lang('common_exchange_to').' '.H($exchange_name); ?>
            </td>
            <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
              x <?php echo to_currency_no_money(1/$exchange_rate); ?>
            </td>
          </tr>
          
          <?php } ?>

          <tr class="text-center item-row">
            <td colspan="<?php echo $total_columns-1; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
              <?php echo lang('common_sub_total'); ?>
            </td>
            <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
                <?php if (isset($exchange_name) && $exchange_name) { 
                  echo to_currency_as_exchange($cart,$subtotal);
                ?>
                <?php } else {  ?>
                <?php echo to_currency($subtotal); ?>       
                <?php
                }
                ?>
            </td>
          </tr>

          <?php foreach($taxes as $name=>$value) { ?>
            <tr class="text-center item-row">
              <td colspan="<?php echo $total_columns-1; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
                <?php echo $name; ?>:
              </td>
              <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
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

          <tr class="text-center item-row">
            <td colspan="<?php echo $total_columns-1; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
              <b><?php echo lang('common_total'); ?></b>
            </td>
            <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
              <b>
              <?php if (isset($exchange_name) && $exchange_name) { 
                ?>
                <?php echo $this->config->item('round_cash_on_sales') && $is_sale_cash_payment ?  to_currency_as_exchange($cart,round_to_nearest_05($total)) : to_currency_as_exchange($cart,$total); ?>        
              <?php } else {  ?>
              <?php echo $this->config->item('round_cash_on_sales') && $is_sale_cash_payment ?  to_currency(round_to_nearest_05($total)) : to_currency($total); ?>        
              <?php
              }
              ?>
              </b>
            </td>
          </tr>
          
          <?php foreach($payments as $payment) {?>
            <?php if (strpos($payment->payment_type, lang('common_giftcard'))=== 0) {?>
              <?php $giftcard_payment_row = explode(':', $payment->payment_type); ?>
              <td colspan="<?php echo $total_columns-2; ?>" class=" padding-right" align="right" style="padding-right:10px;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo lang('sales_giftcard_balance'); ?></td>                       
              <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo H($payment->payment_type);?></td>                       
              <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo to_currency($this->Giftcard->get_giftcard_value(end($giftcard_payment_row))); ?></td>                        
            <?php }?>
          <?php }?> 
          
          <?php if (isset($customer_balance_for_sale) && (float)$customer_balance_for_sale && !$this->config->item('hide_store_account_balance_on_receipt')) { ?>
            <tr>
              <td  colspan="<?php echo $total_columns-1; ?>"   align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo lang('sales_customer_account_balance'); ?></td>
              <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
              <?php echo to_currency($customer_balance_for_sale); ?> </td>
            </tr>
          <?php } ?>
          
          <?php if (!$disable_loyalty && $this->config->item('enable_customer_loyalty_system') && isset($sales_until_discount) && !$this->config->item('hide_sales_to_discount_on_receipt') && $this->config->item('loyalty_option') == 'simple') {?>
            <tr>
              <td  colspan="<?php echo $total_columns-1; ?>"   align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo lang('common_sales_until_discount'); ?></td>
              <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
              <?php echo $sales_until_discount <= 0 ? lang('sales_redeem_discount_for_next_sale') : to_quantity($sales_until_discount); ?> </td>
            </tr>
          <?php
          }
          ?>
          
          <?php if (!$disable_loyalty && $this->config->item('enable_customer_loyalty_system') && isset($customer_points) && !$this->config->item('hide_points_on_receipt') && $this->config->item('loyalty_option') == 'advanced') {?>
            <tr>
              <td  colspan="<?php echo $total_columns-1; ?>"   align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo lang('common_points'); ?></td>
              <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" >
              <?php echo to_currency_no_money($customer_points); ?> </td>
            </tr>
          <?php
          }
          ?>
          
          <?php if (isset($ref_no) && $ref_no) { ?>
            <tr>
              <td  colspan="<?php echo $total_columns-1; ?>"   align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo lang('sales_ref_no'); ?></td>
              <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo H($ref_no); ?></td>
            </tr> 
          <?php } ?>
          
          <?php if (isset($auth_code) && $auth_code) { ?>
            <tr>
              <td  colspan="<?php echo $total_columns-1; ?>"   align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo lang('sales_auth_code'); ?></td>
              <td  align="center" style="padding-right:0;padding-top:10px !important;padding-left:10px;border-width:1px;border-style:solid;border-color:#DCE0E6;border-bottom-width:0px;border-right-width:0px;padding-bottom:10px;" ><?php echo H($auth_code); ?></td>
            </tr> 
          <?php } ?>
        </table>
        <table>
          <tr>
            <td class="one-column" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0;" >
              <table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
                <tr>
                  <td class="inner contents" style="padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;width:100%;text-align:left;" >
                    <p style="Margin:0;font-size:13px;Margin-bottom:10px;" >
                      <?php 
                        if(isset($show_comment_on_receipt) && $show_comment_on_receipt == 1)
                        { 
                          echo lang('common_comments').": ". H($comment); 
                        } 
                      ?>
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
                  <td class="inner contents" style="padding-top:10px;padding-bottom:0px;padding-right:10px;padding-left:10px;width:100%;text-align:left;" >
                    <?php
                    foreach($sale_custom_fields_to_display as $custom_field_id)
                    {
                      if($this->Sale->get_custom_field($custom_field_id) !== false && $this->Sale->get_custom_field($custom_field_id) !== false)
                      {
                          if ($cart->{"custom_field_${custom_field_id}_value"})
                          {
                          ?>
                          <?php
                          if ($this->Sale->get_custom_field($custom_field_id,'type') == 'checkbox')
                          {
                            $format_function = 'boolean_as_string';
                          }
                          elseif($this->Sale->get_custom_field($custom_field_id,'type') == 'date')
                          {
                            $format_function = 'date_as_display_date';        
                          }
                          elseif($this->Sale->get_custom_field($custom_field_id,'type') == 'email')
                          {
                            $format_function = 'strsame';         
                          }
                          elseif($this->Sale->get_custom_field($custom_field_id,'type') == 'url')
                          {
                            $format_function = 'strsame';         
                          }
                          elseif($this->Sale->get_custom_field($custom_field_id,'type') == 'phone')
                          {
                            $format_function = 'strsame';         
                          }
                          else
                          {
                            $format_function = 'strsame';
                          }
                          ?>
                            <?php echo $this->Sale->get_custom_field($custom_field_id,'name'); ?>:
                            <?php echo $format_function($cart->{"custom_field_${custom_field_id}_value"}); ?>
                          <?php
                        }
                      }
                    }
                    ?>
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
                    <p style="Margin:0;font-size:13px;Margin-bottom:10px;" >
                      <?php echo nl2br($this->config->item('announcement_special')) ?>
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
                      <?php echo nl2br(H($this->config->item('return_policy'))); ?>
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
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
        <table width="100%" style="border-spacing:0;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#555555;font-size:13px;" >
          <tr>
            <td style="text-align: center;">
              <img style="width:13%;" src="<?php echo base_url('assets/img/msa.png'); ?>" />
            </td>
            <td style="text-align: center;">
              <img style="width:13%;" src="<?php echo base_url('assets/img/kimberly.png'); ?>" />
            </td>
            <td style="text-align: center;">
              <img style="width:13%;" src="<?php echo base_url('assets/img/3m.png'); ?>" />
            </td>
            <td style="text-align: center;">
              <img style="width:13%;" src="<?php echo base_url('assets/img/regeltex.jpg'); ?>" />
            </td>
            <td style="text-align: center;">
              <img style="width:13%;" src="<?php echo base_url('assets/img/dupont.png'); ?>" />
            </td>
            <td style="text-align: center;">
              <img style="width:13%;" src="<?php echo base_url('assets/img/ansell.png'); ?>" />
            </td>
            <td style="text-align: center;">
              <img style="width:13%;" src="<?php echo base_url('assets/img/steelpro.jpg'); ?>" />
            </td>
          </tr>
        </table>
      </div>
      <!-- /.col -->
    </div>
  </section>
  <!-- /.content -->
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
		$("#duplicate_receipt_holder #signature_holder").addClass('hidden');
		$("#duplicate_receipt_holder .receipt_type_label").text(<?php echo json_encode(lang('sales_duplicate_receipt')); ?>);
		$(".receipt_type_label").show();		
		$(".receipt_type_label").addClass('show_receipt_labels');
	}
	else
	{
		$("#duplicate_receipt_holder").empty();
		$("#duplicate_receipt_holder").removeClass('visible-print-block');
		$("#duplicate_receipt_holder #signature_holder").removeClass('hidden');
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

<script>
html2canvas(document.querySelector("#receipt_wrapper"),{windowWidth: 280}).then(canvas => {
	document.getElementById("print_image_output").innerHTML = canvas.toDataURL();
});
</script>
<script type="text/print-image" id="print_image_output"></script>
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

---------------------------------------
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
<?php if (strpos($payment->payment_type, lang('common_giftcard'))=== 0) {?><?php echo lang('sales_giftcard_balance'); ?>  <?php echo $payment->payment_type;?>: <?php echo str_replace('<span style="white-space:nowrap;">-</span>', '-', to_currency($this->Giftcard->get_giftcard_value(end($giftcard_payment_row)))); ?>
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
------------------------------------------------
<?php 
if ($is_credit_card_sale)
{
	echo lang('sales_card_statement');
}
?><?php }?><?php } ?>
<?php  if ($return_policy) { echo wordwrap(H($return_policy),40);} ?></script>
</body>
</html>