<?php
/*
$id_chat (idc)    = indice messaggi chat
$id_twitter (idt) = indice messaggi twitter  *******  si può usare l'id twitter?
$query (q)        = query
$twid (twid)      = id teamwatch

se $id_chat e $id_twitter sono -1
(SELECT * FROM `teamwatch` WHERE `is_tweet` = 1 and `feed` like '%$query%' ORDER by id DESC LIMIT 1)
--> idt = $row->id_twitter
(SELECT * FROM `teamwatch` WHERE `is_tweet` = 0 and `feed` like '%$query%' ORDER by id DESC LIMIT 1)
--> idc = $row->id
--> restituisce staus = setting con idt e idc più alti per la query q
    
se trova un setting con id > idc
(SELECT * FROM `teamwatch` WHERE `id` > $id_chat and `user` = '$twid' ORDER by id DESC LIMIT 1)
--> restituisce il status = setting, text = text

se trova un messaggio con id > idc, feed like %q% e is_tweet = 0
(SELECT * FROM `teamwatch` WHERE `id` > $id_chat and `feed` like '%$query%' and not `text` like '#tw:%' and `is_tweet` = 0 ORDER by id DESC LIMIT 1)
--> restituisce messaggio chat

se trova un messaggio con id > idt, feed like %q% e is_tweet = 1
(SELECT * FROM `teamwatch` WHERE `id_twitter` > $id_twitter and `feed` like '%$query%' and `is_tweet` = 1 ORDER by id DESC LIMIT 1)
--> restituisce messaggio twitter

recupera messaggi da twitter e li salva nel database
--> restituisce il primo messaggio twitter con id_twitter > $id_twitter
*/

    require_once("db_utils.php")
    require_once('TwitterAPIExchange.php');
    
    $settings = array(
        'oauth_access_token' => "*******secret*******",
        'oauth_access_token_secret' => "*******secret*******",
        'consumer_key' => "*******secret*******",
        'consumer_secret' => "*******secret*******"
    );

    // id, user, text, time, feed, is_tweet, twitter_id

    define('DEBUG', 0);
    if (DEBUG == 1) {
        error_reporting(E_ALL);
    } else {
        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
    }

    function json_error ($reason) {
        $json_answer = array (
            'status' => 'fail',
            'reason' => $reason
        );
        return json_encode($json_answer);
    }

    // ******* GET DATABASE ****************************************************************************************
    $conn = getDB();
    
    // ******* GET PARAMETERS ****************************************************************************************

    $id_chat = intval($_GET['idc']);
    $id_twitter = intval($_GET['idt']);
    if (isset($_GET['q'])) $query = $_GET['q'];
    if (isset($_GET['twid'])) $twid = $_GET['twid'];
    if (isset($_GET['pcid'])) $pcid = $_GET['pcid'];
    $twitter_query_params = $_GET['tqp'];
    $twitter_lang = (isset($_GET['tl'])) ? $_GET['tl'] : '%';
    
    if (isset($query)) {
        $query_array = explode(":", $query);

        $twitter_query = implode (" OR ", $query_array);

        $i = count($query_array);
        $sql_query = '';sql_select

        while ($i) {
            $q = $query_array[--$i];
            $sql_query .= " `feed` like '%$q%' ";
            if ($i>0) $sql_query .= "OR";
        }
    }
    
    if (isset($_GET['godmode'])) $sql_query .= " OR 1"; 

    
    if (DEBUG == 1) {
        echo "\$id_chat = $id_chat<br>";
        echo "\$id_twitter = $id_twitter<br>";
        echo "\$query = $query<br>";
        echo "\$twid = $twid<br>";
        echo "\$pcid = $pcid<br>";
        echo "\$twitter_query_parms = $twitter_query_params<br>";
        echo "\$twitter_query = $twitter_query<br>";
        echo "\$sql_query = $sql_query<br>";
        echo "<br>";
    }

    // ******* SETTINGS #TW:ID ***************************************************************************************
    if (!isset($_GET['idc'], $_GET['idt']) || ($id_chat == -1 && $id_twitter == -1)) {
        // ******* GET LAST TWITTER_ID ***********************************************************************************
        $stmt = $conn->query('SELECT twitter_id FROM messages WHERE is_tweet = 1 ORDER by twitter_id DESC LIMIT 1');
        $id_twitter = $stmt->fetchColumn();
        $id_twitter = ($res) ? $res['twitter_id'] : 0;
        
        // ******* GET LAST CHAT_ID **************************************************************************************
        $stmt = $conn->query('SELECT id FROM messages WHERE is_tweet = 0 ORDER by id DESC LIMIT 1');
        $id_chat = $stmt->fetchColumn();
        $id_chat = ($res) ? $res['id'] : 0;
        die("{\"status\":\"settings\", \"param\": \"#tw:id\", \"idc\": $id_chat, \"idt\": $id_twitter}");
    }
    
    if (!isset($twid) || $twid == '') die(json_error('missing parameter twid'));
    
    // ******* SETTINGS #TW:... **************************************************************************************
    if (isset($pcid) && $pcid != '') {
        $stmt = $conn->prepare('SELECT text, id FROM messages WHERE id > :id_chat and (user = :twid or user = :pcid) ORDER by id DESC LIMIT 1');
        $stmt->bind_param(':pcid', $pcid);
    
    } else {
        $stmt = $conn->prepare('SELECT text, id FROM messages WHERE id > :id_chat and user = :twid ORDER by id DESC LIMIT 1');
    }
    $stmt->bind_param(':id_chat', $id_chat);
    $stmt->bind_param(':twid', $twid);
    $stmt->execute(); 
    $res = $stmt->fetch();
    if ($res) {
        $json_answer = array (
            'status' => 'settings',
            'param' => $res['text'],
            'id' -> $res['id']
        );
        die(json_encode($json_answer));
    }
    
    if (!isset($query) || $query = '') die(json_error('missing parameter q'));    

    // ******* GET NEXT CHAT MESSAGE *********************************************************************************
    //TODO: THIS IS DUMB AND STUPID ($sql_query), ask max00xam what he want to do
    $stmt = $conn->prepare("SELECT id, user, text, feed, time FROM messages WHERE id > :id_chat and (:sql_query) and not text like '#tw:%' and is_tweet = 0 ORDER by id LIMIT 1");
    $stmt->bind_param(':id_chat', $id_chat);
    $stmt->bind_param(':sql_query', $sql_query);
    $stmt->execute(); 
    $res = $stmt->fetch();
    if ($res) {
        $json_answer = array (
            'status' => 'ok',
            'id' => $res['id'],
            'user' -> $res['user'],
            'text' => $res['text'],
            'is_twitter' => 0,
            'time' => $res['time']
        );
        if (isset($_GET['godmode'])) {
            $json_answer['feed'] = $res['feed'];
        }
        
        die(json_encode($json_answer));
    }
    
    if (!isset($_GET['notweet'])) {
        // ******* GET NEXT TWITTER MESSAGE ******************************************************************************
        $stmt = $conn->prepare("SELECT twitter_id, user, text, time FROM messages WHERE twitter_id > :id_twitter and (:sql_query) and is_tweet = 1 and twitter_lang = :twitter_lang ORDER by twitter_id LIMIT 1");
        $stmt->bind_param(':id_twitter', $id_twitter);
        $stmt->bind_param(':sql_query', $sql_query);
        $stmt->bind_param(':twitter_lang', $twitter_lang);
        $stmt->execute(); 
        if ($res) {
            $json_answer = array (
                'status' => 'ok',
                'id' => $res['twitter_id'],
                'user' -> $res['user'],
                'text' => $res['text'],
                'is_twitter' => 1,
                'time' => $res['time']
            );
            die(json_encode($json_answer));
        }
    
        $stmt = $conn->query("SELECT last_request_time FROM twitter_access WHERE 1 LIMIT 1");
        $res = $stmt->fetchColumn();
        if ($res) {
            $tdiff = time() - strtotime($res['last_request_time']);
            if ($tdiff < 10) {
                $t = 10-$tdiff;
                die(json_error("waiting $t secs to avoid search rate limit"));
            }
        }
        
        // ******* TWITTER API SEARCH ************************************************************************************
        $url = 'https://api.twitter.com/1.1/search/tweets.json';
        // $twitter_query_params = "+-filter:retweets&lang=it&result_type=recent&count=15";
        $getfield = "?q=($twitter_query)%20-filter:retweet$twitter_query_params&count=15&since_id=$id_twitter";
        if (DEBUG == 1) echo "Twitter: $url$getfield";
        $requestMethod = 'GET';
        $twitter = new TwitterAPIExchange($settings);
        $response = $twitter->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

        $statuses = json_decode($response)->statuses;

        if (count($statuses) == 0) {
            // ******* NO NEW TWEETS SAVE LAST API/SEARCH TIME FOR RATE LIMIT ************************************************
            $conn->query("UPDATE twitter_access SET result=no feeds, last_request_time=CURRENT_TIMESTAMP WHERE 1")->execute();
            die(json_error('no feeds'));
        } else {
            // ******* SAVE LAST API/SEARCH TIME FOR RATE LIMIT **************************************************************
            $conn->query("UPDATE `twitter_access` SET result='ok', last_request_time=CURRENT_TIMESTAMP WHERE 1")->execute();

            // ******* ADD THE NEW TWEETS TO THE DATABASE ********************************************************************
            $stmt = $conn->prepare("INSERT INTO `messages` VALUES (NULL, ?, ?, NULL, ?, 1, ?, ?");
            for($index = count($statuses)-1; $index >= 0; $index--) {
                $status = $statuses[$index];

                // test the tweet text+username against all tags to find the one that matches
                foreach ($query_array as $tag)
                    if (strpos($status->text . $status->user->name, $tag) !== false)
                        break;

                // test if it is a retweet if "RT @" is found then continue to the next tweet
                if (strpos($status->text, "RT @") !== false)  continue;
                
                if (DEBUG == 1) echo $status->id . "<br>";
                
                $stmt->execute([$status->user->name, $status->text, $tag, $status->id, $status->lang]);
            }
            
            // ******* RETURN THE OLDEST TWEET *******************************************************************************
            $older = $statuses[count($statuses)-1];
            if (DEBUG == 1) echo $older->id . "<br>";
            if ($older == NULL)
                die(json_error('no feeds'));
            else{
                $json_answer = array (
                    'status' => 'ok',
                    'id' => $older->id,
                    'user' -> $older->user->name,
                    'text' => $older->text,
                    'is_twitter' => 1,
                    'time' => $older->created_at
                );
                die(json_encode($json_answer));
            }
        }
    }
    
    die(json_error('no feeds'));
?>
