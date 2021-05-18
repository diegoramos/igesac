<?php
class Quickbooks extends CI_Model
{
	function get_sync_progress()
	{
		return array('percent_complete' => $this->config->item('qb_sync_percent_complete'), 'message'=> $this->config->item('qb_sync_message'));
	}
	
	function reset_qb()
	{
		$this->Appconfig->delete('quickbooks_access_token');
		$this->Appconfig->delete('quickbooks_refresh_token');
		$this->Appconfig->delete('quickbooks_realm_id');
		$this->Appconfig->delete('revenue_account_id');
		$this->Appconfig->delete('asset_account_id');
		$this->Appconfig->delete('expense_account_id');
		$this->Appconfig->delete('refund_credit_account');
		$this->Appconfig->delete('refund_cash_account');
		$this->Appconfig->delete('refund_debit_card_account');
		$this->Appconfig->delete('refund_credit_card_account');
		$this->Appconfig->delete('refund_check_account');
		$this->Appconfig->delete('refund_deposit_account');
		$this->Appconfig->delete('expense_account');
		$this->Appconfig->delete('commission_credit_account');
		$this->Appconfig->delete('commission_debit_account');
		$this->Appconfig->delete('store_account');
		$this->Appconfig->delete('default_customer_id');
		$this->Appconfig->delete('expense_bank_credit_account');
		$this->Appconfig->delete('qb_setup_date');
		$this->Appconfig->delete('default_tax_id');
		$this->Appconfig->delete('default_store_account_tax_id');
		$this->Appconfig->delete('default_country_id');
		
		$this->db->update('customers', array(
			'accounting_id' => NULL
		));

		$this->db->update('employees', array(
			'accounting_id' => NULL
		));

		$this->db->update('suppliers', array(
			'accounting_id' => NULL
		));

		$this->db->update('expenses', array(
			'accounting_id' => NULL
		));

		$this->db->update('inventory', array(
			'inventory_sync_needed' => 0
		));
		
		$this->db->update('people', array(
			'last_modified' => NULL
		));
		
		$this->db->update('items', array(
			'accounting_id' => NULL
		));

		$this->db->update('item_kits', array(
			'accounting_id' => NULL
		));

		$this->db->update('categories', array(
			'accounting_id' => NULL
		));
		$this->db->update('location_items', array(
			'accounting_product_quantity' => NULL
		));

		$this->db->update('sales', array(
			'accounting_id' => NULL,
			'last_modified' => NULL,
		));
		$this->db->update('receivings', array(
			'accounting_id' => NULL,
			'last_modified' => NULL,
		));
		
	}
	
}
?>