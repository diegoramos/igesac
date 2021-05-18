<?php
require_once APPPATH.'libraries/qb/src/config.php';
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\Facades\Customer as QBCustomer;
use QuickBooksOnline\API\Facades\Vendor as QBVendor;
use QuickBooksOnline\API\Facades\Employee as QBEmployee;
use QuickBooksOnline\API\Facades\Item as QBItem;
use QuickBooksOnline\API\Facades\Account as QBAccount;
use QuickBooksOnline\API\Facades\SalesReceipt as QBSalesReceipt;
use QuickBooksOnline\API\Facades\RefundReceipt as QBRefundReceipt;
use QuickBooksOnline\API\Facades\Invoice as QBInvoice;
use QuickBooksOnline\API\Facades\Payment as QBPayment;
use QuickBooksOnline\API\Facades\Purchase as QBPurchase;
use QuickBooksOnline\API\Facades\PurchaseOrder as QBPurchaseOrder;
use QuickBooksOnline\API\Facades\Bill as QBBill;
use QuickBooksOnline\API\Facades\JournalEntry as QBJournalEntry;
use QuickBooksOnline\API\Data\IPPPaymentMethod;
class Quickbooks extends MY_Controller 
{	
  
	public $log_text = '';
	
		function __construct()
		{
			ini_set('memory_limit','1024M');
			parent::__construct();
			$this->lang->load('config');
			
			if (!is_cli())//Running from web should have store config permissions
			{	
				$this->load->model('Employee');
				$this->load->model('Location');
				if(!$this->Employee->is_logged_in())
				{
					redirect('login?continue='.rawurlencode(uri_string().'?'.$_SERVER['QUERY_STRING']));
				}
		
				if(!$this->Employee->has_module_permission('config',$this->Employee->get_logged_in_employee_info()->person_id))
				{
					redirect('no_access/config');
				}
			}			
		}
		
		public function cancel()
		{
			$this->load->model('Appconfig');
			$this->Appconfig->save('kill_qb_cron',1);
			$this->Appconfig->save('qb_cron_running',0);
			$this->Appconfig->save('qb_sync_percent_complete',100);
		}
		
			
		function manual_sync()
		{
			$this->cron();
		}
		
		private function _get_data_service($authed =TRUE)
		{
			// Moved this code to a common helper qb_helper.php, so that we can use it in different controllers
			$this->load->helper('qb');
			$dataService = _get_data_service();
			return $dataService;
		}
		
		
		function refresh_tokens($redirect_to_store_config = 0)
		{
			// Moved this code to a common helper qb_helper.php, so that we can use it in different controllers
			$this->load->helper('qb');
			return refresh_tokens($redirect_to_store_config);
		}
		
		function initial_auth()
		{
			$search = rawurlencode(lang('common_quickbooks'));
			try
			{
				$dataService = $this->_get_data_service(FALSE);
				$OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
				$accessTokenObj = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($this->input->get("code"), $this->input->get("realmId"));
				$accessTokenValue = $accessTokenObj->getAccessToken();
				$refreshTokenValue = $accessTokenObj->getRefreshToken();
				$this->Appconfig->save('quickbooks_access_token',$accessTokenValue);
				$this->Appconfig->save('quickbooks_refresh_token',$refreshTokenValue);
				$this->Appconfig->save('quickbooks_realm_id',$this->input->get('realmId'));
				redirect("config?search=$search");
			}
			catch(Exception $e)
			{
				redirect("config?search=$search");
			}
		}
		
		function oauth()
		{
			$this->load->helper('qb');
			$dataService = _get_data_service(FALSE);
			$OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
			$authorizationCodeUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();
			redirect($authorizationCodeUrl);
		}
		
		/*
		This function is used to sync the PHPPOS items with online quickbooks
		*/
		//$db_override is NOT used at all; but in database.php to select database based on CLI args for cron in cloud
      public function cron($base_url='', $db_override = '')
      {
				ignore_user_abort(TRUE);
				set_time_limit(0);
				ini_set('max_input_time','-1');
				session_write_close();
				
				//Cron's always run on current server path; but if we are between migrations we should run the cron on the previous folder passing along any arguements
				if (defined('SHOULD_BE_ON_OLD') && SHOULD_BE_ON_OLD)
				{
					global $argc, $argv;
					$prev_folder = isset($_SERVER['CI_PREV_FOLDER']) ?  $_SERVER['CI_PREV_FOLDER'] : 'PHP-Point-Of-Sale-Prev';
					system('php '.FCPATH."$prev_folder/index.php quickbooks cron ".$argv[3].$prev_folder.'/ '.$argv[4]);
					exit();
				}
				
				$this->load->helper('demo');
				if (is_on_demo_host())
				{
					echo json_encode(array('success' => FALSE, 'message' => lang('common_disabled_on_demo')));
					die();
				}
				try
				{	
					
					$this->Appconfig->save('kill_qb_cron',0);
					
					if ($this->Appconfig->get_raw_qb_cron_running())
					{
						echo json_encode(array('success' => FALSE, 'message' => lang('common_qb_running')));
						die();
					}
				
					$this->load->model('Location');
					if ($timezone = ($this->Location->get_info_for_key('timezone', 1)))
					{
						date_default_timezone_set($timezone);
					}

					$this->Appconfig->save('qb_cron_running',1);
					$this->Appconfig->save('qb_sync_percent_complete',0);
					
					$qb_sync_operations = unserialize($this->config->item('qb_sync_operations'));
					$valid = array('sync_inventory_changes_qb','import_quickbooks_items_into_phppos','import_customers_into_phppos','import_suppliers_into_phppos','import_employees_into_phppos','export_phppos_items_to_quickbooks','export_customers_to_quickbooks','export_suppliers_to_quickbooks','export_employees_to_quickbooks','export_sales_to_quickbooks','export_receivings_to_quickbooks', 'export_expenses_to_quickbooks');
					
					$numsteps = count($qb_sync_operations);
					$stepsCompleted = 0;
					foreach($qb_sync_operations as $operation)
					{
						if (is_cli())
						{
							echo "START $operation\n";
						}
						
						if(in_array($operation, $valid))
						{
							// Refresh Tokens if is near to expire (that is before 30 minutes)
							$result_refresh_token = $this->refresh_tokens();
							if ($result_refresh_token) {
								$dataService = $this->_get_data_service();
							}
							$percent = floor(($stepsCompleted/$numsteps)*100);
							$message = lang("config_".$operation);
							$this->_update_sync_progress($percent, $message);
							$operation_method = '_'.$operation;
							$this->$operation_method();
							$stepsCompleted ++;
						}
						
						if (is_cli())
						{
							echo "DONE $operation\n";
						}
						
						$this->_kill_if_needed();
					}
					
					$percent = floor(($stepsCompleted/$numsteps)*100);
					$message = lang("config_".$operation);
					$this->_update_sync_progress($percent, $message);
					
					$this->load->model('Appconfig');
					$sync_date = date('Y-m-d H:i:s');
					$this->Appconfig->save('last_qb_sync_date', $sync_date);
					if (is_cli())
					{
						echo "\n\n***************************DONE***********************\n";
					}
					
					$this->_save_log();
					$this->Appconfig->save('qb_cron_running',0);				
					$this->Appconfig->save('qb_sync_percent_complete',100);				
					
					echo json_encode(array('success' => TRUE, 'date' =>$sync_date));
					
					
	      }
				catch(Exception $e)
				{
					if (is_cli())
					{
						echo "*******EXCEPTION 1: ".var_export($e->getMessage(),TRUE);
					}
					$this->Appconfig->save('qb_cron_running',0);				
				}
			}
			
			
			function _get_item_quantity($item_id)
			{
				$this->load->model('Item_location');
				return $this->Item_location->get_location_quantity($item_id, 1);
			}
			
			
			function _sync_inventory_changes_qb()
			{
				$this->_log(lang("config_sync_inventory_changes_qb"));
				
				$this->load->model('Item');
				$this->load->model('Item_location');
				$dataService = $this->_get_data_service();

				/*
				* get the list of inventory adjustments to be synced with QBO 
				*/
				$inventory_to_sync = $this->Item->get_inventory_items_to_sync();

				if ($inventory_to_sync && is_array($inventory_to_sync) && (count($inventory_to_sync) > 0)) {
					foreach ($inventory_to_sync as $inventory_item_data) {
						$item_id = $inventory_item_data['trans_items'];
						$item_quantity_diff = $inventory_item_data['trans_inventory'];
						$item_name = $inventory_item_data['name'];
						$item_accounting_id = $inventory_item_data['accounting_id'];

						if(!empty($item_accounting_id)){
							$entities = $dataService->FindById('Item', $item_accounting_id);
							$error = $dataService->getLastError();
							if (!$error) 
							{
								// get the quantity from the QBO of particular item
								$item_quantity_on_qb = $entities->QtyOnHand;

								// get the latest quantity by doing + /- adjustments in QBO quantity
								$total_Quantity = $item_quantity_on_qb + $item_quantity_diff;
								$updateItem = QBItem::update($entities, array('QtyOnHand' => $total_Quantity));
								$item_response = $dataService->Update($updateItem);
								$error = $dataService->getLastError();
							
								if (!$error)
								{
									$this->_log(lang('common_updated').' '.$item_name);

									// Update all transaction inventory_sync_needed 1 to 0 for all the location in database of cuurent item
									$this->Item->update_inventory_trans($item_id);
								}
								else 
								{
									$xml = simplexml_load_string($error->getResponseBody());
									$error_message = (string)$xml->Fault->Error->Detail;
									$this->_log("*******".lang('common_EXCEPTION').": ".$item_name.' '.$error_message);
								}
							}
							else {
								$xml = simplexml_load_string($error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$item_name.' '.$error_message);
							}
						} else {
							$this->_log("*******".lang('common_EXCEPTION').": ".lang('common_EXCEPTION_AT_SYNC_WHEN_ACCOUNTING_ID_NULL')." ".$item_name);
						}
					}
				}
			}
			
