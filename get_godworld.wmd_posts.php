<?php
require_once ('vendor/facebook/php-sdk/src/facebook.php');

$pdo = new PDO('mysql:host=localhost;dbname=fb_data_analysis', 'root', '');

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

if (create_tables($mysqli)) {
    main($mysqli);
} else {
    die(mysqli_error($mysqli));
}

function main($mysqli) {
    $post_table = 'post';
    $config = array('appId' => '506471126052324', 'secret' => '444c2fdaa1ecd0753fbb4ee6e7c6038e', 'allowSignedRequest' => false);
    $facebook = new Facebook($config);
    $user_id = $facebook->getUser();
    
    $sleep_time = 10;
    $interval_in_day = 30;
    
    $page_id = '219204214762378';
    
    if ($user_id) {
        
        // We have a user ID, so probably a logged in user.
        // If not, we'll get an exception, which we handle below.
        $url = "/$page_id/posts";
        $until = determine_until_form_all_posts($all_posts);
        $since = $until - $interval_in_day * 86400;
        $posts = $facebook->api($url, 'GET', array('since' => $since, 'until' => $until));
        
        foreach ($posts['data'] as $key => $post) {
            $mysqli->prepare("INSERT INTO $post_table (id, data, created, day) VALUES (?, ? ,? ,?)");

        }
        
        try {
            while (!is_last_page($posts)) {                
                $until = determine_until_form_all_posts($all_posts);
                $since = $until - $interval_in_day * 86400;
                $posts = $facebook->api($url, 'GET', array('since' => $since, 'until' => $until));
            }
        }
        catch(FacebookApiException $e) {
            $login_url = $facebook->getLoginUrl();
            echo 'Please <a href="' . $login_url . '">login.</a>';
        }
    } else {
        
        // No user, print a link for the user to login
        $login_url = $facebook->getLoginUrl();
        echo 'Please <a href="' . $login_url . '">login.</a>';
    }
}

function create_tables($mysqli) {
    $ddl = file_get_contents('ddl.sql');
    $result = false;
    if ($ddl) {
        if ($mysqli->multi_query($ddl)) {
            do {
                $result = $mysqli->store_result();
            } while ($mysqli->next_result());
        }
        return $result;
    }
} function determine_until_form_all_posts($all_posts) {
    if ($all_posts) {
        return strtotime(get_created_time(end($all_posts)));
    } else {
        return strtotime('now');
    }
}

function is_last_page($posts) {
    return !array_key_exists('paging', $posts) && array_key_exists('next', $posts['paging']);
}

function get_created_time($post) {
    
    return $post['created_time'];
}
?>