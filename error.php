<?php
$requesterId = $_GET['token_id'];
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
                        <label>System error. Please try again later.</label></div>
                </div>
<!--            </div>-->
        </div>
        <div class="col-xs-12" style="padding-top:20px;">
            <button type="button" class="btn btn-sm btn-ubibot"
                    onclick="goback('<?php echo $requesterId; ?>')">Back
            </button>
        </div>
    </div>
</div>
<script src="bootstrap-3.3.7/jquery.min.js"></script>
<script src="bootstrap-3.3.7/js/bootstrap.min.js"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="bootstrap-3.3.7/ie10-viewport-bug-workaround.js"></script>
<script type="application/javascript">
    function goback(token_id) {
        location.href = "index.php?token_id=" + token_id;
    }
</script>
</body>
</html>
