<?php
namespace Stanford\LetterProject;
/** @var \Stanford\LetterProject\LetterProject $module */

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo LetterProject::$config['project_title'] ?></title>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" type="text/css" media="screen" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php print $module->getUrl("images/stanford_favicon.ico",false,true) ?>">

    <!-- Local CSS/JS -->
    <link rel="stylesheet" type="text/css" media="screen,print" href="<?php print $module->getUrl("css/letter_project.css",false,true) ?>"/>

    <style>
        /* Added this here to import image url */
        div.logo {
            background:url(<?php echo $image_url ?>) 50% 50% no-repeat;
            margin-top:80px;
            height:250px;
            background-size:contain;
            text-indent:-5000px;
        }
    </style>

</head>
<body>
<div class='container'>
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xs-12">
            <?php echo LetterProject::getSessionMessage() ?>
        </div>
    </div>
    <div class="row">
        <h2 class="text-center"><?php echo LetterProject::$config['project_title']; ?></h2>

        <div class="logo"></div>
    </div>
    <div class='row'>
        <form class='form-horizontal' role='form' method='POST' name='frm'>
            <div class="text-center">
                <div class="input-group input-group-lg text-center code">
                    <input autofocus type="text" id='code' name='code' class="pt18 form-control text-center" placeholder="email address" aria-describedby="basic-addon1">
                    <span class="input-group-btn">
                                <button type="submit" name="login" value="1" class="btn btn-primary pt18">Login</button>
                            </span>
                </div>
                <div class="login_comment mb-20">Please contact Letter Project if you do not remember your code</div>
            </div>
        </form>
    </div>
</div>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<!--script src='https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js'></script-->
<script src="<?php print $module->getUrl("js/jquery-3.2.1.min.js",false,true) ?>"></script>

<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>


</body>
</html>

