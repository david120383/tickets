<?php
require 'utility.php';
require 'vendor/autoload.php';

use Zendesk\API\HttpClient as ZendeskAPI;

$token_id = $_GET['token_id'];
$ubibot_url = "http://api.ubibot.io/accounts/view?token_id=" . $token_id;
$result = geturl($ubibot_url);

if ($token_id == "") {
    header('Location: error.php');
} else {
    if (isset($result['result']) &&
        $result['result'] == "success" &&
        isset($result['account']) &&
        isset($result['account']['username']) &&
        isset($result['account']['email'])) {

        try {
            $config = getconfig();
            $subdomain = $config['subdomain'];
            $username_config = $config['username'];
            $token = $config['token'];
            $username = $result['account']['username'];
            $email = $result['account']['email'];

            $client = new ZendeskAPI($subdomain);
            $client->setAuth('basic', ['username' => $username_config, 'token' => $token]);

            $user = get_user($client, $email);

            //在Console注册且登陆过Zendesk
            if (isset($user['users']) &&
                isset($user['users'][0]) &&
                isset($user['users'][0]['id'])) {
                $array = get_user_tickets($client, $user['users'][0]['id']);
            } else {
                //在Console注册但是没登录过Zendesk，需要调用接口注册Zendesk
                $token_id = $_GET['token_id'];
                $postdata['token_id '] = $token_id;
                $ubibot_url2 = "http://api.ubibot.io/zendesk/sso/login_with_token?token_id=" . $token_id;
                $result2 = posturl($ubibot_url2, $postdata);
                //注册Zendesk成功，需要访问location的地址才算真的注册成功
                if (isset($result2['result']) &&
                    isset($result2['location']) &&
                    isset($result2['result']) == "success") {
                    $zendesk_register_url = $result2['location'];
                    //访问location的地址，完成注册
                    $result3 = geturl($zendesk_register_url);

                    $usernew = get_user($client, $email);
                    if (isset($usernew['users']) &&
                        isset($usernew['users'][0]) &&
                        isset($usernew['users'][0]['id'])) {
                        $array = get_user_tickets($client, $usernew['users'][0]['id']);
                    } else {
                        header('Location: error.php?token_id=' . $token_id);
                    }
                } else {
                    header('Location: error.php?token_id=' . $token_id);
                }
            }
        } catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
            header('Location: error.php?token_id=' . $token_id);
        }
    } else {
        header('Location: error.php?token_id=' . $token_id);
    }
}
function get_user($client, $email)
{
    $params = array('query' => $email);
    $user = $client->users()->search($params);
    $user = json_decode(json_encode($user), true);
    return $user;
}

function get_user_tickets($client, $requesterId)
{
    $tickets = $client->users($requesterId)->tickets()->requested();
    $array = json_decode(json_encode($tickets), true);
    return $array;
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../favicon.ico">
    <title>My request</title>
    <!-- Bootstrap core CSS -->
    <link href="bootstrap-3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap theme -->
    <link href="bootstrap-3.3.7/css/bootstrap-theme.min.css" rel="stylesheet">
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="bootstrap-3.3.7/ie10-viewport-bug-workaround.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="bootstrap-3.3.7/theme.css" rel="stylesheet">
    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]>
    <script src="bootstrap-3.3.7/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="bootstrap-3.3.7/ie-emulation-modes-warning.js"></script>
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="bootstrap-3.3.7/html5shiv.min.js"></script>
    <script src="bootstrap-3.3.7/respond.min.js"></script>
    <![endif]-->
