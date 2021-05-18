<?php $this->load->view("partial/header"); ?>
<style>
  .muestra {
    display: block;
  }

  .nomuestra {
    display: none;
  }
</style>

<nav class="navbar manage_buttons">

  <!-- Css Loader  -->
  <div class="spinner" id="ajax-loader" style="display:none">
    <div class="rect1"></div>
    <div class="rect2"></div>
    <div class="rect3"></div>
  </div>

  <div class="manage-row-options hidden">

    <div class="email_buttons items">
      <div class="row">

        <div class="col-md-12 pull-left">

          <a href="http://localhost/jigeneral/index.php/items/bulk_edit/" id="bulk_edit" data-toggle="modal" data-target="#myModal" class="btn btn-primary btn-lg  disabled" title="Edición de múltiples artículos"><span class="">Editar</span></a>
          <a href="#" class="btn btn-lg btn-select-all btn-primary"><span class="ion-android-checkbox-outline"></span> <span class="hidden-xs">Seleccionar todo</span></a>

          <div class="btn-group piluku-dropdown" role="group">
            <button class="btn btn-primary btn-lg dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
              Etiquetas <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
              <li><a href="http://localhost/jigeneral/index.php/items/generate_barcode_labels" id="generate_barcode_labels">Impresora de etiquetas</a></li>
              <li><a href="http://localhost/jigeneral/index.php/items/generate_barcodes" id="generate_barcodes">Impresora estándar</a></li>
            </ul>
          </div>

          <a href="#" class="btn btn-lg btn-clear-selection btn-warning"><span class="ion-close-circled"></span> <span class="hidden-xs">Eliminar selección</span></a>


          <a href="http://localhost/jigeneral/index.php/items/delete" id="delete" class="btn btn-red btn-lg" title="Borrar"><span class="ion-trash-a"></span> <span class="hidden-xs">Borrar</span></a>
        </div><!-- end col-->
      </div> <!-- end row -->


    </div>
  </div>

  <div class="row">

    <div class="col-md-9 col-sm-10 col-xs-10">

      <form action="<?= site_url() ?>/sunat/list" id="search_form" autocomplete="off" class="" method="get" accept-charset="utf-8">
        <div class="search search-items no-left-border">
          <ul class="list-inline">
            <li>
              &nbsp;
              <span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span><input type="text" class="form-control ui-autocomplete-input" name="search" id="search" value="<?= $search ?>" placeholder="Buscar Guia" autocomplete="off">
            </li>
            <li>
              <button type="submit" class="btn btn-primary btn-lg"><span class="ion-ios-search-strong"></span><span class="hidden-xs hidden-sm"> Buscar</span></button>
            </li>
            <li>
              <div class="clear-block items-clear-block hidden">
                <a class="clear" href="http://localhost/jigeneral/index.php/items/clear_state">
                  <i class="ion ion-close-circled"></i>
                </a>
              </div>
            </li>
          </ul>
        </div>
      </form>
    </div>
    <div class="col-md-3 col-sm-2 col-xs-2">
      <div class="buttons-list items-buttons">
        <div class="pull-right-btn">
          <div class="spinner hidden" id="ajax-loader">
            <div class="rect1"></div>
            <div class="rect2"></div>
            <div class="rect3"></div>
          </div>


          <a href="<?php echo site_url(); ?>/sunat/type/guia" class="btn btn-primary btn-lg hidden-sm hidden-xs" title="Nueva guia"><span class="ion-plus"></span> Nuevo guia</a>
          <div class="piluku-dropdown btn-group">
            <!--<button type="button" class="btn btn-more dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
              <span class="hidden-xs ion-android-more-horizontal"> </span>
              <i class="visible-xs ion-android-more-vertical"></i>
            </button>-->
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>
<div class="container-fluid">
  <div class="row manage-table">
    <div class="panel panel-piluku">
      <div class="panel-heading">
        <h3 class="panel-title">
          Guias <span title="4 total items" class="badge bg-primary tip-left" id="manage_total_items"><?= $total_rows ?></span>
          <form id="config_columns">
            <div class="piluku-dropdown btn-group table_buttons pull-right m-left-20">
              <!--<button type="button" class="btn btn-more dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <i class="ion-gear-a"></i>
              </button> -->
            </div>
          </form>
          <div class="panel-options custom">
            <div class="pagination pagination-top hidden-print  text-center" id="pagination_top"></div>
          </div>
        </h3>
      </div>
      <div class="panel-body nopadding table_holder table-responsive" id="table_holder">
        <table class="table tablesorter table-hover" id="sortable_table">
          <thead>
            <tr>
              <th data-sort-column="" class="leftmost"><input type="checkbox" id="select_all"><label for="select_all"><span></span></label></th>
              <th data-sort-column="">Comportamiento</th>
              <th data-sort-column="serie">Serie</th>
              <th data-sort-column="item_number" class="header headerSortUp ion-arrow-up-b">Fecha emision</th>
              <th data-sort-column="name">Cliente</th>
              <th data-sort-column="unity">Unidad medida</th>
              <th data-sort-column="quantity">Peso bruto</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($table_manage as $key => $item) { ?>
              <tr style="cursor: pointer;">
                <td><input type="checkbox" id="item_<?= $item->id ?>" value="<?= $item->id ?>"><label for="item_<?= $item->id ?>"><span></span></label></td>
                <td>
                  <div class="piluku-dropdown dropdown btn-group table_buttons upordown">
                    <a href="javascript:;" aria-valuetext="<?= $item->id ?>" role="button" class="btn btn-more viewss_action">Ver</a>
                    <button type="button" class="btn btn-more dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                      <i class="ion-more"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left " role="menu">
                      <li><a href="javascript:;" class="text-danger" title="Inventario"><i class="ion-trash-b"></i> Anular guia</a></li>
                      <li><a href="javascript:;" class="" title="Códigos de barras"><i class="ion-android-print"></i> Imprimir</a></li>
                    </ul>
                  </div>
                </td>
                <td><?= $item->serie ?></td>
                <td><?= $item->fecha_emision ?></td>
                <td><a class="" href="javascript:;"><?= $item->cliente_nombre ?></a></td>
                <td><?= $item->unidad_medida ?></td>
                <td><?= $item->peso_total ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
<div class="text-center">
  <div class="row pagination hidden-print alternate text-center" id="pagination_bottom">
    <?php echo $pagination; ?>
  </div>
</div>
<div class="modal fade" id="modal_pdf" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" data-keyboard="false" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-sx">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Documento</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true" class="ti-close"></span></button>
      </div>
      <div class="modal-body" id="cuerpo_pdf">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view("partial/footer"); ?>