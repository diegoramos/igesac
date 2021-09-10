<?php

// Descargar padron reducido: http://www.sunat.gob.pe/descargaPRR/mrc137_padron_reducido.html
// Resultado al descomprimir: padron_reducido_ruc.txt 

set_time_limit(0);

function queryRucPadron($txtPath, $ruc)
{
    $handle = fopen($txtPath, "r") or die("No se puede abrir el txt");
    $lines = 0;
    $isFirst = true;

    while (!feof($handle)) {
        $line = fgets($handle, 1024);
        if ($isFirst) {
            $isFirst = false;

            $lines++;
            continue;
        }
        
        if (substr( $line, 0, 11) === $ruc) {
            // position: $lines
            return utf8_encode($line);
        }

        $lines++;
    }
    fclose($handle);
    
    return 'NO ENCONTRADO';
}

?>