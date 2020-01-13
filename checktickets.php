<?php
try {
    //https://developer.zendesk.com/rest_api/docs/help_center/articles
    $subdomain = 'ubibot111';
    $url = "https://" . $subdomain . ".zendesk.com/api/v2/help_center/en-us/articles.json?sort_by=title";
    $result = geturl($url);
    if($result != null){
        //设置状态码
        http_response_code(200);
        $return['code'] = 200;
        $return['message'] = "";
        echo json_encode($return);
    }else{
        //设置状态码
        http_response_code(500);
        $return['code'] = 500;
        $return['message'] = "Not Found";
        echo json_encode($return);
    }
} catch (Exception $e) {
    //设置状态码
    http_response_code(500);
    $return['code'] = 500;
    $return['message'] = $e->getMessage();
    echo json_encode($return);
}

function geturl($url)
{
    $headerArray = array("Content-type:application/json;", "Accept:application/json");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($url, CURLOPT_HTTPHEADER, $headerArray);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output, true);
    return $output;
}
?>