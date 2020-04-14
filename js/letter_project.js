$(document).ready(function() {
    console.log();

    $('[data-toggle="popover_cpr"]').popover({
        placement: 'right',
        trigger: 'hover',
        container: 'body',
        html: true,
        content: '<div class="media d-block"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_cpr.jpg" class="media-object" alt="Sample Image"></a><div class="media-body"><p> If a person’s heart stops or if that person stops breathing and the person has not indicated he or she does not want CPR, health care professionals usually try to revive him or her using CPR. In most cases when people have a terminal illness this is not successful. (You do not need to have an advance directive to request a do-not-resuscitate order.)</p></div></div>'
    });
    $('[data-toggle="popover_breathing"]').popover({
        placement: 'right',
        trigger: 'hover',
        container: 'body',
        html: true,
        content: '<div class="media d-block"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_breathing.jpg" ></a><div class="media-body text-justify"><p> If your lungs stop working properly, doctors can connect you to a machine called a ventilator. A ventilator is a machine that pumps air into a person’s lungs through a tube in the person’s mouth or nose that goes down the throat. The machine breathes for a person when he or she cannot.</p></div></div>'
    });

    $('[data-toggle="popover_feeding_tube"]').popover({
        placement: 'right',
        trigger: 'hover',
        container: 'body',
        html: true,
        content: '<div class="media d-block"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_feeding_tube.png" class="media-object" alt="Sample Image"></a><div class="media-body d-block"><p> There are various methods to feed people who can no longer eat, including inserting a tube into the stomach through a person’s nose or through the stomach wall to give food and fluids.</p></div></div>'
    });

    $('[data-toggle="popover_dialysis"]').popover({
        placement: 'right',
        trigger: 'hover',
        container: 'body',
        html: true,
        content: '<div class="media d-block"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_dialysis.png" class="media-object" alt="Dialysis"></a><div class="media-body d-block"><p>If your kidneys stop working properly, your blood can be cleaned using a dialysis machine. The dialysis machine does the work of your kidneys. Most people have to go to a dialysis center and be dialyzed three times a week. Some are dialyzed at home.</p></div></div>'
    });

    $('[data-toggle="popover_hospice"]').popover({
        placement: 'right',
        trigger: 'hover',
        container: 'body',
        html: true,
        content: '<div class="media d-block"><a href="#" class="top"><img src="https://redcap.stanford.edu/plugins/open/resources/popover_hospice.jpg" class="media-object" alt="Hospice"></a><div class="media-body d-block"><p>is a type of care provided to a patient at the end of life. Hospice care focuses on enhancing the dying person’s quality of life and provides support to their family or friends. Hospice care is usually provided in the home, but also can be provided in a hospital or nursing home.</p></div></div>'
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
        console.log("xSUBMITTING!  need to redirect to witnesses");

        //use ajax??
        //checkWitnessForm();

        //or just get it from php
        var witness_url = $('#hidden_survey_link').val();     //"<?php print $survey_link ?>";
        console.log("REDRIECTING TO ", witness_url);
        $(location).attr('href', witness_url);
        console.log("RETURNED TO RECON");
    });

    $('.btnPrint').click(function() {
        var participant_id = $(this).data('id');
        console.log("Print PDF for record "+participant_id);
        saveResponse();


        //update the PDF
        updatePDF();
        $('.nav-tabs a[href="#home"]').tab('show');

    });


    //bind the buttons from the print page tab
    $('#btn-email-pdf').click(function() {
        var selected = new Array();
        var participant_id = $('#hidden_id').val();
        console.log("EMAIL PDF to "+participant_id);
        $('#checkbox:checked').each(function() {
            selected.push($(this).attr('value'));
        });

        var data = {
            "action": "emailPDF",
            "record_id": participant_id,
            "data": selected
        }

        console.log(data);

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
        var participant_id = $('#hidden_id').val();

        console.log("DOWNLOAD THIS pdf to "+participant_id);
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
                alert ('DOWNLOAD PDF Failed!');
                console.log( "error saving responses : statusText:  " + jqxhr.statusText);
                console.log( "error saving responses : status: " + jqxhr.status);
            })
            .always(function() {

            });

        return false;

    });

    $('#btn-print-pdf').click(function() {
        var participant_id = $(this).data('id');
        console.log("xPRINTING PDF for record "+participant_id);

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
        buttonpressed = $(this).attr('name'); //todo: merge with the other button press

        var event = $(this).data('event');
        var email_str = 'proxy_email_'+event;
        var email = $('#proxy_email_'+event).val();
        var participant_id = $(this).data('id');


        console.log("xEMAIL INVITE is ", email);
        console.log("EVENT is ", event);
        console.log("BUTTONPRESSED is ", buttonpressed);

        var data = {
            "action": "saveNewEmail",
            "record_id": participant_id,
            "event" : event,
            "data": email
        }

        console.log(data);

        var jqxhr = $.ajax({
            type: "POST",
            data: data,
            dataType: "json"
        })
            .done(function(data) {
                if(data.result == "success") {
                    location.reload();

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

        alert("Sending PDF to "+email);

        console.log("xPDF EMAIL to ", email);
        console.log("xPDF ID is ", participant_id);
        console.log("BUTTONPRESSED is ", buttonpressed);

        var data = {
            "action": "emailPDF",
            "record_id": participant_id,
            "data": [email]
        }

        console.log(data);

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


/**
IS THIS NEEDED??
 */
    /*
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
    */
    /**
     * TODO: IS THIS NEEDED???
     */
    function updatePDF() {
        var data = {
            "action": "updatePDF",
            "record_id": $('#hidden_id').val()
    };

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
            "record_id": $('#hidden_id').val(),  //hidden field with participant_id
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
            "q5_state_decision_1": $("#q5_final_state_decision_1").val(),
            "q5_state_decision_2": $("#q5_final_state_decision_2").val(),
            "q5_state_decision_3": $("#q5_final_state_decision_3").val(),
            "q5_zip_decision_1": $("#q5_final_zip_decision_1").val(),
            "q5_zip_decision_2": $("#q5_final_zip_decision_2").val(),
            "q5_zip_decision_3": $("#q5_final_zip_decision_3").val(),
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
            "q9___99": $('input[name="q9_final_99"]').is(":checked") ? 1 : 0,
            "q9_99_other": $('#q9_final').val(),

            "q10": $('input[name="q10_final"]:checked').val(),
            "q11": $('input[name="q11_final"]:checked').val(),
            "q12": $('input[name="q12_final"]:checked').val(),
            "q13": $('input[name="q13_final"]:checked').val(),
            "q13_donate_following": $('#q13_final').val(),

            "q14___1": $('input[name="q14_final_1"]').is(":checked") ? 1 : 0,
            "q15": $('#q15_final').val(),
    };

        // var q6 = [];
        // $('input:checkbox[name="q6_final"]:checked')
        //     .each(function()
        //     {
        //         var id = "q6___"+$(this).val();
        //         console.log("ID is "+id);
        //         q6.push({ id : 1});
        //         console.log("q6_final : "+ $(this).val());
        //     });

        // var q7 = [];
        // $('input:checkbox[name="q7_final"]:checked')
        //     .each(function()
        //     {
        //         var id = "q7___"+$(this).val();
        //         console.log("ID is "+id);
        //         q7.push({ id : 1});
        //         console.log("q7_final : "+ $(this).val());
        //     });


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
    };

});