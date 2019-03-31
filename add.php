<?php
    require_once("db_utils.php")

    if (isset($_GET['user'])) $user = $_GET['user'];
    if (isset($_GET['text'])) $text = $_GET['text'];

    if (!isset($user) || $user == '') die('no user');
    if (!isset($text) || $text == '') die('no text');
    
    $feed = (isset($_GET['feed'])) ? $_GET['feed'] : '#teamwatch';
    
    echo "\$user = $user\n";
    echo "\$text = $text\n";
    
    $text = html_entity_decode($text);
    
    $conn = getDB();

    $stmt = $conn->prepare("INSERT INTO `messages` () VALUES (NULL, :user, :text, NULL, :feed, 0, -1, '')");
    $stmt->bind_param(':user', $user);
    $stmt->bind_param(':text', $text);
    $stmt->bind_param(':feed', $feed);
    
    
    if (!$result = $stmt->execute()) {
        echo $conn->error;
    }
    
    //destroy the connection object
    $conn = null;
    
    echo "ok"
?>
