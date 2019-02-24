<?php
    header('Content-type:text/html; charset=utf-8');
    
    $servername = "localhost"; 
    $database = "TeamWatch";
    $username = "<db username>";
    $password = "<db password>";

    $feedlist = (isset($_COOKIE["TeamWatch-feedlist"])) ? $feedlist = $_COOKIE["TeamWatch-feedlist"] : "#teamwatch";
        
    if (isset($_POST['feed']) && isset($_POST['nickname']) && isset($_POST['message'])) {
        $mysqli = mysqli_connect($servername, $username, $password, $database) or die($json_res);
        if ($mysqli->connect_errno) {
            die ("ERROR");
        }


        // *** QUERYSTRING GET ****************************************************************************************** //
        
        $feed = $mysqli->real_escape_string($_POST['feed']);
        $user = $mysqli->real_escape_string($_POST['nickname']);
        $text = $mysqli->real_escape_string($_POST['message']);
        
        if (strpos($feedlist, $feed) === false) {
            $feedlist .= ":$feed";
        }
        
        if (substr($text, 0, 12) === "#tw:setfeed:") {
            $save_feed = substr($text, 12);
            $feed = "#tw:settings";
        } else if (substr($text, 0, 4) === "#tw:") {
            $save_feed = $feed;
            $feed = "#tw:settings";
        }
        
        // ************************************************************************************************************** //

        $mysqli->query("SET NAMES utf8");
        $mysqli->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

        $sql = "INSERT INTO `messages` VALUES (NULL, '" . $user . "', '" . $text . "', NULL, '" . $feed . "', 0, -1, '')";
        $result = $mysqli->query($sql);
        $mysqli->close();
        
        setcookie("TeamWatch-feedlist", $feedlist, time() + (86400 * 30), "/");
    } else {
        $feedlist = "#teamwatch";
    }
    
    echo "OK:$feedlist";
?>
