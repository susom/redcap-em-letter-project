<?php
namespace Stanford\LetterProject;
/** @var \Stanford\LetterProject\LetterProject $module */

$popover_content = array(
        'cpr' => 'If a person’s heart stops or if that person stops breathing and the person has not indicated he or she'.
            ' does not want CPR, health care professionals usually try to revive him or her using CPR. In most cases when'.
            ' people have a terminal illness this is not successful. (You do not need to have an advance directive to request a do-not-resuscitate order.)'
);

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


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="<?php print $module->getUrl("js/jquery-3.2.1.min.js",false,true) ?>"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <!-- Add local css and js for module -->
    <link href="<?php print $module->getUrl('css/letter_project.css', false, true) ?>" rel="stylesheet" type="text/css" media="screen,print"/>


    <!--
        <script type='text/javascript' src="<?php print $module->getUrl("js/reconciliation.js") ?>"></script>
    -->
</head>
<body>
<div class="container">
    <div class="well">
        <div class="row user_title">
            <div class="col-xs-6">
                <span class="glyphicon glyphicon-user"></span>
                <span class="user_name"><?php print $name; ?></span>
            </div>
            <div class="col-xs-6 text-right">
                <span class="doctor_name"><?php print  "Doctor: ". $doctor_name; ?></span>

            </div>
        </div>
    </div>

    <div>



    <div id="box">
        <div class="main">
            <ul class="nav nav-tabs assessment-tabs">
                <li class="active"><a data-toggle="tab" data-key="home" href="#home">Home</a></li>
                <?php renderTabs(); ?>
            </ul>
            <div class="tab-content">
                <?php renderTabDivs($record); ?>
            </div>
        </div>
    </div>
    <input type="hidden" name="record"/>
</div>
</body>

