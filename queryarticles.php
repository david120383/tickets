<?php
require 'utility.php';
error_reporting(E_ALL);
$query = $_GET['query'];
//$config = parse_ini_file('./config.ini');
$config = getconfig();
$subdomain = $config['subdomain'];
$ubibot_url = "https://" . $subdomain . ".zendesk.com/api/v2/help_center/articles/search.json?query=" . urlencode($query);
$result = geturl($ubibot_url);
for ($x = 0; $x <= count($result['results']); $x++) {
    $item = $result['results'][$x];
    if (!empty($item['created_at'])) {
        $item['created_at2'] = time_tran($item['created_at']);
        $result['results'][$x] = $item;
    }
}
echo json_encode($result);
