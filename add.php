<?php
    $servername = "localhost"; 
    $database = "TeamWatch";
    $username = "<db username>";
    $password = "<db password>";

    $mysqli = mysqli_connect($servername, $username, $password, $database) or die($json_res);
    if ($mysqli->connect_errno) {
        die (json_error($mysqli->connect_error));
    }

    if (isset($_GET['user'])) $user = $mysqli->real_escape_string($_GET['user']);
    if (isset($_GET['text'])) $text = $mysqli->real_escape_string($_GET['text']);

    if (!isset($user) || $user == '') die('no user');
    if (!isset($text) || $text == '') die('no text');
    
    $feed = (isset($_GET['feed'])) ? $_GET['feed'] : '#teamwatch';
    
    echo "\$user = $user\n";
    echo "\$text = $text\n";
    
    $text = html_entity_decode($text);
    
    $mysqli->query("SET NAMES utf8");
    $mysqli->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

    $sql = "INSERT INTO `messages` VALUES (NULL, '" . $user . "', '" . $text . "', NULL, '$feed', 0, -1, '')";
    echo $sql . "\n";
    
    if (!$result = $mysqli->query($sql)) {
        echo $mysqli->error;
    }
    
    $mysqli->close();
    
    echo "ok"
?>