<script type='text/javascript'>
    $(document).ready(function() {

        $('[data-toggle="popover_cpr"]').popover({
            placement: 'right',
            trigger: 'hover',
            container: 'body',
            html: true,
            content: '<div class="media"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_cpr.jpg" class="media-object" alt="Sample Image"></a><br><div class="media-body"><p> If a person’s heart stops or if that person stops breathing and the person has not indicated he or she does not want CPR, health care professionals usually try to revive him or her using CPR. In most cases when people have a terminal illness this is not successful. (You do not need to have an advance directive to request a do-not-resuscitate order.)</p></div></div>'
        });
        $('[data-toggle="popover_breathing"]').popover({
            placement: 'right',
            trigger: 'hover',
            container: 'body',
            html: true,
            content: '<div class="media"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_breathing.jpg" class="media-object" alt="Sample Image"></a><br><div class="media-body"><p> If your lungs stop working properly, doctors can connect you to a machine called a ventilator. A ventilator is a machine that pumps air into a person’s lungs through a tube in the person’s mouth or nose that goes down the throat. The machine breathes for a person when he or she cannot.</p></div></div>'
        });

        $('[data-toggle="popover_feeding_tube"]').popover({
            placement: 'right',
            trigger: 'hover',
            container: 'body',
            html: true,
            content: '<div class="media"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_feeding_tube.png" class="media-object" style="height:150px" alt="Sample Image"></a><br><div class="media-body"><p> There are various methods to feed people who can no longer eat, including inserting a tube into the stomach through a person’s nose or through the stomach wall to give food and fluids.</p></div></div>'
        });

        $('[data-toggle="popover_dialysis"]').popover({
            placement: 'right',
            trigger: 'hover',
            container: 'body',
            html: true,
            content: '<div class="media"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_dialysis.png" class="media-object" style="height:200px" alt="Dialysis"></a><br><div class="media-body"><p>If your kidneys stop working properly, your blood can be cleaned using a dialysis machine. The dialysis machine does the work of your kidneys. Most people have to go to a dialysis center and be dialyzed three times a week. Some are dialyzed at home.</p></div></div>'
        });

        $('[data-toggle="popover_hospice"]').popover({
            placement: 'right',
            trigger: 'hover',
            container: 'body',
            html: true,
            content: '<div class="media"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_hospice.jpg" class="media-object" style="height:200px" alt="Hospice"></a><br><div class="media-body"><p>is a type of care provided to a patient at the end of life. Hospice care focuses on enhancing the dying person’s quality of life and provides support to their family or friends. Hospice care is usually provided in the home, but also can be provided in a hospital or nursing home.</p></div></div>'
        });


        // Bind submit button
        $('button[name="submit"]').on('click', function() { saveResponse(); });

          $('.btnNext').click(function() {
              console.log("NEXT!");
              $('.nav-tabs > .active').next('li').find('a').trigger('click');
               saveResponse();
          });

        $('.btnPrevious').click(function() {
            console.log("PREVIOUS!");
            $('.nav-tabs > .active').prev('li').find('a').trigger('click');
            saveResponse();
        });

        $('.btnSubmit').click(function() {
            console.log("SUBMITTING!");
            saveResponse();
        });


        function saveResponse(record) {
            console.log("SAVING!!");
            //pick up data in all the tab
            var data = {
                "action": "saveResponse",
                "record_id": <?php print $participant_id; ?>,
                "q1": $('#q1_final').val(),
                "q2_milestone_1": $('input[name="q2_final_1"]').val(),
                "q2_milestone_2": $('input[name="q2_final_2"]').val(),
                "q2_milestone_3": $('input[name="q2_final_3"]').val(),
                "q2_milestone_4": $('input[name="q2_final_4"]').val(),
                "q3": $('#q3_final').val(),
                "q4": $('#q4_final').val(),
                "q5_name_decision_1": $('input[name="q5_final_name_decision_1"]').val(),
                "q5_name_decision_2": $('input[name="q5_final_name_decision_2"]').val(),
                "q5_name_decision_3": $('input[name="q5_final_name_decision_3"]').val(),
                "q5_relationship_decision_1": $('input[name="q5_final_relationship_decision_1"]').val(),
                "q5_relationship_decision_2": $('input[name="q5_final_relationship_decision_2"]').val(),
                "q5_relationship_decision_3": $('input[name="q5_final_relationship_decision_3"]').val(),
                "q5_address_decision_1": $('input[name="q5_final_address_decision_1"]').val(),
                "q5_address_decision_2": $('input[name="q5_final_address_decision_2"]').val(),
                "q5_address_decision_3": $('input[name="q5_final_address_decision_3"]').val(),
                "q5_city_decision_1": $('input[name="q5_final_city_decision_1"]').val(),
                "q5_city_decision_2": $('input[name="q5_final_city_decision_2"]').val(),
                "q5_city_decision_3": $('input[name="q5_final_city_decision_3"]').val(),
                "q5_phone_decision_1": $('input[name="q5_final_phone_decision_1"]').val(),
                "q5_phone_decision_2": $('input[name="q5_final_phone_decision_2"]').val(),
                "q5_phone_decision_3": $('input[name="q5_final_phone_decision_3"]').val(),
                "q6": $('input[name="q6_final"]:checked').val(),

                "q7_cpr": $('input[name="q7_cpr_final"]:checked').val(),
                "q7_breathing": $('input[name="q7_breathing_final"]:checked').val(),
                "q7_dialyses": $('input[name="q7_dialyses_final"]:checked').val(),
                "q7_transfusions": $('input[name="q7_transfusions_final"]:checked').val(),
                "q7_food": $('input[name="q7_food_final"]:checked').val(),
                "q7_cpr_inst": $('#q7_cpr_final').val(),
                "q7_breathing_inst": $('#q7_breathing_final').val(),
                "q7_dialyses_inst": $('#q7_dialyses_final').val(),
                "q7_transfusions_inst": $('#q7_transfusions_final').val(),
                "q7_food_inst": $('#q7_food_final').val(),

                "q8_unconscious": $('input[name="q8_unconscious_final"]:checked').val(),
                "q8_confused": $('input[name="q8_confused_final"]:checked').val(),
                "q8_living": $('input[name="q8_living_final"]:checked').val(),
                "q8_illness": $('input[name="q8_illness_final"]:checked').val(),
                "q8_unconscious_inst": $('#q8_unconscious_final_inst').val(),
                "q8_confused_inst": $('#q8_confused_final_inst').val(),
                "q8_living_inst": $('#q8_living_final_inst').val(),
                "q8_illness_inst": $('#q8_illness_final_inst').val(),

                "q9___1": $('input[name="q9_final_1"]').is(":checked") ? 1 : 0,
                "q9___2": $('input[name="q9_final_2"]').is(":checked") ? 1 : 0,
                "q9___3": $('input[name="q9_final_3"]').is(":checked") ? 1 : 0,
                "q9___4": $('input[name="q9_final_4"]').is(":checked") ? 1 : 0,
                "q9___99": $('input[name="q9_final_5"]').is(":checked") ? 1 : 0,
                "q9_99_other": $('#q9_final').val(),

                "q10": $('input[name="q10_final"]:checked').val(),
                "q11": $('input[name="q11_final"]:checked').val(),
                "q12": $('input[name="q12_final"]:checked').val(),
                "q13": $('input[name="q13_final"]:checked').val(),
                "q13_donate_following": $('#q13_final').val(),

                "q14___1": $('input[name="q14_final_1"]').is(":checked") ? 1 : 0,
                "q15": $('#q15_final').val(),
            };

//            var q6 = [];
//            $('input:checkbox[name="q6_final"]:checked')
//                .each(function()
//                {
//                    var id = "q6___"+$(this).val();
//                    console.log("ID is "+id);
//                    q6.push({ id : 1});
//                    console.log("q6_final : "+ $(this).val());
//                });
//
//            var q7 = [];
//            $('input:checkbox[name="q7_final"]:checked')
//                .each(function()
//                {
//                    var id = "q7___"+$(this).val();
//                    console.log("ID is "+id);
//                    q7.push({ id : 1});
//                    console.log("q7_final : "+ $(this).val());
//                });


            var ajax = $.ajax({
                type: "POST",
                data: data,
                dataType: "json"
            })
                .done(function(data) {
                    if(data.result !== "success") {
                        // An error occurred
                        alert (data.message);
                    } else {
                        //alert ("Your entries has been saved.");
                    }
                })
                .fail(function() {
                    alert ('saveResponse Failed!');
                    console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                    console.log( "error saving responses : status: " + jqxhr.status);
                })
                .always(function() {

                });
        };

        function FillFinal(v, d)  {

            console.log("text "+v);
            console.log("Target: "+d);

            var new_string = $('#'+d).val() + v;

            $('#'+d).val(new_string);
        }
    });
</script>

<style>
    .nav-tabs {
        display:none;
    }

</style>



