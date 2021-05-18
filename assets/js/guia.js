var lst_producto = []
if (typeof isThis !== 'undefined') {
    $("input.campo").change(function () {
        $(this).parent().removeClass('has-error');
        $(this).next().empty();
    });
    $("textarea").change(function () {
        $(this).parent().removeClass('has-error');
        $(this).next().empty();
    });
    $("select").change(function () {
        $(this).parent().removeClass('has-error');
        $(this).next().empty();
    });
    window.onload = function () {
        $(".fecha_formato").datepicker(
            { format: 'dd/mm/yyyy' }
        );
    };

    $("#modalida_traslado").select2();
    $("#partida_departamento").select2();
    $("#partida_provincia").select2();
    $("#partida_distrito").select2();
    $("#llegada_departamento").select2();
    $("#llegada_provincia").select2();
    $("#llegada_distrito").select2();

    $("#local").select2();
    $("#serie").select2();
    $("#motivo_traslado").select2();
    $("#unidad_medida").select2();
    
    //Cliente
    $("#cliente").autocomplete({
        source: SITE_URL + "/sunat/customer_search",
        delay: 150,
        autoFocus: false,
        minLength: 0,
        select: function (event, ui) {
            document.getElementById("cliente_id").value = ui.item.id;
        },
    }).data("ui-autocomplete")._renderItem = function (ul, item) {
        return $("<li class='item-suggestions'></li>")
            .data("item.autocomplete", item)
            .append('<a class="suggest-item"><div class="item-image">' +
                '<img src="' + item.avatar + '" alt="">' +
                '</div>' +
                '<div class="details">' +
                '<div class="name">' +
                item.label +
                '</div>' +
                '</div>')
            .appendTo(ul);
    };
    //fin search cliente
    $("#local").change(function (e) {
        e.preventDefault();
        let local = document.getElementById('local').value;
        $.ajax({
            type: "POST",
            url: SITE_URL + "/sunat/get_serie",
            data: {
                local: local
            },
            dataType: "json",
            success: function (response) {
                var htm = '<option value="">Seleccionar</option>';
                if (response != null) {
                    htm += '<option value="' + response.prefix_id + '">' + response.prefijo + '</option>';
                }
                $("#serie").html(htm);
            }
        });
    });
    $("#partida_departamento").change(function (e) { 
        e.preventDefault();
        $.ajax({
            type: "GET",
            url: SITE_URL + "/sunat/ubigeo_search_provincia",
            dataType: "json",
            data: {
                departamento: document.getElementById('partida_departamento').value,
            },
            success: function (data) {
                var htm = '<option value="">Seleccionar</option>';
                $.each(data, function (indexInArray, valueOfElement) { 
                    htm += '<option value="'+valueOfElement.value+'">'+valueOfElement.value+'</option>';
                });
                document.getElementById('partida_provincia').innerHTML=htm;
            }
        });
    });
    $("#partida_provincia").change(function (e) { 
        e.preventDefault();
        $.ajax({
            type: "GET",
            url: SITE_URL + "/sunat/ubigeo_search_distrito",
            dataType: "json",
            data: {
                provincia: document.getElementById('partida_provincia').value,
            },
            success: function (data) {
                var htm = '<option value="">Seleccionar</option>';
                $.each(data, function (indexInArray, valueOfElement) { 
                    htm += '<option value="'+valueOfElement.codigo_ubigeo+'">'+valueOfElement.value+'</option>';
                });
                document.getElementById('partida_distrito').innerHTML=htm;
            }
        });
    });
    //llegada 
    $("#llegada_departamento").change(function (e) { 
        e.preventDefault();
        $.ajax({
            type: "GET",
            url: SITE_URL + "/sunat/ubigeo_search_provincia",
            dataType: "json",
            data: {
                departamento: document.getElementById('llegada_departamento').value,
            },
            success: function (data) {
                var htm = '<option value="">Seleccionar</option>';
                $.each(data, function (indexInArray, valueOfElement) { 
                    htm += '<option value="'+valueOfElement.value+'">'+valueOfElement.value+'</option>';
                });
                document.getElementById('llegada_provincia').innerHTML=htm;
            }
        });
    });

    $("#llegada_provincia").change(function (e) { 
        e.preventDefault();
        $.ajax({
            type: "GET",
            url: SITE_URL + "/sunat/ubigeo_search_distrito",
            dataType: "json",
            data: {
                provincia: document.getElementById('llegada_provincia').value,
            },
            success: function (data) {
                var htm = '<option value="">Seleccionar</option>';
                $.each(data, function (indexInArray, valueOfElement) { 
                    htm += '<option value="'+valueOfElement.codigo_ubigeo+'">'+valueOfElement.value+'</option>';
                });
                document.getElementById('llegada_distrito').innerHTML=htm;
            }
        });
    });
    $("#transortista_tipodocumento").change(function (e) {
        e.preventDefault();
        let tipo_doc = $(this).val();
        if (tipo_doc == '1') {
            $("#transortista_numero").attr('maxlength', 8);
        } else if (tipo_doc == '6') {
            $("#transortista_numero").attr('maxlength', 11);
        } else {
            $("#transortista_numero").removeAttr('maxlength');;
        }
    });

    $("#conductor_tipodocumento").change(function (e) {
        e.preventDefault();
        let tipo_doc = $(this).val();
        if (tipo_doc == '1') {
            $("#conductor_numero").attr('maxlength', 8);
        } else if (tipo_doc == '6') {
            $("#conductor_numero").attr('maxlength', 11);
        } else {
            $("#conductor_numero").removeAttr('maxlength');;
        }
    });
    //buscar productos
    $("#descripcion_producto").autocomplete({
        source: SITE_URL + '/sunat/item_search',
        delay: 150,
        autoFocus: false,
        minLength: 0,
        select: function (event, ui) {
            document.getElementById("producto_id").value = ui.item.id;
            document.getElementById("cantidad").value = 1;
        },
    }).data("ui-autocomplete")._renderItem = function (ul, item) {
        return $("<li class='item-suggestions'></li>")
            .data("item.autocomplete", item)
            .append('<a class="suggest-item"><div class="item-image">' +
                '<img src="' + item.image + '" alt="">' +
                '</div>' +
                '<div class="details">' +
                '<div class="name">' +
                item.label +
                '</div>' +
                '<span class="attributes">' + 'Categoría' + ' : <span class="value">' + (item.category ? item.category : "Ninguno") + '</span></span>' +
                (typeof item.quantity !== 'undefined' && item.quantity !== null ? '<span class="attributes">' + 'Cantidad' + ' <span class="value">' + item.quantity + '</span></span>' : '') +
                (item.attributes ? '<span class="attributes">' + 'Atributos' + ' : <span class="value">' + item.attributes + '</span></span>' : '') +
                '</div>')
            .appendTo(ul);
    };
    //fin busqueda prductos
    function add_producto() {
        var producto_id = document.getElementById('producto_id').value;
        var cantidad = document.getElementById('cantidad').value;
        var index = get_index_producto(producto_id)
        if (index == -1) {
            var producto = {};
            producto.index = lst_producto.length;
            producto.id = producto_id;
            producto.descripcion = document.getElementById('descripcion_producto').value;
            producto.cantidad = cantidad;
            if (producto.descripcion != '' && producto_id != '') {
                lst_producto.push(producto);
                limpiarIngreso();
                refrescarhtml();
            } else {
                alert("Ingresar al menos un producto");
            }
        }else{
            lst_producto[index].cantidad = parseInt(lst_producto[index].cantidad)+parseInt(cantidad);
            limpiarIngreso();
            refrescarhtml();
        }
    }
    function refrescarhtml() {
        var html_to = '';
        for (let i = 0; i < lst_producto.length; i++) {
            html_to += '<tr class="register-item-details remove_all" rowIndex="' + lst_producto[i].index + '">';
            html_to += '<td class="text-center">';
            html_to += '<a href="javascript:;" onclick="deleteItem(this,' + lst_producto[i].index + ')" class="delete-item" tabindex="-1"><i class="icon ion-android-cancel"></i></a> </td>';
            html_to += '<td>' + (lst_producto[i].index + 1) + '</td>';
            html_to += '<td><a tabindex="-1" href="javascript:;" class="register-item-names">' + lst_producto[i].descripcion + '</a></td>';
            html_to += '<td class="text-center"><a href="javascript:;" id="quantity_' + lst_producto[i].index + '" class="editable_para editable editable-click" onclick="cambiar_numero(' + lst_producto[i].index + ',' + lst_producto[i].cantidad + ')" title="Cantidad">' + lst_producto[i].cantidad + '</a></td>';
            html_to += '</tr>';
        }
        $(".register-item-content").append(html_to);
    }
    function deleteItem(element, index) {
        lst_producto.splice(index, 1)
        for (var i = 0; i < lst_producto.length; i++) {
            lst_producto[i].index = i
        }
        $(element).parent().parent().remove();
        limpiarTabla();
        refrescarhtml();
    }
    function limpiarIngreso() {
        document.getElementById('producto_id').value = '';
        document.getElementById('descripcion_producto').value = '';
        document.getElementById('cantidad').value = '';
        limpiarTabla();
    }
    function limpiarTabla() {
        $(".remove_all").each(function (index, element) {
            $(element).remove();
        });
    }
    //devuelve el indice del producto el el array lst_producto definido pro sus parametros
    function get_index_producto(id) {
        for (var i = 0; i < lst_producto.length; i++) {
            if (lst_producto[i].id == id) {
                return lst_producto[i].index;
            }
        }
        return -1
    }
    function  cambiar_numero(index,cantidad) {
        document.getElementById('ncantidad').value = cantidad;
        document.getElementById('nindex').value = index;
        $("#cambio_cantidad").modal("show");
    }
    function add_cantidad(){
        let cantidad = document.getElementById('ncantidad').value;
        let index = document.getElementById('nindex').value;
        lst_producto[index].cantidad = cantidad;
        limpiarIngreso();
        refrescarhtml();
        $("#cambio_cantidad").modal("hide");
    }
    ///Clase para el primer bloque
    function BlockOne() {
        this.local = '';
        this.serie = '';
        this.fecha_emision = '';
        this.cliente = '';
        this.cliente_id = '';
        this.modalida_traslado = '';
        this.motivo_traslado = '';
        this.fecha_traslado = '';
        this.codigo_puerto = '';
        this.transbordo = '';
        this.unidad_medida = '';
        this.peso_total = '';
        this.numero_paquetes = '';
        this.numero_contenedor = '';
        this.observacion = '';
        this.descripcion = '';
    }
    BlockOne.prototype.recolectarDatos = function () {
        this.local = document.getElementById('local').value;
        this.serie = document.getElementById('serie').value;
        this.fecha_emision = document.getElementById('fecha_emision').value;
        this.cliente = document.getElementById('cliente').value;
        this.cliente_id = document.getElementById('cliente_id').value;
        this.modalida_traslado = document.getElementById('modalida_traslado').value;
        this.motivo_traslado = $("#motivo_traslado").val();
        this.fecha_traslado = $("#fecha_traslado").val();
        this.codigo_puerto = $("#codigo_puerto").val();
        this.transbordo = $('input[name="transbordo"]:checked').val();
        this.unidad_medida = $("#unidad_medida").val();
        this.peso_total = $("#peso_total").val();
        this.numero_paquetes = $("#numero_paquetes").val();
        this.numero_contenedor = $("#numero_contenedor").val();
        this.observacion = $("#observacion").val();
        this.descripcion = $("#descripcion").val();
    };

    BlockOne.prototype.obtenerCampoVacio = function () {
        let data = new Array();
        let i = 0;
        if (this.local == '') { data[i] = { "campo": "local", "mensaje": "Seleccionar local" }; i++; }
        if (this.serie == '') { data[i] = { "campo": "serie", "mensaje": "Seleccionar serie" }; i++; }
        if (this.fecha_emision == '') { data[i] = { "campo": "fecha_emision", "mensaje": "Seleccionar fecha de emision" }; i++; }
        if (this.cliente == '') { data[i] = { "campo": "cliente", "mensaje": "Ingresar cliente" }; i++; }
        if (this.cliente_id == '') { data[i] = { "campo": "cliente", "mensaje": "Selecionar cliente" }; i++; }
        if (this.modalida_traslado == '') { data[i] = { "campo": "modalida_traslado", "mensaje": "Seleccionar modalidad" }; i++; }
        if (this.motivo_traslado == '') { data[i] = { "campo": "motivo_traslado", "mensaje": "Seleccione motivo" }; i++; }
        if (this.fecha_traslado == '') { data[i] = { "campo": "fecha_traslado", "mensaje": "Seleccionar fecha de traslado" }; i++; }
        if (this.codigo_puerto == '') { data[i] = { "campo": "codigo_puerto", "mensaje": "Ingresar codigo puerto " }; i++; }
        if (this.transbordo == '' || this.transbordo == undefined) { data[i] = { "campo": "transbordo", "mensaje": "Seleccionar transbordo" }; i++; }
        if (this.unidad_medida == '') { data[i] = { "campo": "unidad_medida", "mensaje": "Seleccionar unidad de medida" }; i++; }
        if (this.peso_total == '') { data[i] = { "campo": "peso_total", "mensaje": "Ingresar peso total" }; i++; }
        if (this.numero_paquetes == '') { data[i] = { "campo": "numero_paquetes", "mensaje": "Ingresar numero de paquetes" }; i++; }
        if (this.numero_contenedor == '') { data[i] = { "campo": "numero_contenedor", "mensaje": "Ingresar numero de contenedor" }; i++; }
        if (this.observacion == '') { data[i] = { "campo": "observacion", "mensaje": "Ingresar observacion" }; i++; }
        if (this.descripcion == '') { data[i] = { "campo": "descripcion", "mensaje": "Ingresar descripcion" }; i++; }
        return data;
    };
    BlockOne.prototype.estaValido = function () {
        if (this.local == '') { return false; }
        if (this.serie == '') { return false; }
        if (this.fecha_emision == '') { return false; }
        if (this.cliente == '') { return false; }
        if (this.cliente_id == '') { return false; }
        if (this.modalida_traslado == '') { return false; }
        if (this.motivo_traslado == '') { return false; }
        if (this.fecha_traslado == '') { return false; }
        if (this.codigo_puerto == '') { return false; }
        if (this.transbordo == '' || this.transbordo == undefined) { return false; }
        if (this.unidad_medida == '') { return false; }
        if (this.peso_total == '') { return false; }
        if (this.numero_paquetes == '') { return false; }
        if (this.numero_contenedor == '') { return false; }
        if (this.observacion == '') { return false; }
        if (this.descripcion == '') { return false; }
        return true;
    };

    //Clase para el segundo bloque
    function BlockSecond() {
        //this.partida_pais = '';
        this.partida_departamento = '';
        this.partida_provincia = '';
        this.partida_distrito = '';
        this.partida_direccion = '';
        //this.llegada_pais = '';
        this.llegada_departamento = '';
        this.llegada_provincia = '';
        this.llegada_distrito = '';
        this.llegada_direccion = '';
    }
    BlockSecond.prototype.recolectarDatos = function () {
        //this.partida_pais = $("#partida_pais").val();
        this.partida_departamento = $("#partida_departamento").val();
        this.partida_provincia = $("#partida_provincia").val();
        this.partida_distrito = $("#partida_distrito").val();
        this.partida_direccion = $("#partida_direccion").val();
        //this.llegada_pais = $("#llegada_pais").val();
        this.llegada_departamento = $("#llegada_departamento").val();
        this.llegada_provincia = $("#llegada_provincia").val();
        this.llegada_distrito = $("#llegada_distrito").val();
        this.llegada_direccion = $("#llegada_direccion").val();
    };
    BlockSecond.prototype.obtenerCampoVacio = function () {
        let data = new Array();
        let i = 0;
        //if (this.partida_pais == '') { data[i] = { "campo": "partida_pais", "mensaje": "Seleccione pais de partida" }; i++; }
        if (this.partida_departamento == '') { data[i] = { "campo": "partida_departamento", "mensaje": "Seleccione departamento de partida" }; i++; }
        if (this.partida_provincia == '') { data[i] = { "campo": "partida_provincia", "mensaje": "Seleccione provincia de partida" }; i++; }
        if (this.partida_distrito == '') { data[i] = { "campo": "partida_distrito", "mensaje": "Seleccione distrito de partida" }; i++; }
        if (this.partida_direccion == '') { data[i] = { "campo": "partida_direccion", "mensaje": "Ingresar direccion de partida" }; i++; }
        //if (this.llegada_pais == '') { data[i] = { "campo": "llegada_pais", "mensaje": "Seleccione pais de llegada" }; i++; }
        if (this.llegada_departamento == '') { data[i] = { "campo": "llegada_departamento", "mensaje": "Seleccione departamento de llegada" }; i++; }
        if (this.llegada_distrito == '') { data[i] = { "campo": "llegada_distrito", "mensaje": "Seleccione distrito de llegada " }; i++; }
        if (this.llegada_provincia == '') { data[i] = { "campo": "llegada_provincia", "mensaje": "Seleccione provincia de llegada" }; i++; }
        if (this.llegada_direccion == '') { data[i] = { "campo": "llegada_direccion", "mensaje": "Ingresar direccion de llegada" }; i++; }
        return data;
    };
    BlockSecond.prototype.estaValido = function () {
        //if (this.partida_pais == '') { return false; }
        if (this.partida_departamento == '') { return false; }
        if (this.partida_provincia == '') { return false; }
        if (this.partida_distrito == '') { return false; }
        if (this.partida_direccion == '') { return false; }
        //if (this.llegada_pais == '') { return false; }
        if (this.llegada_departamento == '') { return false; }
        if (this.llegada_provincia == '') { return false; }
        if (this.llegada_distrito == '') { return false; }
        if (this.llegada_direccion == '') { return false; }
        return true;
    };

    //Clase para el tercer bloque
    function BlockThree() {
        this.transortista_tipodocumento = '';
        this.transortista_numero = '';
        this.transortista_razonSocial = '';
        this.conductor_tipodocumento = '';
        this.conductor_numero = '';
        this.conductor_placa = '';
    }
    BlockThree.prototype.recolectarDatos = function () {
        this.transortista_tipodocumento = $("#transortista_tipodocumento").val();
        this.transortista_numero = $("#transortista_numero").val();
        this.transortista_razonSocial = $("#transortista_razonSocial").val();
        this.conductor_tipodocumento = $("#conductor_tipodocumento").val();
        this.conductor_numero = $("#conductor_numero").val();
        this.conductor_placa = $("#conductor_placa").val();
    };
    BlockThree.prototype.obtenerCampoVacio = function () {
        let data = new Array();
        let i = 0;
        if (this.transortista_tipodocumento == '') { data[i] = { "campo": "transortista_tipodocumento", "mensaje": "Seleccione tipo documento transportista" }; i++; }
        if (this.transortista_numero == '') { data[i] = { "campo": "transortista_numero", "mensaje": "Ingresar numero de transportista" }; i++; }
        if (this.transortista_razonSocial == '') { data[i] = { "campo": "transortista_razonSocial", "mensaje": "Nombre Y/O Razón Social" }; i++; }
        if (this.conductor_tipodocumento == '') { data[i] = { "campo": "conductor_tipodocumento", "mensaje": "Seleccione tipo documento conductor" }; i++; }
        if (this.conductor_numero == '') { data[i] = { "campo": "conductor_numero", "mensaje": "Ingresar numero de conductor" }; i++; }
        if (this.conductor_placa == '') { data[i] = { "campo": "conductor_placa", "mensaje": "Ingresar placa de vehiculo" }; i++; }
        return data;
    };
    BlockThree.prototype.estaValido = function () {
        if (this.transortista_tipodocumento == '') { return false; }
        if (this.transortista_numero == '') { return false; }
        if (this.transortista_razonSocial == '') { return false; }
        if (this.conductor_tipodocumento == '') { return false; }
        if (this.conductor_numero == '') { return false; }
        if (this.conductor_placa == '') { return false; }
        return true;
    };

    function writeError(data) {
        data.forEach(element => {
            document.getElementsByName(element.campo)[0].parentElement.classList.add("has-error");
        });
    }

    var dato1 = new BlockOne();
    var dato2 = new BlockSecond();
    var dato3 = new BlockThree();

    function bloque(page, evt) {
        var aria = $(evt).attr('aria');
        if (aria == 'true') {
            $("#collapse" + page).hide('slow');
            $("#collapse2").hide('slow');
            $("#collapse3").hide('slow');
            $("#boton1").attr('aria', 'false');
            $("#boton2").attr('aria', 'false');
            $("#boton3").attr('aria', 'false');
        } else {
            $("#collapse" + page).show('slow');
            if (page == 1) {
                $("#collapse2").hide('slow');
                $("#collapse3").hide('slow');
                $(evt).attr('aria', 'true');
                $("#boton2").attr('aria', 'false');
                $("#boton3").attr('aria', 'false');
            } else if (page == 2) {
                dato1.recolectarDatos();
                if (!dato1.estaValido()) {
                    writeError(dato1.obtenerCampoVacio());
                    $("#collapse1").show('slow');
                    $("#collapse2").hide('slow');
                    $("#collapse3").hide('slow');
                    $("#boton1").attr('aria', 'true');
                    $("#boton2").attr('aria', 'false');
                    $("#boton3").attr('aria', 'false');
                } else {
                    $("#collapse1").hide('slow');
                    $("#collapse3").hide('slow');
                    $("#boton1").attr('aria', 'false');
                    $("#boton2").attr('aria', 'true');
                    $("#boton3").attr('aria', 'false');
                }
            } else if (page == 3) {
                dato1.recolectarDatos();
                dato2.recolectarDatos();
                if (!dato1.estaValido()) {
                    writeError(dato1.obtenerCampoVacio());
                    $("#collapse1").show('slow');
                    $("#collapse2").hide('slow');
                    $("#collapse3").hide('slow');
                    $("#boton1").attr('aria', 'true');
                    $("#boton2").attr('aria', 'false');
                    $("#boton3").attr('aria', 'false');
                } else {
                    $("#collapse1").hide('slow');
                    $("#boton1").attr('aria', 'false');
                    if (!dato2.estaValido()) {
                        writeError(dato2.obtenerCampoVacio());
                        $("#collapse2").show('slow');
                        $("#collapse3").hide('slow');
                        $("#boton2").attr('aria', 'true');
                    } else {
                        $("#collapse2").hide('slow');
                        $("#boton2").attr('aria', 'false');
                        $("#boton3").attr('aria', 'true');
                    }
                }
            }
        }
    }
}
function save_send(obj) {
    dato1.recolectarDatos();
    dato2.recolectarDatos();
    dato3.recolectarDatos();
    if (!dato1.estaValido()) {
        writeError(dato1.obtenerCampoVacio());
        $("#collapse1").show('slow');
        $("#collapse2").hide('slow');
        $("#collapse3").hide('slow');
        $("#boton1").attr('aria', 'true');
        $("#boton2").attr('aria', 'false');
        $("#boton3").attr('aria', 'false');
        alert("Completar los campos del bloque principal");
        return;
    } else {
        $("#collapse1").hide('slow');
        $("#boton1").attr('aria', 'false');
        if (!dato2.estaValido()) {
            writeError(dato2.obtenerCampoVacio());
            $("#collapse2").show('slow');
            $("#collapse3").hide('slow');
            $("#boton2").attr('aria', 'true');
            alert("Completar los campos del bloque datos envío");
            return;
        } else {
            $("#collapse2").hide('slow');
            $("#boton2").attr('aria', 'false');
            if (!dato3.estaValido()) {
                writeError(dato3.obtenerCampoVacio());
                $("#collapse3").show('slow');
                $("#collapse2").hide('slow');
                $("#boton3").attr('aria', 'true');
                alert("Completar los campos del bloque datos transporte");
                return;
            } else {
                $("#collapse3").hide('slow');
                $("#boton3").attr('aria', 'false');
            }
        }
    }
    if (lst_producto.length>0) {

        $(obj)[0].disabled = true;
        $("#cargando_envio").text("Por favor espera...");
        
        let dato_json1 = JSON.stringify(dato1);
        let dato_json2 = JSON.stringify(dato2);
        let dato_json3 = JSON.stringify(dato3);
        let productos = JSON.stringify(lst_producto);
        let data = {
            principal:dato_json1,
            dato_envio:dato_json2,
            transporte:dato_json3,
            productos:productos
        };
        $.ajax({
            type: "POST",
            url: SITE_URL+"/sunat/save",
            data: data,
            dataType: "json",
            success: function (response) {
                if (response.status) {
                    //GET PDF
                    $.post(SITE_URL+"/sunat/print_docuemento", 
                        {
                            guia_id:response.guia_id,
                        },
                        function (data, textStatus, jqXHR) {
                            $("#cuerpo_pdf").html(data);
                            $("#modal_pdf").modal("show");
                            $(obj)[0].disabled = false;
                            $("#cargando_envio").text("");
                        },
                        "html"
                    );
                }else{
                    $(obj)[0].disabled = false;
                    $("#cargando_envio").text("");
                    alert(response.mensaje);
                }
            }
        });
    }else{
        alert("Ingresar al menos un ítems");
    }
}
$(".viewss_action").click(function (e) { 
    e.preventDefault();
    var id = $(this).attr('aria-valuetext');
    $.post(SITE_URL+"/sunat/print_docuemento", 
        {
            guia_id:id,
        },
        function (data, textStatus, jqXHR) {
            $("#cuerpo_pdf").html(data);
            $("#modal_pdf").modal("show");
        },
        "html"
    );
});