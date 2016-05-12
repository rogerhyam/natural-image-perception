<?php

    require_once('config.php');
    
    
    // get a list of the sample points to work through - change this so
    // we don't over process stuff
    $points_sql = "SELECT * FROM sample_points";
    
    // run the query
    $result = $mysqli->query($points_sql);
    
    // load all the results into memory
    $point_rows = $result->fetch_all(MYSQLI_ASSOC);
    
    if(count($point_rows) < 1){
        echo "No points found that match: $points_sql";
        exit;
    }


    // for each point find the nearest panorama
    foreach($point_rows as $pr){
        
        $point_loc = $pr['sample_lat'] . ',' . $pr['sample_lon'];
        $pano_details = get_nearest_pano_location($point_loc, 10);
        
   
        print_r($pano_details);

        // if we fail - print a warning and crack on
        if($pano_details == null){
            echo "WARNING: No pano found within 1km of $point_loc for point: " . $pr['id'] ."\n";
            continue;
        }
        
        // update the db with the panorama details
        $stmt = $mysqli->prepare("UPDATE sample_points SET view_lat = ?, view_lon = ?,  pano_id = ?, heading = ?, distance = ? WHERE id = ?");
        $stmt->bind_param("ddsiii", $pano_details['view_lat'], $pano_details['view_lon'], $pano_details['pano_id'], $pano_details['heading'], $pano_details['distance'], $pr['id']);
        $stmt->execute();
        if($stmt->error){
            echo $stmt->error;
            exit;
        }
        $stmt->close();
        

        // get the image to go with the panorama
        
        // decide on a file name and download it
        $filename = 'data/streetview/SV_' . $pr['id'] . "_" . $pano_details['pano_id'] . '.jpg';
        $sv_image_url = "https://maps.googleapis.com/maps/api/streetview?size=640x640&pano=" . $pano_details['pano_id'] . "&heading=". $pano_details['heading'] . "&key=" . $api_keys['streetview_image'];
        file_put_contents($filename, fopen($sv_image_url, 'r'));
        
        // add it to the images table if it isn't already there
        $stmt = $mysqli->prepare("SELECT * FROM image WHERE path = ?");
        $stmt->bind_param("s", $filename);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows < 1){
            $stmt2 = $mysqli->prepare("INSERT INTO image (path) VALUES (?)");
            $stmt2->bind_param("s", $filename);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();
        
        
    } // for each point loop

    
   // $loc = $_GET['location']

   
    
    
    //echo 'https://maps.googleapis.com/maps/api/streetview?size=640x640&location=55.9666252,-3.2187310&key=' . $api_keys['streetview_image'];

//$loc ='55.9646556,-3.2162419';

//$loc ='55.9687868,-3.1917801';

// get_nearest_pano_location($loc, 10);

        
 //       header('Content-Type: image/jpeg');
   //     echo file_get_contents("https://maps.googleapis.com/maps/api/streetview?size=640x640&pano=$pano_id&heading=$bearing&key=" . $api_keys['streetview_image']);


function get_nearest_pano_location($loc, $radius){
    
    global $api_keys;
    
    $endpoint = "http://maps.google.com/cbk?output=json&hl=en&ll=$loc&radius=$radius&cb_client=maps_sv&v=4";
    
    //echo $endpoint . "\n";
    
    $handler = curl_init();
    curl_setopt($handler, CURLOPT_HEADER, 0);
    curl_setopt($handler, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handler, CURLOPT_URL, $endpoint);
    $data = curl_exec($handler);
    curl_close($handler);
    // if data value is an empty json document ('{}') , the panorama is not available for that point
    if ($data==='{}' && $radius <= 1000){
        return get_nearest_pano_location($loc, $radius + 10);
    }elseif ($data==='{}' && $radius > 1000){
        //echo "Radius: $radius \n";
        return null;
    }else{
        //echo $radius;
        
        $data = JSON_decode($data);
        
        $pano_id = $data->Location->panoId;
        $panoLat = $data->Location->lat;
        $panoLon = $data->Location->lng;
        list($pointLat,$pointLon) = explode(',', $loc); 
        list($distance, $heading) = GML_distance($panoLat, $panoLon, $pointLat, $pointLon);
    
   
        $result = array();
        $result['pano_id'] = $pano_id;
        $result['pano_loc'] = "$panoLat,$panoLon";
        $result['view_lat'] = $panoLat;
        $result['view_lon'] = $panoLon;
        $result['point_loc'] = "$pointLat,$pointLon";
        $result['distance'] = $distance;
        $result['heading'] = $heading;
        
        // var_dump($result);
        //var_dump($data);
        
        return $result;
        
    }
    
}


// ------------ distance calculation function ---------------------
   
    //**************************************
    //     
    // Name: Calculate Distance and Radius u
    //     sing Latitude and Longitude in PHP
    // Description:This function calculates 
    //     the distance between two locations by us
    //     ing latitude and longitude from ZIP code
    //     , postal code or postcode. The result is
    //     available in miles, kilometers or nautic
    //     al miles based on great circle distance 
    //     calculation. 
    // By: ZipCodeWorld
    //
    //This code is copyrighted and has
	// limited warranties.Please see http://
    //     www.Planet-Source-Code.com/vb/scripts/ShowCode.asp?txtCodeId=1848&lngWId=8    //for details.    //**************************************
    //     
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
    /*:: :*/
    /*:: This routine calculates the distance between two points (given the :*/
    /*:: latitude/longitude of those points). It is being used to calculate :*/
    /*:: the distance between two ZIP Codes or Postal Codes using our:*/
    /*:: ZIPCodeWorld(TM) and PostalCodeWorld(TM) products. :*/
    /*:: :*/
    /*:: Definitions::*/
    /*::South latitudes are negative, east longitudes are positive:*/
    /*:: :*/
    /*:: Passed to function::*/
    /*::lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees) :*/
    /*::lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees) :*/
    /*::unit = the unit you desire for results:*/
    /*::where: 'M' is statute miles:*/
    /*:: 'K' is kilometers (default):*/
    /*:: 'N' is nautical miles :*/
    /*:: United States ZIP Code/ Canadian Postal Code databases with latitude & :*/
    /*:: longitude are available at http://www.zipcodeworld.com :*/
    /*:: :*/
    /*:: For enquiries, please contact sales@zipcodeworld.com:*/
    /*:: :*/
    /*:: Official Web site: http://www.zipcodeworld.com :*/
    /*:: :*/
    /*:: Hexa Software Development Center © All Rights Reserved 2004:*/
    /*:: :*/
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
  function GML_distance($lat1, $lon1, $lat2, $lon2) { 
    $theta = $lon1 - $lon2; 
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
    $dist = acos($dist); 
    $dist = rad2deg($dist); 
    $miles = $dist * 60 * 1.1515;
	$bearingDeg = (rad2deg(atan2(sin(deg2rad($lon2) - deg2rad($lon1)) * 
	   cos(deg2rad($lat2)), cos(deg2rad($lat1)) * sin(deg2rad($lat2)) - 
	   sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon2) - deg2rad($lon1)))) + 360) % 360;
	
    $km = $miles * 1.609344; 
	return(array(round($km *1000),$bearingDeg));
  }
  

?>