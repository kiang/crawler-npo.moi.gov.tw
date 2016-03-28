<?php

require_once __DIR__ . '/twd97Conv.php';
$offset = 0;
$total = 5133;
$count = 30;
$page = 1;
$pages = ceil($total / $count);
$path = __DIR__ . '/tmp/pages';
if (!file_exists($path)) {
    mkdir($path, 0777, true);
}

$result = array();

while ($offset < $total) {
    error_log("processing page {$page}");
    $pageFile = $path . '/page_' . $page;
    if (!file_exists($pageFile)) {
        file_put_contents($pageFile, file_get_contents("http://npo.moi.gov.tw/npom/homepage/queryResult?offset={$offset}&max=30&qtype1=cntcode&qcntcode1=67000&qsort=cntcode"));
    }
    $pageContent = file_get_contents($pageFile);
    $pos = strpos($pageContent, '/npom/homepage/detail/');
    while (false !== $pos) {
        $posEnd = strpos($pageContent, '">', $pos);
        $pos += 22;
        $lineId = substr($pageContent, $pos, $posEnd - $pos);
        if (!isset($result[$lineId])) {
            $lineFile = $path . '/f_' . $lineId;
            if (!file_exists($lineFile)) {
                file_put_contents($lineFile, file_get_contents('http://npo.moi.gov.tw/npom/homepage/detail/' . $lineId));
            }
            $linePage = file_get_contents($lineFile);
            $pos = strpos($linePage, '<table class="table table-striped table-bordered">');
            $pageLines = explode('</tr>', substr($linePage, $pos));
            $lineData = array(
                '詳細資料' => 'http://npo.moi.gov.tw/npom/homepage/detail/' . $lineId,
                '團體類型' => '',
                '團體名稱' => '',
                '現任理事長' => '',
                '理事長屆次' => '',
                '理事長任期' => '',
                '成立日期' => '',
                '團體狀態' => '',
                '會址' => '',
                '電話' => '',
                '傳真' => '',
                '會員人數' => '',
                '會員代表人數' => '',
                '全國性/地區性' => '',
                '行政區' => '',
                '核准立案字號' => '',
                '解散日期' => '',
                '電子郵件' => '',
                '郵遞區號' => '',
                '團體分類' => '',
                '宗旨' => '',
                '任務' => '',
                '理事姓名' => '',
                '監事姓名' => '',
                '網址' => '',
                '教會名稱' => '',
                '負責人' => '',
                '寺廟名稱' => '',
                '主祀神祇/配祀神祇' => '',
                '教別' => '',
                '建別' => '',
                '組織型態' => '',
                '農會名稱' => '',
                '漁會名稱' => '',
                '工會類別' => '',
                '工會名稱' => '',
                '編號' => '',
                '社區發展協會' => '',
            );
            foreach ($pageLines AS $pageLine) {
                $cols = explode('</td>', $pageLine);
                if (count($cols) === 3) {
                    $cols[0] = trim(strip_tags($cols[0]));
                    $cols[1] = trim(strip_tags($cols[1]));
                    $lineData[$cols[0]] = $cols[1];
                }
            }
            $jsonFile = $path . '/' . $lineId . '.json';
            if (!file_exists($jsonFile)) {
                file_put_contents($jsonFile, file_get_contents('http://npo.moi.gov.tw/npom/map/preOrgQuery?orgID=' . $lineId));
            }
            $lineJson = json_decode(file_get_contents($jsonFile), true);
            $lineData['經度'] = $lineData['緯度'] = 0.0;
            if (!empty($lineJson['data'])) {
                $latlng = twd97_to_latlng($lineJson['data'][0]['x'], $lineJson['data'][0]['y']);
                $lineData['經度'] = $latlng['lng'];
                $lineData['緯度'] = $latlng['lat'];
            }
            $result[$lineId] = $lineData;
        }
        $pos = strpos($pageContent, '/npom/homepage/detail/', $posEnd);
    }
    $page += 1;
    $offset += $count;
}
ksort($result);

$rFh = fopen(__DIR__ . '/npo.csv', 'w');
$headerWritten = false;
foreach ($result AS $lineData) {
    if (false === $headerWritten) {
        $headerWritten = true;
        fputcsv($rFh, array_keys($lineData));
    }
    fputcsv($rFh, $lineData);
}
