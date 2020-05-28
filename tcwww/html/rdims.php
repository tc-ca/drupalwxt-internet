<?php
    
    $rdims = $_GET["id"];

    if($rdims != ''){

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="RDIMS'.$rdims.'.DRF"');

        echo 'Document;RDIMS;'.$rdims.';R';
        
        exit();
    }
    else {
        echo 'Empty RDIMS number. / Numéro SGDDI vide.';
    }
?>