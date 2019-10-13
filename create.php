<?php
session_save_path('sessions');
if (!session_id()) session_start();
error_reporting(E_ALL);
require 'utility.php';
require 'vendor/autoload.php';
require_once('../shared-util/classes/securimage/securimage.php');

use Zendesk\API\HttpClient as ZendeskAPI;

//$config = parse_ini_file('./config.ini');
$config = getconfig();
$subdomain = $config['subdomain'];
$username = $config['username'];
$token = $config['token'];
$captchacheck = $config['captchacheck'];
$client = new ZendeskAPI($subdomain);
$client->setAuth('basic', ['username' => $username, 'token' => $token]);

if (isset($_POST['action']) && 'redirect' === $_POST['action']) {
    $token_id = $_POST['tokenid'];
    if ($token_id == "") {
        header('Location: error.php');
    } else {
        $email = $_POST['email'];
        $user_name = $_POST['user_name'];
        $showcode = $_POST['showcode'];
        if ($showcode == true) {
            $securimage = new Securimage();
            $captcha = $_POST['verificationcode'];
            if ($securimage->check($captcha) == false) {
                header('Location: createerror.php?error=4&token_id=' . $token_id . "&user_name=" . $user_name . "&email=" . $email);
                exit();
            }
        }
        try {
            if (!empty($_FILES['attachments']['tmp_name']) && !empty($_FILES['attachments']['tmp_name'][0])) {
                foreach ($_FILES['attachments']['tmp_name'] as $position => $tmp_name) {
                    if (!is_uploaded_file($tmp_name)) {
                        header('Location: createerror.php?error=1&token_id=' . $token_id . "&user_name=" . $user_name . "&email=" . $email);
                        exit();
                    }
                }
                $files = @$_FILES['attachments'];
                //check image size
                $isoksize = 6144000;
                foreach ($files['size'] as $position => $tmp_size) {
                    if ($isoksize < $tmp_size) {
                        header('Location: createerror.php?error=2&token_id=' . $token_id . "&user_name=" . $user_name . "&email=" . $email);
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
                        header("Location: createerror.php?error=3&token_id=$token_id&user_name=$user_name&email=$email");
                        exit();
                    }
                }
                $comment = array(
                    'body' => $_POST['details'],
                    'uploads' => $uploads
                );
            } else {
                $comment = array(
                    'body' => $_POST['details']
                );
            }
            $topic = array("device___probes", "account", "billing", "web_console", "app", "pc_tools", "apis", "ubibot_space", "others");
            $serial = "";
            $devicemodel = "";
            $purchase = "";
            $channelid = "";
            if ($_POST['serial'] == "-1") {
                $devicemodel = strtolower($_POST['devicemodel']);
                if ($devicemodel == "ws1 pro") {
                    $devicemodel = "ws1_pro";
                }
                $serial = $_POST['serialno'];
            } elseif ($_POST['serial'] != "") {
                $serial_channelid = explode('|', $_POST['serial']);
                $serial = $serial_channelid[0];
                $channelid = (int)$serial_channelid[1];
            }
            if ($_POST['purchase'] == "-1") {
                $purchase = $_POST['otherpurchase'];
            } else {
                $purchase = $_POST['purchase'];
            }
            $create = [
                'subject' => $_POST['subject'],
                'comment' => $comment,
                'requester' => array(
                    'name' => $_POST['username'],
                    'email' => $_POST['email'],
                ),
                "custom_fields" => [
                    array('id' => 360022465992, 'value' => $devicemodel),
                    array('id' => 360016542292, 'value' => $_POST['email']),
                    array('id' => 360022323292, 'value' => $channelid),
                    array('id' => 360016542312, 'value' => $serial),
                    array('id' => 360016542332, 'value' => $purchase),
                    array('id' => 360022465792, 'value' => $topic[$_POST['topic'] - 1])
                ]
            ];
            $newTicket = $client->tickets()->create($create);
            $_SESSION['lastsubmit'] = time();
            header('Location: submit.php?token_id=' . $token_id);
        } catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
            header('Location: error.php?token_id=' . $token_id);
        }
    }
} else {
    $token_id = $_GET['token_id'];
    $followup_id = $_GET['followup_id'];
    $subject_before = "";
    $query = "";
    if ($followup_id != "") {
        $tickets_before = $client->tickets()->find($followup_id);
        $array_before = json_decode(json_encode($tickets_before), true);
        if (isset($array_before['ticket'])) {
            $printarray = $array_before['ticket'];
            $subject_before = "Re: " . $printarray['subject'];
            $query = "This is a follow-up to your previous request #$followup_id \"" . $printarray['description'] . "\"";
        }
    } else {
        $query = $_GET['query'];
    }

    $username = $_GET['user_name'];
    $email = $_GET['email'];
    $ubibot_url = "http://api.ubibot.io/channels?token_id=" . $token_id;
    $haschannel = false;
    $lastsubmit = $_SESSION['lastsubmit'];
    $showcode = false;
    $now = time();
    if (!empty($lastsubmit) && $now - $lastsubmit < $captchacheck) {
        $showcode = true;
    }
    try {
        $result = get_curl($ubibot_url);
    } catch (Exception $e) {
    }
    if (isset($result['result']) &&
        $result['result'] == "success" &&
        isset($result['channels'])) {
        $printarray = $result['channels'];
        $haschannel = true;
    } else {
        $printarray = array();
        $haschannel = false;
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
        <title>Submit a request</title>
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
            .suggestion-list {
                font-size: 13px;
                margin-top: 30px;
            }

            .suggestion-list label {
                border-bottom: 1px solid #ddd;
                display: block;
                padding-bottom: 5px;
            }

            .suggestion-list li {
                padding: 10px 0;
            }

            .topic {
                border: 1px solid black;
                border-radius: 6px;
                font-weight: 500;
                font-size: 13px;
            }

            .topic-select {
                border: 1px solid #0072EF;
                border-radius: 6px;
                background-color: #0072EF;
                color: white;
                font-weight: 500;
                font-size: 13px;
            }
        </style>
    </head>
    <body style="padding-top:10px;">

    <div class="container theme-showcase" role="main">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <form id="form-oauth" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="redirect"/>
                    <input type="hidden" id="username" name="username" value="<?php echo $username; ?>"/>
                    <input type="hidden" id="email" name="email" value="<?php echo $email; ?>"/>
                    <input type="hidden" id="tokenid" name="tokenid" value="<?php echo $token_id; ?>"/>
                    <input type="hidden" id="hiddenattachments" name="hiddenattachments"/>
                    <input type="hidden" id="topic" name="topic" value="1"/>
                    <input type="hidden" id="showcode" name="showcode" value="<?php echo $showcode; ?>"/>
                    <div class="form-group">
                        <label style="width:100%;">Topic<span style="color:red;">*</span></label>
                        <label id="topic1" class="topic-select"
                               onclick="handleChange(1)">&nbsp;&nbsp;Device & Probes&nbsp;&nbsp;</label>
                        <label id="topic2" class="topic"
                               onclick="handleChange(2)">&nbsp;&nbsp;Account&nbsp;&nbsp;</label>
                        <label id="topic3" class="topic"
                               onclick="handleChange(3)">&nbsp;&nbsp;Billing&nbsp;&nbsp;</label>
                        <label id="topic4" class="topic" onclick="handleChange(4)">&nbsp;&nbsp;Web
                            Console&nbsp;&nbsp;</label>
                        <label id="topic5" class="topic" onclick="handleChange(5)">&nbsp;&nbsp;APP&nbsp;&nbsp;</label>
                        <label id="topic6" class="topic" onclick="handleChange(6)">&nbsp;&nbsp;PC
                            Tools&nbsp;&nbsp;</label>
                        <label id="topic7" class="topic" onclick="handleChange(7)">&nbsp;&nbsp;APIs&nbsp;&nbsp;</label>
                        <label id="topic8" class="topic" onclick="handleChange(8)">&nbsp;&nbsp;UbiBot
                            Space&nbsp;&nbsp;</label>
                        <label id="topic9" class="topic"
                               onclick="handleChange(9)">&nbsp;&nbsp;Others&nbsp;&nbsp;</label>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject"
                               value="<?php echo $subject_before; ?>"
                               required/>
                    </div>
                    <div class="suggestion-list" style="display:none;">
                        <div class="searchbox" style=""><label>Suggested articles</label>
                            <div class="searchbox-suggestions">
                                <ul id="articlesul">
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:15px;">
                        <label for="details">Description<span style="color:red;">*</span></label>
                        <textarea id="details" name="details" class="form-control text-left" cols="50" rows="3"
                                  placeholder="Description" required><?php echo $query ?></textarea>
                        <label style="font-weight:200;font-size:12px;">Please describe the problem you're having and the
                            steps you've taken to troubleshoot so far. Please also attach screenshots showing the issue
                            or any error(s).</label>
                    </div>
                    <div class="form-group" id="divChannelID">
                        <label for="serial">Regarding device</label>
                        <select id="serial" name="serial" class="form-control">
                            <option value="">-</option>
                            <?php foreach ($printarray as $item) : ?>
                                <option value="<?php echo $item['full_serial']; ?>|<?php echo $item['channel_id']; ?>"><?php echo $item['channel_id']; ?>
                                    (<?php echo $item['name']; ?>)
                                </option>
                            <?php endforeach; ?>
                            <option value="-1" style="font-weight:700;">Enter Manually</option>
                        </select>
                    </div>
                    <div class="form-group" id="divDeviceModel">
                        <label for="devicemodel" style="width:100%;">Device Model</label>
                        <select id="devicemodel" name="devicemodel" class="form-control"
                                style="width:49%;display:inline-block; ">
                            <option value="">-</option>
                            <option value="ws1">WS1</option>
                            <option value="ws1_pro">WS1 Pro WiFi only</option>
                            <option value="gs1-al2g1rs">WS1 Pro WiFi and SIM</option>
                            <option value="gs1-a">GS1-A</option>
<!--                            <option value="GS1-AL2G1RS">GS1-AL2G1RS</option>-->
                            <option value="gs1-al4g1rs">GS1-AL4G1RS</option>
                            <option value="gs1-aeth1rs">GS1-AETH1RS</option>
                            <option value="probe">Probe</option>
                            <option value="accessories">Accessories</option>
                        </select>
                        <input type="text" class="form-control" id="serialno" name="serialno"
                               placeholder="Serial No."
                               style="width:49%;display:inline-block; "/>
                    </div>
                    <div class="form-group" id="divPurchase">
                        <label for="purchase">Purchase From</label>
                        <select id="purchase" name="purchase" class="form-control">
                            <option value="">-</option>
                            <option value="Ubibot Online Store">Ubibot Online Store</option>
                            <option value="Amazon UK">Amazon UK</option>
                            <option value="Amazon USA">Amazon USA</option>
                            <option value="Amazon CA">Amazon CA</option>
                            <option value="Amazon AU">Amazon AU</option>
                            <option value="AliExpress">AliExpress</option>
                            <option value="-1">Other</option>
                        </select>
                    </div>
                    <div class="form-group" id="divOtherPurchase">
                        <label for="otherpurchase" style="width:100%;">Other Purchase From</label>
                        <input type="text" class="form-control" id="otherpurchase" name="otherpurchase"
                               placeholder="Other Purchase From"/>
                    </div>
                    <div class="form-group">
                        <label for="attachments">Attachments</label>
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
        </div>
    </div>
    <script src="bootstrap-3.3.7/jquery.min.js"></script>
    <script type="application/javascript">
        var lastIndex = 1;
        $(function () {
            $('#divChannelID').show();
            $('#divDeviceModel').hide();
            $('#divPurchase').show();
            $('#divOtherPurchase').hide();
            reloadCaptcha();
        });

        function goback(token_id) {
            location.href = "index.php?token_id=" + token_id;
        }

        function goarticle(token_id, article_id) {
            location.href = "article.php?token_id=" + token_id + "&id=" + article_id;
        }

        function reloadCaptcha() {
            $('#captcha-img').attr('src', 'captcha.php?_=' + Date.now());
            return false;
        }

        function handleChange(index) {
            if (lastIndex != index) {
                if (index == 1) {
                    // $("#serial").val("");
                    // $("#purchase").val("");
                    // $('#divChannelID').show();
                    // $('#divDeviceModel').hide();
                    // $('#divPurchase').show();
                    // $('#divOtherPurchase').hide();
                    $('#topic1').css("background-color", "#0072EF");
                    $('#topic2').css("background-color", "white");
                    $('#topic3').css("background-color", "white");
                    $('#topic4').css("background-color", "white");
                    $("#topic5").css("background-color", "white");
                    $("#topic6").css("background-color", "white");
                    $("#topic7").css("background-color", "white");
                    $("#topic8").css("background-color", "white");
                    $("#topic9").css("background-color", "white");
                    $('#topic1').css("color", "white");
                    $('#topic2').css("color", "black");
                    $('#topic3').css("color", "black");
                    $('#topic4').css("color", "black");
                    $("#topic5").css("color", "black");
                    $("#topic6").css("color", "black");
                    $("#topic7").css("color", "black");
                    $("#topic8").css("color", "black");
                    $("#topic9").css("color", "black");
                    $('#topic1').css("border", "1px solid #0072EF");
                    $('#topic2').css("border", "1px solid black");
                    $('#topic3').css("border", "1px solid black");
                    $('#topic4').css("border", "1px solid black");
                    $("#topic5").css("border", "1px solid black");
                    $("#topic6").css("border", "1px solid black");
                    $("#topic7").css("border", "1px solid black");
                    $("#topic8").css("border", "1px solid black");
                    $("#topic9").css("border", "1px solid black");
                } else {
                    // $("#serial").val("");
                    // $("#purchase").val("");
                    // $('#divChannelID').hide();
                    // $('#divDeviceModel').hide();
                    // $('#divPurchase').hide();
                    // $('#divOtherPurchase').hide();
                    $('#topic1').css("background-color", "white");
                    $('#topic2').css("background-color", "white");
                    $('#topic3').css("background-color", "white");
                    $('#topic4').css("background-color", "white");
                    $("#topic5").css("background-color", "white");
                    $("#topic6").css("background-color", "white");
                    $("#topic7").css("background-color", "white");
                    $("#topic8").css("background-color", "white");
                    $("#topic9").css("background-color", "white");
                    $("#topic" + index).css("background-color", "#0072EF");
                    $('#topic1').css("color", "black");
                    $('#topic2').css("color", "black");
                    $('#topic3').css("color", "black");
                    $('#topic4').css("color", "black");
                    $("#topic5").css("color", "black");
                    $("#topic6").css("color", "black");
                    $("#topic7").css("color", "black");
                    $("#topic8").css("color", "black");
                    $("#topic9").css("color", "black");
                    $("#topic" + index).css("color", "white");
                    $('#topic1').css("border", "1px solid black");
                    $('#topic2').css("border", "1px solid black");
                    $('#topic3').css("border", "1px solid black");
                    $('#topic4').css("border", "1px solid black");
                    $("#topic5").css("border", "1px solid black");
                    $("#topic6").css("border", "1px solid black");
                    $("#topic7").css("border", "1px solid black");
                    $("#topic8").css("border", "1px solid black");
                    $("#topic9").css("border", "1px solid black");
                    $("#topic" + index).css("border", "1px solid #0072EF");
                }
                $('#topic').val(index);
                lastIndex = index;
            }
        }

        $('#serial').on("change", function () {
            var serial = $.trim($("#serial option:selected").val());
            if (serial === '-1') {
                $('#divDeviceModel').show();
            } else {
                $('#divDeviceModel').hide();
            }
        });

        $('#purchase').on("change", function () {
            var serial = $.trim($("#purchase option:selected").val());
            if (serial === '-1') {
                $('#divOtherPurchase').show();
            } else {
                $('#divOtherPurchase').hide();
            }
        });

        // $('#subject').keyup(function () {
        //     var querySubject = $.trim($("#subject").val());
        //     if (querySubject != "") {
        //         $.ajax({
        //             url: "queryarticles.php?query=" + querySubject
        //         }).done(function (data) {
        //             if (data != null) {
        //                 var data = JSON.parse(data);
        //                 if (data['count'] == undefined || data['count'] == 0) {
        //                     $("#articlesul li").remove();
        //                     $('.suggestion-list').hide();
        //                 } else {
        //                     $("#articlesul li").remove();
        //                     $('.suggestion-list').show();
        //                     var results = data['results'];
        //                     var token_id = $.trim($("#tokenid").val());
        //                     for (var i = 0; i < results.length; i++) {
        //                         $("#articlesul").append("<li><a href=\"javascript:void(0);\" onclick=\"goarticle('" + token_id + "','" + results[i]['id'] + "')\">" + results[i]['name'] + "</a></li>");
        //                     }
        //                 }
        //             } else {
        //                 $("#articlesul li").remove();
        //                 $('.suggestion-list').hide();
        //             }
        //         });
        //     } else {
        //         $("#articlesul li").remove();
        //         $('.suggestion-list').hide();
        //     }
        // });
        // $(".upload-remove").on("click", function () {
        //     console.log($(this).val());
        //     // $("#attachments-pool").hide();
        //     // $("#attachments").val("");
        //     // $("#hiddenattachments").val("");
        //     // $("#upload-link").text();
        // });

        $("#attachments").on("change", function () {
            var oFReader = new FileReader();
            var file = document.getElementById('attachments').files;
            // console.log(file);
            $("#attachments-pool").show();
            $("#attachments-pool").empty();
            for (var i = 0; i < file.length; i++) {
                var html_li = '';
                html_li += '<li class="upload-item">';
                html_li += '<img src="bootstrap-3.3.7/image/clip.png" style="width:11px;height:11px;">';
                html_li += '<a class="upload-link" target="_blank" >' + file[i].name + '</a>';
                // html_li += '<span class="upload-remove" onclick="removefile(this)" ></span>';
                html_li += '</li>';
                $("#attachments-pool").append(html_li);
            }
        });

        // function removefile(index) {
        //     // console.log(this);
        //     $(index).parent().remove();
        //     var file = document.getElementById('attachments').files;
        //     console.log(file);
        // }

    </script>
    </body>
    </html>

    <?php
}
function get_curl($url, $post = false, $return_json = false, $show_header = false)
{
    if ($post) {
        $is_post = 1;
    } else {
        $is_post = 0;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSLVERSION, 4);
    curl_setopt($ch, CURLOPT_HEADER, $show_header);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, $is_post);
    if ($is_post) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    if (!$return_json) {
        return json_decode($result, true);
    } else {
        return $result;
    }
}

?>