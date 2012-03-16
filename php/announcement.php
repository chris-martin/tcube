<?php

require_once('util.php');
require_once('site.php');
require_once('page.php');

#
# API path: /session/site/announcement INDEX
#
function announcement_index($session_id, $site_id) {
  $site_id = site_id($session_id, $site_id);
  if (!$site_id) return array('status' => 404);
  $page_id = page_id($session_id, $site_id, 'announcement');
  if (!$page_id) return array('announcements' => array());
  return exec_python(
    'announcement.py',
    '--page', $page_id,
    '--cookie', fetch_cookies($session_id)
  );
}

#
# API path: /session/site/announcement GET
#
function announcement_get($session_id, $site_id, $announcement_id) {
  $site_id = site_id($session_id, $site_id);
  if (!$site_id) return array('status' => 404);
  $page_id = page_id($session_id, $site_id, 'announcement');
  if (!$page_id) return array('status' => 404);
  return exec_python(
    'announcement.py',
    '--id', $announcement_id,
    '--page', $page_id,
    '--site', $site_id,
    '--cookie', fetch_cookies($session_id)
  );
}

function announcement_index_array($session_id, $site_id) {
  $x = announcement_index($session_id, $site_id);
  if (is_array($x)) return array_key_exists('announcements', $x) ? $x['announcements'] : array();
  $x = json_decode($x);
  return property_exists($x, 'announcements') ? $x->announcements : array();
}

?>
