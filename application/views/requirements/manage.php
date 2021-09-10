<?php $this->load->view("partial/header"); ?>
<script type="text/javascript">
	$(document).ready(function() 
	{ 
	    enable_sorting("<?php echo site_url("$controller_name/sorting"); ?>");
	    enable_select_all();
	    enable_checkboxes();
	    enable_row_selection();
      	enable_cleanup(<?php echo json_encode(lang($controller_name."_confirm_cleanup"));?>);
	    enable_search('<?php echo site_url("$controller_name");?>',<?php echo json_encode(lang("common_confirm_search"));?>);
	    
			<?php if(!$deleted) { ?>
				enable_delete(<?php echo json_encode(lang($controller_name."_confirm_delete"));?>,<?php echo json_encode(lang($controller_name."_none_selected"));?>);
			<?php } else { ?>
				enable_delete(<?php echo json_encode(lang($controller_name."_confirm_undelete"));?>,<?php echo json_encode(lang($controller_name."_none_selected"));?>);
			<?php } ?>
		 
		 <?php if ($this->session->flashdata('manage_success_message')) { ?>
 			gritter(<?php echo json_encode(lang('common_success')); ?>, <?php echo json_encode($this->session->flashdata('manage_success_message')); ?>,'gritter-item-success',false,false);
		 <?php } ?>
		console.log(enable_sorting);
	});

	function reload_work_order_table()
	{
		clearSelections();
		$("#table_holder").load("<?php echo site_url("requirements/reload_work_order_table");?>");
	}
	
	$(document).ready(function()
	{	
		$("#technician").select2({dropdownAutoWidth : true});

		$("#sortable").sortable({
			items : '.sort',
			containment: "#sortable",
			cursor: "move",
			handle: ".handle",
			revert: 100,
			update: function( event, ui ) {
				$input = ui.item.find("input[type=checkbox]");
				$input.trigger('change');
			}
		});
		
		$("#sortable").disableSelection();
	
		$(document).on(
		    'click.bs.dropdown.data-api', 
		    '[data-toggle="collapse"]', 
		    function (e) { e.stopPropagation() }
		);
		
		$("#config_columns a").on("click", function(e) {
			e.preventDefault();
			
			if($(this).attr("id") == "reset_to_default")
			{
				//Send a get request wihtout columns will clear column prefs
				$.get("https:\/\/demo.phppointofsale.com\/index.php\/work_orders\/save_column_prefs", function()
				{
					reload_work_order_table();
					var $checkboxs = $("#config_columns a").find("input[type=checkbox]");
					$checkboxs.prop("checked", false);
					
												$("#config_columns a").find('#'+"id").prop("checked", true);
												$("#config_columns a").find('#'+"sale_id").prop("checked", true);
												$("#config_columns a").find('#'+"sale_time").prop("checked", true);
												$("#config_columns a").find('#'+"status").prop("checked", true);
												$("#config_columns a").find('#'+"technician_name").prop("checked", true);
												$("#config_columns a").find('#'+"estimated_repair_date").prop("checked", true);
												$("#config_columns a").find('#'+"first_name").prop("checked", true);
												$("#config_columns a").find('#'+"last_name").prop("checked", true);
												$("#config_columns a").find('#'+"item_name_being_repaired").prop("checked", true);
												$("#config_columns a").find('#'+"email").prop("checked", true);
												$("#config_columns a").find('#'+"phone_number").prop("checked", true);
									});
			}
			
			if(!$(e.target).hasClass("handle"))
			{
				var $checkbox = $(this).find("input[type=checkbox]");
				
				if($checkbox.length == 1)
				{
					$checkbox.prop("checked", !$checkbox.prop("checked")).trigger("change");
				}
			}
			
			return false;
		});
		
		
		$("#config_columns input[type=checkbox]").change(
			function(e) {
				var columns = $("#config_columns input:checkbox:checked").map(function(){
      		return $(this).val();
    		}).get();
				
				$.post("https:\/\/demo.phppointofsale.com\/index.php\/work_orders\/save_column_prefs", {columns:columns}, function(json)
				{
					reload_work_order_table();
				});
				
		});
				
		$('#print_work_order_btn').click(function()
		{
			var selected = get_selected_values();
			
			$(this).attr('href','https://demo.phppointofsale.com/index.php/work_orders/print_work_order/'+selected.join('~'));
		});	

		$('#print_service_tag_btn').click(function()
		{
			var selected = get_selected_values();
			if (selected.length == 0)
			{
				bootbox.alert("You must select at least 1 item to generate barcodes");
				return false;
			}

			var default_to_raw_printing = "";
			if(default_to_raw_printing == "1"){
				$(this).attr('href','https://demo.phppointofsale.com/index.php/work_orders/raw_print_service_tag/'+selected.join('~'));
			}
			else{
				$(this).attr('href','https://demo.phppointofsale.com/index.php/work_orders/print_service_tag/'+selected.join('~'));
			}
		});	

		$("#change_status").change(function(){
			var status = $(this).val();
			if(status != ''){
				bootbox.confirm("¿Está seguro de que desea cambiar el estado de este requerimiento?", function(result)
				{
					if (result)
					{
						$('#grid-loader').show();
						event.preventDefault();
						var selected = get_selected_values();
						
						$.post('<?php echo site_url("requirements/change_status");?>', {requirements_ids : selected,status:status},function(response) {
							$('#grid-loader').hide();
							show_feedback(response.success ? 'success' : 'error', response.message,response.success ? "Success" : "Error");

							//Refresh tree if success
							if (response.success)
							{
								setTimeout(function(){location.href = location.href;},800);
							}
						}, "json");
					}
				});
			}

		});

		$(".excel_export_btn").click(function(e){
			var selected = get_selected_values();
			$(this).attr('href','https://demo.phppointofsale.com/index.php/work_orders/excel_export_selected_rows/'+selected.join('~'));
		});
	});
