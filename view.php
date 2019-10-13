<?php
session_save_path('sessions');
if (!session_id()) session_start();
error_reporting(E_ALL);
require 'utility.php';
require 'vendor/autoload.php';
require_once('../shared-util/classes/securimage/securimage.php');

use Zendesk\API\HttpClient as ZendeskAPI;

if (isset($_POST['action']) && 'redirect' === $_POST['action']) {
//    $config = parse_ini_file('./config.ini');
    $config = getconfig();
    $subdomain = $config['subdomain'];
    $username = $config['username'];
    $token = $config['token'];
    $client = new ZendeskAPI($subdomain);
    $client->setAuth('basic', ['username' => $username, 'token' => $token]);
    $token_id = $_POST['tokenid'];
    $requestid = $_POST['requestid'];
    $email = $_POST['email'];
    $user_name = $_POST['user_name'];
    if ($token_id == "" || $requestid == "") {
        header('Location: error.php');
    } else {
        $showcode = $_POST['showcode'];
        if ($showcode == true) {
            $securimage = new Securimage();
            $captcha = $_POST['verificationcode'];
            if ($securimage->check($captcha) == false) {
                header('Location: updateerror.php?error=5&token_id=' . $token_id . "&user_name=" . $user_name . "&email=" . $email);
                exit();
            }
        }
        try {
            if (!empty($_FILES['attachments']['tmp_name']) && !empty($_FILES['attachments']['tmp_name'][0])) {
                foreach ($_FILES['attachments']['tmp_name'] as $position => $tmp_name) {
                    if (!is_uploaded_file($tmp_name)) {
                        header("Location: updateerror.php?error=2&token_id=$token_id&id=$requestid&user_name=$user_name&email=$email");
                        exit();
                    }
                }
                $files = @$_FILES['attachments'];
                //check image size
                $isoksize = 6144000;
                foreach ($files['size'] as $position => $tmp_size) {
                    if ($isoksize < $tmp_size) {
                        header("Location: updateerror.php?error=3&token_id=$token_id&id=$requestid&user_name=$user_name&email=$email");
                        exit();
                    }
                }
                $uploads = [];
                for ($i = 0; $i < count($files['tmp_name']); $i++) {
                    $exe = substr($files['name'][$i], stripos($files['name'][$i], '.') + 1);
                    $newname = time();
                    $newname .= rand() * 1000;
                    $savadirfile = getcwd() . '/uploads/' . $newname . '.' . $exe;
                    $r = move_uploaded_file($files['tmp_name'][$i], $savadirfile);
                    if ($r) {
                        $attachment = $client->attachments()->upload([
                            'file' => $savadirfile,
                            'type' => $files['type'][$i],
                            'name' => $files['name'][$i]
                        ]);
                        array_push($uploads, $attachment->upload->token);
                    } else {
                        header("Location: updateerror.php?error=4&token_id=$token_id&id=$requestid&user_name=$user_name&email=$email");
                        exit();
                    }
                }
                $comment = array(
                    'body' => $_POST['conversation'],
                    'uploads' => $uploads,
                    'author_id' => $_POST['author_id']
                );
            } else {
                $comment = [
                    'body' => $_POST['conversation'],
                    'author_id' => $_POST['author_id']
                ];
            }
            $updateTicket = $client->tickets()->update($requestid, [
                'comment' => $comment,
            ]);
            $_SESSION['lastsubmit'] = time();
            header("Location: view.php?token_id=$token_id&id=$requestid&user_name=$user_name&email=$email");
            exit();
        } catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
            header("Location: updateerror.php?error=1&token_id=$token_id&id=$requestid&user_name=$user_name&email=$email");
            exit();
        }
    }
} else {
    $token_id = $_GET['token_id'];
    $user_name_get = $_GET['user_name'];
    $email = $_GET['email'];
    if ($token_id == "") {
        header('Location: error.php');
    } else {
//        $config = parse_ini_file('./config.ini');
        $config = getconfig();
        $subdomain = $config['subdomain'];
        $username = $config['username'];
        $token = $config['token'];
        $captchacheck = $config['captchacheck'];
        $client = new ZendeskAPI($subdomain);
        $client->setAuth('basic', ['username' => $username, 'token' => $token]);

        $id = $_GET['id'];
        if ($id == "") {
            header('Location: error.php?token_id=' . $token_id);
        } else {
            $tickets = $client->tickets()->find($id);
            $comments = $client->tickets($id)->comments()->findAll();
            $array = json_decode(json_encode($tickets), true);
            $arrayComments = json_decode(json_encode($comments), true);
            $status = "";
            if (isset($array['ticket'])) {
                $printarray = $array['ticket'];
                $ubibotemail = "";
                $serial = "";
                $plrchase = "";
                if (isset($printarray['custom_fields'])) {
                    foreach ($printarray['custom_fields'] as $f) {
                        if ($f['id'] == '360016542292') {
                            $ubibotemail = $f['value'];
                        }
                        if ($f['id'] == '360016542312') {
                            $serial = $f['value'];
                        }
                        if ($f['id'] == '360016542332') {
                            $plrchase = $f['value'];
                        }
                    }
                }
                if (isset($printarray['status'])) {
                    $status = $printarray['status'];
                }
            } else {
                $printarray = [];
            }
        }
//        print_r("lastsubmit");
        $lastsubmit = $_SESSION['lastsubmit'];
//        print_r($_SESSION);
        $showcode = false;
        $now = time();
        if (!empty($lastsubmit) && $now - $lastsubmit < $captchacheck) {
            $showcode = true;
        }
//        $showcode = true;
    }
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
    <style type="text/css">
        .attachments .attachment-item {
            position: relative;
        }

        .xuanfu {
            left: 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            z-index: 100;
        }

        .btn-seemore {
            background-color: #0072EF;
            color: white;
            padding-bottom: 5px;
        }
    </style>
</head>
<body style="padding-top:10px;">
<div class="container theme-showcase" role="main">
    <div class="row" style="padding-left:10px; padding-right:10px;">

        <div class="col-xs-12"
             style="">
            <div style="padding-left:10px;">
                <label><?php echo $printarray['subject']; ?></label>
            </div>

            <?php
            $author_id_first = "";
            $classmiddle = "";
            $arrayTickets = $arrayComments['comments'];
            if (count($arrayTickets) > 0) {
                $author_id_first = $arrayTickets[0]['author_id'];
            }
            for ($i = count($arrayTickets) - 1; $i >= 0; $i--) :
                $author_id_this = $arrayTickets[$i]['author_id'];
                $classmiddle = "";
                if ($i != count($arrayTickets) - 1 && $i != 0) {
                    $classmiddle = "middlediv";
                }
                ?>
                <div class="col-xs-12 <?php echo $classmiddle ?>"
                     style="border-radius: 0px;padding-top:10px;padding-left:0px;padding-right: 0px; border-bottom: 1px solid #c0c0c0; ">
                    <div style="float: left;display: inline-block;width:15%; ">
                        <?php if ($author_id_first == "" || $author_id_this == $author_id_first):
                            $author_id_first = $author_id_this; ?>
                            <img src="bootstrap-3.3.7/image/02.png"
                                 style="width:40px;height:40px;">
                        <?php else: ?>
                            <img src="bootstrap-3.3.7/image/01.png"
                                 style="width:40px;height:40px;">
                        <?php endif; ?>
                    </div>
                    <div style="float: left;display: inline-block;width:85%;">
                        <div>
                            <?php if (strpos($arrayTickets[$i]['html_body'], 'agent/tickets/') != false && strpos($arrayTickets[$i]['html_body'], 'target="_blank"') != false) : ?>
                                <label style="font-size:12px;word-break:break-word;margin-bottom:0px;font-weight:400; ">
                                    <?php echo str_replace('target="_blank"', 'target="_self"', str_replace("agent/tickets/", "tickets/view.php?token_id=$token_id&id=", $arrayTickets[$i]['html_body'])); ?>
                                </label>
                            <?php else: ?>
                                <label style="font-size:12px;word-break:break-word;margin-bottom:0px;font-weight:400;">
                                    <?php echo $arrayTickets[$i]['html_body']; ?>
                                </label>
                            <?php endif; ?>
                        </div>

                        <?php
                        $attachments = [];
                        if (isset($arrayTickets[$i]['attachments'])) :
                            $attachments = $arrayTickets[$i]['attachments'];
                        endif;
                        ?>
                        <div>
                            <?php for ($j = count($attachments) - 1; $j >= 0; $j--) : ?>
                                <ul class="attachments">

                                    <li class="attachment-item">
                                        <a href="javascript:void(0);"
                                           style="font-size:12px;"><?php echo $attachments[$j]['file_name']; ?></a>
                                        <div class="attachment-meta meta-group">
                                            <span class="attachment-meta-item meta-data"
                                                  style="font-size:12px;"><?php echo floor($attachments[$j]['size'] / 1024); ?>
                                                KB</span>
                                        </div>
                                    </li>
                                </ul>
                            <?php endfor; ?>

                        </div>
                        <div style="padding-bottom:10px; ">
                            <label style="font-size:12px;display:inline-block;word-break:break-word;color:#c0c0c0 ;"><?php echo time_tran($arrayTickets[$i]['created_at']); ?></label>
                        </div>
                    </div>
                </div>
                <?php if ($i == count($arrayTickets) - 1 && count($arrayTickets) > 2) : ?>
                <div class="col-xs-12" style="text-align:center;border-bottom: 1px solid #c0c0c0;">
                    <i class="glyphicon glyphicon-chevron-down" style="color:#0072EF;" aria-hidden="true" id="btnShowMore" ></i>
                </div>
            <?php endif; ?>
            <?php endfor; ?>

            <?php if ($status != 'closed' && $status != 'solved') : ?>
<!--                <div class="col-xs-12" style="height:300px; "></div>-->
                <div class="col-xs-12" >
                    <form id="form-oauth" method="POST" enctype="multipart/form-data" style="padding-top:10px;">

                        <input type="hidden" name="action" value="redirect"/>
                        <input type="hidden" id="requestid" name="requestid" value="<?php echo $id; ?>"/>
                        <input type="hidden" id="tokenid" name="tokenid" value="<?php echo $token_id; ?>"/>
                        <input type="hidden" id="author_id" name="author_id" value="<?php echo $author_id_first; ?>"/>
                        <input type="hidden" id="user_name" name="user_name" value="<?php echo $user_name_get; ?>"/>
                        <input type="hidden" id="email" name="email" value="<?php echo $email; ?>"/>
                        <!--                    <input type="hidden" id="hiddenattachments" name="hiddenattachments"/>-->
                        <input type="hidden" id="showcode" name="showcode" value="<?php echo $showcode; ?>"/>
                        <div class="form-group" style="margin-top:15px;">
                            <label for="conversation">Reply<span style="color:red;">*</span></label>
                            <textarea id="conversation" name="conversation" class="form-control text-left" cols="50"
                                      rows="6"
                                      placeholder="Type your message here" required></textarea>
                        </div>
                        <div class="form-group">
                            <div id="upload-dropzone" class="upload-dropzone">
                                <input type="file" id="attachments" name="attachments[]" multiple>
                                <span><img src="bootstrap-3.3.7/image/clip.png" style="width:20px;height:20px;">
                                <a style="margin-left:5px;">Add file</a>
                            </span>
                            </div>
                            <ul id="attachments-pool" class="upload-pool" style="display:none;">
                            </ul>
                        </div>
                        <?php if ($showcode == true) : ?>
                            <div class="form-group">
                                <label for="otherpurchase" style="width:100%;">Verification code</label>
                                <img id="captcha-img" style="width: 80px" height="auto"
                                     alt="Verification code" src=""
                                     onclick="return reloadCaptcha();">
                                <input type="text" class="form-control" id="verificationcode" name="verificationcode"
                                       placeholder="Verification code"/>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <button type="button" class="btn btn-sm btn-ubibot" style="width:48%;"
                                    onclick="goback('<?php echo $token_id; ?>')">Back
                            </button>
                            <button type="submit" class="btn btn-sm btn-ubibot" style="width:48%;">Submit</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="col-xs-12" style="height:110px; "></div>
                <div class="col-xs-12 xuanfu" style="padding-top:20px;padding-bottom:20px;background-color: #f5f5f5;">
                    <label style="width:100%;">Still need help with this specific issue? You can reopen this case via
                        "Create a follow-up"</label>
                    <button type="button" class="btn btn-sm btn-ubibot" style="width:48%;"
                            onclick="goback('<?php echo $token_id; ?>')">
                        Back
                    </button>
                    <button type="button" class="btn btn-sm btn-ubibot" style="width:48%;"
                            onclick="gocreate('<?php echo $token_id; ?>','<?php echo $id; ?>','<?php echo $user_name_get; ?>','<?php echo $email; ?>')">
                        Create a follow-up
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="bootstrap-3.3.7/jquery.min.js"></script>
<script src="bootstrap-3.3.7/js/bootstrap.min.js"></script>
<script src="bootstrap-3.3.7/ie10-viewport-bug-workaround.js"></script>
<script type="application/javascript">
    var showMiddleDiv = false;

    function gocreate(token_id, followup_id, username, email) {
        location.href = "create.php?token_id=" + token_id + "&followup_id=" + followup_id + "&user_name=" + decodeURI(username) + "&email=" + decodeURI(email);
    }

    function goback(token_id) {
        location.href = "index.php?token_id=" + token_id;
    }

    function reloadCaptcha() {
        $('#captcha-img').attr('src', 'captcha.php?_=' + Date.now());
        return false;
    }

    $(function () {
        $('.middlediv').hide();
        reloadCaptcha();
    });

    $('#btnShowMore').click(function () {
        if (showMiddleDiv == false) {
            showMiddleDiv = true;
            $('.middlediv').show();
            $('#btnShowMore').attr("class", " glyphicon glyphicon-chevron-up");
        } else {
            showMiddleDiv = false;
            $('.middlediv').hide();
            $('#btnShowMore').attr("class", " glyphicon glyphicon-chevron-down");
        }
    });

    $("#attachments").on("change", function () {
        var oFReader = new FileReader();
        var file = document.getElementById('attachments').files;
        $("#attachments-pool").show();
        $("#attachments-pool").empty();
        for (var i = 0; i < file.length; i++) {
            var html_li = '';
            html_li += '<li class="upload-item">';
            html_li += '<img src="bootstrap-3.3.7/image/clip.png" style="width:11px;height:11px;">';
            html_li += '<a class="upload-link" target="_blank" >' + file[i].name + '</a>';
            html_li += '</li>';
            $("#attachments-pool").append(html_li);
        }
    });
</script>
</body>
</html>
