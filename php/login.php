<?php

require_once('util.php');

#
# API path: /session POST
#
function login($username, $password) {

  $x = exec_python(
    'login.py',
    '--user', $username,
    '--pass', $password
  );

  $x = json_decode($x);

  if ($x->status == 200) {

    // let our session expire shortly before the t-square session expires
    $x->timeout -= 30;

    $session = array(
      'session_id' => $x->session_id,
      'username' => $username,
      'timeout' => $x->timeout
    );

    $cookies = array();
    foreach ($x->cookies as $c) {
      $cookies []= array(
        'session_id' => $x->session_id,
        'domain' => $c->domain,
        'name' => $c->name,
        'value' => $c->value,
        'path' => $c->path,
        'path_specified' => $c->path_specified,
        'expires' => $c->expires
      );
    }

    db_insert('session', $session);
    db_insert_all('cookie', $cookies);

  } else {
    unset($x->session_id);
  }

  unset($x->cookies);

  return $x;
}

?>
