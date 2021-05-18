<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?=$info->serie?></title>
    <style>
        .text11{
            font-size: 11px;
        }
        .text12{
            font-size: 12px;
        }
        .text14{
            font-size: 14px;
        }
        .text16{
            font-size: 16px;
        }
        .text18{
            font-size: 18px;
        }
        .negrita{
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div align="center">
        <table width="900" border="0">
            <tr>
                <td style="width: 100%;">
                    <table style="width: 100%">
                        <tr>
                            <td style="width: 30%;">
                                <?php if($ruta_img!='') {?>
                                    <?php echo img(array('src' => $ruta_img)); ; ?>
                                <?php } else { ?>
                                    <?php echo H($company); ?>
                                <?php } ?>
                            </td>
                            <td style="width: 40%;">
                                <span class="text14 negrita"><?=$company?></span><br>
                                RUC: <?=$ruc_company?><br>
                                <?=$website?>
                                </ul>
                            </td>
                            <td style="width: 30%;border: 1px solid #000;" align="center">
                                <span class="text14 negrita">GUIA DE REMISIÓN REMITENTE</span>
                                <br>
                                <?=$info->serie?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width: 100%;padding-bottom: 5px;">
                    <table style="width: 100%;border-collapse: collapse;border: 1px solid #000;" border="0">
                        <tr>
                            <td class="negrita text16" style="border: 1px solid #000;">DESTINATARIO</td>
                        </tr>
                        <tr>
                            <td style="border: none;">Razón Social: <?=$info->cliente_nombre?></td>
                        </tr>
                        <tr>
                            <td style="border: none;">RUC: <?=$info->cliente_ruc?></td>
                        </tr>
                        <tr>
                            <td style="border: none;">Dirección: <?=$info->cliente_direccion?></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width: 100%;padding-bottom: 5px;">
                    <table style="width: 100%;border-collapse: collapse;border: 1px solid #000;" border="0">
                        <tr>
                            <td colspan="2" class="negrita text16" style="border: 1px solid #000;">ENVIO</td>
                        </tr>
                        <tr>
                            <td style="border: none;">Fecha Emisión:  <?=$info->fecha_emision?></td>
                            <td style="border: none;">Fecha Inicio de Traslado: <?=$info->fecha_traslado?></td>
                        </tr>
                        <tr>
                            <td style="border: none;">Motivo Traslado: <?=$info->motivo_traslado?></td>
                            <td style="border: none;">Modalidad de Transporte: <?=$info->modalida_traslado?></td>
                        </tr>
                        <tr>
                            <td style="border: none;">Peso Bruto Total(NIU): <?=$info->peso_total?></td>
                            <td style="border: none;">Número de Bultos: <?=$info->numero_paquetes?></td>
                        </tr>
                        <tr>
                            <td style="border: none;">P.Partida: <?=$info->partida_ubigeo?> - <?=$info->partida_direccion?></td>
                            <td style="border: none;">P.Llegada: <?=$info->llegada_ubigeo?> - <?=$info->llegada_direccion?></td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td style="width: 100%;padding-bottom: 5px;">
                    <table style="width: 100%;border-collapse: collapse;border: 1px solid #000;" border="0">
                        <tr>
                            <td colspan="2" class="negrita text16" style="border: 1px solid #000;">TRANSPORTE</td>
                        </tr>
                        <tr>
                            <td style="border: none;">Razón Social:   <?=$info->transortista_razonSocial?></td>
                            <td style="border: none;">RUC:  <?=$info->transortista_numero?></td>
                        </tr>
                        <tr>
                            <td style="border: none;">Número de placa del vehículo: <?=$info->conductor_placa?></td>
                            <td style="border: none;">Conductor:  <?=$info->conductor_numero?></td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td style="width: 100%;padding-bottom: 5px;">
                    <table style="width: 100%;border-collapse: collapse;border: 1px solid #000;" border="0">
                        <tr>
                            <td style="border: none;" class="negrita">Item</td>
                            <td style="border: none;" class="negrita">Código</td>
                            <td style="border: none;" class="negrita">Descripción</td>
                            <td style="border: none;" class="negrita">Unidad</td>
                            <td style="border: none;" class="negrita">Cantidad</td>
                        </tr>
                        <?php $aa=1; foreach ($info->detalle as $key => $item) { ?>
                        <tr>
                            <td style="border-right: none;" class="text14" align="center"><?=$aa?></td>
                            <td style="border-right: none;border-left: none;" align="center" class="text16"><?=$item->codigo?></td>
                            <td style="border-right: none;border-left: none;" class="text16"><?=$item->name?></td>
                            <td style="border-right: none;border-left: none;" align="center" class="text16"><?=$item->unidad_medida?></td>
                            <td style="border-right: none;border-left: none;" align="center" class="text16"><?=$item->cantidad?></td>
                        </tr>
                        <?php $aa++; } ?>
                    </table>
                </td>
            </tr>

            <tr>
                <td style="width: 100%;padding-bottom: 5px;">
                    <table style="width: 100%;border-collapse: collapse;border: 1px solid #000;" border="0">
                        <tr>
                            <td class="negrita text14" style="border: 1px solid #000;">OBSERVACIONES</td>
                        </tr>
                        <tr>
                            <td style="border: none;"><?=$info->observacion?></td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td style="width: 100%;padding-bottom: 5px;">
                    <table style="width: 100%;border-collapse: collapse;border: 1px solid #000;" border="0">
                        <tr>
                            <td class="negrita text14" style="border: 1px solid #000;">HASH CPE</td>
                        </tr>
                        <tr>
                            <td style="border: none;"><?=$info->hash_cpe?></td>
                        </tr>
                    </table>
                </td>
            </tr>
            
        </table>
    </div>
</body>
</html>