			function _import_quickbooks_items_into_phppos()
			{
				$this->_log(lang("config_import_quickbooks_items_into_phppos"));
				
				$this->load->model('Item');
				$this->load->model('Item_location');
				$dataService = $this->_get_data_service();
				
				// Iterate through all Items, even if it takes multiple pages
				$offset = 0;
				$per_page = 100;
				
				while (1) 
				{
					sleep(1);
					$this->_kill_if_needed();
					// in case we have huge number of accounts e.g 4000+, then there is a possibility that the token may expire. To overcome that situation we will check the token validity in each call and refresh it before 30 minutes of expiration
					$result_refresh_token = $this->refresh_tokens();
					if ($result_refresh_token) {
						$dataService = $this->_get_data_service();
					}
				    $allItems = $dataService->FindAll('Item', $offset, $per_page);
						$offset+=$per_page;
				    $error = $dataService->getLastError();
				    if ($error) 
						{
								$last_error = $dataService->getLastError();
								$xml = simplexml_load_string($last_error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$error_message);
				    }
						
				    if (!$allItems || (0==count($allItems))) 
						{
				        break;
				    }
						
				    foreach ($allItems as $oneItem) 
						{
							$accounting_id = $oneItem->Id;
							if(!$this->Item->is_item_in_quickbooks($accounting_id) && $oneItem->Type!='Category') //We don't want to import categories as items
							{
								$item_data = array(
									  'accounting_id' => $oneItem->Id,
										'name'=>$oneItem->Name ? $oneItem->Name : '',
										'description'=>$oneItem->Description ? $oneItem->Description : '',
										'long_description'=>$oneItem->Description ? $oneItem->Description : '',
										'deleted' => $oneItem->Active ? 0 : 1,
										'override_default_tax' => $oneItem->Taxable ? false : true,//If we are not taxable we will override tax so it doesn't have any by default
										'is_service' => $oneItem->Type =="Service" ? 1 : 0,
										'cost_price' => $oneItem->PurchaseCost ? $oneItem->PurchaseCost : 0,
										'unit_price' => $oneItem->UnitPrice ? $oneItem->UnitPrice : 0,
										'item_number' =>$oneItem->Sku,
										'tax_included' => $oneItem->SalesTaxIncluded ? 1 : 0,
										'reorder_level' => $oneItem->ReorderPoint,
									);
									
									$fqn = $oneItem->FullyQualifiedName;
									$convert_to_phppos_format = str_replace(':','|',$fqn);
									$category_path = substr($convert_to_phppos_format,0,strrpos($convert_to_phppos_format,'|'.$oneItem->Name));
									if ($category_path)
									{
										$this->load->model('Category');
										$categories_indexed_by_name = $this->Category->get_all_categories_and_sub_categories_as_indexed_by_name_key(false);
										$this->Category->create_categories_as_needed($category_path,$categories_indexed_by_name);
										$item_data['category_id'] = $categories_indexed_by_name[strtoupper($category_path)];
									}
									
								$this->Item->save($item_data);
								$item_id = $item_data['item_id'];
								
								if ($oneItem->TrackQtyOnHand)
								{
									$item_location_data = array(
									'quantity' => $oneItem->QtyOnHand,
									'accounting_product_quantity' => $oneItem->QtyOnHand,
									);
									
									$this->Item_location->save($item_location_data, $item_id, 1);
								}
								
								$this->_log(lang('common_added').' '.$item_data['name']);
							}
						}
				}
				
				//Link up qb cats to php pos cats
				$qb_cats = $this->_get_all_categories_and_sub_categories_in_qb();
				$this->load->model('Category');
	
				//This category list is in order by name and in hierarchy order
				$phppos_cats = $this->Category->get_all_categories_and_sub_categories_as_indexed_by_category_id(FALSE);
				foreach($phppos_cats as $phppos_category_id => $phppos_category_path)
				{
					if(isset($qb_cats[$phppos_category_path]))
					{
						$qb_category_id = $qb_cats[$phppos_category_path];
						$this->Category->link_qb_category($phppos_category_id, $qb_category_id);
					}
				}
			}
			
			function _import_customers_into_phppos()
			{
				$this->_log(lang("config_import_customers_into_phppos"));
				$this->load->model('Customer');
				$dataService = $this->_get_data_service();
				
				// Iterate through all Customers, even if it takes multiple pages
				$offset = 0;
				$per_page = 100;
				
				while (1) 
				{
					sleep(1);
					$this->_kill_if_needed();
					$result_refresh_token = $this->refresh_tokens();
					if ($result_refresh_token) {
						$dataService = $this->_get_data_service();
					}

				    $allCustomers = $dataService->FindAll('Customer', $offset, $per_page);
						$offset+=$per_page;
				    $error = $dataService->getLastError();
				    if ($error) 
						{
								$last_error = $dataService->getLastError();
								$xml = simplexml_load_string($last_error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$error_message);
				    }
						
				    if (!$allCustomers || (0==count($allCustomers))) 
						{
				        break;
				    }
						
				    foreach ($allCustomers as $oneCustomer) 
						{
							
							$accounting_id = $oneCustomer->Id;
							if(!$this->Customer->is_customer_in_quickbooks($accounting_id))
							{
								$person_data = array(
								'first_name'=>$oneCustomer->GivenName ? $oneCustomer->GivenName : '',
								'last_name'=>$oneCustomer->FamilyName ? $oneCustomer->FamilyName : '',
								'email'=>$oneCustomer->PrimaryEmailAddr && $oneCustomer->PrimaryEmailAddr->Address ? $oneCustomer->PrimaryEmailAddr->Address : '',
								'phone_number'=>$oneCustomer->PrimaryPhone && $oneCustomer->PrimaryPhone->FreeFormNumber ? $oneCustomer->PrimaryPhone->FreeFormNumber : '',
								'address_1'=>$oneCustomer->BillAddr && $oneCustomer->BillAddr->Line1 ? $oneCustomer->BillAddr->Line1 : '',
								'address_2'=>$oneCustomer->BillAddr && $oneCustomer->BillAddr->Line2 ? $oneCustomer->BillAddr->Line2 : '',
								'state'=>$oneCustomer->BillAddr && $oneCustomer->BillAddr->CountrySubDivisionCode ? $oneCustomer->BillAddr->CountrySubDivisionCode : '',
								'zip'=>$oneCustomer->BillAddr && $oneCustomer->BillAddr->PostalCode ? $oneCustomer->BillAddr->PostalCode : '',
								'comments'=>$oneCustomer->Notes ? $oneCustomer->Notes : '',
								);


								$customer_data=array(
								'accounting_id' => $accounting_id,
								'balance' => $oneCustomer->Balance ? $oneCustomer->Balance : 0,
								'company_name' => $oneCustomer->CompanyName ? $oneCustomer->CompanyName : '',
								'taxable'=>$oneCustomer->Taxable ?  1 : 0,
								'deleted' => $oneCustomer->Active ? 0 : 1,
								);
								$this->Customer->save_customer($person_data,$customer_data);
								$this->_log(lang('common_added').' '.$person_data['first_name'].' '.$person_data['last_name']);
							}
						}
				}		
			}
			
			function _import_suppliers_into_phppos()
			{
				$this->_log(lang("config_import_suppliers_into_phppos"));
				
				$this->load->model('Supplier');
				$dataService = $this->_get_data_service();
				
				// Iterate through all Suppliers, even if it takes multiple pages
				$offset = 0;
				$per_page = 100;
				
				while (1) 
				{
					sleep(1);
					$this->_kill_if_needed();
					$result_refresh_token = $this->refresh_tokens();
					if ($result_refresh_token) {
						$dataService = $this->_get_data_service();
					}
					
				    $allSuppliers = $dataService->FindAll('Vendor', $offset, $per_page);
						$offset+=$per_page;
				    $error = $dataService->getLastError();
				    if ($error) 
						{
								$last_error = $dataService->getLastError();
								$xml = simplexml_load_string($last_error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$error_message);
				    }
						
				    if (!$allSuppliers || (0==count($allSuppliers))) 
						{
				        break;
				    }
						
				    foreach ($allSuppliers as $oneSupplier) 
						{							
							$accounting_id = $oneSupplier->Id;
							if(!$this->Supplier->is_Supplier_in_quickbooks($accounting_id))
							{
								$person_data = array(
								'first_name'=>$oneSupplier->GivenName ? $oneSupplier->GivenName : '',
								'last_name'=>$oneSupplier->FamilyName ? $oneSupplier->FamilyName : '',
								'email'=>$oneSupplier->PrimaryEmailAddr && $oneSupplier->PrimaryEmailAddr->Address ? $oneSupplier->PrimaryEmailAddr->Address : '',
								'phone_number'=>$oneSupplier->PrimaryPhone && $oneSupplier->PrimaryPhone->FreeFormNumber ? $oneSupplier->PrimaryPhone->FreeFormNumber : '',
								'address_1'=>$oneSupplier->BillAddr && $oneSupplier->BillAddr->Line1 ? $oneSupplier->BillAddr->Line1 : '',
								'address_2'=>$oneSupplier->BillAddr && $oneSupplier->BillAddr->Line2 ? $oneSupplier->BillAddr->Line2 : '',
								'state'=>$oneSupplier->BillAddr && $oneSupplier->BillAddr->CountrySubDivisionCode ? $oneSupplier->BillAddr->CountrySubDivisionCode : '',
								'zip'=>$oneSupplier->BillAddr && $oneSupplier->BillAddr->PostalCode ? $oneSupplier->BillAddr->PostalCode : '',
								'comments'=>$oneSupplier->Notes ? $oneSupplier->Notes : '',
								);


								$supplier_data=array(
								'accounting_id' => $accounting_id,
								'company_name' => $oneSupplier->CompanyName ? $oneSupplier->CompanyName : '',
								'deleted' => $oneSupplier->Active ? 0 : 1,
								);
								$this->Supplier->save_supplier($person_data,$supplier_data);
								$this->_log(lang('common_added').' '.$supplier_data['company_name'].' - '.$person_data['first_name'].' '.$person_data['last_name']);
							}
						}
				}		
				
			}
		
			function _import_employees_into_phppos()
			{
				$this->_log(lang("config_import_employees_into_phppos"));
				
				$this->load->model('Employee');
				$dataService = $this->_get_data_service();
				
				// Iterate through all Employees, even if it takes multiple pages
				$offset = 0;
				$per_page = 100;
				
				while (1) 
				{
					sleep(1);
					$this->_kill_if_needed();
					$result_refresh_token = $this->refresh_tokens();
					if ($result_refresh_token) {
						$dataService = $this->_get_data_service();
					}
					
				    $allEmployees = $dataService->FindAll('Employee', $offset, $per_page);
						$offset+=$per_page;
				    $error = $dataService->getLastError();
				    if ($error) 
						{
								$last_error = $dataService->getLastError();
								$xml = simplexml_load_string($last_error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$error_message);
				    }
						
				    if (!$allEmployees || (0==count($allEmployees))) 
						{
				        break;
				    }
						
				    foreach ($allEmployees as $oneEmployee) 
						{							
							$accounting_id = $oneEmployee->Id;
							if(!$this->Employee->is_Employee_in_quickbooks($accounting_id))
							{
								$person_data = array(
								'first_name'=>$oneEmployee->GivenName ? $oneEmployee->GivenName : '',
								'last_name'=>$oneEmployee->FamilyName ? $oneEmployee->FamilyName : '',
								'email'=>$oneEmployee->PrimaryEmailAddr && $oneEmployee->PrimaryEmailAddr->Address ? $oneEmployee->PrimaryEmailAddr->Address : '',
								'phone_number'=>$oneEmployee->PrimaryPhone && $oneEmployee->PrimaryPhone->FreeFormNumber ? $oneEmployee->PrimaryPhone->FreeFormNumber : '',
								'address_1'=>$oneEmployee->PrimaryAddr && $oneEmployee->PrimaryAddr->Line1 ? $oneEmployee->PrimaryAddr->Line1 : '',
								'address_2'=>$oneEmployee->PrimaryAddr && $oneEmployee->PrimaryAddr->Line2 ? $oneEmployee->PrimaryAddr->Line2 : '',
								'state'=>$oneEmployee->PrimaryAddr && $oneEmployee->PrimaryAddr->CountrySubDivisionCode ? $oneEmployee->PrimaryAddr->CountrySubDivisionCode : '',
								'zip'=>$oneEmployee->PrimaryAddr && $oneEmployee->PrimaryAddr->PostalCode ? $oneEmployee->PrimaryAddr->PostalCode : '',
								);


								$employee_data=array(
								'accounting_id' => $accounting_id,
								'deleted' => $oneEmployee->Active ? 0 : 1,
								);
								$empty = array();
								
								$this->Employee->save_employee($person_data,$employee_data,$empty,$empty,$empty);
								$this->_log(lang('common_added').' '.$person_data['first_name'].' '.$person_data['last_name']);
							}
						}
				}		
				
			}
			
			private function _build_paths($tree, $path = '') 
			{
			    $result = array();
			    foreach ($tree as $id => $cat) 
					{
			        $result[$id] = $path . $cat['name'];
			        if (isset($cat['children'])) 
							{
			            $result += $this->_build_paths($cat['children'], $result[$id] . '|');
			        }
			    }
			    return $result;
			}
							
