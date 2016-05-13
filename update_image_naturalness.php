<?php

    require_once('config.php');
    
    // build a list of the labels to use below
    $labels = array();
    $results = $mysqli->query("SELECT * FROM label ORDER BY id");
    while($row = $results->fetch_assoc()){
        $labels[] = $row;
    }
    
    // works through all the images and updates their naturalness and artificialness
    // scores according to the scoring of the labels.
    
    $sql = "SELECT id FROM image";
    $result = $mysqli->query($sql);
    $image_ids = $result->fetch_all(MYSQLI_ASSOC);

    foreach($image_ids as $row){
        
        $image_id = $row['id'];
        
        $results = $mysqli->query("SELECT l.id as id, l.naturalness as naturalness FROM `label` AS l JOIN scoring AS s ON l.id = s.label_id WHERE s.image_id = $image_id ");
        $scores = array();
        $naturals = array();
        $artificials = array();
        while($row = $results->fetch_assoc()){
            
            $scores[] = $row['id'];
            
            if($row['naturalness'] > 0){
                $naturals[] =  $row['id'];
            }
            
            if($row['naturalness'] < 0){
                $artificials = $row['id'];
            }
            
        }
        foreach($labels as $l){
            if(in_array($l['id'], $scores)){
                $csvRow[] = 1;
            }else{
                $csvRow[] = 0;
            }
        }
        
        $label_count = count($scores);
        
        // catch division by zero
        if(count($scores) > 0){
            $naturalness = count($naturals) / $label_count;
            $artificialness = count($artificials) / $label_count;
        }else{
            $naturalness = 0;
            $artificialness = 0;    
        }
        
        
        
        
        $sql = "UPDATE image SET calc_naturalness = $naturalness, calc_artificialness = $artificialness, label_count = $label_count WHERE id = $image_id";
        $mysqli->query($sql);
        
        echo "$image_id: $naturalness : $artificialness \n";
        
    }
    
    
    
    
?>