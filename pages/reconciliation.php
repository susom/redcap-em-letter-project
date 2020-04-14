<?php
namespace Stanford\LetterProject;
/** @var \Stanford\LetterProject\LetterProject $module */
?>
<!DOCTYPE html>
<html lan="en">
<head>
    <!-- Required meta tags -->
    <title>Stanford Letter Project</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap core CSS -->
    <!-- link rel="stylesheet" type="text/css" media="screen" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"-->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">

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
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <!--script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script-->
    <!--script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script-->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" crossorigin="anonymous"></script>

    <script src="<?php echo $module->getUrl("js/letter_project.js") ?>"></script>

</head>
<body>
<div id="mainbox">
    <nav class="navbar navbar-expand navbar-dark">
        <span class="navbar-brand mb-0 h1 float-left">Letter Project</span>
        <div class="collapse navbar-collapse justify-content-start" id="navbarText">
            <span class="navbar-brand mb-0 h1 float-left"><?php print  $name; ?></span>
        </div>
        <div class="collapse navbar-collapse justify-content-end" id="navbarText">
            <span class="navbar-text doctor_name float-right"><?php print  "Doctor: ". $doctor_name; ?></span>
        </div>
    </nav>
    <div class="container">
        <div id="box">
            <div class="main">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link active" id="tab-home" data-toggle="tab" role="tab" href="#home" aria-controls="home" aria-selected='true'>Home</a></li>
                    <?php renderTabs(); ?>
                    <li><a data-toggle="tab" data-key="print_page" href="#print_page">Print Page</a></li>
                </ul>
                <div class="tab-content">
                    <?php renderTabDivs($record); ?>
                </div>
            </div>
        </div>
        <input type="hidden" id="hidden_id" name="record" value="<?php print  $record; ?>"/>
        <input type="hidden" id="hidden_survey_link" name="record" value="<?php print  $survey_link; ?>"/>
    </div>
</div>
</div>
</body>


