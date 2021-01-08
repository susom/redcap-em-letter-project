<?php
namespace Stanford\LetterProject;
/** @var \Stanford\LetterProject\LetterProject $module */

?>
<!DOCTYPE html>
<html>
<head>
    <title>Stanford Letter Project</title>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>

    <!-- Bootstrap core CSS -->

    <link rel="stylesheet" type="text/css" media="screen" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php print $module->getUrl("images/stanford_favicon.ico",true,true) ?>">

    <!-- Add local css and js for module -->
    <link href="<?php print $module->getUrl('css/letter_project.css', false, true) ?>" rel="stylesheet" type="text/css" media="screen,print"/>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" crossorigin="anonymous"></script>


    <style>
        /* Added this here to import image url */
        div.logo {
            background:url(<?php echo $image_url ?>) 50% 50% no-repeat;
            margin-top:80px;
            height:150px;
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

    <div class="logo"></div>

    <div class="pt-lg-5">
        <h1 class="text-center">Stanford Letter Project</h1>
    </div>
        <div class="row justify-content-center align-items-center h-100">
        <form role='form' method='POST' name='frm'>
            <div class="text-center">
                <div class="input-group input-group-lg text-center code w-100 login_email">
                    <input autofocus type="text" id='code' name='code' class="form-control text-center" placeholder="email address" aria-describedby="basic-addon1">
                    <div class="input-group-append">
                        <button type="submit" name="login" value="1" class="btn btn-primary">Login</button>
                    </div>
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
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>


</body>
</html>