			function _get_all_categories_and_sub_categories_in_qb()
			{
				try
				{
					
					$dataService = $this->_get_data_service();
					
					$count_statement = "SELECT COUNT(*) FROM Item where Type='Category'";
					$count = $dataService->Query($count_statement);
					$limit = 100;
					$pages = intval(ceil($count / $limit));
						
					$categories = array();
					for ($i = 1; $i <= $pages; $i++) 
					{
						$result_refresh_token = $this->refresh_tokens();
						if ($result_refresh_token) {
							$dataService = $this->_get_data_service();
						}
						if($i == 1)
						 {
						 	$position = 1;
						 }
						 else
						 {
						 	$position = ($i-1)*$limit;
						 }
						
						 $result = $dataService->Query("SELECT * FROM Item where Type='Category' STARTPOSITION $position MAXRESULTS $limit");
			       if($result) 
						 {
			            $categories = array_merge($categories, $result);
			       }
					}
				
					$error = $dataService->getLastError();
					if (!$error) 
					{
						$return_qb_categories = array();

						foreach($categories as $category)
						{
							$result_refresh_token = $this->refresh_tokens();
							if ($result_refresh_token) {
								$dataService = $this->_get_data_service();
							}
							
							$categoryNameWithoutPHPPOSCategoryId = substr($category->Name, 0, strrpos($category->Name, " ["));
							
							$return_qb_categories[] = array('name' => $categoryNameWithoutPHPPOSCategoryId, 'id' => $category->Id, 'parent' => $category->ParentRef ? $category->ParentRef : 0);
						}
						
						$tree = array();
						
						foreach ($return_qb_categories as $cat)
						{
							$result_refresh_token = $this->refresh_tokens();
							if ($result_refresh_token) {
								$dataService = $this->_get_data_service();
							}
							if (!isset($tree[$cat['id']]))
								{
									$tree[$cat['id']] = array();
								}
						    $tree[$cat['id']]['name'] = $cat['name'];

						    if (!isset($tree[$cat['parent']]))
								{
									$tree[$cat['parent']] = array();
								}

						    $tree[$cat['parent']]['children'][$cat['id']] =& $tree[$cat['id']];
						}

						if (!empty($tree))
						{
							$cat_map = array_flip($this->_build_paths($tree[0]['children']));
						}
						else
						{
							$cat_map = array();
						}
						
						return $cat_map;
					}
					else
					{
						$xml = simplexml_load_string($error->getResponseBody());
						$error_message = (string)$xml->Fault->Error->Detail;
						$this->_log("*******".lang('common_EXCEPTION').": ".$row['name'].' '.$error_message);
					}
				}				
				catch(Exception $e)
				{
					$this->_log("*******".lang('common_EXCEPTION')." 2 : ".$e->getMessage());
				}
				
				return NULL;
				
			}
			
			function _export_phppos_categories_to_quickbooks()
			{
				$this->_log(lang("config_export_phppos_categories_to_quickbooks"));
				$qb_cats = $this->_get_all_categories_and_sub_categories_in_qb();
				$this->load->model('Category');
	
				//This category list is in order by name and in hierarchy order
				$phppos_cats = $this->Category->get_all_categories_and_sub_categories_as_indexed_by_category_id(FALSE);

				foreach($phppos_cats as $phppos_category_id => $phppos_category_path)
				{	
					sleep(1);
					$this->_kill_if_needed();
					$result_refresh_token = $this->refresh_tokens();
					if ($result_refresh_token) {
						$dataService = $this->_get_data_service();
					}
					
					if(!isset($qb_cats[$phppos_category_path]))
					{
						$category_parts = explode('|',$phppos_category_path);
						$category_name = end($category_parts);
						$qb_parent_id = $this->_get_qb_category_parent_id($phppos_category_path,$qb_cats);
		
						//Update qb_cats array with new category we just made
						sleep(1);
						$this->_kill_if_needed();
						/*
						* Check the accounting_id of a category
						* if accounting_id is not empty or null then it will show category already exists in log
						* if accounting_id is empty or null then it will move to the _save_category_to_qb function to add the category
						*/
						$cat_accounting_id = $this->Category->get_qb_category_id($phppos_category_id);
						if (empty($cat_accounting_id)) {
							$qb_cats[$phppos_category_path] = $this->_save_category_to_qb($category_name, $qb_parent_id,$phppos_category_id);
						}

					}
					else {
						// Add the Category Accounting id created by POS Like Discount Category
						$qb_parent_id = $qb_cats[$phppos_category_path];
						$this->Category->link_qb_category($phppos_category_id, $qb_parent_id);
					}
				}
			}
			
			function _get_qb_category_parent_id($category_path, $qb_cats)
			{
				if ($qb_cats == NULL)
				{
					$qb_cats = $this->_get_all_categories_and_sub_categories_in_qb();
				}

				$path_parts = explode('|',$category_path);

				//Remove last part of path as we only want parent
				$path_parts = array_slice($path_parts, 0, count($path_parts) - 1);
				for($k=0;$k < count($path_parts);$k++)
				{
					$result_refresh_token = $this->refresh_tokens();
					if ($result_refresh_token) {
						$dataService = $this->_get_data_service();
					}
					$category_path = implode('|',array_slice($path_parts,$k));

					if (isset($qb_cats[$category_path]))
					{
						return $qb_cats[$category_path];
					}
				}
					return 0;
			}
			
			function _save_category_to_qb($category_name,$qb_parent_id,$phppos_category_id)
			{
				$this->load->model('Category');
				
				try
				{
					$dataService = $this->_get_data_service();
				
					$cat_name_filtered = '';
					$cat_name = '';
					$cat_name = $category_name;
					$cat_name_filtered = $this->_get_filtered_name($cat_name);

					$category_info = array(
						'Name' => $cat_name_filtered.' ['.$phppos_category_id.']',
						'Type' => 'Category',
					);
				
					if ($qb_parent_id)
					{
						$category_info['SubItem'] = TRUE;
					
						$parent_category_info = $this->Category->get_info($this->Category->get_category_id_from_qb_id($qb_parent_id));
						$category_info['ParentRef'] = array(
							'Name' => $parent_category_info->name.' ['.$parent_category_info->id.']',
							'Value' => $qb_parent_id
						);
					}
				
					$cat_create = QBItem::create($category_info);
					$cat_response = $dataService->Add($cat_create);
					$error = $dataService->getLastError();
				
					if(!$error)
					{
						$this->db->where('id', $phppos_category_id);
						$this->db->update('categories', array(
							'accounting_id' => $cat_response->Id,
						));
					
						$this->_log(lang('common_added').' '.$category_name);
					
						return $cat_response->Id;
					}
					else
					{
						$xml = simplexml_load_string($error->getResponseBody());
						$error_message = (string)$xml->Fault->Error->Detail;
						$this->_log("*******".lang('common_EXCEPTION').": ".$category_name.' '.$error_message);
					}
				}
				catch(Exception $e)
				{
					$this->_log("*******".lang('common_EXCEPTION')." 3 : ".$e->getMessage());
				}
					
				return NULL;
			}

			/*
			* filter characters/new line etc. which are not supported by QBO
			*
			* Characters which are allowed By QBO
			* a-z, A-Z, 0-9
			* ,.?@&!#'~*_-;+
			*
			*
			*/ 
			function _get_filtered_name($name) {
				$name = strip_tags($name);
				$name = preg_replace("/[^a-zA-Z0-9\,\.\?\@\&\!\#\'\~\*\_\-\;\+ ]/", " ", $name);
				// check if the length is greater than 92 the it will trim it to 92 characters
				if(strlen($name) > MAX_LENGTH_NAME_QB){
					$name = substr($name, 0, MAX_LENGTH_NAME_QB);
				}
				return $name;
			}
			
