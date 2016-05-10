<?php

// include your composer dependencies
require_once 'vendor/autoload.php';


// let us get some credentials..
// these are typically set in the GOOGLE_APPLICATION_CREDENTIALS env
$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->setScopes([Google_Service_Vision::CLOUD_PLATFORM]);
$service = new Google_Service_Vision($client);

$feature = new Google_Service_Vision_Feature();
$feature->setType('LABEL_DETECTION');
$feature->setMaxResults(200);

$file_uri = 'data/berman/MDS600X800/MDS1.jpg';

$image_data = file_get_contents($file_uri);
$image_base64 = base64_encode($image_data);
$image = new Google_Service_Vision_Image();
$image->setContent($image_base64);

$payload = new Google_Service_Vision_AnnotateImageRequest();
$payload->setFeatures([$feature]);
$payload->setImage($image);

$body = new Google_Service_Vision_BatchAnnotateImagesRequest();
$body->setRequests([$payload]);

$res = $service->images->annotate($body);

$responses = $res->getResponses();

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Natural Image Perception</title>
    </head>
    <body>
        <h1>Natural Image Perception</h1>
        <p>This is a test application</p>

<?php

echo "<img style=\"max-width: 300px;\" src=\"$file_uri\">";

foreach($responses as $r){
    $annotations = $r->getLabelAnnotations(); // Google_Service_Vision_EntityAnnotation
    foreach($annotations as $a){
        echo "<div>";
        echo $a->getDescription();
        echo ":";
        echo $a->getScore();
        echo ":";
        echo $a->getMid();
        echo "</div>";
    }
    
}

?>
    </body>
</html>