</script>


<div class="navbar manage_buttons">
	<!-- Css Loader  -->
	<div class="spinner" id="ajax-loader" style="display:none">
	  <div class="rect1"></div>
	  <div class="rect2"></div>
	  <div class="rect3"></div>
	</div>
	
	<div class="manage-row-options hidden">
	
		<div class="email_buttons people">
			<div class="row">
				<div class="col-md-12 pull-left">
					<?php if(!$deleted) { ?>
						<?php if ($this->Employee->has_module_action_permission($controller_name, 'delete', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
						<?php echo anchor("$controller_name/delete",
							'<span class="ion-trash-a"></span> <span class="hidden-xs">'.lang('common_delete').'</span>'
							,array('id'=>'delete', 'class'=>'btn btn-red btn-lg disabled delete_inactive ','title'=>lang("common_delete"))); ?>
						<?php } ?>

						<a href="#" class="btn btn-lg btn-clear-selection btn-warning"><span class="ion-close-circled"></span> <span class="hidden-xs"><?php echo lang('common_clear_selection'); ?></span></a>
						
						<?php } else { ?>
							<?php if ($this->Employee->has_module_action_permission($controller_name, 'delete', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
							<?php echo anchor("$controller_name/undelete",
									'<span class="ion-trash-a"></span> '.'<span class="hidden-xs">'.lang("common_undelete").'</span>',
									array('id'=>'delete','class'=>'btn btn-green btn-lg disabled delete_inactive','title'=>lang("common_undelete"))); ?>
							<?php } ?>

							<a href="#" class="btn btn-lg btn-clear-selection btn-warning"><span class="ion-close-circled"></span> <?php echo lang('common_clear_selection'); ?></a>
						<?php } ?>
						<select name="change_status" class="" id="change_status">
							<option value="" selected="selected">Cambio de Estado</option>
							<option value="1">Nuevo</option>
							<option value="2">En Progreso</option>
							<option value="3">Esperando Confirmación</option>
							<option value="4">Completado</option>
							<option value="5">Cancelado</option>
						</select>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-6 col-sm-6 col-xs-6">
			<?php echo form_open("$controller_name/search",array('id'=>'search_form', 'autocomplete'=> 'off')); ?>
				<div class="search no-left-border">
					<input type="text" class="form-control" name ='search' id='search' value="<?php echo H($search); ?>" placeholder="<?php echo $deleted ? lang('common_search_deleted') : lang('common_search'); ?> <?php echo lang('module_'.$controller_name); ?>"/>
				</div>
				<div class="clear-block <?php echo ($search=='') ? 'hidden' : ''  ?>">
					<a class="clear" href="<?php echo site_url($controller_name.'/clear_state'); ?>">
						<i class="ion ion-close-circled"></i>
					</a>	
				</div>

			<?php echo form_close() ?>
			
		</div>
		<div class="col-md-6 col-sm-6 col-xs-6">
			<div class="buttons-list">
				<div class="pull-right-btn">
					<?php if ($deleted) 
					{
						echo 
						anchor("$controller_name/toggle_show_deleted/0",
							'<span class="ion-android-exit"></span> <span class="hidden-xs">'.lang('common_done').'</span>',
							array('class'=>'btn btn-primary btn-lg toggle_deleted','title'=> lang('common_done')));
					}	
					?>
					<?php if ($this->Employee->has_module_action_permission($controller_name, 'add_update', $this->Employee->get_logged_in_employee_info()->person_id) && !$deleted) {?>				
						<?php echo 
							anchor("$controller_name/view/-1/",
							'<span class="ion-plus-round"></span> '.'<span class="hidden-xs">'.lang($controller_name.'_new').'</span>',
							array('class'=>'btn btn-primary btn-lg', 
								'title'=>lang($controller_name.'_new')));
						?>

					<?php } ?>
					<?php if(!$deleted) { ?>					
					<div class="piluku-dropdown btn-group">
						<button type="button" class="btn btn-more dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<span class="hidden-xs ion-android-more-horizontal"> </span>
						<i class="visible-xs ion-android-more-vertical"></i>
						</button>
						<ul class="dropdown-menu" role="menu">
							<?php if ($this->Employee->has_module_action_permission($controller_name, 'delete', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
								<li>
									<?php echo anchor("$controller_name/toggle_show_deleted/1", '<span class="ion-trash-a"> '.lang($controller_name."_manage_deleted").'</span>',
									array('class'=>'toggle_deleted','title'=> lang($controller_name."_manage_deleted"))); ?>
								</li>
							<?php }?>
						</ul>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>
									
<div class="container-fluid">
	<div class="row manage-table">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<?php echo ($deleted ? lang('common_deleted').' ' : '').lang('module_'.$controller_name); ?>
					<span title="<?php echo $total_rows; ?> total <?php echo $controller_name?>" class="badge bg-primary tip-left" id="manage_total_items"><?php echo $total_rows; ?></span>
					<span class="panel-options custom">
						<div class="pagination pagination-top hidden-print  text-center" id="pagination_top">
							<?php echo $pagination;?>		
						</div>
					</span>
				</h3>

			</div>
			<div class="panel-body nopadding table_holder table-responsive" >
				<?php echo $manage_table; ?>
			</div>
		</div>
	</div>
</div>

<div class="row pagination hidden-print alternate text-center" id="pagination_bottom" >
	<?php echo $pagination;?>
</div>

</div>
<?php $this->load->view("partial/footer"); ?>