<?php

    require_once('config.php');
    
    $mysqli->query("UPDATE image SET evaluation = 0");
    
    pick_randoms(get_ids(0, 0.2), 20);
    pick_randoms(get_ids(0.2, 0.4), 20);
    pick_randoms(get_ids(0.4, 0.6), 20);
    pick_randoms(get_ids(0.6, 0.8), 20);
    pick_randoms(get_ids(0.8, 1), 20);
    
    function get_ids($min, $max){
        
        global $mysqli;
        
        $result = $mysqli->query("SELECT id FROM image WHERE path like '%SV_%' AND calc_naturalness >= $min AND calc_naturalness < $max");
        $image_rows = $result->fetch_all(MYSQLI_ASSOC);
        $ids = array();
        foreach($image_rows as $row){
            $ids[] = $row['id'];
        }
        
        return $ids;
        
    }

    function pick_randoms($ids, $n){
        shuffle($ids);
        for($i= 0; $i<$n; $i++){
            pick_random($ids);
        }
    }
    
    function pick_random(&$ids){
        
        global $mysqli;
        
        $image_id = array_pop($ids);
        
        echo $image_id . "\n";
        
        $mysqli->query("UPDATE image SET evaluation = 1 WHERE id = $image_id");
        
    }
    
?>