<?php

    require_once('config.php');
    
    // outputs all data as a table suitable for import into R
    
    // work out the column headers
    $headers = array();
    $headers[] = 'image_id';
    $headers[] = 'image_path';
    
    // one header for each label - lets make a list as we use them later
    $labels = array();
    $results = $mysqli->query("SELECT * FROM label ORDER BY id");
    
    while($row = $results->fetch_assoc()){
        $labels[] = $row;
        $safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', (string) $row['description']);
        $headers[] = "L_" . $row['id'] . "_" . $safe_name;
    }
    
    // scores for naturalness and arificialness
    $headers[] = 'naturalness';
    $headers[] = 'artificialness';
    $headers[] = 'label_count';
    
    // next we build in the columns from berman data
    $berman_cols = array();
    $results = $mysqli->query("SHOW COLUMNS IN berman_data");
    while($row = $results->fetch_assoc()){
        $berman_cols[] = $row['Field'];
        $safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', (string)$row['Field']);
        $headers[] = "B_" . $safe_name;
    }
    
    // Open a memory "file" for read/write...
    $fp = fopen('php://temp', 'r+');
    
    // ... write the headers out
    fputcsv($fp, $headers, ',', '"');
    
    // now we need to output each row of the file
    
    // build a list of all the images and hold it so we don't get multiple
    // query issues
    $results = $mysqli->query("SELECT * FROM image ORDER BY id");
    $images = $results->fetch_all(MYSQLI_ASSOC);
    
    foreach($images as $image){
        
        $csvRow = array();
    
        // image name stripped from the image path
        $image_name = substr($image['path'], strrpos($image['path'], '/') + 1);
        $image_name = substr($image_name, 0, strpos($image_name, '.'));
        
        // add the basic info
        $csvRow[] = str_pad($image['id'],  3, '0', STR_PAD_LEFT) . '_' . $image_name;
        $csvRow[] = $image['path'];
        
        // add the label scoring
        $csvRow[] = $image['calc_naturalness'];
        $csvRow[] = $image['calc_artificialness'];  
        $csvRow[] = $image['label_count'];  
        
        // now tag the berman results on the end
        // need to find the row that matches the image name
        $results = $mysqli->query("SELECT * FROM berman_data WHERE ImageName = '$image_name' ");
        $rows = $results->fetch_all(MYSQLI_ASSOC);
        if(count($rows) > 0){
            $berman_vals = $rows[0];
        }else{
            $berman_vals = array();
        }
        
        foreach($berman_cols as $berman_col){
            if(isset($berman_vals[$berman_col])){
                $csvRow[] = $berman_vals[$berman_col];
            }else{
                $csvRow[] = 'NA';
            }
        }
        
        // write it to the file
        fputcsv($fp, $csvRow, ',', '"');
    }
    
    // set the HTTP headers correctly
    header('Content-type: text/csv');
    $now = new DateTime();
    header('Content-Disposition: attachment; filename="'. $now->format(DATE_ATOM) .'.csv"');
    
    // dump the file to the output
    rewind($fp);
    fpassthru($fp); // write the whole thing out
    fclose($fp);  
    

?>