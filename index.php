<?php

// include your composer dependencies
require_once 'config.php';

$subject = false;

// if this is a post save the data first
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    // we must have a $subject or we can do nothing
    $subject = trim($_POST['subject']);
    if(!$subject){
        echo "We must have a subject";
        exit;
    }
    
    if(isset($_POST["naturalness"]) && isset($_POST["image_id"])){
        
        $image_id = $_POST["image_id"];
        $naturalness = $_POST["naturalness"];
        
        // save the naturalness score for this image.
        $mysqli->query("INSERT INTO image_evaluation (image_id, naturalness, subject) VALUES ($image_id, $naturalness, '$subject')");
        echo $mysqli->error;
            
    }
    
    // get a list of possible images
    $sql = "SELECT i.id, i.path FROM image as i LEFT JOIN image_evaluation as e on i.id = e.image_id AND e.subject = '$subject' WHERE e.id is null  AND i.evaluation = 1";
    $result  = $mysqli->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    shuffle($rows);
    $remaining = count($rows);
    $row = array_pop($rows);
    $image_id = $row['id'];
    $image_path = $row['path'];
    
}


?>
<!DOCTYPE html>
<html>
    <head>
        <title>Natural Image Evaluation Survey</title>
        <style>
            body{
                font-family: sans-serif;
            }
            #wrapper{
                margin-left: 1em;
                text-align: center;
            }
            #button-block input{
                width: 40px;
                height: 40px;
                font-size: 30px;
            }
            #button-block{
                display: inline-block;
                font-size: 30px;
            }
        </style>
    </head>
    <body>
        <div id="wrapper">
        <form method="POST" action="index.php">
        
        <div id="subject-block">
            <p>Your Name: <input type="text" name="subject" value="<?php echo $subject ?>"/></p>
            
            
        </div>
        
<?php 
    // we display the picture and scoring block only if we have a subject
    if($subject && $image_path){
?>
        <p><?php echo $remaining ?> of 100 remaining.</p>
        <input type="hidden" name="image_id" value="<?php echo $image_id ?>"/>
        <div id="image-block">
            <img src="<?php echo $image_path ?>"/>
        </div>
        <div id="button-block">
            Artificial
            <input type="submit" name="naturalness" value="1"/>
            <input type="submit" name="naturalness" value="2"/>
            <input type="submit" name="naturalness" value="3"/>
            <input type="submit" name="naturalness" value="4"/>
            <input type="submit" name="naturalness" value="5"/>
            <input type="submit" name="naturalness" value="6"/>
            <input type="submit" name="naturalness" value="7"/>
            Natural
        </div>
        <p>
            How natural are the things in this image?
        </p>

<?php
    }elseif ($subject && !$image_path) {
?>
    <h2>Finished! Thank you for your hard work.</h2>
    <a href="index.php">Next person.</a>
<?php
    }else{ // end subject check
?>
        <div id="start-block">
            <input type="submit" name="start" value="Start"/>
        </div>
<?php
    } // end no subject check
?>
    
        </form>
        
        </div>
    </body>
</html>
