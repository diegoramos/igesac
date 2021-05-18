<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Unit_model extends CI_Model {

    /* Esto es una funcion que llama a un unico data de unidad items */
    public function getDataUnidadById($unit_id){
        $this->db->select('name');
        $this->db->where('unit_id', $unit_id);
        $this->db->from('unit');
        $this->db->limit(1);
        $data=$this->db->get();
        return $data->result()[0]->name;
    }
    public function getUnitById($unit_id){
        $this->db->where('unit_id', $unit_id);
        $this->db->from('unit');
        $this->db->limit(1);
        $data=$this->db->get();
        return $data->row();
    }
    public function getDataAllUnidades(){
        $this->db->select('unit_id,name');
        $this->db->from('unit');
        $data=$this->db->get()->result();
        $unit=array();
        $unit[0]='Seleccione una unidad';
        foreach ($data as $value) {
            $unit[$value->unit_id]=$value->name;
        }
        return $unit;
    }

    function get_all($limit=10000, $offset=0,$col='name',$order='asc')
    {
        $this->db->from('unit');
        $this->db->where('deleted',0);
        if (!$this->config->item('speed_up_search_queries'))
        {
            $this->db->order_by($col, $order);
        }
        
        $this->db->limit($limit);
        $this->db->offset($offset);
        
        $return = array();
        
        foreach($this->db->get()->result_array() as $result)
        {
            $return[$result['unit_id']] = array('abbreviation' => $result['abbreviation']);
        }
        
        return $return;
    }

    function save($unit_abbrev, $unit_id = FALSE)
    {
        if ($unit_id == FALSE)
        {
            if ($unit_abbrev)
            {
                if($this->db->insert('unit',array('abbreviation' => $unit_abbrev, 'name' => $unit_abbrev)))
                {
                    return $this->db->insert_id();
                }
            }
            
            return FALSE;
        }
        else
        {
            $this->db->where('unit_id', $unit_id);
            if ($this->db->update('unit',array('abbreviation' => $unit_abbrev, 'name' => $unit_abbrev)))
            {
                return $unit_id;
            }
        }
        return FALSE;
    }

    function exist($table,$key){
        $query = $this->db->get_where($table, $key);
        return $query->num_rows();
    }

    function saveUnidad($name = "", $abbreviation = "")
    {
        if (!$this->exist('unit',array('name'=>$name)) and !$this->exist('unit',array('abbreviation'=>$abbreviation))) {
             if ($name!='' && $abbreviation!='') {
                if($this->db->insert('unit',array('abbreviation' => $abbreviation,'name' => $name)))
                {
                    return $this->db->insert_id();
                }
            }
            return FALSE;
        }else{
            return FALSE;
        }
    }
}

/* End of file Unit_model.php */
/* Location: ./application/models/Unit_model.php */