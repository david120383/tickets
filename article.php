<?php
require 'utility.php';

$token_id = $_GET['token_id'];

if ($token_id == "") {
    header('Location: error.php');
} else {
    $id = $_GET['id'];
    if ($id == "") {
        header('Location: error.php?token_id=' . $token_id);
    } else {

        $username = $_GET['user_name'];
        $email = $_GET['email'];
        $query = $_GET['query'];
//        $config = parse_ini_file('./config.ini');
        $config = getconfig();
        $subdomain = $config['subdomain'];
        $ubibot_url = "https://{$subdomain}.zendesk.com/api/v2/help_center/en-us/articles/{$id}.json";
        $array = geturl($ubibot_url);
        if (isset($array['article'])) {
            $printarray = $array['article'];
        } else {
            $printarray = [];
        }
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
        .article-header {
            align-items: flex-start;
            display: flex;
            flex-direction: column;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 40px;
            margin-top: 20px;
        }

        h1 {
            font-size: 32px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            font-weight: 400;
            margin-top: 0;
        }

        .article-author {
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .article-avatar {
            margin-right: 10px;
        }

        .avatar {
            display: inline-block;
            position: relative;
        }

        .article-meta {
            display: inline-block;
            vertical-align: middle;
        }

        .meta-group {
            display: block;
        }

        ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .meta-data {
            color: #666;
            font-size: 13px;
            font-weight: 300;
        }
    </style>
</head>
<body style="padding-top:30px;">
<div class="container theme-showcase" role="main">

    <header class="article-header">
        <h1 title="<?php echo $printarray['title'] ?>" class="article-title"><?php echo $printarray['title'] ?></h1>
        <div class="article-author">

            <div class="avatar article-avatar">
                <span class="icon-agent"></span>

                <img src="bootstrap-3.3.7/image/02.png"
                     style="width:40px;height:40px;">
            </div>

            <div class="article-meta">

                <ul class="meta-group">

                    <li class="meta-data">
                        <time datetime="2019-02-22T09:40:41Z" title="2019-02-22 17:40"
                              data-datetime="relative"><?php echo time_tran($printarray['created_at']) ?> updated
                        </time>
                    </li>

                </ul>
            </div>
        </div>
    </header>

    <section class="article-info">
        <div class="article-content">
            <div class="article-body">
                <?php echo $printarray['body'] ?>
            </div>

            <div class="article-attachments">
                <ul class="attachments">

                </ul>
            </div>
        </div>
    </section>

    <div class="row">
        <div class="col-xs-12" style="padding-top:20px;">
            <button type="button" class="btn btn-sm btn-ubibot"
                    onclick="goback('<?php echo $token_id; ?>','<?php echo $username; ?>','<?php echo $email; ?>','<?php echo $query; ?>')">
                Back
            </button>
        </div>
    </div>
</div>
<script src="bootstrap-3.3.7/jquery.min.js"></script>
<script src="bootstrap-3.3.7/js/bootstrap.min.js"></script>
<script src="bootstrap-3.3.7/ie10-viewport-bug-workaround.js"></script>
<script type="application/javascript">
    function goback(token_id, username, email, query) {
        location.href = "guide.php?token_id=" + token_id + "&user_name=" + decodeURI(username) + "&email=" + decodeURI(email) + "&query=" + decodeURI(query);
    }
</script>
</body>
</html>