			function _export_phppos_items_to_quickbooks()
			{
				$this->_log(lang("config_export_phppos_items_to_quickbooks"));
				$this->_export_phppos_categories_to_quickbooks();
				try
				{
					
					// Get account Id from Database for Revenue, Asset and Expense to add item online 
					$revenue_account_id = $this->config->item('revenue_account_id');
					$asset_account_id = $this->config->item('asset_account_id');
					$expense_account_id = $this->config->item('expense_account_id');
					
					if ((!empty($revenue_account_id)) && (!empty($asset_account_id)) && (!empty($expense_account_id))) {

						$this->load->model('Item');
						$this->load->model('Category');
						$this->load->model('Item_taxes_finder');
						$this->load->model('Item_taxes');
						$this->load->model('Location');

						$result_item = $this->Item->get_items_not_in_quickbooks_or_modified_since_last_sync();
						$dataService = $this->_get_data_service();

						// Return Accounting Id of House account in Qbo
						$storeAccountId = $this->config->item('store_account');
						
						// Get Discount item id
						$discountItemId = $this->Item->get_item_id_for_flat_discount_item();

						// Return Store account Item id in QBO
						$storeItemId = $this->Item->get_store_account_item_id();
						$storeItemAccountingId = "";
						if ($storeItemId) {
							$store_item_info = $this->Item->get_info($storeItemId, false);
							$storeItemAccountingId = $store_item_info->accounting_id;
						}
						
						// Code To Get Store Account Item Name from Accounting id
						$storeAccountName = "";
						if ((isset($storeItemAccountingId)) && ($storeItemAccountingId != ""))  {
							$storeAccountInfo = $this->Item->get_item_with_accounting_id($storeItemAccountingId);
							if ($storeAccountInfo) {
								$storeAccountName = $storeAccountInfo->name;
							}
						}

						while($row = $result_item->unbuffered_row('array'))
						{
							try
							{
								sleep(1);
								$this->_kill_if_needed();
								// in case we have huge number of accounts e.g 4000+, then there is a possibility that the token may expire. To overcome that situation we will check the token validity in each call and refresh it before 5 minutes of expiration
								$result_refresh_token = $this->refresh_tokens();
								if ($result_refresh_token) {
									$dataService = $this->_get_data_service();
								}
								$total_quantity = 0;

								$item_name_filtered = '';
								$item_name_filtered = $this->_get_filtered_name($row['name']);
								$item_name_filtered = $item_name_filtered." [".$row['item_id']."]";

								// Set Revenue Account ID given from Chart of account (House Account) from frontend for Store Account QBO to check the Debits and Credits online
								if ($item_name_filtered==$storeAccountName) {
									$revenue_account_id = $storeAccountId;
								}

								$taxes = $this->Item_taxes_finder->get_info($row['item_id']);
								$is_taxed = count($taxes) > 0;
								$item_info = array(
									'Name' => $item_name_filtered,
									'Description' => $row['description'],
									'Active' => $row['deleted'] == 0,
									'Taxable' => $is_taxed,
									'Type' => $row['is_service'] ? 'Service' : 'Inventory',
									'UnitPrice' => to_currency_no_money($row['unit_price']),
									'SalesTaxIncluded' => (boolean)$row['tax_included'],
									'PurchaseCost' => to_currency_no_money($row['cost_price']),
									'TrackQtyOnHand' => (boolean)!$row['is_service'],
									'IncomeAccountRef' => array(
										"Value"=> $revenue_account_id,
									),
								);
							
								if ($row['item_number'])
								{
									$item_info['Sku'] = $row['item_number'];
								}
							
								if ($row['category_id'] && $this->Category->get_qb_category_id($row['category_id']))
								{
									$item_info['SubItem'] = TRUE;
									$item_info['ParentRef'] = array(
									'Name' => $this->Category->get_full_path($row['category_id'],':'),
									'Value' => $this->Category->get_qb_category_id($row['category_id']),
									);
								}
							
								$item_info["ExpenseAccountRef"] = array(
									"Value"=> $expense_account_id,
								);
							
								if ($item_info['TrackQtyOnHand'])
								{							
									$item_location_info = $this->Item_location->get_info($row['item_id'], 1);
									if (!$row['accounting_id'])
									{
										$item_info['InvStartDate'] = date("Y-m-d", strtotime("-1 days"));
									}
							
									$item_info["AssetAccountRef"] = array(
										"Value"=> $asset_account_id,
									);
								
									$reorder_level = ($item_location_info && $item_location_info->reorder_level) ? $item_location_info->reorder_level : $row['reorder_level'];
								
									if ($reorder_level !== NULL)
									{
										$item_info["ReorderPoint"] = $reorder_level;
									}
								}
						
						
								//New item
								if (!$row['accounting_id'])
								{

									if ($item_info['TrackQtyOnHand'])
									{
										$qb_setup_date  = $this->config->item('qb_setup_date');
										if ($row['last_modified'] >= $qb_setup_date){
											/*
											* Inventory Item Quantity will be send as 0 when we create new items which are added after connecting the QBO
											* Item quantity will update only at that time when SYNC INVETORY CHANGES function will called
											*/
											$item_info['QtyOnHand'] = '0';
										} else {
											/*
											* Get the Quantity for the items which are added before connecting the QBO
											*/
											if (($discountItemId == $row['item_id']) || ($storeItemId == $row['item_id'])) {
												// Location will remain same in case of discount item and Store Account Payment
												$item_location_info = $this->Item_location->get_info($row['item_id'], 1);
												$item_info['QtyOnHand'] = to_quantity($item_location_info->quantity);
											} else {
												// Get Quantity from all the locations 
												$all_locations_info = $this->Location->get_all()->result_array();
	
												if ($all_locations_info && is_array($all_locations_info) && (count($all_locations_info) > 0)) {
													foreach ($all_locations_info as $location_data) {
	
														$location_id = $location_data['location_id'];
														$item_location_info = $this->Item_location->get_info($row['item_id'], $location_id);
														// Code to Get the total qualtity of current product from all the locations
														$total_quantity = $total_quantity + to_quantity($item_location_info->quantity ? $item_location_info->quantity : 0);
													}
												}
												$item_info['QtyOnHand'] = $total_quantity;
											}
										}
									}
									
									$item_create = QBItem::create($item_info);
									$item_response = $dataService->Add($item_create);
									$error = $dataService->getLastError();

									if(!$error)
									{
										$this->Item->link_item_to_quickbooks($row['item_id'],$item_response->Id);
										$this->_log(lang('common_added').' '.$item_name_filtered);
									}
									else
									{
										$xml = simplexml_load_string($error->getResponseBody());
										$error_message = (string)$xml->Fault->Error->Detail;
										$this->_log("*******".lang('common_EXCEPTION').": ".$item_name_filtered.' '.$error_message);
									}
								}
								else //Update item
								{
									$entities = $dataService->Query("SELECT * FROM Item where Id='".$row['accounting_id']."'");
									$error = $dataService->getLastError();
									if (!$error) 
									{
										//Get the item we want to update
										$theItemToUpdate = reset($entities);
										$updateItem = QBItem::update($theItemToUpdate, $item_info);
										$item_response = $dataService->Update($updateItem);
										$error = $dataService->getLastError();
								
										if (!$error)
										{
											$this->_log(lang('common_updated').' '.$item_name_filtered);
										}
										else 
										{
											$xml = simplexml_load_string($error->getResponseBody());
											$error_message = (string)$xml->Fault->Error->Detail;
											$this->_log("*******".lang('common_EXCEPTION').": ".$item_name_filtered.' '.$error_message);
										}
									}
									else {
										$xml = simplexml_load_string($error->getResponseBody());
										$error_message = (string)$xml->Fault->Error->Detail;
										$this->_log("*******".lang('common_EXCEPTION').": ".$item_name_filtered.' '.$error_message);
									}
								}
								
							}
							catch(Exception $e)
							{
								$this->_log("*******".lang('common_EXCEPTION')." 4: ".$e->getMessage());
							}
						}
					}
					else {
						if (empty($revenue_account_id)) {
							$this->_log("*******".lang('common_revenue_account_id_missing'));
						}
						if (empty($asset_account_id)) {
							$this->_log("*******".lang('common_asset_account_id_missing'));
						}
						if (empty($expense_account_id)) {
							$this->_log("*******".lang('common_expense_account_id_missing'));
						}
					}
				}
				catch(Exception $e)
				{
					$this->_log("*******".lang('common_EXCEPTION')." 5: ".$e->getMessage());
				}
			}

		
			function _export_customers_to_quickbooks()
			{
				$this->_log(lang("config_export_customers_to_quickbooks"));
				
				$this->load->model('Customer');
				$result = $this->Customer->get_customers_not_in_quickbooks_or_modified_since_last_sync();
				$dataService = $this->_get_data_service();

				while($row = $result->unbuffered_row('array'))
				{
					sleep(1);
					$this->_kill_if_needed();
					$result_refresh_token = $this->refresh_tokens();
					if ($result_refresh_token) {
						$dataService = $this->_get_data_service();
					}

					$customer_name_filtered = '';
					$cust_name = '';
					$cust_name = $row['company_name'].' - '.$row['first_name'].' '.$row['last_name'];
					$customer_name_filtered = $this->_get_filtered_name($cust_name).' ['.$row['person_id'].']';
					
					$customer_info = array(
						"Balance" => $row['balance'],
						"GivenName" => $row['first_name'],
						"CompanyName" => $row['company_name'].' ['.$row['person_id'].']',
						"DisplayName" => $customer_name_filtered,
						"Taxable" => (boolean) $row['taxable'],
						'Notes' => $row['comments'],
						"BillAddr" => array(
							"Line1" => $row['address_1'],
							"Line2" => $row['address_2'],
							"City" => $row['city'],
							"CountrySubDivisionCode" => $row['state'],
							"PostalCode" => $row['zip'],
						),
						"PrimaryPhone" => array(
							"FreeFormNumber" => $row['phone_number'],
						),
						"PrimaryEmailAddr" => array(
							"Address" => $row['email'],
						),
					);
					
					//New customer
					if (!$row['accounting_id'])
					{
						$customer_create = QBCustomer::create($customer_info);
						$customer_response = $dataService->Add($customer_create);
						$error = $dataService->getLastError();
					
						if(!$error)
						{
							$person_data= array();
							$customer_data = array('accounting_id' => $customer_response->Id);
							
							//Save quickbooks id for customer
							$this->Customer->save_customer($person_data, $customer_data,$row['person_id']);
							$this->_log(lang('common_added').' '.$row['first_name'].' '.$row['last_name']);
						}
						else
						{
							$xml = simplexml_load_string($error->getResponseBody());
							$error_message = (string)$xml->Fault->Error->Detail;
							$this->_log("*******".lang('common_EXCEPTION').": ".$row['first_name'].' '.$row['last_name'].' '.$error_message);
						}
					}
					else //Update customer
					{
						$entities = $dataService->Query("SELECT * FROM Customer where Id='".$row['accounting_id']."'");
						$error = $dataService->getLastError();
						if (!$error) 
						{
							//Get the customer we want to update
							$theCustomerToUpdate = reset($entities);
							$updateCustomer = QBCustomer::update($theCustomerToUpdate, $customer_info);
							$customer_response = $dataService->Update($updateCustomer);
							$error = $dataService->getLastError();
							
							if (!$error)
							{
								$this->_log(lang('common_updated').' '.$row['first_name'].' '.$row['last_name']);
							}
							else 
							{
								$xml = simplexml_load_string($error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$row['first_name'].' '.$row['last_name'].' '.$error_message);
							}
						}
					}
				}
			}


			// New Functions Created

			// New Expense Function Start
			function _export_expenses_to_quickbooks()
			{
				$this->load->model('Expense');
				$this->load->model('Location');
				$expense_to_sync = $this->Expense->get_expenses_not_in_quickbooks_or_modified_since_last_sync()->result_array();

				$initArray = array();
				$dataService = $this->_get_data_service();

				// Code for add account has been start
				$accountId = $this->config->item('expense_account');
				$bankAccountId = $this->config->item('expense_bank_credit_account');
				// Code for add account has been ends

				if ($expense_to_sync && is_array($expense_to_sync) && (count($expense_to_sync) > 0)) {
					foreach ($expense_to_sync as $expense) {
						sleep(1);
						$this->_kill_if_needed();
						$result_refresh_token = $this->refresh_tokens();
						if ($result_refresh_token) {
							$dataService = $this->_get_data_service();
						}
						
						// Get Location info from database Starts
						$locationId = $expense['location_id'];
						if ($locationId) {
							$departmentName = $this->_get_DepartmentName_by_locationId($locationId);
						}
						// Get Location Info from database Ends
						$expense_create = $initArray;
						$line = $initArray;
						$expenseAmount = $expense['expense_amount'];
						// Code for add department has been start
						$currentSecDeptId = $this->_get_DepartmentIdByName_FromQB($departmentName);
						if (($currentSecDeptId) && ($currentSecDeptId != "")) {
							$expense_create['DepartmentRef'] = array('value' => $currentSecDeptId);
						}
						// Code for add department has been ends
						$expenseDesc = $expense['expense_description'];
						$expenseId = $expense['id'];
						$accountReferenceId = $accountId;
						$expense_create['AccountRef'] = array('value' => $bankAccountId);
						$expense_create['PaymentType'] = 'Cash';
						$expense_create['TotalAmt'] = $expenseAmount;
						$line[] = array(
							'Id' => '1',
							'Amount' => $expenseAmount,
							'DetailType' => 'AccountBasedExpenseLineDetail',
							'Description' => $expenseDesc,
							'AccountBasedExpenseLineDetail' => array(
								'AccountRef' => array(
									'value' => $accountReferenceId,
								),
								'BillableStatus' => 'NotBillable',
								'TaxCodeRef' => array(
									'value' => 'NON',
								),
							),
						);
						$expense_create['Line'] = $line;
						$expense_receipt_create = QBPurchase::create($expense_create);
						$expense_create_result = $dataService->Add($expense_receipt_create);
						$error = $dataService->getLastError();

						if (!$error) {
							$accountingId = $expense_create_result->Id;
							$this->Expense->link_qb_expense($expenseId, $accountingId);
							$this->_log(lang('common_added').' '.$expenseDesc);
							
						} else {
							$xml = simplexml_load_string($error->getResponseBody());
							$error_message = (string)$xml->Fault->Error->Detail;
							$this->_log("*******" . lang('common_EXCEPTION') . ": " . $expenseId . ' ' . $error_message);
						}
					}
				}
			}
			// New Expense Function Ends

			// Export of Employee Commissions captured to QB start
			function _add_EmployeeCommissions($commissionAmount, $departmentName, $saleTotal, $saleTime) {
				$initArray = array();
				$dataService = $this->_get_data_service();

				$creditAccountInfo = '';
				$creditAccountId = '';
				$debitAccountInfo = '';
				$debitAccountId = '';
				$departmentId = '';
				$currentSecDeptId = '';
				$totalAmount = $saleTotal;
				
				if ($totalAmount < 0) {
					// Posting type for ReFund Receipt
					$postingType1 = 'Debit';
					$postingType2 = 'Credit';
				} else {
					// Posting type for Invoice
					$postingType1 = 'Credit';
					$postingType2 = 'Debit';
				}
			
				$creditAccountId = $this->config->item('commission_credit_account');
				$debitAccountId = $this->config->item('commission_debit_account');
				
				if (($creditAccountId) && ($debitAccountId)) {
					// Journal Entry Start
					$journalEntryAmount = $commissionAmount;
					$journal_entry_create = $initArray;
					$journal_entry_lines = $initArray;
					$journal_entry_create['Adjustment'] = false;
					$journal_entry_create['domain'] = 'QBO';
					$journal_entry_create['sparse'] = false;
					$journal_entry_create['SyncToken'] = '0';
					$journal_entry_create['TxnDate'] = $this->_get_TimeFormat($saleTime);

					// Code for add department has been start
					$currentSecDeptId = $this->_get_DepartmentIdByName_FromQB($departmentName);
					if (($currentSecDeptId) && ($currentSecDeptId != "")) {
						$departmentId = $currentSecDeptId;
					}
					// Code for add department has been ends

					$arrayJournalEntry = array($postingType1 => $creditAccountId, $postingType2 => $debitAccountId);
					if ($arrayJournalEntry && is_array($arrayJournalEntry) && (count($arrayJournalEntry) > 0)) {
						foreach($arrayJournalEntry as $key => $journalEntryAccountId){
							$journal_entry_line = array(
								'Amount' => $journalEntryAmount,
								'DetailType' => 'JournalEntryLineDetail',
								'JournalEntryLineDetail' => array(
									'PostingType' => $key,
									'AccountRef' => array('value' => $journalEntryAccountId),
									'DepartmentRef' => array('value' =>  $departmentId),
								),
							);
							$journal_entry_lines[] = $journal_entry_line;
						}
					}

					$journal_entry_create['Line'] = $journal_entry_lines;
					$journal_entry_receipt_create = QBJournalEntry::create($journal_entry_create);
					$journal_entry_receipt_create_result = $dataService->Add($journal_entry_receipt_create);
					$error = $dataService->getLastError();

					if ($error) {
						$xml = simplexml_load_string($error->getResponseBody());
						$error_message = (string)$xml->Fault->Error->Detail;
						$this->_log("*******" . lang('common_EXCEPTION') . ": ". $error_message);
					}
				} 
				else 
				{
					$this->_log("*******" . lang('qb_Credit_Debit_Account_Missing'));
				}
				// Journal Entry Ends
			}
			// Export of Employee Commissions captured to QB Ends

