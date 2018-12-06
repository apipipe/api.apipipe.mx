<?php
$usernames = [];
$u_default = ['dann.cortess', 'karla_teylor', 'zara_luna23', 'jackieecortes'];

function get_usernames(){
  GLOBAL $conn, $u_default;

  $u_query = "SELECT DISTINCT username FROM instagram_tool WHERE enabled = '1' ORDER BY username ASC;";
  if ($u_query = $conn->query($u_query)) {
    if ($u_query->num_rows > 0) {
        // output data of each row
        while($row = $u_query->fetch_assoc()) {
          if (!in_array($row['username'], $u_default))
            $u_default[] = $row['username'];
        }
    }
  }
  return $u_default;
}

function remove_username ($username){
  if (strlen($username)) return false;

  $usernames = array_diff($usernames, [$username]);

  GLOBAL $conn, $usernames;
  $u_query = "UPDATE FROM instagram_tool SET enabled = '0' WHERE username = '$username';";
  if ($u_query = $conn->query($u_query)) {
    return true;
  }
}

$usernames = get_usernames();
if (isset($_GET['addList']) && !empty(trim($_GET['addList'])) && !in_array(trim($_GET['addList']), $usernames)) $usernames[] = trim($_GET['addList']);

if (isset($_GET['removeList']) && !empty(trim($_GET['removeList'])) && in_array(trim($_GET['removeList']), $usernames)) remove_username (trim($_GET['removeList']));