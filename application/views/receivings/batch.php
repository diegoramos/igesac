<?php $this->load->view("partial/header"); ?>
	
<div class="row" id="form">
	<div class="spinner" id="grid-loader" style="display:none">
	  <div class="rect1"></div>
	  <div class="rect2"></div>
	  <div class="rect3"></div>
	</div>
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<?php echo lang('import_export_batch_receivings'); ?>&nbsp;<sup><?php echo lang('common_mass_import_from_excel'); ?></sup>
			</div>
			<div class="panel-body">
				<?php echo form_open_multipart('receivings/do_excel_import/',array('id'=>'item_form','class'=>'form-horizontal')); ?>
				<h3><?php echo lang('common_step_1'); ?>: </h3>
				<p><?php echo lang('download_file_batch_receivings'); ?></p>
				<div class="form-group">
					
					<div class="col-sm-9 col-md-9 col-lg-10">
						<a class="btn btn-green btn-sm" href="<?php echo site_url('receivings/excel'); ?>"><?php echo lang('download_excel_template_batch_receivings'); ?></a>
						<?php //echo lang('common_or');?>
					</div>
				</div>
			
				<h3><?php echo lang('common_step_2'); ?>: </h3>
				<p><?php echo lang('import_batch_receivings'); ?></p>	
				<ul class="text-danger" id="error_message_box"></ul>					
				<div class="form-group">
						<?php echo form_label(lang('common_file_path').':', 'file_path',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<input type="file" name="file_path" id="file_path" class="filestyle" data-icon="false">  	
						</div>
					</div>
				<div class="form-actions pull-right">
					<?php echo form_submit(array(
						'name'=>'submitf',
						'id'=>'submitf',
						'value'=>lang('common_save'),
						'class'=>'btn btn-primary')
						); ?>
				</div>
				<?php echo form_close() ?>
			</div>
		</div>	
	</div>
</div>
</div>
		
<script type='text/javascript'>
	//validation and submit handling
	$(document).ready(function()
	{	
		var submitting = false;
		$('#item_form').validate({
			submitHandler:function(form)
			{
				if (submitting) return;
				submitting = true;
$('#grid-loader').show();
				$(form).ajaxSubmit({
					success:function(response)
					{
$('#grid-loader').hide();
						if(!response.success)
						{ 
							show_feedback('error', response.message, <?php echo json_encode(lang('common_error')); ?>);
						}
						else
						{
							show_feedback('success', response.message, <?php echo json_encode(lang('common_success')); ?>);
							window.location.href="<?php echo site_url('receivings'); ?>";
						}
						submitting = false;
					},
					dataType:'json',
					resetForm: true
				});
			},
			errorLabelContainer: "#error_message_box",
	 		wrapper: "li",
			highlight:function(element, errorClass, validClass) {
				$(element).parents('.form-group').addClass('error');
			},
			unhighlight: function(element, errorClass, validClass) {
				$(element).parents('.form-group').removeClass('error');
				$(element).parents('.form-group').addClass('success');
			},
			rules: 
			{
				file_path:"required"
	   		},
			messages: 
			{
	   			file_path:<?php echo json_encode(lang('common_full_path_to_excel_required')); ?>
			}
		});
	});
</script>
<?php $this->load->view("partial/footer"); ?>