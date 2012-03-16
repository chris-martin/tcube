<?php

ini_set("display_errors","1");
ERROR_REPORTING(E_ALL);

function http_header($x) {
  $GLOBALS["_PLATFORM"]->sandboxHeader($x);
}

$http_status_codes = array(
  401 => 'Unauthorized',
  404 => 'Not Found',
  500 => 'Internal Server Error'
);

function http_status($x) {
  global $http_status_codes;
  $description = array_key_exists($x, $http_status_codes)
    ? ' ' . $http_status_codes[$x] : '';
  http_header('HTTP/1.1 ' . $x . $description);
}

function our_json_encode($x) {
  if (!$x) return '{}';
  $x = json_encode($x);
  if (array_key_exists('pretty', $_GET)) {
    $x = exec_python_with_input('prettyjson.py', $x."\n");
  }
  return $x;
}

function process_json($x) {
  if (is_string($x)) $x = json_decode($x);
  $x = (array) $x;
  if (array_key_exists('status', $x) && $x['status'] != 200) {
    http_status($x['status']);
  }
  unset($x['status']);
  return our_json_encode($x) . "\n";
}

#
# Runs python with all args given, printing the output
# to stdout.
#
# Don't worry about escaping anything; this function
# takes care of it.
#
# Example:
#
#   Bash command:
#     python ./some_script.py --some_arg arg_value
#
#   PHP call:
#     python('some_script.py', '--some_arg', 'arg_value');
#
# Note that this runs our own local python build, because
# the one installed on the system is hideously outdated.
#
function exec_python() {
  $cmd = '/third/tcube/python/Python-2.7.2/python';
  $args = func_get_args();
  $args[0] = getcwd() . '/python/' . $args[0];
  if (array_key_exists('verbose', $_GET)) {
    $args []= '--verbose';
  }
  foreach ($args as $arg) {
    $cmd .= ' ' . escapeshellarg($arg);
  }
  return exec_http($cmd);
}

function exec_python_with_input($py_file, $input) {
  $cmd = '/third/tcube/python/Python-2.7.2/python '
    . getcwd() . '/python/' . $py_file;
  return exec_http($cmd, $input);
}

function exec_http($cmd, $input = '') {

  $result = exec_full($cmd, $input);
  if ($result['return']) {
    http_status(500);
    die($result['stderr']."\n");
  }
  return $result['stdout'];
}

#
# Executes a system command (with input, optionally)
# and returns the output from stdout.
#
# PHP's builtin 'system' function doesn't work within the
# gtmob framework (for reasons unknown), but this does.
#
function exec_stdout($cmd, $input = '') {
  $result = exec_full($cmd, $input);
  return $result['stdout'];
}

#
# http://www.php.net/manual/en/function.system.php#94929
#
# Executes a command and returns the contents of stdout,
# stderr, and the process exit code.
#
function exec_full($cmd, $input = '') {
  $proc = proc_open($cmd, array(
    0 => array('pipe', 'r'),
    1 => array('pipe', 'w'),
    2 => array('pipe', 'w')
  ), $pipes);
  fwrite($pipes[0], $input);
  fclose($pipes[0]);
  $stdout = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[2]);
  $rtn = proc_close($proc);
  return array(
    'stdout' => $stdout,
    'stderr' => $stderr,
    'return' => $rtn
  );
}

$db_connected = false;

function db_init() {
  $db_host = 'db.cip.gatech.edu';
  $db_username = 'tcube';
  $db_password = '2hqQhjY3';
  $db_database = 'CONTRIB_' . $db_username;

  global $db_connected;
  if (!$db_connected) {
    $db_connection = mysql_connect($db_host, $db_username, $db_password);
    if (!$db_connection) { http_status(500); die(mysql_error()."\n"); }
    $db_select = mysql_select_db($db_database);
    if (!$db_select) { http_status(500); die(mysql_error()."\n"); }
    $db_connected = true;
  }
}

function db_query($query) {
  db_init();
  $query = call_user_func_array('sprintf', func_get_args());
  $result = mysql_query($query);
  if (!$result) { http_status(500); die(mysql_error()."\n"); }
  return $result;
}

function db_insert($table, $x) {
  db_insert_all($table, array($x));
}

function db_insert_all($table, $xs) {

  $table = sprintf('`%s`', $table);

  $keys = array();
  foreach ($xs[0] as $key => $value) {
    $keys []= sprintf('`%s`', $key);
  }
  $keys = sprintf('(%s)', implode(', ', $keys));

  $values = array();
  foreach ($xs as $x) {
    $v = array();
    foreach ($x as $value) {
      $v []= sprintf('\'%s\'', db_escape($value));
    }
    $values []= sprintf('(%s)', implode(', ', $v));
  }
  $values = implode(', ', $values);

  return db_query('insert into %s %s values %s', $table, $keys, $values);
}

function db_escape($x) {
  db_init(); // mysql_real_escape_string requires a database connection
  return mysql_real_escape_string($x);
}

function fetch_cookies($session_id) {
  $result = db_query("select * from `cookie`"
    ." where `session_id` = '%s'", db_escape($session_id));
  $cookies = array();
  while ($row = mysql_fetch_assoc($result)) {
    $cookies []= array(
      'domain' => $row['domain'],
      'name' => $row['name'],
      'value' => $row['value'],
      'path' => $row['path'],
      'path_specified' => !!$row['path_specified'],
      'expires' => $row['expires']
    );
  }
  return json_encode($cookies);
}

function check_session($session_id) {
  if (!is_alive($session_id)) {
    http_status(401);
    die("{ errors: ['Session id is incorrect or expired.'] }\n");
  }
}

function is_alive($session_id) {
  $result = db_query("select count(*) from `session`"
    . " where `session_id` = '%s'"
    . " and current_timestamp < addtime(`last_access`, sec_to_time(`timeout`))", db_escape($session_id));
  return mysql_result($result, 0);
}

function keep_alive($session_id) {
  db_query("update `session` set `last_access` = current_timestamp"
    . " where `session_id` = '%s'", db_escape($session_id));
}

function ping_tsquare($cookies) {
  if ($cookies) exec_python('keepalive.py', '--cookie', $cookies);
}

?>