</head>
<body style="padding-top:10px;">
<div class="container theme-showcase" role="main">

    <div class="row">
        <div class="col-xs-12">
            <button type="button" class="btn btn-sm btn-ubibot"
                    onclick="create('<?php echo $token_id; ?>','<?php echo $username; ?>','<?php echo $email; ?>')">
                Create a request
            </button>
        </div>
        <div class="col-xs-12" style="padding-top:0px;padding-top:10px; " id="ticketsdiv">
            <div>
                <input type="text" id="searchsubject" style="width:48%;display:inline-block;" class="form-control"
                       placeholder="Search">
                <select id="searchtype" name="searchtype" class="form-control"
                        style="width:48%;display:inline-block;">
                    <option value="">All</option>
                    <option value="open">Open</option>
                    <option value="awaiting your reply">Awaiting your reply</option>
                    <option value="solved">Solved</option>
                </select>
            </div>
            <ui style="list-style-type:none">
                <?php
                $arrayTickets = $array['tickets'];
                for ($i = count($arrayTickets) - 1; $i >= 0; $i--) :
                    ?>
                    <li class="<?php echo $arrayTickets[$i]['subject']; ?>">
                        <div style="box-shadow: 2px 2px 5px #bbb;border-radius:12px;margin-top:10px;"
                             onclick="view('<?php echo $token_id; ?>','<?php echo $arrayTickets[$i]['id']; ?>','<?php echo $username; ?>','<?php echo $email; ?>')">
                            <div style="padding-left:10px;padding-top:5px;">
                                <label style="width:60%;margin-bottom: 0px;font-weight:500;">#<?php echo $arrayTickets[$i]['id']; ?></label>
                                <?php
                                if ($arrayTickets[$i]['status'] == "solved" || $arrayTickets[$i]['status'] == "closed") : ?>
                                    <label style="word-break:break-word;width:35%;text-align:center;background-color:#999;color:white;border-radius:5px;font-size:12px;font-weight:400;margin-bottom:0px;height:20px;"
                                           class="ticketstatus">solved</label>
                                <?php elseif ($arrayTickets[$i]['status'] == "new" || $arrayTickets[$i]['status'] == "open" || $arrayTickets[$i]['status'] == "hold"): ?>
                                    <label style="word-break:break-word;width:35%;text-align:center;background-color:#cc3340;color:white;border-radius:5px;font-size:12px;font-weight:400;margin-bottom:0px;height:20px;"
                                           class="ticketstatus">open</label>
                                <?php elseif ($arrayTickets[$i]['status'] == "pending"): ?>
                                    <label style="word-break:break-word;width:35%;text-align:center;background-color:#1eb848;color:white;border-radius:5px;font-size:12px;font-weight:400;margin-bottom:0px;height:20px;"
                                           class="ticketstatus">awaiting your reply</label>
                                <?php endif; ?>
                            </div>
                            <div style="padding-left:10px;padding-right:10px;">
                                <label style="word-break:break-word;margin-bottom: 0px;"
                                       class="ticketsubject"><?php echo $arrayTickets[$i]['subject']; ?></label>
                            </div>
                            <div style="padding-left:10px;padding-right:10px;margin-bottom: 0px;">
                                <label style="font-size:12px;display:inline-block;word-break:break-word;color:#c0c0c0 ;"><?php echo time_tran($arrayTickets[$i]['updated_at']); ?></label>
                            </div>
                        </div>
                    </li>
                <?php endfor; ?>
            </ui>
        </div>
        <div class="col-xs-12">
        </div>
    </div>
</div>

<script src="bootstrap-3.3.7/jquery.min.js"></script>
<script src="bootstrap-3.3.7/js/bootstrap.min.js"></script>
<script src="bootstrap-3.3.7/ie10-viewport-bug-workaround.js"></script>
<script type="application/javascript">

    $("#searchsubject").keyup(function () {
        checkickets();
    });

    $("#searchtype").change(function () {
        checkickets();
    });

    function checkickets() {
        var querySubject = $.trim($("#searchsubject").val());
        // var queryStatus = UpperFirstLetter($.trim($("#searchtype option:selected").val()));
        var queryStatus = $.trim($("#searchtype option:selected").val());
        if (querySubject === '' && queryStatus === '') {
            $("#ticketsdiv li").show();
        } else if (querySubject !== '' && queryStatus === '') {
            $("#ticketsdiv li").hide().find(".ticketsubject").filter(":contains('" + querySubject + "')").parent().parent().parent("li").show();
        } else if (querySubject === '' && queryStatus !== '') {
            $("#ticketsdiv li").hide().find(".ticketstatus").filter(":contains('" + queryStatus + "')").parent().parent().parent("li").show();
        } else {
            $("#ticketsdiv li").hide().find(".ticketsubject").filter(":contains('" + querySubject + "')").parent().parent().find(".ticketstatus").filter(":contains('" + queryStatus + "')").parent().parent().parent("li").show();
        }
    }

    // function UpperFirstLetter(str) {
    //     return str.replace(/\b\w+\b/g, function (word) {
    //         return word.substring(0, 1).toUpperCase() + word.substring(1);
    //     });
    // }

    function create(token_id, username, email) {
        location.href = "guide.php?token_id=" + token_id + "&user_name=" + decodeURI(username) + "&email=" + decodeURI(email);
    }

    function view(token_id, ticket_id, username, email) {
        // location.href = "view.php?token_id=" + token_id + "&id=" + ticket_id + "&timezone=" + decodeURI(timezone);
        location.href = "view.php?token_id=" + token_id + "&id=" + ticket_id + "&user_name=" + decodeURI(username) + "&email=" + decodeURI(email);
    }
</script>
</body>
</html>