			// Function to get Department Name from Location Id
			function _get_DepartmentName_by_locationId ($locationId) {
				$this->load->model('Location');
				$departmentName = '';
				$locationInfo = $this->Location->get_info($locationId);
				if ($locationInfo) {
					$departmentName = $locationInfo->name;
				}
				return $departmentName;
			}

			// Function to get department id from name
			function _get_DepartmentIdByName_FromQB ($departmentName) {
				$dataService = $this->_get_data_service();
				$deptId = '';
				if ($departmentName) {
					$allDepartments = $dataService->Query("SELECT * FROM Department where Name='$departmentName'");
					if (!empty($allDepartments)) {
						$departmentInfo = reset($allDepartments);
						$deptId = $departmentInfo->Id;
					}
				}
				return $deptId;
			}

			// Function for formatting amount into the positive value to record +ve value in qb even in the case of refunds.
			function _format_Posting_Amount($amount)
			{
				return abs($amount);
			}

			// Function to get line array for items, to be passed in Quickbooks request
			function _get_itemLine_quickbooks($itemAmount, $accountingId, $itemDiscountPer, $itemQuanity, $saleTax, $itemTaxIdQb = 0)
			{
				$defaultCountryCode = $this->config->item('default_country_id');
				if ($defaultCountryCode != US_CODE) {
					/*
					* Check the if itemTaxIdQb is equal to 0 then it will set the default tax rate which we set into the qbo chart of account else we will add the id which is coming from id 
					*/
					if ($itemTaxIdQb == 0) {
						$taxValue = $this->config->item('default_tax_id');
					} else {
						$taxValue = $itemTaxIdQb;
					}
				} else {
					if (($saleTax == '') || ($saleTax == 0)) {
						$taxValue = 'NON';
					} else {
						$taxValue = 'TAX';
					}
				}

				$line = array(
					'Amount' => $itemAmount,
					'DetailType' => 'SalesItemLineDetail',
					'SalesItemLineDetail' => array(
						'ItemRef' => array('value' => $accountingId),
						'DiscountRate' => $itemDiscountPer,
						'Qty' => to_quantity($itemQuanity),
						'TaxCodeRef' => array(
							'value' => $taxValue
						)
					)
				);
				return $line;
			}

			// Function to get line array for discounts, to be passed in Quickbooks request
			function _get_discountItemLine_quickbooks($totalDiscountAmount, $discountItemAccountingId, $saleTax, $itemTaxIdQb)
			{
				$defaultCountryCode = $this->config->item('default_country_id');
				if ($defaultCountryCode != US_CODE) {
					/*
					* Check the if itemTaxIdQb is equal to 0 then it will set the default tax rate which we set into the qbo chart of account else we will add the id which is coming from id 
					*/
					if ($itemTaxIdQb == 0) {
						$taxValue = $this->config->item('default_tax_id');
					} else {
						$taxValue = $itemTaxIdQb;
					}
				} else {
					if (($saleTax == '') || ($saleTax == 0)) {
						$taxValue = 'NON';
					} else {
						$taxValue = 'TAX';
					}
				}
				
				$discountedItemLine = array(
					'Amount' => '-' . $totalDiscountAmount,
					'DetailType' => 'SalesItemLineDetail',
					'SalesItemLineDetail' => array(
						'ItemRef' => array('value' => $discountItemAccountingId),
						'DiscountRate' => '',
						'Qty' => '1',
						'TaxCodeRef' => array(
							'value' => $taxValue
						)
					)
				);
				return $discountedItemLine;
			}


			// Function for add Discount Item Line if discount is same in %
			function _get_AddDiscountPercentage_ItemLine($discountAmount, $accountingId, $itemDiscountPer)
			{
				$initArray = array();
				$discountArray = $initArray;
				$DiscountLineDetail = $initArray;
				$DiscountAccountRef = $initArray;
				$DiscountLineDetail = array("PercentBased" => 'true', "DiscountPercent" => $itemDiscountPer);
				$discountArray = array("Amount" => $discountAmount, "DetailType" => "DiscountLineDetail", "DiscountLineDetail" => $DiscountLineDetail);
				return $discountArray;
			}

			// Function for add Discount Item Line if discount is same in %
			function _get_TimeFormat($entryTime)
			{
				$dateFormat = date('Y-m-d', strtotime($entryTime));
				return $dateFormat;
			}

			// Function Get Payment Method
			/**
			* Remove the duplicate code and use common function to create payment for invoices / return array of journal entry lines based on the @$switchCase value
			* 
			* $switchCase => 1 : means request is to create the payments for invoices and return nothing
			* $switchCase => 2 : means request is to create the array of journal entry lines and return array
			*/
			function _get_InvoicePayment_Method($sale_payments, $saleTotal, $saleId, $itemCustomerId, $itemCustomerName, $accounting_id, $departmentId, $journalEntryAmount, $switchCase)
			{
				$dataService = $this->_get_data_service();
				$initArray = array();
				$journal_entry_lines = $initArray;
				if ($sale_payments && is_array($sale_payments) && (count($sale_payments) > 0)) {
					foreach ($sale_payments as $sale_payment_method => $sale_amount) {
						$paymentMethod = $sale_payment_method;
						$entities = $dataService->Query("SELECT * FROM PaymentMethod where Name='$paymentMethod'");
						if (!empty($entities)) {
							$paymentTypeQb = reset($entities);
							switch ($switchCase) {
								case '1':
									/**
									* Create payments for invoice linked to the invoice
									*/
									$payment_create = $initArray;
									$payment_create_line = $initArray;
									$payment_create_linkedTxn = $initArray;
									if ($itemCustomerId != '') {
										$payment_create["CustomerRef"] = array("value" => $itemCustomerId, "name" => $itemCustomerName);
									}
									$paymentAmount = $sale_amount;
									$payment_create['PaymentMethodRef'] = array('value' => $paymentTypeQb->Id, 'name' => $paymentMethod);
									$payment_create["TotalAmt"] = to_currency_no_money($paymentAmount);
									$payment_create_linkedTxn[] = array("TxnId" => $accounting_id, "TxnType" => "Invoice");
									$payment_create_line[] = array("Amount" => to_currency_no_money($paymentAmount), "LinkedTxn" => $payment_create_linkedTxn);
									$payment_create["Line"] = $payment_create_line;
									$sales_payment_receipt_create = QBPayment::create($payment_create);
									$sales_payment_receipt_create_result = $dataService->Add($sales_payment_receipt_create);
									$paymentCreateError = $dataService->getLastError();
									if ($paymentCreateError) {
										$xml = simplexml_load_string($paymentCreateError->getResponseBody());
										$error_message = (string)$xml->Fault->Error->Detail;
										$this->_log("*******" . lang('common_EXCEPTION') . ": " . $saleId . ' ' . $error_message);
									} else {
										$this->_log(lang('common_added') . ' ' . $saleId);
									}
									break;
								case '2':
									$paymentAmount = $this->_format_Posting_Amount($sale_amount);
									if ($paymentMethod == 'Cash') {
										$debitAccountId = $this->config->item('refund_cash_account');
									} elseif ($paymentMethod == 'Debit Card') {
										$debitAccountId = $this->config->item('refund_debit_card_account');
									} elseif ($paymentMethod == 'Credit Card') {
										$debitAccountId = $this->config->item('refund_credit_card_account');
									} elseif ($paymentMethod == 'Check') {
										$debitAccountId = $this->config->item('refund_check_account');
									}
									$journal_entry_line2 = array(
										'Amount' => $paymentAmount,
										'DetailType' => 'JournalEntryLineDetail',
										'JournalEntryLineDetail' => array(
											'PostingType' => 'Credit',
											'AccountRef' => array('value' => $debitAccountId),
											'DepartmentRef' => array('value' =>  $departmentId),
										),
									);
									$journal_entry_lines[] = $journal_entry_line2;
									break;
							}
						}
					}
					if ($switchCase == '2') {
						$creditAccountId = $this->config->item('refund_credit_account');
						$journal_entry_line = array(
							'Amount' => $journalEntryAmount,
							'DetailType' => 'JournalEntryLineDetail',
							'JournalEntryLineDetail' => array(
								'PostingType' => 'Debit',
								'AccountRef' => array('value' => $creditAccountId),
								'DepartmentRef' => array('value' =>  $departmentId),
							),
						);
						$journal_entry_lines[] = $journal_entry_line;
						return $journal_entry_lines;
					}
				}
			}
			
			function _export_suppliers_to_quickbooks()
			{
				$this->_log(lang("config_export_suppliers_to_quickbooks"));
				
				$this->load->model('Supplier');
				$result = $this->Supplier->get_suppliers_not_in_quickbooks_or_modified_since_last_sync();
				$dataService = $this->_get_data_service();

				while($row = $result->unbuffered_row('array'))
				{
					sleep(1);
					$this->_kill_if_needed();
					$result_refresh_token = $this->refresh_tokens();
					if ($result_refresh_token) {
						$dataService = $this->_get_data_service();
					}

					$supp_name = '';
					$supplier_name_filtered = '';
					$supp_name = $row['company_name'].' - '.$row['first_name'].' '.$row['last_name'];
					$supplier_name_filtered = $this->_get_filtered_name($supp_name).' ['.$row['person_id'].']';

					
					$supplier_info = array(
						"GivenName" => $row['first_name'],
						"CompanyName" => $row['company_name'].' ['.$row['person_id'].']',
						"DisplayName" => $supplier_name_filtered,
						'Notes' => $row['comments'],
						"BillAddr" => array(
							"Line1" => $row['address_1'],
							"Line2" => $row['address_2'],
							"City" => $row['city'],
							"CountrySubDivisionCode" => $row['state'],
							"PostalCode" => $row['zip'],
						),
						"PrimaryPhone" => array(
							"FreeFormNumber" => $row['phone_number'],
						),
						"PrimaryEmailAddr" => array(
							"Address" => $row['email'],
						),
					);
					
					//New supplier
					if (!$row['accounting_id'])
					{
						$supplier_create = QBVendor::create($supplier_info);
						$supplier_response = $dataService->Add($supplier_create);
						$error = $dataService->getLastError();
					
						if(!$error)
						{
							$person_data= array();
							$supplier_data = array('accounting_id' => $supplier_response->Id);
							
							//Save quickbooks id for supplier
							$this->Supplier->save_supplier($person_data, $supplier_data,$row['person_id']);
							$this->_log(lang('common_added').' '.$row['company_name'].' - '.$row['first_name'].' '.$row['last_name']);
						}
						else
						{
							$xml = simplexml_load_string($error->getResponseBody());
							$error_message = (string)$xml->Fault->Error->Detail;
							$this->_log("*******".lang('common_EXCEPTION').": ".$row['company_name'].' - '.$row['first_name'].' '.$row['last_name'].' '.$error_message);
						}
					}
					else //Update supplier
					{
						$entities = $dataService->Query("SELECT * FROM Vendor where Id='".$row['accounting_id']."'");
						$error = $dataService->getLastError();
						if (!$error) 
						{
							//Get the supplier we want to update
							$theSupplierToUpdate = reset($entities);
							$updateSupplier = QBVendor::update($theSupplierToUpdate, $supplier_info);
							$supplier_response = $dataService->Update($updateSupplier);
							$error = $dataService->getLastError();
							
							if (!$error)
							{
								$this->_log(lang('common_updated').' '.$row['company_name'].' - '.$row['first_name'].' '.$row['last_name']);
							}
							else 
							{
								$xml = simplexml_load_string($error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$row['company_name'].' - '.$row['first_name'].' '.$row['last_name'].' '.$error_message);
							}
						}
					}
				}
			}
			
