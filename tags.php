<?php
    $servername = "localhost"; 
    $database = "TeamWatch";
    $username = "<db username>";
    $password = "<db password>";

    $mysqli = mysqli_connect($servername, $username, $password, $database);
    if ($mysqli->connect_errno) {
        die ('ERROR');
    }
    
    $sec = (isset($_GET['m'])) ? intval($_GET['m'])*60 : 2*60*60;
    
    $mysqli->query("SET NAMES utf8");
    $mysqli->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

    // TAG CLOUD
    // SELECT DISTINCT feed FROM messages WHERE is_tweet=0 and not feed like '#tw:%' and UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(time) < 3600 ORDER BY time DESC
    
    $out = '';
    $sql = "SELECT DISTINCT feed FROM messages WHERE not feed like '#tw:%' and UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(time) < $sec ORDER BY time DESC LIMIT 5";
    
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $feed=$row['feed'];
                $sql = "SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(time) as tdiff FROM `messages` WHERE `feed` = '$feed' ORDER BY `time` DESC LIMIT 1";
                $t = $mysqli->query($sql)->fetch_assoc();
                
                $th = intval($t['tdiff']/3600);
                $tm = intval(($t['tdiff']-$th*3600)/60);
                $ts = $t['tdiff']-$th*3600-$tm*60;
                $out .= "$feed:$th:$tm:$ts%";
            }
            
            $result->close();
        }
    }
    $mysqli->close();
    echo ($out) ? "OK:".substr($out, 0, strlen($out)-1) : "OK:NOTAGS";
?>
