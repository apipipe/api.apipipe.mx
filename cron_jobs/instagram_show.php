<?php
$server = "localhost";
$user = "id8020227_master";
$pwd = "buVXc3nyEIZkXLmq";
$db = "id8020227_apipipe";

// Create connection
$conn = new mysqli($server, $user, $pwd, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$date= date('Y-m-j H:i:s'); 
$now = strtotime ( '-6 hour' , strtotime ($date));
$now = date ( 'Y-m-j H:i:s' , $now);

function call($keyword, $input)
{
  $api = "https://apipipe.000webhostapp.com/apipipe.mx/content/web";
  $ch = curl_init();
  $options = [
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_URL            => "$api/dictionary/keyword/$keyword?input=$input"
  ];

  curl_setopt_array($ch, $options);

  if ($json_file = curl_exec($ch)) {
      $json_data = json_decode($json_file, true);
  } else {
      return false;
  }
  curl_close($ch);
  return $json_data;
}

function get_info($username)
{
  $json_data = call('instagram_info', $username);
  if ($json_data) {
    $info = $json_data['data'];
    if (!empty($info['user']['id'])) {
      $extract = [
        'idinstagram' => $info['user']['id'],
        'username' => $info['user']['username'],
        'posts' => $info['posts'],
        'followers' => $info['followers'],
        'following' => $info['following']
      ];
      if (!$info['user']['is_private']) {
        $extract['last_posts'] = $info['last_posts'];
      }
    } else {
      $extract = false;
    }
    return $extract;
  }
  return 'JSON incorrect!';
}

function send_telegram($users)
{
  echo '<pre>Sending Telegram message...</pre>';
  $sending_message = '';
  if (count($users)) {
    // call('telegram_send', '_Total changed: '.$total_users.'_');
    $sending_message .= '_Total changed: '.count($users).'_';
    foreach ($users as $user) {
      $tags = implode('.', explode('|', $user['tag']));
      $sending_message .= "{$user['username']} {$user['posts']} ; {$user['followers']} ; {$user['following']} $tags";
    }
  } else {
    $sending_message = '_No user data changes!_';
  }
  $request = call('telegram_send', urlencode($sending_message));
  if ($request['data']) { 
    echo '<pre>Request: correct</pre>';

  } else {
    echo '<pre>Request: error request</pre><pre>-';var_dump($request); echo '</pre>';
  }
}

function insert_data($data)
{
  GLOBAL $conn, $now;

  $sql = "INSERT INTO instagram_tool (idinstagram, username, posts, followers, following, tag, timestamp, new_posts) VALUES ";

  $sql_d = [];
  $print = '';
  foreach ($data as $d) {
    if (!is_array ($d)) break;
    $new_posts = 'NULL';
    if (isset($d['last_posts'])) $new_posts = "'".json_encode($d['last_posts'])."'";
    $sql_d[] = "('".$d['idinstagram']."', '".$d['username']."', ".$d['posts'].", ".$d['followers'].", ".$d['following'].", '".$d['tag']."', '$now', $new_posts)";
    $print .= "<pre>[".$d['username']."] Trying data inserted</pre>";
  }
  if (count($sql_d) > 0){
    $sql .= implode(', ', $sql_d).';';
    if ( $resultado = $conn->query($sql)) {
      echo "$print<pre>All data before has been inserted</pre>";
      // $resultado->close();
      return true;
    } else {
      echo "<pre>error to inser db `".$sql."`\n".$conn->error."</pre><pre>$sql</pre>";
      return false;
    }
  } else {
    echo '<pre>Something is wrong</pre><pre>';
    print_r($data);
    echo '</pre>';
  }
}

function type_tag($req, $db)
{
  $tag = [];
  if ((int)$db['posts'] < (int)$req['posts']) $tag[] = 'new_post';
  if ((int)$db['posts'] > (int)$req['posts']) $tag[] = 'del_post';
  if ((int)$db['followers'] < (int)$req['followers']) $tag[] = 'new_follower';
  if ((int)$db['followers'] > (int)$req['followers']) $tag[] = 'del_follower';
  if ((int)$db['following'] < (int)$req['following']) $tag[] = 'new_following';
  if ((int)$db['following'] > (int)$req['following']) $tag[] = 'unsb_following';
  return implode('|', $tag);
}

// START SHOW

require_once('usernames.php');

if (isset($usernames) && is_array($usernames) && count($usernames) > 0) {
  echo '<pre>Total usernames:'.count($usernames).'</pre>';

  $users = [];
  foreach ($usernames as $username) {
    $user = get_info($username);

    if (!is_array($user)) {
      echo '<pre>Error to get info from '.$username.'</pre>';
      continue;
    }

    $sql = "SELECT posts, followers, following FROM instagram_tool WHERE username = '$username' ORDER BY timestamp DESC LIMIT 1;";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
      $r = $result->fetch_assoc();
      if ((int)$r['posts'] != (int)$user['posts'] 
        || (int)$r['followers'] != (int)$user['followers'] 
        || (int)$r['following'] != (int)$user['following']) {
        $diff_posts = ((int)$user['posts'] - (int)$r['posts']);
        if (isset($user['last_posts']) && $diff_posts > 0) {
          $user['last_posts'] = array_slice($user['last_posts'], 0, $diff_posts);
        } else if (isset($user['last_posts'])) {
          unset($user['last_posts']);
        }
        $user['tag'] = type_tag($user, $r);
        $users[] = $user;
      }
    } else {
      $user['tag'] = 'new_user';
      $users[] = $user;
    }
    // sleep(1);
  }
  if (count($users)) {
    insert_data($users);
  } else echo '<pre>No user data changes!</pre>';
  send_telegram($users);
} else echo '<pre>error get usernames list</pre>';

