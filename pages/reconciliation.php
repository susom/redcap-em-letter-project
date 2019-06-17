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


    <!--
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <!--script src="<?php print $module->getUrl("js/jquery-3.2.1.min.js",false,true) ?>"></script-->

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <!--script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'></script-->
    <!--link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"-->
    <!--script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script-->
    <!--script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script-->
    <!--script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script-->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" crossorigin="anonymous"></script>




    <!-- Add local css and js for module -->
    <link href="<?php print $module->getUrl('css/letter_project.css', false, true) ?>" rel="stylesheet" type="text/css" media="screen,print"/>


    <!--
        <script type='text/javascript' src="<?php print $module->getUrl("js/letter_project.js") ?>"></script>
    -->


</head>
<body>

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
    <div>



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
            content: '<div class="media d-block"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_cpr.jpg" class="media-object" alt="Sample Image"></a><br><div class="media-body"><p> If a person’s heart stops or if that person stops breathing and the person has not indicated he or she does not want CPR, health care professionals usually try to revive him or her using CPR. In most cases when people have a terminal illness this is not successful. (You do not need to have an advance directive to request a do-not-resuscitate order.)</p></div></div>'
        });
        $('[data-toggle="popover_breathing"]').popover({
            placement: 'right',
            trigger: 'show',
            container: 'body',
            html: true,
            content: '<div class="media d-block"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_breathing.jpg" ></a><div class="media-body text-justify"><p> If your lungs stop working properly, doctors can connect you to a machine called a ventilator. A ventilator is a machine that pumps air into a person’s lungs through a tube in the person’s mouth or nose that goes down the throat. The machine breathes for a person when he or she cannot.</p></div></div>'
        });

        $('[data-toggle="popover_feeding_tube"]').popover({
            placement: 'right',
            trigger: 'hover',
            container: 'body',
            html: true,
            content: '<div class="media"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_feeding_tube.png" class="media-object" style="height:150px" alt="Sample Image"></a><div class="media-body d-block"><p> There are various methods to feed people who can no longer eat, including inserting a tube into the stomach through a person’s nose or through the stomach wall to give food and fluids.</p></div></div>'
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
        $('button[name="submit"]').on('click', function() {
            saveResponse();
        });

        $('.btnNext').click(function() {
            console.log("NEXT!");
              //$('.nav-tabs > .active').next('li').find('a').trigger('click');
            $('.nav-tabs > .nav-item > .active').parent().next('li').find('a').trigger('click');
            saveResponse();
        });

        $('.btnPrevious').click(function() {
            console.log("PREVIOUS!");
            //$('.nav-tabs > .active').prev('li').find('a').trigger('click');
            $('.nav-tabs > .nav-item > .active').parent().prev('li').find('a').trigger('click');

            saveResponse();
        });

        $('.btnWitness').click(function() {
            saveResponse();
            console.log("SUBMITTING!  need to redirect to witnesses");

            //use ajax??
            //checkWitnessForm();

            //or just get it from php
            var witness_url = "<?php print $survey_link ?>";
            console.log("REDRIECTING TO ", witness_url);
             $(location).attr('href', witness_url);
            consoel.log("RETURNED TO RECON");
        });

        $('.btnPrint').click(function() {
            saveResponse();
            console.log("Print PDF");

            //update the PDF
            updatePDF();
            $('.nav-tabs a[href="#home"]').tab('show');

        });


        //bind the buttons from the print page tab
        $('#btn-email-pdf').click(function() {
            var selected = new Array();
            var participant_id = "<?php print $participant_id; ?>";
            console.log("EMAIL THIS");
             $('#checkbox:checked').each(function() {
                 selected.push($(this).attr('value'));
             });

            var data = {
                "action": "emailPDF",
                "record_id": participant_id,
                "data": selected
            }

            console.log(data);

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
                        alert (data.message);
                    }
                })
                .fail(function() {
                    alert ('Email PDF Failed!');
                    console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                    console.log( "error saving responses : status: " + jqxhr.status);
                })
                .always(function() {

                });
        });

        $('#btn-download-pdf').click(function() {
            var participant_id = "<?php print $participant_id; ?>";

            console.log("DOWNLOAD THIS");
            var data = {
                "action": "downloadPDF",
                "record_id": participant_id
            }

            var jqxhr = $.ajax({
                type: "POST",
                data: data,
                dataType: "json"
            })
                .done(function(data) {
                    if(data.result !== "success") {
                        // An error occurred
                        alert (data.message);
                    } else {
                        alert ("Your letter will be downloaded to your Downloads folder. ");
                        window.open(data.url);
                        return false;
                    }
                })
                .fail(function() {
                    alert ('DOWNOAD PDF Failed!');
                    console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                    console.log( "error saving responses : status: " + jqxhr.status);
                })
                .always(function() {

                });

            return false;

        });

        $('#btn-print-pdf').click(function() {
            console.log("PRINT THIS");

             var participant_id = "<?php print $participant_id; ?>";

            var data = {
                "action": "printPDF",
                "record_id": participant_id
            }

            var jqxhr = $.ajax({
                type: "POST",
                data: data,
                dataType: "json"
            })
                .done(function(data) {
                    if(data.result !== "success") {
                        // An error occurred
                        alert (data.message);
                    } else {
                        alert ("The print PDF will be displayed in a new tab. ");
                        window.open(data.url);
                        return false;
                    }
                })
                .fail(function() {
                    alert ('PRINT PDF Failed!');
                    console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                    console.log( "error saving responses : status: " + jqxhr.status);
                })
                .always(function() {

                });

            return false;
        });

        $('#decision_maker').on('click', 'button.send-invite', function () {
            buttonpressed = $(this).attr('name'); //todo: merge with teh other button press

            var event = $(this).data('event');
            var email_str = 'proxy_email_'+event;
            var email = $('#proxy_email_'+event).val();
            var participant_id = $(this).data('id');


            console.log("EMAIL is ", email);
            console.log("EVENT is ", event);
            console.log("BUTTONPRESSED is ", buttonpressed);

            var data = {
                "action": "saveNewEmail",
                "record_id": participant_id,
                "event" : event,
                "data": email
            }

            console.log(data);

            var ajax = $.ajax({
                type: "POST",
                data: data,
                dataType: "json"
            })
                .done(function(data) {
                    if(data.result == "success") {
                        location.reload();

                        // An error occurred
                        alert (data.message);
                        console.log('New email saved '+email);

                    } else {
                        //alert ("Your entries has been saved.");
                        alert (data.message);

                    }
                })
                .fail(function() {
                    alert ('Email PDF Failed!');
                    console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                    console.log( "error saving responses : status: " + jqxhr.status);
                })
                .always(function() {

                });
        });


        $('#decision_maker').on('click', 'button.send-pdf', function () {
            buttonpressed = $(this).attr('name');

            var email = $(this).data('email');
            var participant_id = $(this).data('id');

            console.log("EMAIL is ", email);
            console.log("BUTTONPRESSED is ", buttonpressed);

            var data = {
                "action": "emailPDF",
                "record_id": participant_id,
                "data": [email]
            }

            console.log(data);

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
                        alert (data.message);
                    }
                })
                .fail(function() {
                    alert ('Email PDF Failed!');
                    console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                    console.log( "error saving responses : status: " + jqxhr.status);
                })
                .always(function() {

                });
        });


        function checkWitnessForm() {
            var participant_id = <?php print $participant_id; ?>;

            var w_data = {
                "action": "checkWitnessForm",
                "record_id": participant_id
            };
            console.log("WITNESS FORMS", participant_id);


            //set up redirect
            var ajax = $.ajax({
                type: "POST",
                data: w_data,
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
                    alert ('checkWitnessForm Failed!');
                    console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                    console.log( "error saving responses : status: " + jqxhr.status);
                })
                .always(function() {

                });

        }

        /**
         * TODO: IS THIS NEEDED???
         */
        function updatePDF() {
            var data = {
                "action": "updatePDF",
                "record_id": <?php print $participant_id; ?>
            };

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
                    alert ('updatePDF Failed!');
                    console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                    console.log( "error saving responses : status: " + jqxhr.status);
                })
                .always(function() {

                });
        }

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
                "q5_name_decision_1": $("#q5_final_name_decision_1").val(),
                "q5_name_decision_2": $("#q5_final_name_decision_2").val(),
                "q5_name_decision_3": $("#q5_final_name_decision_3").val(),
                "q5_relationship_decision_1": $("#q5_final_relationship_decision_1").val(),
                "q5_relationship_decision_2": $("#q5_final_relationship_decision_2").val(),
                "q5_relationship_decision_3": $("#q5_final_relationship_decision_3").val(),
                "q5_address_decision_1": $("#q5_final_address_decision_1").val(),
                "q5_address_decision_2": $("#q5_final_address_decision_2").val(),
                "q5_address_decision_3": $("#q5_final_address_decision_3").val(),
                "q5_city_decision_1": $("#q5_final_city_decision_1").val(),
                "q5_city_decision_2": $("#q5_final_city_decision_2").val(),
                "q5_city_decision_3": $("#q5_final_city_decision_3").val(),
                "q5_phone_decision_1": $("#q5_final_phone_decision_1").val(),
                "q5_phone_decision_2": $("#q5_final_phone_decision_2").val(),
                "q5_phone_decision_3": $("#q5_final_phone_decision_3").val(),
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


