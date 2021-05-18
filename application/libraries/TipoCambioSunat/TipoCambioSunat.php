<?php

/**
 * Created by PhpStorm.
 * User: toni
 * Date: 5/15/2018
 * Time: 6:12 PM
 */
require_once __DIR__ . '/lib/simple_html_dom.php';

class TipoCambioSunat
{

    public function consultarTipoCambio()
    {
        
        $url = "http://e-consulta.sunat.gob.pe/cl-at-ittipcam/tcS01Alias";//ruta
        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_TIMEOUT,3);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpcode==200) {
            //all script ok
            try{
                $result = array();
                $html = file_get_html('http://e-consulta.sunat.gob.pe/cl-at-ittipcam/tcS01Alias', false, null, 0);
                $i = -1;
                $n = 0;
                $counter = 0;
                $temp = new stdClass();
                foreach ($html->find('table[class="class="form-table""] > tbody > tr') as $trs) {
                    if ($i++ == -1) continue;

                    foreach ($trs->find('td') as $td) {
                        if ($n == 0) $temp->fecha = trim($td->plaintext).date('/m/Y');
                        if ($n == 1) $temp->compra = trim($td->plaintext);
                        if ($n == 2) $temp->venta = trim($td->plaintext);

                        if ($n++ == 2) {
                            $result[$counter] = $temp;
                            $temp = new stdClass();
                            $n = 0;
                            $counter++;
                        }
                    }
                }
                return array_reverse($result);
            }catch(Exception $e){
                echo $e;
                return null;
            }

        }else{
            //script bad return false
            return null;
        }
    }
    
/*    public function consultarTipoCambio()
    {
        try{
            $result = array();
            $html = file_get_html('http://www.sunat.gob.pe/cl-at-ittipcam/tcS01Alias', false, null, 0);
            $i = -1;
            $n = 0;
            $counter = 0;
            $temp = new stdClass();
            foreach ($html->find('table[class="class="form-table""] > tbody > tr') as $trs) {
                if ($i++ == -1) continue;
                foreach ($trs->find('td') as $td) {
                    if ($n == 0) $temp->fecha = trim($td->plaintext).date('/m/Y');
                    if ($n == 1) $temp->compra = trim($td->plaintext);
                    if ($n == 2) $temp->venta = trim($td->plaintext);

                    if ($n++ == 2) {
                        $result[$counter] = $temp;
                        $temp = new stdClass();
                        $n = 0;
                        $counter++;
                    }
                }
            }
            return array_reverse($result);
        }catch(Exception $e){
            echo $e;
            return null;
        }
    }*/

}