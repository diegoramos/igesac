<?php 

defined('BASEPATH') OR exit('No direct script access allowed');
                        
class Ubigeo_model extends CI_Model {
    
    function obtenerTodoDepartamento(){
        return $this->db->select('departamento')->group_by('departamento')->get('sunat_codigoubigeo')->result();
    }
    function getAll($search){
        return $this->db->like('codigo_ubigeo',$search)->get('sunat_codigoubigeo')->result();
    }
    function getDepartamento($search){
        return $this->db->select('departamento as value,codigo_ubigeo')->like('departamento',$search)->group_by('departamento')->get('sunat_codigoubigeo')->result();
    }
    function getProvincia($departamento){
        return $this->db->select('provincia as value,codigo_ubigeo')->where('departamento',$departamento)->group_by('provincia')->get('sunat_codigoubigeo')->result();
    }
    function getDistrito($provincia){
        return $this->db->select('distrito as value,codigo_ubigeo')->where('provincia',$provincia)->group_by('distrito')->get('sunat_codigoubigeo')->result();
    }
}
     
/* End of file Ubigeo.php */
    
                        