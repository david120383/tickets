<?php

function getconfig()
{
    $config['subdomain'] = 'ubibot';
    $config['username'] = 'leon@cloudforce.cn';
    $config['token'] = 'bMbK2Ng32ETLfmcheVixzUH0sIPOjBzvJEVcvgGy';
    $config['captchacheck'] = '120';
    return $config;
}

function time_tran($the_time)
{
    $now_time = time();
    $show_time = strtotime($the_time);
    $dur = $now_time - $show_time;
    if ($dur < 0) {
        return $the_time;
    } else {
        if ($dur < 60) {
            return 'a few seconds ago';
        } else {
            if ($dur < 3600) {
                $return = floor($dur / 60);
                if ($return == 1) {
                    return '1 minute ago';
                } else {
                    return $return . ' minutes ago';
                }
            } else {
                if ($dur < 86400) {
                    $return = floor($dur / 3600);
                    if ($return == 1) {
                        return '1 hour ago';
                    } else {
                        return $return . ' hours ago';
                    }
                } else {
                    if ($dur < 2592000) {
                        $return = floor($dur / 86400);
                        if ($return == 1) {
                            return '1 day ago';
                        } else {
                            return $return . ' days ago';
                        }
                    } else {
                        if ($dur < 31536000) {

                            $return = floor($dur / 2592000);
                            if ($return == 1) {
                                return '1 month ago';
                            } else {
                                return $return . ' months ago';
                            }
                        } else {
                            $return = floor($dur / 31536000);
                            if ($return == 1) {
                                return '1 year ago';
                            } else {
                                return $return . ' years ago';
                            }
                        }
                    }
                }
            }
        }
    }
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

function posturl($url, $data)
{
    $data = json_encode($data);
    $headerArray = array("Content-type:application/json;charset='utf-8'", "Accept:application/json");
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return json_decode($output, true);
}

function puturl($url, $data, $userpwd)
{
    $data = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
    $output = curl_exec($ch);
    curl_close($ch);
    return json_decode($output, true);
}

function delurl($url, $data)
{
    $data = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output, true);
}

function patchurl($url, $data)
{
    $data = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);     //20170611修改接口,用/id的方式传递,直接写在url中了
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output);
    return $output;
}

//function gmdate_to_mydate($gmdate)
//{
//    /* $gmdate must be in YYYY-mm-dd H:i:s format*/
//    str_replace('T', ' ', $gmdate);
//    str_replace('Z', '', $gmdate);
//    $timezone = date_default_timezone_get();
//    $userTimezone = new DateTimeZone($timezone);
//    $gmtTimezone = new DateTimeZone('GMT');
//    $myDateTime = new DateTime($gmdate, $gmtTimezone);
//    $offset = $userTimezone->getOffset($myDateTime);
//    return date("Y-m-d H:i:s", strtotime($gmdate));
//}