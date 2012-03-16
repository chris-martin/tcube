<?php

require_once('util.php');
require_once('site.php');
require_once('page.php');

#
# API path: /session/site/assignment INDEX
#
function assignment_index($session_id, $site_id) {
  $site_id = site_id($session_id, $site_id);
  if (!$site_id) return array('status' => 404);
  $page_id = page_id($session_id, $site_id, 'assignment');
  if (!$page_id) return array('assignments' => array());
  return exec_python(
    'assignment.py',
    '--page', $page_id,
    '--cookie', fetch_cookies($session_id)
  );
}

#
# API path: /session/site/assignment GET
#
function assignment_get($session_id, $site_id, $assignment_id) {
  $site_id = site_id($session_id, $site_id);
  if (!$site_id) return array('status' => 404);
  $page_id = page_id($session_id, $site_id, 'assignment');
  if (!$page_id) return array('status' => 404);
  return exec_python(
    'assignment.py',
    '--id', $assignment_id,
    '--page', $page_id,
    '--cookie', fetch_cookies($session_id)
  );
}

function assignment_index_array($session_id, $site_id) {
  $x = assignment_index($session_id, $site_id);
  if (is_array($x)) return array_key_exists('assignments', $x) ? $x['assignments'] : array();
  $x = json_decode($x);
  return property_exists($x, 'assignments') ? $x->assignments : array();
}

?>
