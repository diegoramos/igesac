<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once("Secure_area.php");
require_once  FCPATH . 'vendor/autoload.php';
class Sunat extends Secure_area
{
    function __construct()
    {
        parent::__construct('sales');
        $this->lang->load('module');
        $this->load->helper('items');
        $this->load->model('Customer');
        $this->load->model('Category');
        $this->load->model('Tag');
        $this->load->model('Item');
        $this->load->model('Ubigeo_model');
        $this->load->model('Number_invoice');
        $this->load->model('Unit_model');
        $this->load->model('Guia_remision');
    }
    public function index()
    {
        $data = array();
        $this->load->view('sunat/principal', $data, FALSE);
    }

    public function type($ipo = 'guia')
    {
        $data = array();
        $location = $this->Employee->get_current_location_info();
        if ($location) {
            $data['location_id'] = $location->location_id;
            $data['name'] = $location->name;
        }
        $data['units'] = $this->Unit_model->getDataAllUnidades();
        $data['departamentos'] = $this->Ubigeo_model->obtenerTodoDepartamento();
        $this->load->view('sunat/guia', $data, FALSE);
    }
    public function list($offset=0){
        $data = array();
        $search = $this->input->get("search")?$this->input->get("search"):"";
        $config['per_page'] = 25; 
        $table_manage = $this->Guia_remision->search($search,$config['per_page'],$offset);
        $total_rows = $this->Guia_remision->countAll($search);
        $data['search'] = $search;
        $config['base_url'] = site_url('sunat/list');
    
        $data['table_manage'] = array();
        for ($i=0; $i < count($table_manage); $i++) { 
            $cliente = $this->Customer->get_info($table_manage[$i]->cliente_id);
            $unit = $this->Unit_model->getUnitById($table_manage[$i]->local_id);
            $row = new stdClass();
            $row->id = $table_manage[$i]->guiaremision_id;
            $row->fecha_emision = $table_manage[$i]->fecha_emision;
            $row->cliente_nombre = $cliente->full_name;
            $row->unidad_medida = $unit->name;
            $row->serie = $table_manage[$i]->serie;
            $row->peso_total = $table_manage[$i]->peso_total;
            $data['table_manage'][] = $row;
        }
        $config['total_rows'] = $total_rows;
        $data['total_rows'] = $total_rows;
        $this->load->library('pagination');
		$this->pagination->initialize($config);
		$data['pagination'] = $this->pagination->create_links();
        $this->load->view('sunat/guia_lista', $data, FALSE);
    }
    function customer_search()
    {
        //allow parallel searchs to improve performance.
        session_write_close();
        $suggestions = $this->Customer->get_customer_buscar($this->input->get('term'), 0, 100);
        echo json_encode(H($suggestions));
    }
    function ubigeo_search_departamento()
    {
        $suggestions = $this->Ubigeo_model->getDepartamento($this->input->get('term'), 0, 100);
        echo json_encode($suggestions);
    }
    function ubigeo_search_provincia()
    {
        $suggestions = $this->Ubigeo_model->getProvincia($this->input->get('departamento'));
        echo json_encode($suggestions);
    }
    function ubigeo_search_distrito()
    {
        $suggestions = $this->Ubigeo_model->getDistrito($this->input->get('provincia'));
        echo json_encode($suggestions);
    }
    function get_serie()
    {
        $res = $this->Number_invoice->get_numeration_receiving('guia_remision', $this->input->post('local'));
        echo json_encode($res);
    }
    function item_search()
    {
        //allow parallel searchs to improve performance.
        session_write_close();
        if (!$this->config->item('speed_up_search_queries')) {
            $suggestions = $this->Item->get_item_search_suggestions($this->input->get('term'), 0, 'unit_price', 100);
            $suggestions = array_merge($suggestions, $this->Item_kit->get_item_kit_search_suggestions_sales_recv($this->input->get('term'), 0, 'unit_price', 100));
        } else {
            $suggestions = $this->Item->get_item_search_suggestions_without_variations($this->input->get('term'), 0, 100, 'unit_price');
            $suggestions = array_merge($suggestions, $this->Item_kit->get_item_kit_search_suggestions_sales_recv($this->input->get('term'), 0, 'unit_price', 100));

            for ($k = 0; $k < count($suggestions); $k++) {
                if (isset($suggestions[$k]['avatar'])) {
                    $suggestions[$k]['image'] = $suggestions[$k]['avatar'];
                }

                if (isset($suggestions[$k]['subtitle'])) {
                    $suggestions[$k]['category'] = $suggestions[$k]['subtitle'];
                }
            }
        }
        for ($k = 0; $k < count($suggestions); $k++) {
            if (isset($suggestions[$k]['value'])) {
                $suggestions[$k]['id'] = $suggestions[$k]['value'];
            }

            if (isset($suggestions[$k]['label'])) {
                $suggestions[$k]['value'] = $suggestions[$k]['label'];
            }
        }


        echo json_encode(H($suggestions));
    }

