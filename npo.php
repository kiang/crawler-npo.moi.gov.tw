<?php

require_once __DIR__ . '/twd97Conv.php';

$path = __DIR__ . '/tmp/pages';
if(!file_exists($path)) {
  mkdir($path, 0777, true);
}

$jsonPath = __DIR__ . '/tmp/json';
if(!file_exists($jsonPath)) {
  mkdir($jsonPath, 0777);
}

$total = 92371;
$pages = ceil($total / 300);

for($i = 1; $i <= $pages; $i++) {
  error_log("processing page {$i}");
  $pageFile = $path . '/' . $i;
  if(!file_exists($pageFile)) {
    $offset = ($i - 1) * 300;
    file_put_contents($pageFile, file_get_contents('http://npo.moi.gov.tw/npom/homepage/list?offset=' . $offset . '&max=300'));
  }
  $page = file_get_contents($pageFile);
  $lines = explode('</tr>', $page);
  foreach($lines AS $line) {
    $cols = explode('</td>', $line);
    if(count($cols) === 6) {
      $cols[0] = explode('/npom/homepage/detail/', $cols[1]);
      $cols[0] = explode('"', $cols[0][1])[0];
      $cols[1] = explode('="', $cols[1]);
      $cols[1] = explode('"', $cols[1][4])[0];
      $cols[2] = trim(strip_tags($cols[2]));
      $cols[3] = trim(strip_tags($cols[3]));
      $cols[4] = trim(strip_tags($cols[4]));
      $jsonFile = $jsonPath . '/' . $cols[0] . '.json';
      if(!file_exists($jsonFile)) {
        file_put_contents($jsonFile, file_get_contents('http://npo.moi.gov.tw/npom/map/preOrgQuery?orgID=' . $cols[0]));
      }
    }
  }
}
