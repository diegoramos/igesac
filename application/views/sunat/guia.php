<?php $this->load->view("partial/header"); ?>
<style>
    .muestra {
        display: block;
    }

    .nomuestra {
        display: none;
    }
</style>
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
            <div class="col-md-1"></div>
            <div class="col-lg-10 col-md-7 col-sm-12 col-xs-12 no-padding-right no-padding-left">
                <!-- Register Items. @contains : Items table -->
                <div class="register-box register-items-form">
                    <div id="itemForm" class="item-form">
                        <form action="http://localhost/jigeneral/index.php/sales/add" id="add_item_form" class="form-inline" autocomplete="off" method="post" accept-charset="utf-8">
                            <h5 class="register-items-header" style="color:black;font-weight: bold;">Nueva Guía de Remisión</h5>
                            <div class="row">
                                <div class="panel-group" id="accordion">
                                    <div class="panel panel-primary">
                                        <div class="panel-heading">
                                            <h4 class="panel-title">
                                                <a style="cursor: pointer;" id="boton1" onclick="bloque(1,this)" aria="true" href="javascript:;">Principal</a>
                                            </h4>
                                        </div>
                                        <div id="collapse1" class="muestra">
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="local">Establecimiento</label>
                                                        <select name="local" id="local" >
                                                            <option value="">Seleccionar</option>
                                                            <?php if (isset($location_id)) { ?>
                                                                <option value="<?= isset($location_id) ? $location_id : '' ?>"><?= isset($name) ? $name : '' ?></option>
                                                            <?php } ?>
                                                        </select>
                                                        <span for="local" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="serie">Serie </label>
                                                        <select name="serie" id="serie" >
                                                            <option value="">Seleccionar</option>
                                                        </select>
                                                        <span for="serie" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="fecha_emision">Fecha de emisión</label>
                                                        <input name="fecha_emision" id="fecha_emision" value="<?= date('d/m/Y') ?>" type="text" placeholder="Fecha de emision" style="width: 100%;border-radius: 10px;" class="form-control input-sm fecha_formato campo">
                                                        <span for="fecha_emision" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="cliente">Cliente</label>
                                                        <input name="cliente" id="cliente" type="text" style="width: 100%;border-radius: 10px;" class="form-control campo">
                                                        <input type="hidden" id="cliente_id" name="cliente_id">
                                                        <span for="cliente" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="modalida_traslado">Modo de translado</label>
                                                        <select name="modalida_traslado" id="modalida_traslado">
                                                            <option value="">Seleccionar</option>
                                                            <option value="01">Transporte público</option>
                                                            <option value="02">Transporte privado</option>
                                                        </select>
                                                        <span for="modalida_traslado" class="text-danger"></span>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="exampleInputEmail1">Motivo de translado</label>
                                                        <select name="motivo_traslado" id="motivo_traslado" >
                                                            <option value="">Seleccionar</option>
                                                            <option value="01">Venta</option>
                                                            <option value="14">Venta sujeta a confirmacion del comprador </option>
                                                            <option value="02">Compra</option>
                                                            <option value="04">Traslado entre establecimientos de la misma empresa</option>
                                                            <option value="18">Traslado emisor itinerante cp</option>
                                                            <option value="08">Importación</option>
                                                            <option value="09">Exportación</option>
                                                            <option value="19">Traslado a zona primaria</option>
                                                            <option value="13">Otros</option>
                                                        </select>
                                                        <span for="motivo_traslado" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="fecha_traslado">Fecha de traslado </label>
                                                        <input name="fecha_traslado" id="fecha_traslado" value="<?= date('d/m/Y') ?>" type="text" style="width: 100%;border-radius: 10px;" placeholder="Fecha de traslado" class="form-control fecha_formato campo">
                                                        <span for="fecha_traslado" class="text-danger"></span>

                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="codigo_puerto">Codigo del Puerto</label>
                                                        <input name="codigo_puerto" id="codigo_puerto" type="text" style="width: 100%;border-radius: 10px;" placeholder="Codigo Del Puerto" class="form-control campo">
                                                        <span for="codigo_puerto" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="transbordo">Transbordo</label>
                                                        <br>
                                                        <input class="" type="radio" name="transbordo" id="trasnbordo1" value="1"><label for="trasnbordo1"><span></span></label>Si
                                                        <input class="" type="radio" name="transbordo" id="trasnbordo2" value="0"><label for="trasnbordo2"><span></span></label>No
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="unidad_medida">Unidad de medida</label>
                                                        <select name="unidad_medida" id="unidad_medida">
                                                            <?php foreach ($units as $key => $value) { ?>
                                                                <option value="<?= $key ?>"><?= $value ?></option>
                                                            <?php } ?>
                                                        </select>
                                                        <span for="unidad_medida" class="text-danger"></span>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="peso_total">Peso total</label>
                                                        <input name="peso_total" id="peso_total" type="number" min="0" style="width: 100%;border-radius: 10px;" class="form-control campo">
                                                        <span for="peso_total" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="numero_paquetes" style="font-size: 12px;">Número de paquetes</label>
                                                        <input name="numero_paquetes" id="numero_paquetes" type="number" min="0" style="width: 100%;border-radius: 10px;" class="form-control campo">
                                                        <span for="numero_paquetes" class="text-danger"></span>

                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="numero_contenedor" style="font-size: 11px;">Número de contenedor</label>
                                                        <input name="numero_contenedor" id="numero_contenedor" type="number" style="width: 100%;border-radius: 10px;" class="form-control campo">
                                                        <span for="numero_contenedor" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-6 form-group">
                                                        <label class="control-label" for="observacion">Observaciones</label>
                                                        <textarea name="observacion" id="observacion" placeholder="Observaciones" style="width: 100%;border-radius: 10px;" class="form-control"></textarea>
                                                        <span for="observacion" class="text-danger"></span>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-8 form-group">
                                                        <label class="control-label" for="descripcion">Descripción de motivo de traslado</label>
                                                        <textarea name="descripcion" id="descripcion" placeholder="Descripcion de motivo de traslado..." style="width: 100%;border-radius: 10px;" class="form-control"></textarea>
                                                        <span for="descripcion" class="text-danger"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel panel-primary">
                                        <div class="panel-heading">
                                            <h4 class="panel-title">
                                                <a style="cursor: pointer;" id="boton2" onclick="bloque(2,this)" aria="false" href="javascript:;">Datos envío</a>
                                            </h4>
                                        </div>
                                        <div id="collapse2" style="display: none;">
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <h5>Dirección partida</h5>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="partida_departamento">Departamento</label>
                                                        <!--<input type="text" name="partida_departamento" id="partida_departamento" class="form-control campo" style="width: 100%;border-radius: 10px;">-->
                                                        <select name="partida_departamento" id="partida_departamento" >
                                                            <option value="">Seleccionar</option>
                                                            <?php foreach ($departamentos as $key => $value) {  ?>
                                                                        <option value="<?= $value->departamento ?>"><?= $value->departamento ?></option>
                                                            <?php } ?>
                                                        </select>
                                                        <span for="partida_departamento" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="partida_provincia">Provincia</label>
                                                        <!--<input type="text" name="partida_provincia" id="partida_provincia" class="form-control campo" style="width: 100%;border-radius: 10px;">-->
                                                        <select name="partida_provincia" id="partida_provincia" >
                                                            <option value="">Seleccionar</option>
                                                        </select>
                                                        <span for="partida_provincia" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="partida_distrito">Distrito</label>
                                                        <!--<input type="text" name="partida_distrito" id="partida_distrito" class="form-control campo" style="width: 100%;border-radius: 10px;">-->
                                                        <select name="partida_distrito" id="partida_distrito" >
                                                            <option value="">Seleccionar</option>
                                                        </select>
                                                        <span for="partida_distrito" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="partida_direccion">Dirección</label>
                                                        <input type="text" name="partida_direccion" id="partida_direccion" style="width: 100%;border-radius: 10px;" placeholder="Direccion" class="form-control campo">
                                                        <span for="partida_direccion" class="text-danger"></span>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <h5>Datos llegada</h5>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="llegada_departamento">Departamento</label>
                                                        <!--<input type="text" name="llegada_departamento" id="llegada_departamento" class="form-control campo" style="width: 100%;border-radius: 10px;">-->
                                                            <select name="llegada_departamento" id="llegada_departamento" >
                                                            <option value="">Seleccionar</option>
                                                            <?php foreach ($departamentos as $key => $value) {  ?>
                                                                        <option value="<?= $value->departamento ?>"><?= $value->departamento ?></option>
                                                            <?php } ?>
                                                            </select>
                                                        <span for="llegada_departamento" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label class="control-label" for="llegada_provincia">Provincia</label>
                                                        <!--<input type="text" name="llegada_provincia" id="llegada_provincia" class="form-control campo" style="width: 100%;border-radius: 10px;">-->
                                                        <select name="llegada_provincia" id="llegada_provincia" >
                                                            <option value="">Seleccionar</option>
                                                        </select>
                                                        <span for="llegada_provincia" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="llegada_distrito">Distrito</label>
                                                        <!--<input type="text" name="llegada_distrito" id="llegada_distrito" class="form-control campo" style="width: 100%;border-radius: 10px;">-->
                                                        <select name="llegada_distrito" id="llegada_distrito" >
                                                            <option value="">Seleccionar</option>
                                                        </select>
                                                        <span for="llegada_distrito" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="llegada_direccion">Dirección</label>
                                                        <input name="llegada_direccion" id="llegada_direccion" type="text" style="width: 100%;border-radius: 10px;" placeholder="Direccion" class="form-control campo">
                                                        <span for="llegada_direccion" class="text-danger"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel panel-primary">
                                        <div class="panel-heading">
                                            <h4 class="panel-title">
                                                <a style="cursor: pointer;" id="boton3" onclick="bloque(3,this)" aria="false" href="javascript:;">Datos transporte</a>
                                            </h4>
                                        </div>
                                        <div id="collapse3" style="display: none;" class="nomuestra">
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <h5>Datos transportista</h5>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="transortista_tipodocumento">Tipo Doc. Identidad </label>
                                                        <select name="transortista_tipodocumento" id="transortista_tipodocumento" class="form-control" style="width: 100%;border-radius: 10px;">
                                                            <option value="">Seleccionar</option>
                                                            <option value="1">Doc. nacional de identidad</option>
                                                            <option value="4">Carnet de extranjeria</option>
                                                            <option value="6">Reg. unico de contribuyentes</option>
                                                            <option value="7">Pasaporte</option>
                                                        </select>
                                                        <span for="transortista_tipodocumento" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="transortista_numero">Número</label>
                                                        <input name="transortista_numero" id="transortista_numero" type="text" style="width: 100%;border-radius: 10px;" placeholder="Número" class="form-control campo">
                                                        <span for="transortista_numero" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="transortista_razonSocial">Nombre y/o razón social</label>
                                                        <input name="transortista_razonSocial" id="transortista_razonSocial" type="text" style="width: 100%;border-radius: 10px;" placeholder="Nombre y/o razón social..." class="form-control campo">
                                                        <span for="transortista_razonSocial" class="text-danger"></span>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <h5>Datos conductor</h5>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="conductor_tipodocumento">Tipo Doc. Identidad </label>
                                                        <select name="conductor_tipodocumento" id="conductor_tipodocumento" class="form-control" style="width: 100%;border-radius: 10px;" id="exampleFormControlSelect1">
                                                            <option value="">Seleccionar</option>
                                                            <option value="1">Doc. nacional de identidad</option>
                                                            <option value="4">Carnet de extranjeria</option>
                                                            <option value="6">Reg. unico de contribuyentes</option>
                                                            <option value="7">Pasaporte</option>
                                                        </select>
                                                        <span for="conductor_tipodocumento" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="conductor_numero">Número</label>
                                                        <input name="conductor_numero" id="conductor_numero" type="text" style="width: 100%;border-radius: 10px;" placeholder="Número" class="form-control campo">
                                                        <span for="conductor_numero" class="text-danger"></span>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label class="control-label" for="conductor_placa">Numero de placa del vehiculo </label>
                                                        <input name="conductor_placa" id="conductor_placa" type="text" style="width: 100%;border-radius: 10px;" placeholder="Numero de placa del vehiculo..." class="form-control campo">
                                                        <span for="conductor_placa" class="text-danger"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="register-box register-items paper-cut">
                        <div ><h4>Agregar items</h4></div>
                        <div class="register-items-holder">
                            <table id="register" class="table table-hover">
                                <thead>
                                    <tr class="register-items-header">
                                        <th></th>
                                        <th class="item_number_heading">#</th>
                                        <th class="item_name_heading">Descripión</th>
                                        <th class="sales_quantity">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody class="register-item-content">
                                    <tr class="register-item-details">
                                        <td class="text-center">
                                            <button class="btn-success btn-sm" onclick="add_producto()">Agregar</button>
                                        </td>
                                        <td></td>
                                        <td>
                                            <input type="text" class="form-control" name="descripcion_producto" id="descripcion_producto">
                                            <input type="hidden" name="producto_id" id="producto_id">
                                        </td>
                                        <td align="center">
                                            <input type="number" min="1" style="width:70px;" class="form-control" name="cantidad" id="cantidad">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-success" onclick="save_send(this);">Guardar y enviar</button>
                        </div>
                        <div class="col-md-5">
                            <h4 id="cargando_envio"></h4>
                        </div>
                    </div>
                </div>
                <!-- End of Store Account Payment Mode -->
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="cambio_cantidad" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-sx">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Cantidad</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class]="form">
            <div class="form-group">
                <label for="">Cantidad</label>
                <input type="number" name="ncantidad" id="ncantidad" class="form-control">
                <input type="hidden" name="nindex" id="nindex">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="add_cantidad()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_pdf" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" data-keyboard="false" data-backdrop="static"  aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-sx">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Documento</h5>
      </div>
      <div class="modal-body" id="cuerpo_pdf">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="location.reload()">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<script>
    var isThis = 1;
</script>
<?php $this->load->view("partial/footer"); ?>