			function _export_employees_to_quickbooks()
			{
				$this->_log(lang("config_export_employees_to_quickbooks"));
				
				$this->load->model('Employee');
				$result = $this->Employee->get_employees_not_in_quickbooks_or_modified_since_last_sync();
				$dataService = $this->_get_data_service();

				while($row = $result->unbuffered_row('array'))
				{
					sleep(1);
					$this->_kill_if_needed();
					$result_refresh_token = $this->refresh_tokens();
					if ($result_refresh_token) {
						$dataService = $this->_get_data_service();
					}

					$emp_first_name = '';
					$emp_last_name = '';
					$emp_first_name_filtered = '';
					$emp_last_name_filtered = '';
					$emp_first_name = $row['first_name'];
					$emp_last_name = $row['last_name'];
					$emp_first_name_filtered = $this->_get_filtered_name($emp_first_name);
					$emp_last_name_filtered = $this->_get_filtered_name($emp_last_name);
					$emp_first_name_length = strlen($emp_first_name_filtered);
					$emp_last_name_length = strlen($emp_last_name_filtered);
					// check if first name length is greater than 50 if yes then it will trim characters to 50 only because QBO accept only 50 characters for first name
					if($emp_first_name_length > 50) {
						$emp_first_name_filtered = substr($emp_first_name_filtered, 0 ,50);
					}
					// check if last name length is greater than 42 if yes then it will trim characters to 42 only because QBO accept only 50 characters for last name + we are attaching person id 
					if($emp_last_name_length > 42) {
						$emp_last_name_filtered = substr($emp_last_name_filtered, 0 ,42);
					}
					$emp_last_name_filtered = $emp_last_name_filtered.' ['.$row['person_id'].']';
					$this->_log($emp_first_name_filtered);
					$this->_log($emp_last_name_filtered);
					
					$employee_info = array(
						"GivenName" => $emp_first_name_filtered,
						"FamilyName" => $emp_last_name_filtered,
						"PrimaryAddr" => array(
							"Line1" => $row['address_1'],
							"Line2" => $row['address_2'],
							"City" => $row['city'],
							"CountrySubDivisionCode" => $row['state'],
							"PostalCode" => $row['zip'],
						),
						"PrimaryPhone" => array(
							"FreeFormNumber" => $row['phone_number'],
						),
						"PrimaryEmailAddr" => array(
							"Address" => $row['email'],
						),
					);
					
					//New employee
					if (!$row['accounting_id'])
					{
						$employee_create = QBEmployee::create($employee_info);
						$employee_response = $dataService->Add($employee_create);
						$error = $dataService->getLastError();
					
						if(!$error)
						{
							//Save quickbooks id for employee
							$this->Employee->link_employee_to_quickbooks($row['person_id'],$employee_response->Id);
							$this->_log(lang('common_added').' '.$row['first_name'].' '.$row['last_name']);
						}
						else
						{
							$xml = simplexml_load_string($error->getResponseBody());
							$error_message = (string)$xml->Fault->Error->Detail;
							$this->_log("*******".lang('common_EXCEPTION').": ".$row['first_name'].' '.$row['last_name'].' '.$error_message);
						}
					}
					else //Update employee
					{
						$entities = $dataService->Query("SELECT * FROM Employee where Id='".$row['accounting_id']."'");
						$error = $dataService->getLastError();
						if (!$error) 
						{
							//Get the employee we want to update
							$theEmployeeToUpdate = reset($entities);
							$updateEmployee = QBEmployee::update($theEmployeeToUpdate, $employee_info);
							$employee_response = $dataService->Update($updateEmployee);
							$error = $dataService->getLastError();
							
							if (!$error)
							{
								$this->_log(lang('common_updated').' '.$row['first_name'].' '.$row['last_name']);
							}
							else 
							{
								$xml = simplexml_load_string($error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$row['first_name'].' '.$row['last_name'].' '.$error_message);
							}
						}
					}
				}
				
			}
			
			function _create_payment_methods()
			{
				$this->_log(lang("config_create_payment_methods"));
				$this->load->model('Sale');
				$payment_methods = array_keys($this->Sale->get_payment_options_with_language_keys());
				
				try
				{
					$dataService = $this->_get_data_service();
				
					foreach($payment_methods as $payment_method)
					{
						sleep(1);
						$this->_kill_if_needed();
						$result_refresh_token = $this->refresh_tokens();
						if ($result_refresh_token) {
							$dataService = $this->_get_data_service();
						}
						
						$entities = $dataService->Query("SELECT * FROM PaymentMethod where Name='$payment_method'");
						
						if(empty($entities))
						{
							$payment_create = new IPPPaymentMethod(); 
							$payment_create->Name = $payment_method;
							$payment_response = $dataService->Add($payment_create);
							$error = $dataService->getLastError();
			
							if(!$error)
							{
								$this->_log(lang('common_added').' '.$payment_method);
							}
							else
							{
								$xml = simplexml_load_string($error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$payment_method.' '.$error_message);
							}
						}
					}
				}
				catch(Exception $e)
				{
					$this->_log("*******".lang('common_EXCEPTION')." 6: ".$payment_method.' '.$e->getMessage());
					return NULL;
				}	
			}
			
