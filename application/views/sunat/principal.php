<?php $this->load->view("partial/header"); ?>
<div id="sales_page_holder">
    <div id="sale-grid-big-wrapper" class="clearfix register">
        <div class="row">
            <div class="clearfix" id="category_item_selection_wrapper">
                <div class="">
                    <div class="spinner" id="grid-loader" style="display:none">
                        <div class="rect1"></div>
                        <div class="rect2"></div>
                        <div class="rect3"></div>
                    </div>

                    <div class="text-center">
                        <div id="grid_selection" class="btn-group" role="group">
                            <a href="javascript:void(0);" class="<?php echo $this->config->item('default_type_for_grid') == 'categories' || !$this->config->item('default_type_for_grid') ? 'btn active' : ''; ?> btn btn-grid" id="by_category"><?php echo lang('reports_categories') ?></a>
                            <a href="javascript:void(0);" class="<?php echo $this->config->item('default_type_for_grid') == 'tags' ? 'btn active' : ''; ?> btn btn-grid" id="by_tag"><?php echo lang('common_tags') ?></a>
                        </div>
                    </div>

                    <div id="grid_breadcrumbs"></div>
                    <div id="category_item_selection" class="row register-grid"></div>
                    <div class="pagination hidden-print alternate text-center"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="register_container" class="sales clearfix">
        <div class="row register">
            <div class="col-lg-12 col-md-7 col-sm-12 col-xs-12 no-padding-right no-padding-left">
                <!-- Register Items. @contains : Items table -->
                <div class="register-box register-items paper-cut">
                    <div class="register-items-holder">
                        <div class="register-items-header">
                            <div class="row">
                                <div class="col-md-4">
                                    <a href="<?=site_url()?>/sunat/list">
                                        <button class="btn-more btn-warning" style="color:#000;font-weight: bold;width: 100%;">Guia de remision remitente</button>
                                    </a>
                                </div>
                                <!--
                                <div class="col-md-4">
                                    <a href="http://localhost/jigeneral/index.php/sunat">
                                        <button class="btn-more btn-info" style="color:#000;font-weight: bold;width: 100%;">Nota credito</button>
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="http://localhost/jigeneral/index.php/sunat">
                                        <button class="btn-more btn-warning" style="color:#000;font-weight: bold;width: 100%;">Nota debito</button>
                                    </a>
                                </div>
-->
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Store Account Payment Mode -->
            </div>
        </div>
    </div>
</div>

                       <!-- <table id="register" class="table table-hover">
                            <thead>
                                <tr class="">
                                    <th></th>
                                    <th class="item_name_heading"><?php echo lang('sales_item_name'); ?></th>
                                    <th class="sales_price"><?php echo lang('common_price'); ?></th>
                                    <th class="sales_quantity"><?php echo lang('common_quantity'); ?></th>
                                    <th class="sales_discount"><?php echo lang('common_discount_percent'); ?></th>
                                    <th><?php echo lang('common_total'); ?></th>
                                </tr>
                            </thead>

                            <tbody class="register-item-content">
                            </tbody>
                        </table>-->

<?php $this->load->view("partial/footer"); ?>