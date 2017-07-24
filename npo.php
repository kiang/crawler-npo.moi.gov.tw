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

$dataPath = __DIR__ . '/data';
if(!file_exists($dataPath)) {
  mkdir($dataPath, 0777);
}

$total = 92371;
$pages = ceil($total / 300);
$fhPool = array();

$header = array('id', 'type', 'name', 'phone', 'address', 'latitude', 'longitude', 'nptype', 'nptypename', 'purpose', 'mission');
$initBody = array('','','','','','','','','','','',);
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
      $result = array_combine($header, $initBody);
      $cols[0] = explode('/npom/homepage/detail/', $cols[1]);
      $result['id'] = explode('"', $cols[0][1])[0];
      $cols[1] = explode('="', $cols[1]);
      $result['type'] = explode('"', $cols[1][4])[0];
      $result['name'] = trim(strip_tags($cols[2]));
      $result['phone'] = trim(strip_tags($cols[3]));
      $result['address'] = trim(strip_tags($cols[4]));
      $jsonFile = $jsonPath . '/' . $result['id'] . '.json';
      if(!file_exists($jsonFile)) {
        file_put_contents($jsonFile, file_get_contents('http://npo.moi.gov.tw/npom/map/preOrgQuery?orgID=' . $result['id']));
      }
      $json = json_decode(file_get_contents($jsonFile), true);
      if(!empty($json['data'])) {
        $geo = twd97_to_latlng($json['data'][0]['x'], $json['data'][0]['y']);
        $result['latitude'] = $geo['lat'];
        $result['longitude'] = $geo['lng'];
        $result['nptype'] = $json['data'][0]['nptype'];
        $result['nptypename'] = $json['data'][0]['nptypename'];
        $result['purpose'] = $json['data'][0]['purpose'];
        $result['mission'] = $json['data'][0]['mission'];
      }
      if(!isset($fhPool[$result['type']])) {
        $fhPool[$result['type']] = fopen(__DIR__ . '/data/' . $result['type'] . '.csv', 'w');
        fputcsv($fhPool[$result['type']], $header);
      }
      fputcsv($fhPool[$result['type']], $result);
    }
  }
}