			//https://developer.intuit.com/docs/api/accounting/salesreceipt
			function _export_sales_to_quickbooks()
			{
				try
				{
					$this->_log(lang("config_export_sales_to_quickbooks"));
					$this->_create_payment_methods();
					$this->load->model('Sale');
					$this->load->model('Item');
					$this->load->model('Item_kit');
					$this->load->model('Item_kit_items');
					$this->load->model('Category');
					$this->load->model('Item_location');
					$this->load->model('Customer');
					$this->load->model('Location');
					$this->load->model('Item_taxes');

					$sales_to_sync = $this->Sale->get_sales_not_in_quickbooks_since_last_sync()->result_array();
					$initArray = array();
					$flaghouseAccount = false;
					$defaultCustomerId = $this->config->item('default_customer_id');
					$storeAccountPaymentName = lang('qb_DEFAULT_STORE_PAYMENT_NAME');
					$dataService = $this->_get_data_service();

					// Setting Default Item id for Payment type Store Payment Start
					// $houseAccountItemId = $this->config->item('house_item');
					// Setting Default Item id for Payment type Store Payment Ends

					$storeItemId = $this->Item->get_store_account_item_id();
					$storeItemAccountingId = "";
					if ($storeItemId) {
						$store_item_info = $this->Item->get_info($storeItemId, false);
						$storeItemAccountingId = $store_item_info->accounting_id;
					}

					// Refund Deposit Account Start
					$refundAccountId = $this->config->item('refund_deposit_account');
					// Refund Deposit Account Ends

					// Setting Discount Item id for Discounted Item Start
					$discountItemId = $this->Item->create_or_update_flat_discount_item();
					$discountItemAccountingId = '';
					$discount_item_info = $this->Item->get_info($discountItemId, false);
					if ($discount_item_info) {
						$discountItemAccountingId = $discount_item_info->accounting_id;
					}
					// Setting Discount Item id for Discounted Item Ends

					// Get all the taxes name and id from th QBO by checking the country code

					$defaultCountryCode = $this->config->item('default_country_id');

					if ($defaultCountryCode != US_CODE) {
						$tax_offset = 0;
						$tax_per_page = 100;
						$taxArray = array();
						while (1) {
							$allTaxcode = $dataService->FindAll('TaxCode', $tax_offset, $tax_per_page);
							$error = $dataService->getLastError();
							if (!$error) {
								$tax_offset += $tax_per_page;
								if (!empty($allTaxcode) and count($allTaxcode) > 0) {
									foreach ($allTaxcode as $taxes) {
										$taxArray[$taxes->Id] = $taxes->Name;
									}
								} else {
									break;
								}
							} else {
								$last_error = $dataService->getLastError();
								$xml = simplexml_load_string($last_error->getResponseBody());
								$error_message = (string)$xml->Fault->Error->Detail;
								$this->_log("*******".lang('common_EXCEPTION').": ".$error_message);
							}
						}
					}

				
					if ($sales_to_sync && is_array($sales_to_sync) && (count($sales_to_sync) > 0)) {
						foreach ($sales_to_sync as $sale) {
							sleep(1);
							$this->_kill_if_needed();
							$result_refresh_token = $this->refresh_tokens();
							if ($result_refresh_token) {
								$dataService = $this->_get_data_service();
							}
						
							$flagDiscountIdRequired = false;
							$sale_create = $initArray;
							$saleId = $sale['sale_id'];
							$saleCustomerId = $sale['customer_id'];
							$flagItemDiscount = true;
							$flagItemKitDiscount = true;
							$itemCustomerId = '';
							$itemCustomerName = '';
							$saleTotal = $sale['total'];
							$saleTime = $sale['sale_time'];
							$saleTax = $sale['tax'];
							$discountKitAmount = 0;
							$sales_item_kits = $this->Sale->get_sale_item_kits($saleId)->result_array();

							// Get Location info from database Starts
							$locationId = $sale['location_id'];
							if ($locationId) {
								$departmentName = $this->_get_DepartmentName_by_locationId($locationId);
							}
							// Get Location Info from database Ends
							$lines = $initArray;
							$sales_items = $this->Sale->get_sale_items($saleId)->result_array();
							$sales_items_count = 0;
							if ($sales_items) {
								$sales_items_count = count($sales_items);
							}

						

							if ($saleCustomerId) {
								$customer_info = $this->Customer->get_info($saleCustomerId);
							} else {
								$customer_info = $this->Customer->get_info($defaultCustomerId);
							}
							if (($customer_info) && ($customer_info->accounting_id)) {
								$itemCustomerId = $customer_info->accounting_id;
								$itemCustomerName = $customer_info->company_name ? $customer_info->company_name : $customer_info->full_name;
							}
						
							// Sale Item Start
							if ($sales_items && is_array($sales_items) && (count($sales_items) > 0)) {
								// code to check discount is on per item is different or same
								$itemDiscountPerDiff = $initArray;
								foreach ($sales_items as $sale_item_sec) {
									$itemDiscountPerDiff[] = $sale_item_sec['discount_percent'];
								}
								$results = array_unique($itemDiscountPerDiff);
								if (count($results) === 1) {
									$flagItemDiscount = false;
								}
							
								$totalDiscountAmount = 0;
								foreach ($sales_items as $sale_item) {
									$item_info = $this->Item->get_info($sale_item['item_id'], false);

									if ($item_info) {
										$categoryId = $item_info->category_id;
										$categoryInfo = $this->Category->get_info($categoryId);
										$categoryname = $categoryInfo->name;
										$accountingId = $item_info->accounting_id;
										$itemUnitPrice = to_currency_no_money($sale_item['item_unit_price']);
										$itemQuanity = $sale_item['quantity_purchased'];
										$itemAmount = $itemUnitPrice * $itemQuanity;
										$itemDiscountPer = $sale_item['discount_percent'];
										$discountAmount = ($itemAmount * $itemDiscountPer) / 100;

									
										// Code For Refund Receipt Start
										if ($saleTotal < 0) {
											$itemAmount = $this->_format_Posting_Amount($itemAmount);
											$totalDiscountAmount = $discountAmount - $totalDiscountAmount;
										} else {
											$totalDiscountAmount = $discountAmount + $totalDiscountAmount;
										}
										$discountAmount = $this->_format_Posting_Amount($discountAmount);
										$totalDiscountAmount = $this->_format_Posting_Amount($totalDiscountAmount);
										// Code For Refund Receipt Ends

										/*
										* get the tax id form tax name given to the item  
										*/
										$itemTaxIdQb = 0;
										$taxDiscountAmount = 0;
										$flagTaxNoId = false;
										$itemTaxInfo = $this->Item_taxes->get_info($sale_item['item_id']);
										if ($itemTaxInfo) {
											$itemTaxName = $itemTaxInfo['0']['name'];
											$itemTaxPercent = $itemTaxInfo['0']['percent'];
											// Check if the tax name is exists in teh list which we get from qbo
											foreach ($taxArray as $taxId => $taxName) {
												if ($taxName == $itemTaxName) {
													$itemTaxIdQb = $taxId;
												}
											}
											// Check if the tax name is not exits in list come from qbo then it will set flagTaxNoId to true and it will break the loop and exists from it 
											if($itemTaxIdQb == 0) {
												$flagTaxNoId = true;
												break;
											}
											// change the discount amount according to the taxes
											$taxDiscountAmount = ($discountAmount * $itemTaxPercent) / 100;
											$totalDiscountAmount = $totalDiscountAmount + $taxDiscountAmount;
										}


										$line = $this->_get_itemLine_quickbooks($itemAmount, $accountingId, $itemDiscountPer, $itemQuanity, $saleTax, $itemTaxIdQb);
										$lines[] = $line;
										// Discount Added as Whole % if discount is same
										if (($flagItemDiscount === false) && (!$sales_item_kits)) {
											$discountArray = $this->_get_AddDiscountPercentage_ItemLine($discountAmount, $accountingId, $itemDiscountPer);
											$lines[] = $discountArray;
										}
									}
								}
								// Discount Added as item if discount is different per item
								if (($flagItemDiscount === true) && (!$sales_item_kits)) {
									if ((!empty($discountItemAccountingId))) {
										$discountedItemLine = $this->_get_discountItemLine_quickbooks($totalDiscountAmount, $discountItemAccountingId, $saleTax, $itemTaxIdQb);
										$lines[] = $discountedItemLine;
									} else {
										$flagDiscountIdRequired = true;
									}
								}
							}
							// Sale Item Ends

							// Adding Store Payment Method as item with negative amount Start
							$sale_payments = $this->Sale->get_payments_for_sale($saleId);
						
							if ($sale_payments && is_array($sale_payments) && (count($sale_payments) > 0)) {
								foreach ($sale_payments as $sale_payment_method => $sale_amount) {
									$paymentAmount = $sale_amount;
									$paymentType = $sale_payment_method;
									if ($paymentType === $storeAccountPaymentName) {
										$defaultCountryCode = $this->config->item('default_country_id');
										if ($defaultCountryCode != US_CODE) {
											$taxValue = $this->config->item('default_store_account_tax_id');
										} else {
											$taxValue = 'NON';
										}

										$storePaymentItem = array(
											'Amount' => '-'.$paymentAmount,
											'DetailType' => 'SalesItemLineDetail',
											'SalesItemLineDetail' => array(
												'ItemRef' => array('value' => $storeItemAccountingId),
												'DiscountRate' => '',
												'Qty' => '1',
												'TaxCodeRef' => array(
													'value' => $taxValue
												)
											)
										);
										$lines[] = $storePaymentItem;
									}
								}
							}
							// Adding Store Payment Method as item with negative amount Ends

							// Sale Item Kits Start
							if ($sales_item_kits && is_array($sales_item_kits) && (count($sales_item_kits) > 0)) {
								// code to check discount is on per itemkit is different or same
								$itemKitDiscountPerDiff = $initArray;
								foreach ($sales_item_kits as $sale_item_kit_sec) {
									$itemKitDiscountPerDiff[] = $sale_item_kit_sec['discount_percent'];
								}
								$kitResults = array_unique($itemKitDiscountPerDiff);
								if (count($kitResults) === 1) {
									$flagItemKitDiscount = false;
								}

								foreach ($sales_item_kits as $sale_item_kit) {
									$itemKitAmount = $sale_item_kit['subtotal'];
									$itemKitAmount = $this->_format_Posting_Amount($itemKitAmount);
									$item_kit_info = $this->Item_kit_items->get_info($sale_item_kit['item_kit_id']);
									// for each to get the total of all the items in Item kit
									if ($item_kit_info && is_array($item_kit_info) && (count($item_kit_info) > 0)) {
										$item_kit_count = count($item_kit_info);
										$itemsTotalAmount = 0;
										foreach ($item_kit_info as $sale_item) {
											$item_info = $this->Item->get_info($sale_item->item_id, false);
											if ($item_info) {
												$itemUnitPrice = $item_info->unit_price;
												if ($saleTotal > 0) {
													$itemsTotalAmount = ($itemUnitPrice * $sale_item_kit['quantity_purchased']) + $itemsTotalAmount;
												} else {
													$itemsTotalAmount = ($itemUnitPrice * (-1 * $sale_item_kit['quantity_purchased'])) + $itemsTotalAmount;
												}
											}
										}
			
										// Items In Item kit Code Start
										$discountKitAmount = 0;
										foreach ($item_kit_info as $sale_item) {
											$item_info = $this->Item->get_info($sale_item->item_id, false);
											if ($item_info) {
												$itemUnitPrice = $item_info->unit_price;
												$accountingId = $item_info->accounting_id;
												$itemKitQuantity = $sale_item_kit['quantity_purchased'];
												$itemQuantity = $sale_item->quantity;
												$subTotal = $sale_item_kit['subtotal'];
												$discountPer = $sale_item_kit['discount_percent'];
												$totalQuantity = to_quantity($itemKitQuantity) * $itemQuantity;
												if ($itemKitAmount == $itemsTotalAmount) {
													$kitAmount = $itemUnitPrice * $itemKitQuantity * $itemQuantity;
												} else if ($itemKitAmount < $itemsTotalAmount) {
													$kitAmount = $itemUnitPrice * $itemKitQuantity * $itemQuantity;
													$discountKitAmount = $itemsTotalAmount - $itemKitAmount;
												} else if ($itemKitAmount > $itemsTotalAmount) {
													$kitAmount = to_currency_no_money($subTotal) / $item_kit_count;
												}

												// Code For Refund Receipt Start
												$kitAmount = $this->_format_Posting_Amount($kitAmount);
												// Code For Refund Receipt Ends

												$line = $this->_get_itemLine_quickbooks($kitAmount, $accountingId, $discountPer, $totalQuantity);
												$lines[] = $line;

												$kitDiscountAmount = (to_currency_no_money($subTotal) * $discountPer) / 100;

												// Code For Refund Receipt Start
												$kitDiscountAmount = $this->_format_Posting_Amount($kitDiscountAmount);
												// Code For Refund Receipt Ends

												/*
												* get the tax id form tax name given to the item  
												*/
												$itemTaxIdQb = 0;
												$taxDiscountAmount = 0;
												$flagTaxNoId = false;
												$itemTaxInfo = $this->Item_taxes->get_info($sale_item->item_id);
												if ($itemTaxInfo) {
													$itemTaxName = $itemTaxInfo['0']['name'];
													$itemTaxPercent = $itemTaxInfo['0']['percent'];
													// Check if the tax name is exists in teh list which we get from qbo
													foreach ($taxArray as $taxId => $taxName) {
														if ($taxName == $itemTaxName) {
															$itemTaxIdQb = $taxId;
														}
													}
													// Check if the tax name is not exits in list come from qbo then it will set flagTaxNoId to true and it will break the loop and exists from it 
													if($itemTaxIdQb == 0) {
														$flagTaxNoId = true;
														break;
													}
												}

												$line = $this->_get_itemLine_quickbooks($kitAmount, $accountingId, $discountPer, $totalQuantity, $saleTax, $itemTaxIdQb);
												$lines[] = $line;


												if (($flagItemKitDiscount === false) && (!$sales_items) && ($discountKitAmount <= 0)) {
													$kitDiscountArray = $this->_get_AddDiscountPercentage_ItemLine($kitDiscountAmount, $accountingId, $discountPer);
													$lines[] = $kitDiscountArray;
												}
											}
										}
									}
									if (($discountKitAmount > 0) && (!$sales_items)) {
										if ((!empty($discountItemAccountingId))) {
											$kitDiscountedItemLine = $this->_get_discountItemLine_quickbooks($discountKitAmount, $discountItemAccountingId, $saleTax, $itemTaxIdQb);
											$lines[] = $kitDiscountedItemLine;
										} else {
											$flagDiscountIdRequired = true;
										}
									}
								}
							}
							if (($sales_items) && ($sales_item_kits)) {
								if ((!empty($discountItemAccountingId))) {
									$totalDiscountAmount = $totalDiscountAmount + $discountKitAmount;
									if ($totalDiscountAmount > 0) {
										$discountedItemLine = $this->_get_discountItemLine_quickbooks($totalDiscountAmount, $discountItemAccountingId, $saleTax, $itemTaxIdQb);
										$lines[] = $discountedItemLine;
									}
								} else {
									$flagDiscountIdRequired = true;
								}
							}
							// Sale Item Kits Ends

							// Code For Refund Receipt Start
							if ($saleTotal < 0)
							{
								$refundSaleTotal = $this->_format_Posting_Amount($saleTotal);
								$refund_lines = array(
									'Amount' => $refundSaleTotal,
									'DetailType' => 'SubTotalLineDetail',
									"SubTotalLineDetail"=> array(),
								);
								$lines[] = $refund_lines;

								$sale_create['TotalAmt'] = $refundSaleTotal;
								$sale_create['PaymentRefNum'] = 'To Print';
								$sale_create['DepositToAccountRef'] = array('value' => $refundAccountId);
							}
							// Code For Refund Receipt Ends

							$sale_create['TxnDate'] = $this->_get_TimeFormat($saleTime);
							$sale_create['Line'] = $lines;
							if ($sale['comment']) {
								$sale_create['CustomerMemo'] = array('value' => $sale['comment']);
							}
							if ($itemCustomerId != '') {
								$sale_create['CustomerRef'] = array('value' => $itemCustomerId);
							}

							// Code for add department has been start
							$currentSecDeptId = $this->_get_DepartmentIdByName_FromQB($departmentName);
							if (($currentSecDeptId) && ($currentSecDeptId != "")) {
								$sale_create['DepartmentRef'] = array('value' => $currentSecDeptId);
							}
							// Code for add department has been ends

							// Tax Add in Invoice Start
							if ($saleTax > 0) {
								$sale_create['TxnTaxDetail'] = array('TotalTax' => to_currency_no_money($saleTax) );
							} else {
								// Code For Refund Receipt Start
								$sale_tax = $this->_format_Posting_Amount($saleTax);
								$sale_create['TxnTaxDetail'] = array('TotalTax' => $sale_tax);
								// Code For Refund Receipt Ends
							}
							// Tax Add in Invoice Ends

							$sale_create['TotalAmt'] = to_currency_no_money($saleTotal);

							// check if tax is in list of taxes come from qbo for non usa countries
							if($flagTaxNoId) {
								$this->_log(lang('config_check_tax_name').$saleId);
							} else {
								if (($flagDiscountIdRequired == true) && (empty($discountItemAccountingId))) {
									$this->_log(lang('config_discount_accounting_id').": ".$saleId.". ".lang('config_sync_for_discount_accounting_id'));
								} else {
									if ($sale['total'] >= 0) {
										$sales_receipt_create = QBInvoice::create($sale_create);
									} else {
										$sales_receipt_create = QBRefundReceipt::create($sale_create);
									}
									$create_result = $dataService->Add($sales_receipt_create);

									$this->_log(lang('common_added').' '.lang('common_sale_id').' '.$saleId);
									$error = $dataService->getLastError();
									if (!$error) {
									
										// Employee Commission Start
										$totalCommission = 0;
										$itemCommissionAmount = 0;
										$itemKitCommissionAmount = 0;
										$saleItemCommission = $this->Sale->get_sale_items($saleId)->result_array();
										if ($saleItemCommission && is_array($saleItemCommission) && (count($saleItemCommission) > 0)) {
											foreach($saleItemCommission as $saleitemCommissionInfo) {
												$itemCommsissionAmount = $saleitemCommissionInfo['commission'];
												$itemCommissionAmount = $itemCommsissionAmount + $itemCommissionAmount;
											}
										}
										$saleItemKitCommission = $this->Sale->get_sale_item_kits($saleId)->result_array();
										if ($saleItemKitCommission && is_array($saleItemKitCommission) && (count($saleItemKitCommission) > 0)) {
											foreach($saleItemKitCommission as $saleitemKitCommissionInfo) {
												$kitCommsissionAmount = $saleitemKitCommissionInfo['commission'];
												$itemKitCommissionAmount = $kitCommsissionAmount + $itemKitCommissionAmount;
											}
										}
										$totalCommission = $itemCommissionAmount + $itemKitCommissionAmount;
										$totalCommission = $this->_format_Posting_Amount($totalCommission);
										if ($totalCommission != 0) {
											$this->_add_EmployeeCommissions($totalCommission, $departmentName, $saleTotal, $saleTime);
										}
										// Employee Commission Ends

										$accounting_id = $create_result->Id;
										$this->Sale->link_qb_sale($saleId, $accounting_id);
										//Subtract qb quantities for item so it doesn't get double deducted
										if ($saleTotal > 0) {
											foreach ($sales_items as $sale_item) {
												$this->Item_location->adjust_accounting_product_quantity(-$sale_item['quantity_purchased'], $sale_item['item_id'], $locationId ? $locationId : 1);
											}

											// Payment Recipt Create Method Starts
											// 1 Is flag added for Switch Case
											$this->_get_InvoicePayment_Method($sale_payments, $saleTotal, $saleId, $itemCustomerId, $itemCustomerName, $accounting_id, '', '', '1');
											// Payment Recipt Create Method Ends
										}

										// Code for Journal entry for refund receipt Start
										if ($saleTotal < 0) {

											$refundAmount = $this->_format_Posting_Amount($saleTotal);
											$journalEntryAmount = $refundAmount;
											$journal_entry_create = $initArray;
											$journal_entry_lines = $initArray;
											$journal_entry_create['Adjustment'] = false;
											$journal_entry_create['domain'] = 'QBO';
											$journal_entry_create['sparse'] = false;
											$journal_entry_create['SyncToken'] = '0';
											$journal_entry_create['TxnDate'] = $this->_get_TimeFormat($saleTime);

											// Code for add department has been start
											$departmentId = $this->_get_DepartmentIdByName_FromQB($departmentName);
											// Code for add department has been ends

											// 2 Is flag added for Switch Case
											$journal_entry_lines = $this->_get_InvoicePayment_Method($sale_payments, $saleTotal, $saleId, $itemCustomerId, $itemCustomerName, '', $departmentId, $journalEntryAmount, '2');
											$journal_entry_create['Line'] = $journal_entry_lines;						

											$journal_entry_receipt_create = QBJournalEntry::create($journal_entry_create);
											$journal_entry_receipt_create_result = $dataService->Add($journal_entry_receipt_create);
											$error = $dataService->getLastError();

											if ($error) {
												$xml = simplexml_load_string($error->getResponseBody());
												$error_message = (string)$xml->Fault->Error->Detail;
												$this->_log("*******" . lang('common_EXCEPTION') . ": ". $error_message);
											}
										}
										// Code for Journal entry for refund receipt ends

										$this->_log(lang('common_added') . ' ' . $saleId);
									} else {
										$xml = simplexml_load_string($error->getResponseBody());
										$error_message = (string)$xml->Fault->Error->Detail;
										$this->_log("*******" . lang('common_EXCEPTION') . ": " . $saleId . ' ' . $error_message);
									}
								}
							}
						}
					}
				}
				catch(Exception $e)
				{
					$this->_log("*******".lang('common_EXCEPTION')." 1-1 : ".$e->getMessage());
				}
			}
			
