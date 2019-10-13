<?php
error_reporting(E_ALL);

require 'vendor/autoload.php';

use Zendesk\API\HttpClient as ZendeskAPI;

if (isset($_POST['action']) && 'redirect' === $_POST['action']) {

} else {
    $token_id = $_GET['token_id'];
    $username = $_GET['user_name'];
    $email = $_GET['email'];
    $query = $_GET['query'];
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
        <title>Your question</title>
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

            .snippet {
                padding-top: 5px;
                font-size: 12px;
                color: black;
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
                    <input type="hidden" id="query" name="query" value="<?php echo $query; ?>"/>
                    <div class="form-group">
                        <label for="subject">Tell us how we can help</label>
                        <textarea id="subject" name="subject" class="form-control text-left" cols="50" rows="3"
                                  placeholder="Describe your issue here and we will look for a quick solution"
                                  required><?php echo $query ?></textarea>
                    </div>
                    <div class="suggestion-list" style="display:none;">
                        <div class="searchbox" style=""><label>Related FAQs</label>
                            <div class="searchbox-suggestions">
                                <ul id="articlesul">
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-sm btn-ubibot" style="width:48%;"
                                onclick="goback('<?php echo $token_id; ?>')">Back
                        </button>
                        <button type="button" class="btn btn-sm btn-ubibot" style="width:48%;"
                                onclick="gocreate('<?php echo $token_id; ?>','<?php echo $username; ?>','<?php echo $email; ?>')">
                            Submit a request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="bootstrap-3.3.7/jquery.min.js"></script>
    <script type="application/javascript">

        $(function () {
            var querySubject = $.trim($("#query").val());
            queryarticles(querySubject);
        });

        $('#subject').keyup(function () {
            var querySubject = $.trim($("#subject").val());
            queryarticles(querySubject);
        });

        function goback(token_id) {
            location.href = "index.php?token_id=" + token_id;
        }

        function goarticle(token_id, article_id, username, email) {
            var querySubject = $.trim($("#subject").val());
            location.href = "article.php?token_id=" + token_id + "&id=" + article_id + "&user_name=" + decodeURI(username) + "&email=" + decodeURI(email) + "&query=" + decodeURI(querySubject);
        }

        function gocreate(token_id, username, email) {
            var querySubject = $.trim($("#subject").val());
            location.href = "create.php?token_id=" + token_id + "&user_name=" + decodeURI(username) + "&email=" + decodeURI(email) + "&query=" + decodeURI(querySubject);
        }

        function queryarticles(querySubject) {
            if (querySubject != "") {
                $.ajax({
                    url: "queryarticles.php?query=" + querySubject
                }).done(function (data) {
                    if (data != null) {
                        var token_id = $.trim($("#tokenid").val());
                        var username = $.trim($("#username").val());
                        var email = $.trim($("#email").val());
                        var data = JSON.parse(data);
                        var html_button = 'Read more';
                        // var html_button = '<button type="button" class="btn btn-sm btn-ubibot" style="font-size:12px;height:30px;">See more</button>';

                        if (data['count'] == undefined || data['count'] == 0) {
                            $("#articlesul li").remove();
                            $('.suggestion-list').show();
                            $("#articlesul").append("<li style='font-size:15px;font-weight:700;'><a href=\"javascript:void(0);\" onclick=\"gocreate('" + token_id + "','" + username + "','" + email + "')\"><div>Didn't find your answer? Contact us by \"Submit a request\"</div></a></li>");
                        } else {
                            $("#articlesul li").remove();
                            $('.suggestion-list').show();
                            var results = data['results'];
                            for (var i = 0; i < results.length; i++) {
                                var created_at = results[i]['created_at2'];
                                $("#articlesul").append("<li><a href=\"javascript:void(0);\" onclick=\"goarticle('" + token_id + "','" + results[i]['id'] + "','" + username + "','" + email + "')\"><div>" + results[i]['name'] + "</div><div class='snippet'>" + removeHTMLTag(results[i]['body']).substring(0, 50) + "...</div><div style='display: inline-block;width: 100%;'><div style='display: inline-block;width: 50%;'>" + created_at + "</div><div style='display: inline-block;width: 50%;text-align: right;'>" + html_button + "</div></div></a></li>");
                            }
                            $("#articlesul").append("<li style='font-size:15px;font-weight:700;><a href=\"javascript:void(0);\" onclick=\"gocreate('" + token_id + "','" + username + "','" + email + "')\"><div>Didn't find your answer? Contact us by \"Submit a request\"</div></a></li>");
                        }
                    } else {
                        $("#articlesul li").remove();
                        $('.suggestion-list').show();
                        $("#articlesul").append("<li style='font-size:15px;font-weight:700;><a href=\"javascript:void(0);\" onclick=\"gocreate('" + token_id + "','" + results[i]['id'] + "')\"><div>Didn't find your answer? Contact us by \"Submit a request\"</div></a></li>");
                    }
                });
            } else {
                $("#articlesul li").remove();
                $('.suggestion-list').hide();
            }
        }

        // $("#attachments-remove").on("click", function () {
        //     $("#attachments-pool").hide();
        //     $("#attachments").val("");
        //     $("#hiddenattachments").val("");
        //     $("#upload-link").text();
        // });
        //
        // $("#attachments").on("change", function () {
        //     var oFReader = new FileReader();
        //     var file = document.getElementById('attachments').files[0];
        //     oFReader.readAsDataURL(file);
        //     oFReader.onloadend = function (oFRevent) {
        //         var src = oFRevent.target.result;
        //         $("#attachments-pool").show();
        //         $("#hiddenattachments").val(file.name);
        //         $("#upload-link").text(file.name);
        //     }
        // });
        function removeHTMLTag(str) {
            str = str.replace(/<\/?[^>]*>/g, ''); //去除HTML tag
            str = str.replace(/[ | ]*\n/g, '\n'); //去除行尾空白
            //str = str.replace(/\n[\s| | ]*\r/g,'\n'); //去除多余空行
            // str = str.replace(/ /ig, ''); //去掉
            return str;
        }
    </script>
    </body>
    </html>

    <?php
}

?>