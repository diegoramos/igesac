<?php $this->load->view("partial/header"); ?>

<div class="row" id="form">
	
	<div class="spinner" id="grid-loader" style="display:none">
	  <div class="rect1"></div>
	  <div class="rect2"></div>
	  <div class="rect3"></div>
	</div>
	<div class="col-md-12">
		 <?php echo form_open('requirements/save/'.$requirement_info->id,array('id'=>'requirements_form','class'=>'form-horizontal')); ?>
		<div class="panel panel-piluku">
			<div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="ion-edit"></i> <?php if(!$requirement_info->id) { echo lang('requirements_new'); } else { echo lang('requirements_update'); } ?>
								<small>(<?php echo lang('common_fields_required_message'); ?>)</small>
	                </h3>
						 
            </div>
			<div class="panel-body">
			<h5><?php echo lang("requirements_basic_information"); ?></h5>

				<div class="form-group">
				<?php echo form_label(lang('requirements_num').':', 'requirements_num_input', array('class'=>'required col-sm-3 col-md-3 col-lg-2 control-label')); ?>
				<div class="col-sm-9 col-md-9 col-lg-10 cmp-inps">
					<?php echo form_input(array(
						'class'=>'form-control form-inps',
						'name'=>'requirement_num',
						'id'=>'requirements_num_input',
						'value'=>$requirement_info->requirement_num)
					);?>
					</div>
				</div>

				<div class="form-group">
				<?php echo form_label(lang('proyect_name').':', 'requirements_name_input', array('class'=>'required col-sm-3 col-md-3 col-lg-2 control-label')); ?>
				<div class="col-sm-9 col-md-9 col-lg-10 cmp-inps">
					<?php echo form_input(array(
						'class'=>'form-control form-inps',
						'name'=>'proyect_name',
						'id'=>'requirements_name_input',
						'value'=>$requirement_info->proyect_name)
					);?>
					</div>
				</div>
				<div><?php echo date(get_date_format(), strtotime($requirement_info->requirement_date)); ?></div>
				<div class="form-group p-lr-15">
					<?php echo form_label(lang('requirements_date').':', 'requirements_date_input', array('class'=>'required col-sm-3 col-md-3 col-lg-2 control-label')); ?>
				  	<div class="input-group date">
				    	<span class="input-group-addon"><i class="ion-calendar"></i></span>
				    	<?php echo form_input(array(
				      		'name'=>'requirement_date',
							'id'=>'requirements_date_input',
							'class'=>'form-control form-inps datepicker',
							'value'=>$requirement_info->requirement_date ? date(get_date_format(), strtotime($requirement_info->requirement_date)) : date(get_date_format()))
				    	);?> 
				    </div>  
				</div>

				<div class="form-group">
					<?php echo form_label(lang('employee_recv').':', 'employee_id', array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10 cmp-inps">
						<?php echo form_dropdown('employee_id',$employees, $requirement_info->employee_id ? $requirement_info->employee_id : $logged_in_employee_id , 'id="employee_id" class=""'); ?>
					</div>
				</div>


				<div class="form-group">
					<?php echo form_label(lang('common_approved_by').':', 'approved_employee_id', array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10 cmp-inps">
						<?php echo form_dropdown('approved_employee_id',$employees, $requirement_info->employee_id ? $requirement_info->employee_id : $logged_in_employee_id , 'id="approved_employee_id" class=""'); ?>
					</div>
				</div>

				<?php echo form_hidden('redirect', $redirect_code); ?>

			<div class="form-actions pull-right">
				<?php
					echo form_submit(array(
						'name'=>'submitf',
						'id'=>'submitf',
						'value'=>lang('common_save'),
						'class'=>'btn btn-primary btn-lg submit_button floating-button btn-large')
						);
				?>
			</div>
		</div>
	</div>
	<?php echo form_close(); ?>
</div>
</div>
</div>

<script type='text/javascript'>
var submitting = false;
//validation and submit handling
$(document).ready(function()
{
	        	
	$('#requirements_form').validate({
		ignore: ':hidden:not([class~=selectized]),:hidden > .selectized, .selectize-control .selectize-input input',
		submitHandler:function(form)
	{

	$('#grid-loader').show();
			if (submitting) return;
			submitting = true;
			$(form).ajaxSubmit({
			success:function(response)
			{

	$('#grid-loader').hide();
				submitting = false;
				
				show_feedback(response.success ? 'success' : 'error',response.message, response.success ? <?php echo json_encode(lang('common_success')); ?>  : <?php echo json_encode(lang('common_error')); ?>);
				
				if(response.redirect==1 && response.success)
				{ 
					$.post('<?php echo site_url("requirements");?>', {requirement: response.id}, function()
					{
						window.location.href = '<?php echo site_url('requirements'); ?>'
					});					
				}
				if(response.redirect==2 && response.success)
				{ 
					window.location.href = '<?php echo site_url('requirements'); ?>'
				}

			},
			
			<?php if(!$requirement_info->id) { ?>
			resetForm: true,
			<?php } ?>
			dataType:'json'
		});

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
        requirement_num: "required",
        proyect_name: "required",
        requirements_date: "required",
		},
		messages:
		{
     		requirement_num: <?php echo json_encode(lang('expenses_type_required')); ?>,
     		proyect_name: <?php echo json_encode(lang('expenses_description_required')); ?>,
     		requirements_date: <?php echo json_encode(lang('expenses_date_required')); ?>,
		}
	});
});

date_time_picker_field($('.datepicker'), JS_DATE_FORMAT);

$("#employee_id").select2();
$("#approved_employee_id").select2();
</script>
<?php $this->load->view('partial/footer')?>