			function _export_receivings_to_quickbooks()
			{
				try
				{
					$this->_log(lang("config_export_receivings_to_quickbooks"));
					$this->load->model('Receiving');
					$this->load->model('Item');
					$this->load->model('Item_kit');
					$this->load->model('Item_kit_items');
					$this->load->model('Supplier');
					$this->load->model('Location');
					$this->load->model('Item_location');
					$receiving_to_sync = $this->Receiving->get_receivings_not_in_quickbooks_since_last_sync()->result_array();

					$initArray = array();
					$dataService = $this->_get_data_service();
					$arrayReceivingAndBillData = $initArray;

					// According to client's new requirement sync will work when suspended = 0 and both purchase order and bills created at one time
					if ($receiving_to_sync and count($receiving_to_sync) > 0) {
						foreach($receiving_to_sync as $arrayReceivingOrBill) {
							sleep(1);
							$this->_kill_if_needed();
							$result_refresh_token = $this->refresh_tokens();
							if ($result_refresh_token) {
								$dataService = $this->_get_data_service();
							}
							$arrayReceivingOrBillCreate = $initArray;
							$lines = $initArray;
							$itemVendorId = '';
							$vendorInfo = '';
							$arrayReceivingOrBillItems = '';

							$receivingOrBillId = $arrayReceivingOrBill['receiving_id'];
							$receivingOrBillSupplierId = $arrayReceivingOrBill['supplier_id'];
							$receivingOrBillTotal = $arrayReceivingOrBill['total'];
							$receivingOrBillTime = $arrayReceivingOrBill['receiving_time'];

							if ($receivingOrBillTotal >= 0) {
								// Get Location info from database Starts
								$departmentName = '';
								$locationId = $arrayReceivingOrBill['location_id'];
								if ($locationId) {
									$departmentName = $this->_get_DepartmentName_by_locationId($locationId);
								}
								// Get Location Info from database Ends

								if ($receivingOrBillSupplierId) {
									$vendorInfo = $this->Supplier->get_info($receivingOrBillSupplierId);
									if (($vendorInfo) && ($vendorInfo->accounting_id)) {
										$itemVendorId = $vendorInfo->accounting_id;
									}
								}
								$arrayReceivingOrBillItems = $this->Receiving->get_receiving_items($receivingOrBillId)->result_array();

								if ($arrayReceivingOrBillItems && is_array($arrayReceivingOrBillItems) && (count($arrayReceivingOrBillItems) > 0)) {
									$discountAmount = 0;
									foreach ($arrayReceivingOrBillItems as $receivingOrBillItem) {
										$item_info = $this->Item->get_info($receivingOrBillItem['item_id'], false);
										if ($item_info) {
											$accountingId = $item_info->accounting_id;
											$itemUnitPrice = $receivingOrBillItem['item_unit_price'];
											$itemQuanitity = $receivingOrBillItem['quantity_purchased'];
											$discountPer = $receivingOrBillItem['discount_percent'];
											$costPrice = $item_info->cost_price;
											$itemAmount = to_currency_no_money($itemUnitPrice) * $itemQuanitity;
											if ($discountPer > 0) {
												$discountAmount = ((to_currency_no_money($itemUnitPrice) * $itemQuanitity) * $discountPer) / 100;
												$itemAmount = $itemAmount - $discountAmount;
											}
											$line = array(
												'Amount' => $itemAmount,
												'DetailType' => 'ItemBasedExpenseLineDetail',
												'ItemBasedExpenseLineDetail' => array(
													'ItemRef' => array('value' => $accountingId),
													'BillableStatus' => 'NotBillable',
													'UnitPrice' => $costPrice,
													'Qty' => to_quantity($itemQuanitity),
												)
											);
											$lines[] = $line;
										}
									}
								}

								$arrayReceivingOrBillCreate['TxnDate'] = $this->_get_TimeFormat($receivingOrBillTime);
								$arrayReceivingOrBillCreate['Line'] = $lines;
								if ($itemVendorId != '') {
									$arrayReceivingOrBillCreate['VendorRef'] = array('value' => $itemVendorId);
								}
								$arrayReceivingOrBillCreate['TotalAmt'] = to_currency_no_money($arrayReceivingOrBill['subtotal']);

								if ($arrayReceivingOrBill['tax']) {
									$arrayReceivingOrBillCreate['TxnTaxDetail'] = array('TotalTax' => to_currency_no_money($arrayReceivingOrBill['tax']));
								}

								// Code for add department has been start
								if ($departmentName) {
									$currentSecDeptId = $this->_get_DepartmentIdByName_FromQB($departmentName);
									if (($currentSecDeptId) && ($currentSecDeptId != "")) {
										$arrayReceivingOrBillCreate['DepartmentRef'] = array('value' => $currentSecDeptId);
									}
								}
								// Code for add department has been ends

								$purchaseOrderCreate = QBPurchaseOrder::create($arrayReceivingOrBillCreate);
								$purchaseOrderCreateResult = $dataService->Add($purchaseOrderCreate);

								$error = $dataService->getLastError();
								if (!$error) {
								
									$this->_log(lang('common_added').' '.lang('receivings_id').' '.$arrayReceivingOrBill['receiving_id']);
								
									$accountingId = $purchaseOrderCreateResult->Id;
									$this->Receiving->link_qb_receving($receivingOrBillId, $accountingId);
									$billReceiptCreate = QBBill::create($arrayReceivingOrBillCreate);
									$billCreateResult = $dataService->Add($billReceiptCreate);
									$bill_error = $dataService->getLastError();
									if ($bill_error) {
										$xml = simplexml_load_string($bill_error->getResponseBody());
										$error_message = (string)$xml->Fault->Error->Detail;
										$this->_log("*******" . lang('common_EXCEPTION') . ": " . $receivingOrBillId . ' ' . $error_message);
									}
								} else {
									$xml = simplexml_load_string($error->getResponseBody());
									$error_message = (string)$xml->Fault->Error->Detail;
									$this->_log("*******" . lang('common_EXCEPTION') . ": " . $receivingOrBillId . ' ' . $error_message);
								}
							}
						}
					}
				}
				catch(Exception $e)
				{
					$this->_log("*******".lang('common_EXCEPTION')." 2-2 : ".$e->getMessage());
				}
			}
			
			function _log($msg)
			{
				$msg = date(get_date_format().' h:i:s ').': '.$msg."\n"; 
		
				if (is_cli())
				{
					echo $msg;
				}
				$this->log_text.=$msg;
			}
			
			function _save_log()
			{
		    $CI =& get_instance();	
				$CI->load->model("Appfile");
				$this->Appfile->save('quickbooks_log.txt',$this->log_text,'+72 hours');
			}
			
			private function _kill_if_needed()
			{
				if ($this->Appconfig->get_raw_kill_qb_cron())
				{
					if (is_cli())
					{
						echo date(get_date_format().' h:i:s ').': KILLING CRON'."\n";
					}
			
					$this->Appconfig->save('kill_qb_cron',0);
					echo json_encode(array('success' => TRUE, 'cancelled' => TRUE, 'sync_date' => date('Y-m-d H:i:s')));
					$this->_save_log();
					die();
				}
			}
			
			function _update_sync_progress($progress,$message)
			{
				$this->Appconfig->save('qb_sync_percent_complete',$progress);
				$this->Appconfig->save('qb_sync_message', $message ? $message : '');
			}
			
		}

		
?>
