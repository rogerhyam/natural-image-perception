<?php

    require_once('config.php');
    
   // $loc = $_GET['location']

   
    
    
    //echo 'https://maps.googleapis.com/maps/api/streetview?size=640x640&location=55.9666252,-3.2187310&key=' . $api_keys['streetview_image'];

//$loc ='55.9646556,-3.2162419';

$loc ='55.9687868,-3.1917801';

get_nearest_pano_location($loc, 10);

function get_nearest_pano_location($loc, $radius){
    
    global $api_keys;
    
    $endpoint = "http://maps.google.com/cbk?output=json&hl=en&ll=$loc&radius=$radius&cb_client=maps_sv&v=4";
    $handler = curl_init();
    curl_setopt($handler, CURLOPT_HEADER, 0);
    curl_setopt($handler, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handler, CURLOPT_URL, $endpoint);
    $data = curl_exec($handler);
    curl_close($handler);
    // if data value is an empty json document ('{}') , the panorama is not available for that point
    if ($data==='{}' && $radius <= 1000){
        get_nearest_pano_location($loc, $radius + 10);
    }elseif ($data==='{}' && $radius > 1000){
        return null;
    }else{
        //echo $radius;
        
        $data = JSON_decode($data);
            
        $pano_id = $data->Location->panoId;
        $panoLat = $data->Location->lat;
        $panoLon = $data->Location->lng;
        list($pointLat,$pointLon) = explode(',', $loc); 
        list($distance, $bearing) = GML_distance($panoLat, $panoLon, $pointLat, $pointLon);
        
        $result['pano_id'] = $pano_id;
        $result['pano_loc'] = "$panoLat,$panoLon";
        $result['point_loc'] = "$pointLat,$pointLon";
        $result['distance'] = $distance;
        $result['bearing'] = $bearing;
        //var_dump($result);
        //var_dump($data);
        header('Content-Type: image/jpeg');
        echo file_get_contents("https://maps.googleapis.com/maps/api/streetview?size=640x640&pano=$pano_id&heading=$bearing&key=" . $api_keys['streetview_image']);
        
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