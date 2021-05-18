<?php
/**
 * Created by PhpStorm.
 * User: toni
 * Date: 5/15/2018
 * Time: 6:13 PM
 */
require_once './TipoCambioSunat.php';

$tipo_cambio = new TipoCambioSunat();

var_dump($tipo_cambio->consultarTipoCambio());