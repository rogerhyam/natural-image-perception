<?php

/*
    simple commandline script to run through subset of images
    and call the google vision api then store the labels
    
    expects the SQL below to be edited to something sensible
    so we can batch and not re-do them.
*/ 

$images_sql = "SELECT * FROM image WHERE path LIKE '%/SV_%' and id = 1076";

// include composer dependencies and db setup
require_once('config.php');

// run the query
$result = $mysqli->query($images_sql);

// load all the results into memory
$image_rows = $result->fetch_all(MYSQLI_ASSOC);

if(count($image_rows) < 1){
    echo "No images found that match: $images_sql";
    exit;
}

// we are going to use the service so set it up
// these are typically set in the GOOGLE_APPLICATION_CREDENTIALS env
$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->setScopes([Google_Service_Vision::CLOUD_PLATFORM]);
$service = new Google_Service_Vision($client);

// we use the same feature for each call
$feature = new Google_Service_Vision_Feature();
$feature->setType('LABEL_DETECTION');
$feature->setMaxResults(200);


// iterate through the results calling the vision api
foreach($image_rows as $ir){

    $image_path = $ir['path'];
    $image_id = $ir['id'];
    
    echo "\n$image_id: $image_path ... ";
    
    // create the vision image object
    $image_data = file_get_contents($image_path);
    $image_base64 = base64_encode($image_data);
    $image = new Google_Service_Vision_Image();
    $image->setContent($image_base64);

    echo "loaded ... ";
    
    // create the request
    $payload = new Google_Service_Vision_AnnotateImageRequest();
    $payload->setFeatures([$feature]);
    $payload->setImage($image);
    
    $body = new Google_Service_Vision_BatchAnnotateImagesRequest();
    $body->setRequests([$payload]);
    
    // actually call the service - this costs money!
    $res = $service->images->annotate($body);

    $responses = $res->getResponses();
    
    echo "\n";

    foreach($responses as $r){
        $annotations = $r->getLabelAnnotations(); // Google_Service_Vision_EntityAnnotation
        foreach($annotations as $a){

            echo '    Description: ' . $a->getDescription() . "\n";
            echo '    Score: ' . $a->getScore() . "\n";
            echo '    Mid: ' . $a->getMid() . "\n";
        
            $label_id = get_label_id($a->getMid(), $a->getDescription());
            set_score($image_id, $label_id, $a->getScore());
            
        }
        
    }

}

function set_score($image_id, $label_id, $score){
    
    global $mysqli;
    
    // see if it exists
    $stmt = $mysqli->prepare("SELECT * FROM scoring WHERE image_id = ? AND label_id = ?");
    $stmt->bind_param("ii", $image_id, $label_id);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows > 0){
        $stmt2 = $mysqli->prepare("UPDATE scoring SET score = ? WHERE image_id = ? AND label_id = ? AND created = now()");
        $stmt2->bind_param("dii", $score, $image_id, $label_id);
        $stmt2->execute();
        $stmt2->close();
        $stmt->close();
        echo "WARNING: Updated scoring for label: $label_id, image: $image_id to $score\n";
        return;
    }
    $stmt->close();
    
    // doesn't exist so create it
    $stmt = $mysqli->prepare("INSERT INTO scoring (image_id, label_id, score) VALUES (?,?,?)");
    $stmt->bind_param("iid", $image_id, $label_id, $score);
    $stmt->execute();
    $stmt->close();
    echo "    Set scoring for label: $label_id, image: $image_id to $score\n";

}

function get_label_id($mid, $description){
    
    global $mysqli;
    
    // see if it exists
    $stmt = $mysqli->prepare("SELECT id FROM label WHERE uri = ? AND description = ?");
    $stmt->bind_param("ss", $mid, $description);
    $stmt->execute();
    $stmt->store_result();
    
    // if we find one
    if($stmt->num_rows > 0){
        $stmt->bind_result($label_id);
        $stmt->fetch();
        $stmt->close();
        return $label_id;
    }
    
    // not found one so make it
    $stmt = $mysqli->prepare("INSERT INTO label (uri, description) VALUES (?,?)");
    $stmt->bind_param("ss", $mid, $description);
    $stmt->execute();
    return $mysqli->insert_id;
    
}


?>