
			<div id="footers" class="col-md-12 hidden-print text-center">
				<?php echo lang('common_please_visit_my'); ?> 
					<a tabindex="-1" href="#" target="_blank"><?php echo lang('common_website'); ?></a> <?php echo lang('common_learn_about_project'); ?>.
					<span class="text-info"><?php echo lang('common_you_are_using_phppos')?> <span class="badge bg-primary"> <?php echo APPLICATION_VERSION; ?></span></span> <?php echo lang('common_built_on'). ' '.BUILT_ON_DATE;?>
			</div>
		</div>
		<!---content -->
	</div>
	<script src="<?php echo base_url().'assets/js/guia.js?'.ASSET_TIMESTAMP;?>" type="text/javascript" charset="UTF-8"></script>
	<script>
		$(document).on('click', '[data-toggle="lightbox"]', function(event) {
			event.preventDefault();
			$(this).ekkoLightbox();
		});
	</script>
</body>
</html>