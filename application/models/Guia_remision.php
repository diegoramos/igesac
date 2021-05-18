<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Guia_remision extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Item');
        $this->load->model('Unit_model');
        $this->load->model('Number_invoice');
        $this->load->model('Customer');
    }

    var $table = "guia_remision";

    function search($search='',$limit=1,$offset=0){
        //$this->db->where('guiaremision_id',$guia_id);
        $query = $this->db->query("SELECT * FROM phppos_guia_remision WHERE serie LIKE '%$search%' OR fecha_emision LIKE '%$search%' LIMIT $limit OFFSET $offset");
        return $query->result();
        //$this->db->limit($limit, $offset);
        //return $this->db->get($this->table)->result();
    }
    function countAll($search=''){
        $query = $this->db->query("SELECT * FROM phppos_guia_remision  WHERE serie LIKE '%$search%' OR fecha_emision LIKE '%$search%' ");
        return $query->num_rows();
    }

    function getInfo($guia_id=-1){
        return $this->db->get_where($this->table,array('guiaremision_id'=>$guia_id))->row();
    }
    function getDetalle($guia_id=-1){
        return $this->db->get_where("guia_remision_items",array('guiaremision_id'=>$guia_id))->result();
    }

    function save_guia(&$principal, $dato_envio, $transporte, $productos)
    {
        if ($this->config->item('ruta_sunat') != '' && $this->config->item('token')!='') {
                
            $this->db->trans_begin();
            $series = $this->Number_invoice->get_numeration_by_id($principal['serie']);
            $fecha_emision = $this->saveFormatDate($principal['fecha_emision']);
            $fecha_traslado = $this->saveFormatDate($principal['fecha_traslado']);
            $units_general = $this->Unit_model->getUnitById($principal['unidad_medida']); //unidad de medida general
            $customer = $this->Customer->get_info($principal['cliente_id']);
            $tipo_documento_cliente = 1;
            if (strlen($customer->account_number) == 11) {
                $tipo_documento_cliente = 6;
            } else if (strlen($customer->account_number) == 8) {
                $tipo_documento_cliente = 1;
            }
            $data_sunat = array(

                'tipo_de_comprobante'=> 'guia',
                ///cliente
                'cliente_nombre' => $customer->full_name,
                'cliente_numerodocumento' => $customer->account_number,
                'cliente_tipodocumento' => $tipo_documento_cliente,
                'cod_tipo_documento' => '09', //codigo documento catalogo 01 guia remision
                //informacion general
                'serie_comprobante'  => $series->prefijo,
                'numero_comprobante' => $series->numeracion,

                'codmotivo_traslado' => $principal['motivo_traslado'], //codigo motivo de traslado
                'motivo_traslado' => $principal['descripcion'], //descripcion motivo de traslado
                'fecha_comprobante'  => $fecha_emision, //formato 10/07/2019 //emison facturador
                'fecha_traslado' => $fecha_traslado, //formato 10/07/2019 //fecha traslado
                'codigo_puerto' => $principal['codigo_puerto'],
                'transbordo' => $principal['transbordo'],
                'unidad_medida' => $units_general->abbreviation, //de peso bruto

                'peso' => $principal['peso_total'],
                'numero_paquetes' => $principal['numero_paquetes'],
                'codtipo_transportista' => $principal['modalida_traslado'],

                'numero_contenedor' => $principal['numero_contenedor'], //OJO FALTA ESTO
                'nota' => $principal['observacion'],
                //informacion partida y destino
                'ubigeo_partida' => $dato_envio['partida_distrito'],
                'dir_partida' => $dato_envio['partida_direccion'],
                'ubigeo_destino' => $dato_envio['llegada_distrito'],
                'dir_destino' => $dato_envio['llegada_direccion'],
                //datos transṕortista
                'tipo_documento_transporte' => $transporte['transortista_tipodocumento'],
                'nro_documento_transporte' => $transporte['transortista_numero'],
                'razon_social_transporte' => $transporte['transortista_razonSocial'],
                //datos conductor FALTA ESTO TAMBIEN
                'conductor_tipodocumento' => $transporte['conductor_tipodocumento'],
                'conductor_numero' => $transporte['conductor_numero'],
                'conductor_placa' => $transporte['conductor_placa']
            );

            //guardar en base de datos
            $data = array(
                'local_id' => $principal['local'],
                'cliente_id' => $principal['cliente_id'],
                'serie'  => $series->prefijo . "-" . $series->numeracion,
                'modalida_traslado' => $principal['modalida_traslado'],
                'motivo_traslado' => $principal['motivo_traslado'],
                'fecha_emision'  => $fecha_emision, //formato 10/07/2019
                'fecha_traslado' => $fecha_traslado, //formato 10/07/2019
                'codigo_puerto' => $principal['codigo_puerto'],
                'transbordo' => $principal['transbordo'],
                'unidad_medida' => $units_general->unit_id,
                'peso_total' => $principal['peso_total'],
                'numero_paquetes' => $principal['numero_paquetes'],
                'numero_contenedor' => $principal['numero_contenedor'],
                'observacion' => $principal['observacion'],
                'descripcion' => $principal['descripcion'],

                'partida_ubigeo' => $dato_envio['partida_distrito'],
                'partida_direccion' => $dato_envio['partida_direccion'],
                'llegada_ubigeo' => $dato_envio['llegada_distrito'],
                'llegada_direccion' => $dato_envio['llegada_direccion'],

                'transortista_tipodocumento' => $transporte['transortista_tipodocumento'],
                'transortista_numero' => $transporte['transortista_numero'],
                'transortista_razonSocial' => $transporte['transortista_razonSocial'],
                'conductor_tipodocumento' => $transporte['conductor_tipodocumento'],
                'conductor_numero' => $transporte['conductor_numero'],
                'conductor_placa' => $transporte['conductor_placa']
            );

            $status = $this->db->insert($this->table, $data);
            $principal['guia_id'] = $this->db->insert_id();
            if ($status) {
                $data_producto = array();
                $data_detalle_sunat = array();
                $item_num = 1;
                foreach ($productos as $key => $value) {
                    $res = $this->Item->get_info($value['id']);
                    $units = $this->Unit_model->getUnitById($res->unit_id);
                    ///DETALLE SUNAT
                    $data_detalle_sunat[] = array(
                        'ITEM' => $item_num,//SE DIFERENCIAN EN ALGO 
                        'NUMERO_ORDEN' => $item_num,
                        'CANTIDAD' => $value['cantidad'],
                        'UNIDAD_MEDIDA' => $units->abbreviation,
                        'CODIGO_PRODUCTO' => $res->item_number,
                        'DESCRIPCION' => $res->name,
                    );

                    //ITEMS SOLO PARA GUARDAR
                    //$res->unit_id
                    $data_item = array(
                        'item_id' => $value['id'],
                        'cantidad' => $value['cantidad'],
                        'unidad_medida' => $units->abbreviation,
                        'guiaremision_id' => $principal['guia_id'],
                    );
                    $data_producto[] = $data_item;

                    $item_num++;
                }
                $this->db->insert_batch('guia_remision_items', $data_producto);
            }
            $data_sunat['detalle'] = $data_detalle_sunat;
            
            //Actualizar numeracion de guia de remision
            $this->Number_invoice->update_prefix(array('numeracion'=>($series->numeracion+1)),array('prefix_id'=>$series->prefix_id));

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return array('status' => false, 'mensaje' => "Error al insertar");
            } else {
                $this->db->trans_commit();
                $ruta =  $this->config->item('ruta_sunat');
                //Invocamos el servicio
                $token = $this->config->item('token'); //en caso quieras utilizar algún token generado desde tu sistema
                //codificamos la data
                $data_json = json_encode($data_sunat);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $ruta);
                curl_setopt(
                    $ch,
                    CURLOPT_HTTPHEADER,
                    array(
                        ///'Authorization: Token token="'.$token.'"',
                        'x-api-key:' . $token,
                        'Content-Type: application/json',
                    )
                );
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $respuesta  = curl_exec($ch);
                curl_close($ch);
                $response = json_decode($respuesta, true);

                $mensaje = '';
                $hash = '';
                $update_data = array();
                if($response['status']==1){
                    $mensaje = $response['message'];
                    $hash = $response['hash_cpe'];
                }else{
                    if (isset($response['hash_cpe'])) {
                        $hash = $response['hash_cpe'];
                    }
                    if (isset($response['message'])) {
                        $mensaje = $response['message'];
                    }
                }
                $update_data['mensaje'] = $mensaje;
                $update_data['hash_cpe'] = $hash;
                $update_data['estado'] = $response['status'];
                $this->update_guia($principal['guia_id'],$update_data);
            return array('status' => true, 'mensaje' => $mensaje, 'hash'=> $hash);
            }
        }else{
            return array('status' => false, 'mensaje' => "Por favor habilitar facturacion o ingresar ruta de envio.");
        }
    }
    public function update_guia($id,$update_data){
        $this->db->where('guiaremision_id', $id);
        $this->db->update($this->table, $update_data);
    }
    private function saveFormatDate($date)
    {
        if ($date != '' && $date != null) {
            $part = explode("/", $date);
            $rew_date = $part[2] . "-" . $part[1] . "-" . $part[0];
            return $rew_date;
        }
        return null;
    }
}
