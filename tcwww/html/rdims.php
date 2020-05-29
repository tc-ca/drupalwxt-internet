<?php
    
    $id = $_GET["id"];

    if(preg_match("/^\d+$/", $id)) {

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="RDIMS'.$id.'.DRF"');

        echo 'Document;RDIMS;'.$id.';R';
        
        exit();
    }
    else {
        echo 'Invalid RDIMS number. / Numéro SGDDI invalide.';
    }

?>