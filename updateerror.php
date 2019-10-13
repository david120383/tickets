<?php
$token_id = $_GET['token_id'];
$requestid = $_GET['requestid'];
$username = $_GET['user_name'];
$email = $_GET['email'];
$error = $_GET['error'];
$errorMessage = "";
if ($error == 1) {
    $errorMessage = "System error. Please reply later. ";
} elseif ($error == 2) {
    $errorMessage = "Upload failure. Please try again later.";
} elseif ($error == 3) {
    $errorMessage = "The file is oversize. Please compress it and try again. ";
} elseif ($error == 4) {
    $errorMessage = "Upload failure. Please try again later. ";
} elseif ($error == 5) {
    $errorMessage = " Verification code is incorrectly. Please enter it before submitting the request.  ";
} else {
    $errorMessage = "System error. Please try again later.";
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
<body>
<div class="container theme-showcase" role="main">
    <div class="row">
        <div class="col-xs-12">

            <!--            <div style="box-shadow: 2px 2px 5px #bbb;border-radius:12px;margin-top:10px;">-->
            <div style="padding-left:10px;padding-top:20px;padding-bottom:20px;text-align:center;">
                <img src="bootstrap-3.3.7/image/error.png" style="width:40px;height:40px;">
                <div style="padding-top:5px;">
                    <label style="word-break:break-word;"><?php echo $errorMessage ?></label>
                </div>
            </div>
            <!--            </div>-->
        </div>
        <div class="col-xs-12" style="padding-top:20px;">
            <button type="button" class="btn btn-sm btn-ubibot"
                    onclick="goback('<?php echo $token_id; ?>','<?php echo $requestid; ?>','<?php echo $username; ?>','<?php echo $email; ?>')">
                Back
            </button>
        </div>
    </div>
</div>
<script src="bootstrap-3.3.7/jquery.min.js"></script>
<script src="bootstrap-3.3.7/js/bootstrap.min.js"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="bootstrap-3.3.7/ie10-viewport-bug-workaround.js"></script>
<script type="application/javascript">
    function goback(token_id, id, user_name, email) {
        location.href = "view.php?token_id=" + token_id + "&id=" + id + "&user_name=" + user_name + "&email= " + email;
    }
</script>
</body>
</html>
