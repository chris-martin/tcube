<?php

require_once('util.php');

#
# API path: /session/site INDEX
#
function site_index($session_id) {
  return exec_python(
    'site.py',
    '--cookie', fetch_cookies($session_id)
  );
}

#
# API path: /session/site GET
#
function site_get($session_id, $site_id) {
  $site_id = site_id($session_id, $site_id);
  if (!$site_id) return array('status' => 404);
  return exec_python(
    'site.py',
    '--cookie', fetch_cookies($session_id),
    '--id', $site_id
  );
}

function site_by_title($session_id, $site_title) {
  $sites = json_decode(site_index($session_id));
  $matches = array();
  foreach ($sites->sites as $site) {
    if ($site->title == $site_title) {
      $matches []= $site;
    }
  }
  return count($matches) != 1 ? null : $matches[0];
}

#
# Converts a site id alias to a real site id.
# Returns null is title: prefix is present but no such site exists.
#
# If site_id is an array, returns a new array with each of the items
# converted, and null values removed.
#
function site_id($session_id, $site_id) {
  if (is_array($site_id)) {
    $result = array();
    foreach ($site_id as $x) {
      $site_id = site_id($session_id, $x);
      if ($site_id) $result []= $site_id;
    }
    return $result;
  }
  if ($site_id && substr($site_id, 0, strlen('title:')) == 'title:') {
    $site_title = substr($site_id, strlen('title:'));
    $site = site_by_title($session_id, $site_title);
    if (!$site) return null;
    $site_id = $site->id;
  }
  return $site_id;
}

?>
