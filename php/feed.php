<?php

require_once('util.php');
require_once('site.php');
require_once('page.php');
require_once('announcement.php');
require_once('assignment.php');

#
# API path: /session/feed INDEX
#
function feed($session_id, $site_ids) {
  $site_ids = site_id($session_id, $site_ids);
  $feed = array();
  foreach ($site_ids as $site_id) {
    $site_feed = site_feed($session_id, $site_id);
    foreach ($site_feed['feed'] as $item) {
      $item['site_id'] = $site_id;
      $feed []= $item;
    }
  }
  usort($feed, 'feed_sorting_comparison');
  return array('feed' => $feed);
}

#
# API path: /session/site/feed INDEX
#
function site_feed($session_id, $site_id) {

  $site_id = site_id($session_id, $site_id);

  $feed = array();

  $announcements = announcement_index_array($session_id, $site_id);
  foreach ($announcements as $announcement) {
    $feed []= array(
      'date' => $announcement->date,
      'type' => 'announcement',
      'value' => $announcement
    );
  }

  $assignments = assignment_index_array($session_id, $site_id);
  foreach ($assignments as $assignment) {
    $feed []= array(
      'date' => $assignment->openDate,
      'type' => 'assignment',
      'value' => $assignment
    );
  }

  usort($feed, 'feed_sorting_comparison');

  return array('feed' => $feed);
}

function feed_sorting_comparison($a, $b) {

  $a = $a['date'];
  $b = $b['date'];

  if ($a < $b) return 1;
  if ($a > $b) return -1;

  return 0;
}

?>
