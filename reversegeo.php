<?php

    require_once('config.php');
    
    // get a list of all the sample_points
    $sql = "SELECT * FROM sample_points";
    
    $result = $mysqli->query($sql);

    // load all the results into memory
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    
    if(count($rows) < 1){
        echo "No points found that match: $sql";
        exit;
    }
    
    
    // look up the postcode with google
    
    
    foreach($rows as $r){
        
        $postcodes = array();
        
        $sample_id = $r['id'];
        $loc = $r['sample_lat'] . ',' . $r['sample_lon'];
        $key = $api_keys['streetview_image'];
        $uri = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$loc&key=$key";
        $json = file_get_contents($uri);
        $data = JSON_decode($json);
        
        foreach($data->results as $address){
            if(isset($address->address_components)){
                foreach($address->address_components as $part){
                    if(in_array('postal_code', $part->types)){
                        $postcodes[] = $part->long_name;
                    }
                }
                
            }
        }
        
        print_r($postcodes);
        
        $simd_id = get_simd_id($postcodes);
        $postcodes_str = implode('|', $postcodes);
        
        // write it to the db
        /*
        $sql = "UPDATE sample_points SET simd_id = $simd_id AND postcodes = '$postcodes_str' WHERE id = $sample_id";
        $mysqli->query($sql);
        echo $sql;
        */
        
        $stmt = $mysqli->prepare("UPDATE sample_points SET simd_id = ?,  postcodes = ? WHERE id = ?");
        $stmt->bind_param("isi", $simd_id, $postcodes_str, $sample_id);
        $stmt->execute();
        $stmt->close();

    }
    
    
    function get_simd_id($postcodes){
        
        global $mysqli;
        
        foreach($postcodes as $pc){
        
            $sql = "SELECT id FROM simd WHERE postcode = '$pc'";
            $result = $mysqli->query($sql);
            // load all the results into memory
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            
            if(count($rows) > 0){
                return $rows[0]['id'];
            }
            
        }
        
        return -1;
    
        
    }
    
    //
    
    
    
    
    // if there are multipl
    
    // write the postcode to the sample_table
    // - we will join it to the simd table later
    
    
      
    
?>