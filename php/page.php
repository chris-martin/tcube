<?php

require_once('util.php');
require_once('site.php');

#
# API path: /session/site/page INDEX
#
function page_index($session_id, $site_id) {
  $site_id = site_id($session_id, $site_id);
  if (!$site_id) return array('status' => 404);
  return exec_python(
    'page.py',
    '--site', $site_id,
    '--cookie', fetch_cookies($session_id)
  );
}

#
# API path: /session/site/page GET
#
function page_get($session_id, $site_id, $page_id) {
  $site_id = site_id($session_id, $site_id);
  if (!$site_id) return array('status' => 404);
  $pages = json_decode(page_index($session_id, $site_id))->pages;
  $page = page_filter($pages, $page_id);
  $x = array();
  if ($page) {
    $x['page'] = $page;
  } else {
    $x['status'] = 404;
  }
  return $x;
}

function page_by_tool($session_id, $site_id, $tool_name) {
  $pages = json_decode(page_index($session_id, $site_id));
  $matches = array();
  if (property_exists($pages, 'pages')) {
    foreach ($pages->pages as $page) {
      if ($page->tool == $tool_name) {
        $matches []= $page;
      }
    }
  }
  return count($matches) != 1 ? null : $matches[0];
}

function page_id($session_id, $site_id, $page_id) {
  $page_id = page_alias($page_id);
  if (substr($page_id, 0, strlen('tool:')) == 'tool:') {
    $tool_name = substr($page_id, strlen('tool:'));
    $page = page_by_tool($session_id, $site_id, $tool_name);
    $page_id = $page ? $page->id : null;
  }
  return $page_id;
}

function page_alias($page_id) {
  $tool_alias = array(
    'announcement' => 'sakai.announcements',
    'assignment' => 'sakai.assignment.grades',
    'chat' => 'sakai.chat'
  );
  if (array_key_exists($page_id, $tool_alias)) {
    $page_id = sprintf('tool:%s', $tool_alias[$page_id]);
  }
  return $page_id;
}

function page_filter($pages, $page_id) {
  $page_id = page_alias($page_id);
  $tool_name = substr($page_id, 0, strlen('tool:')) == 'tool:'
    ? substr($page_id, strlen('tool:')) : null;

  $matches = array();
  foreach ($pages as $page) {
    $is_match = $tool_name ? $page->tool == $tool_name : $page->id == $page_id;
    if ($is_match) {
      $matches []= $page;
    }
  }
  return count($matches) == 1 ? $matches[0] : null;
}

?>