    function save()
    {
        $principal = json_decode($this->input->post('principal'), true);
        $dato_envio = json_decode($this->input->post('dato_envio'), true);
        $transporte = json_decode($this->input->post('transporte'), true);
        $productos = json_decode($this->input->post('productos'), true);
        $resp = $this->Guia_remision->save_guia($principal, $dato_envio, $transporte, $productos);
        $resp['guia_id'] = $principal['guia_id'];
        echo json_encode($resp);
    }
    function getPDF($id = -1)
    {
        $data = array();
        $data['info'] = $this->Guia_remision->getInfo($id);
        if ($data['info'] != null) {
            $data['info']->detalle = $this->Guia_remision->getDetalle($data['info']->guiaremision_id);

            for ($i = 0; $i < count($data['info']->detalle); $i++) {
                $res = $this->Item->get_info($data['info']->detalle[$i]->item_id);
                $data['info']->detalle[$i]->name = $res->name;
                $data['info']->detalle[$i]->codigo = $res->item_number;
            }
            $cliente = $this->Customer->get_info($data['info']->cliente_id);
            $data['info']->cliente_nombre = $cliente->full_name;
            $data['info']->cliente_ruc = $cliente->account_number;
            $data['info']->cliente_direccion = $cliente->address_1;
        }

        $override_location_id = $data['info']->local_id;
        $company_logo = ($company_logo = $this->Location->get_info_for_key('company_logo', isset($override_location_id) ? $override_location_id : FALSE)) ? $company_logo : $this->config->item('company_logo');

        $data['ruta_img'] = $this->Appfile->get_url_for_file($company_logo);
        $data['company'] = ($company = $this->Location->get_info_for_key('company', isset($override_location_id) ? $override_location_id : FALSE)) ? $company : $this->config->item('company');
        $data['website'] = ($website = $this->Location->get_info_for_key('website', isset($override_location_id) ? $override_location_id : FALSE)) ? $website : $this->config->item('website');
        $data['ruc_company'] = $this->config->item('ruc_company');

        $html = $this->load->view('sunat/guia_pdf', $data, TRUE);

        $filename = date('Ymdhis');
        $mpdf = new \Mpdf\Mpdf([
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'format' => 'Letter'
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename . '.pdf', 'I');
    }

    function print_docuemento()
    {
        $documento_id = $this->input->post('guia_id');
        /*<a href="#" class="btn btn-success" onclick="printJS(\''.site_url().'/sunat/getPDF/'.$documento_id.'\')">
             IMPRIMIR
           </a>*/
        echo  '<div class="button-group">
           <a class="btn btn-primary" target="_blank" href="' . site_url() . '/sunat/getPDF/' . $documento_id . '">Abrir en navegador</a>
         </div>
         <br>
         <iframe src="' . site_url() . '/sunat/getPDF/' . $documento_id . '" width="100%" height="400" frameborder="none">
 
         </iframe>';
    }
}
