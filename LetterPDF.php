<?php

namespace Stanford\LetterProject;

/** @var \Stanford\LetterProject\LetterProject $module */

require __DIR__ . '/vendor/autoload.php';

use TCPDF;
use REDCap;

require_once('tcpdf_include.php');

class LetterPDF extends TCPDF
{
    public function Header()
    {
        global $module;

        $from_name = $this->CustomHeaderText;
        $img = $module->getUrl('images/shc_barcode.png');

        $this->SetFont('arial', 'A', 8);
        $this->SetXY(15, 5);
        $this->Cell(90, 2, 'Medical Record Number', 'R', 1, 'L', 0, '', 0, false, 'T', 'C');
        $this->Cell(90, 2, 'Patient Name:  '.$from_name, 'R', 1, 'L', 0, '', 0, false, 'T', 'C');
        $this->SetXY(15, 8);
        $this->Cell(90, 10, '', 'R', 1, 'L', 0, '', 0, false, 'T', 'C');
        //$this->SetXY(15, 10);
        //$this->Cell(90, 6, '', 'R', 1, 'L', 0, '', 0, false, 'T', 'C');
        //$this->SetXY(15, 14);
        //$this->Cell(90, 6, '', 'R', 1, 'L', 0, '', 0, false, 'T', 'C');

        $this->SetXY(105, 2);
        $this->SetFont('arial', 'A', 7);
        $this->Cell(90, 4, 'STANFORD HEALTH CARE', 0, 0, 'C', 0, '', 0, false, 'T', 'C');
        $this->SetXY(105, 5);
        $this->Cell(90, 4, 'STANFORD, CALIFORNIA 94305', 0, 0, 'C', 0, '', 0, false, 'T', 'C');

        $this->SetXY(90, 8);
        //$this->Image('images/shc_barcode.png', 105, 6, 40, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
        $this->Image($img, 135, 8, '', 9, '', '', 'T', false, 300, '', false, false, 1, false, false, false);

        $this->SetXY(105, 17);
        $this->Cell(90, 4, 'ADMIN ADVANCE DIRECTIVE:', 'L', 0, 'C', 0, '', 0, false, 'T', 'C');
        $this->SetXY(105, 20);
        $this->Cell(90, 4, 'WHAT MATTERS MOST', 0, 0, 'C', 0, '', 0, false, 'T', 'C');

        $this->SetXY(15, 20);
        $this->SetFont('arial', 'A', 6);
        $this->Cell(90, 4, 'Addressograph or Label - Patient Name, Medical Record Number', 'R', 0, 'C', 0, '', 0, false, 'T', 'C');
        $this->SetXY(125, 20);
        $this->Cell(90, 3, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'C');

        //$this->SetXY(400, 10);
        //$this->writeHTML($html_r, true, false, false, false, '');



        //$this->SetXY(800, 15);


        //$title = utf8_encode('title');
        //$subtitle = utf8_encode('sub title');
        //$this->Cell(0, 4, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        $this->SetHeaderMargin(40);
        $this->Line(15, 24, 405, 24);

    }

    public function Footer()
    {
        $this->SetFont('arial', '', 8);
        $this->Cell(0,5,'15-3192 (03/19)', 0,false, 'L');


    }

    public static function makeHTMLPage1($record_id, $final_data)
    {
        global $module;
        $doctor_name = $final_data['ltr_doctor_name'];
        $from_name = $final_data['ltr_name'];

        //$module->emDebug("DOCTOR name is $doctor_name", nl2br($final_data['q1']));
        $q1 = nl2br($final_data['q1']);
        $q3 = nl2br($final_data['q3']);
        //create some HTML content
        $html = <<<EOF

<head>
<style>
.cls_section {background-color:#e1e1e1;color:#962b28;font-size:18pt; font-weight:bold}
.cls_question {font-weight:bold }
.cls_example {font-style:italic }
.cls_response {
  border-bottom: 1px solid black;
  min-width: 100px;
}
.cls_response_2 {
text-decoration: underline; 
white-space: pre;
}
.cls_response_4 {
color:#1b1fff;
}
</style>
</head>
<body>
<div class="cls_section"><span>Part 1: Tell Us about What Matters Most to You</span></div>
<div class="cls_question"><span>Dear Doctor {$doctor_name},</span></div>
<div class="cls_question">RE: What matters most to me at the end of my life</span></div>
<div style="" class="cls_013"><span class="cls_013">I realize how important it is that I communicate my wishes to you and my family. I know that you are very busy. You may find it awkward to talk to me about my end-of-life wishes or you may feel that it is too early for me to have this conversation. So I am writing this letter to clarify what matters most to me.</span></div>

<div class="cls_question">Here is what matters most to me:</span></div>
<div class="cls_response_4"><span class="cls_013"> {$q1}</span></div>

<div class="cls_question"><span>Here are my important future life milestones:</span></div>
<div class="cls_response_4"><span>1. {$final_data['q2_milestone_1']} </span></div>
<div class="cls_response_4"><span>2. {$final_data['q2_milestone_2']}</span></div>
<div class="cls_response_4"><span>3. {$final_data['q2_milestone_3']}</span></div>
<div class="cls_response_4"><span>4. {$final_data['q2_milestone_4']}</span></div>
<div class="cls_question">Here is how we prefer to handle bad news in my family:</span></div>
<div class="cls_response_4">{$q3}</span></div>
</body>
EOF;

        return $html;

    }

    public static function makeHTMLPage2($record_id, $final_data) {

        //preserve the line feeds
        $q4 = nl2br($final_data['q4']);



        $html = <<<EOF
<head>
<style>
    .cls_section {background-color:#e1e1e1;color:#962b28;font-size:18pt; font-weight:bold}
    .cls_question {font-weight:bold }
    .cls_example {font-style:italic }
    .cls_response {
      border-bottom: 1px solid black;
      min-width: 100px;
    }
.cls_response_2 {
text-decoration: underline; 
white-space: pre;
}
.cls_response_4 {
color:#1b1fff;
}
    .cls_grey_bkgd {background-color:#e1e1e1;font-weight:bold}
</style>
</head>
<body>
<div class="cls_section">
<span> Part 2: Who Makes Decisions for You when You Cannot</span></div>
<div class="cls_question">Here is how we make medical decisions in our family:</div>
<div class="cls_response_4"><span> {$q4} </span></div>
<div class="cls_question"><span>Here is who I want making medical decisions for me when I am not able to make my own decision:</span></div>
<div class="cls_grey_bkgd"><span>Decision maker #1</span></div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
  <tr>
  <td colspan="3">Signature:</td>
  <td colspan="3">Date:</td>
 </tr>
  <tr>
  <td colspan="3">Name: <span class="cls_response_4"> {$final_data['q5_name_decision_1']}</span></td>
  <td colspan="3">Relationship: <span class="cls_response_4"> {$final_data['q5_relationship_decision_1']}</span></td>
 </tr>
 <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['q5_address_decision_1']}</span></td>
 </tr>
 <tr>
  <td colspan="2">City: <span class="cls_response_4"> {$final_data['q5_city_decision_1']}</span></td>
  <td colspan="1">State: <span class="cls_response_4"> {$final_data['q5_state_decision_1']}</span></td>
  <td colspan="1">Zip: <span class="cls_response_4"> {$final_data['q5_zip_decision_1']}</span></td>  
  <td colspan="2">Phone: <span class="cls_response_4"> {$final_data['q5_phone_decision_1']}</span></td>
 </tr> 
</table>
<div class="cls_grey_bkgd"><span>Decision maker #2</span></div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
  <tr>
  <td colspan="3">Signature:</td>
  <td colspan="3">Date:</td>
 </tr>
  <tr>
  <td colspan="3">Name: <span class="cls_response_4"> {$final_data['q5_name_decision_2']}</span></td>
  <td colspan="3">Relationship: <span class="cls_response_4"> {$final_data['q5_relationship_decision_2']}</span></td>
 </tr>
 <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['q5_address_decision_2']}</span></td>
 </tr>
 <tr>
  <td colspan="2">City: <span class="cls_response_4"> {$final_data['q5_city_decision_2']}</span></td>
  <td colspan="1">State: <span class="cls_response_4"> {$final_data['q5_state_decision_2']}</span></td>
  <td colspan="1">Zip: <span class="cls_response_4"> {$final_data['q5_zip_decision_2']}</span></td>  
  <td colspan="2">Phone: <span class="cls_response_4"> {$final_data['q5_phone_decision_2']}</span></td>
 </tr> 
</table>
<div class="cls_grey_bkgd"><span>Decision maker #3</span></div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
  <tr>
  <td colspan="3">Signature:</td>
  <td colspan="3">Date:</td>
 </tr>
   <tr>
  <td colspan="3">Name: <span class="cls_response_4"> {$final_data['q5_name_decision_3']}</span></td>
  <td colspan="3">Relationship: <span class="cls_response_4"> {$final_data['q5_relationship_decision_3']}</span></td>
 </tr>
 <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['q5_address_decision_3']}</span></td>
 </tr>
 <tr>
  <td colspan="2">City: <span class="cls_response_4"> {$final_data['q5_city_decision_3']}</span></td>
  <td colspan="1">State: <span class="cls_response_4"> {$final_data['q5_state_decision_3']}</span></td>
  <td colspan="1">Zip: <span class="cls_response_4"> {$final_data['q5_zip_decision_3']}</span></td>  
  <td colspan="2">Phone: <span class="cls_response_4"> {$final_data['q5_phone_decision_3']}</span></td>
 </tr> 
</table>
<br>
<div class="cls_question">I want my proxy to make health decisions for me:</div>
</body>
EOF;

        return $html;
    }


    public static function makeTableOne($final_data) {
        global $module;

        $dd = REDCap::getDataDictionary($module->getProjectId(), 'array');

        $q7_decoded = $final_data['q7_cpr'] == 1 ? 'Refuse' : 'Accept';
        $q7_breathing = $final_data['q7_breathing'] == 1 ? 'Refuse' : 'Accept';
        $q7_dialyses = $final_data['q7_dialyses'] == 1 ? 'Refuse' : 'Accept';
        $q7_transfusions = $final_data['q7_transfusions'] == 1 ? 'Refuse' : 'Accept';
        $q7_food = $final_data['q7_food'] == 1 ? 'Refuse' : 'Accept';

$tbl1 =
    <<<EOD
<div style="color: #962b28;"><b>If I become ill and require artificial support, here is what I want:</b></div>
<table class='care_choices' cellspacing="0" cellpadding="1" border="1">
    <tr>
        <th style="width: 60%;">Treatment</th>
        <th style="width: 10%;">Refuse/Accept</th>
        <th style="width: 30%;">Specific Instructions<br>(example: for how long)</th>
    </tr>
    <tr>
        <th class='shazam'> {$dd['q7_cpr']['field_label']}</th>
        <td class='shazam'> {$q7_decoded}</td>
        <td class='shazam'> {$final_data['q7_cpr_inst']}</td>
    </tr>
    <tr>
        <th class='shazam'>  {$dd['q7_breathing']['field_label']}</th>
        <td class='shazam'> {$q7_breathing}</td>
        <td class='shazam'> {$final_data['q7_breathing_inst']}</td>
    </tr>
    <tr>
        <!-- This will map the LABEL to the field nf_grants field -->
        <th class='shazam'> {$dd['q7_dialyses']['field_label']}</th>
        <td class='shazam'> {$q7_dialyses}</td>
        <td class='shazam'> {$final_data['q7_dialyses_inst']}</td>
    </tr>
    <tr>
        <th class='shazam'> {$dd['q7_transfusions']['field_label']}</th>
        <td class='shazam'> {$q7_transfusions}</td>
        <td class='shazam'> {$final_data['q7_transfusions_inst']}</td>
    </tr>
    <tr>
        <th class='shazam'> {$dd['q7_food']['field_label']}</th>
        <td class='shazam'> {$q7_food}</td>
        <td class='shazam'> {$final_data['q7_food_inst']}</td>
    </tr>
</table>

EOD;

return $tbl1;

    }


    public static function makeTableNaturalDeath($final_data) {
        global $module;


        $dd = REDCap::getDataDictionary($module->getProjectId(), 'array');

        $q8_unconscious_label = nl2br($dd['q8_unconscious']['field_label']);
        $q8_confused_label = nl2br($dd['q8_confused']['field_label']);
        $q8_living_label = nl2br($dd['q8_living']['field_label']);
        $q8_illness_label = nl2br($dd['q8_illness']['field_label']);

        $q8_unconscious = $final_data['q8_unconscious'] == 1 ? 'Yes' : 'No';
        $q8_confused = $final_data['q8_confused'] == 1 ? 'Yes' : 'No';
        $q8_living = $final_data['q8_living'] == 1 ? 'Yes' : 'No';
        $q8_illness = $final_data['q8_illness'] == 1 ? 'Yes' : 'No';

$tbl1 =
    <<<EOD
<div style="color: #962b28;"><b>Allow natural death to happen (do not connect me to machines or disconnect me from machines)</b></div>
<table class='natural_death' cellspacing="0" cellpadding="1" border="1"  style="width: 100%;">
    <colgroup>
       <col span="1" style="width: 60%;">
       <col span="1" style="width: 10%;">
       <col span="1" style="width: 30%;">
    </colgroup>
    <tr>
        <th style="width: 60%;">When I become</th>
        <th style="width: 10%;"></th>
        <th style="width: 30%;">Special Instructions</th>
    </tr>
    <tr>
        <th class='shazam' > {$q8_unconscious_label}</th>
        <td class='shazam'> {$q8_unconscious}</td>
        <td class='shazam'> {$final_data['q8_unconscious_inst']}</td>
    </tr>
    <tr>
        <th class='shazam'>{$q8_confused_label}</th>
        <td class='shazam'> {$q8_confused}</td>
        <td class='shazam'> {$final_data['q8_confused_inst']}</td>
    </tr>
    <tr>
        <th class='shazam'> {$q8_living_label}</th>
        <td class='shazam'> {$q8_living}</td>
        <td class='shazam'> {$final_data['q8_living_inst']}</td>
    </tr>
    <tr>
        <th class='shazam'> {$q8_illness_label}</th>
        <td class='shazam'> {$q8_illness}</td>
        <td class='shazam'> {$final_data['q8_illness_inst']}</td>
    </tr>
</table>

EOD;

return $tbl1;

    }




    public static function makeHTMLPage3($record_id, $final_data, $pdf) {
        global $module;

        $dd = REDCap::getDataDictionary($module->getProjectId(), 'array');

        $html = <<<EOF
<head>
<style>
    .cls_section {background-color:#e1e1e1;color:#962b28;font-size:18pt; font-weight:bold}
    .cls_question {font-weight:bold }
    .cls_example {font-style:italic }
    .cls_red_header {   
    color: #962b28;
    font-size: large;
    }
    .cls_response {
      border-bottom: 1px solid black;
      min-width: 100px;
    }
.cls_response_2 {
text-decoration: underline; 
white-space: pre;
}
.cls_response_4 {
color:#1b1fff;
}
    .cls_grey_bkgd {background-color:#e1e1e1;font-weight:bold}
</style>
</head>
<body>
<div class="cls_section">
<span> Part 3: Please Write Down Your Care Choices</span>
</div>
</body>
EOF;

        $pdf->writeHTML($html, true, false, true, false, '');

        $tbl2 = self::makeTableOne($final_data);
        $pdf->writeHTML($tbl2, true, false, false, false, '');

        //$pdf->Cell(35,5,'<span class="cls_red_header">Please allow natural death</span>');
        $pdf->writeHTMLCell(35, 5, '', '', '<font color=red">Please allow natural death</font>');


        $pdf->Ln(6);
        $tbl3 = self::makeTableNaturalDeath($final_data);
        $pdf->writeHTML($tbl3, true, false, false, false, '');

return $pdf;
    }


    public static function makeHTMLPage4($record_id, $final_data, $pdf) {
        global $module;

        $pdf->writeHTMLCell(185, 5, '', '', '<b>Here is what I DO WANT at the <u>end of my life (in the last six months of life)</u>:</b>');
        $pdf->ln(10);

        $pdf->CheckBox('q9', 5, $final_data['q9___1'] == 1, array(), array());
        $pdf->Cell(70, 5, 'I want to be pain free');
        $pdf->ln(8);
        $pdf->CheckBox('q9', 5,  $final_data['q9___2'] == 1, array(), array());
        $pdf->Cell(70, 5, 'I want you to allow me to die gently and naturally');
        $pdf->ln(8);
        $pdf->CheckBox('q9', 5, $final_data['q9___3'] == 1, array(), array());
        $pdf->Cell(70, 5, 'I want hospice care');
        $pdf->ln(8);
        $pdf->CheckBox('q9', 5, $final_data['q9___99'] == 1, array(), array());

        $pdf->Cell(70, 5, 'Other: Please use the space below to give detailed instructions to your doctors');
        $pdf->ln(8);

        $pdf->Cell(5,5,'');
        $pdf->TextField('q9_99_other', 150, 18, array('multiline'=>true, 'lineWidth'=>0, 'borderStyle'=>'none'), array('v'=>$final_data['q9_99_other']));
        $pdf->Ln(19);


        $pdf->ln(10);
        $pdf->writeHTMLCell(185, 5, '', '', '<b>Here is where I want to spend the last days of my life:</b>');

        $pdf->Ln(10);
        $pdf->RadioButton('q10', 5, array(), array(), '1', $final_data['q10'] == 1 ? true : false);
        $pdf->Cell(70, 5, 'In the hospital');
        $pdf->Ln(6);
        $pdf->RadioButton('q10', 5, array(), array(), '2', $final_data['q10'] == 2 ? true : false);
        $pdf->Cell(70, 5, 'At home or in a home-like setting');
        $pdf->Ln(6);

        $pdf->Ln(10);
        $pdf->writeHTMLCell(185, 5, '', '', '<b>If my pain and distress are difficult to control, please sedate me (make with sleep with sleep medicines) even if this means that I may not live as long</b>');
        $pdf->Ln(6);

        $pdf->Ln(10);
        $pdf->RadioButton('q11', 5, array(), array(), '1',  $final_data['q11'] === '1' ? true : false);
        $pdf->Cell(70, 5, 'Yes');
        $pdf->Ln(6);
        $pdf->RadioButton('q11', 5, array(), array(), '0', $final_data['q11'] === '0' ? true : false);
        $pdf->Cell(70, 5, 'No');
        $pdf->Ln(6);

        $pdf->Ln(10);
        $pdf->writeHTMLCell(185, 5, '', '', '<b>Here is what I want to do when my family wants you to do something different than what I want for myself:</b>');
        $pdf->Ln(6);

        $pdf->Ln(10);
        $pdf->RadioButton('q12', 5, array(), array(), '1',  $final_data['q12'] == 1 ? true : false);
        $pdf->Cell(70, 5, 'I am asking you to show them this letter and guide my family to follow my wishes.');
        $pdf->Ln(10);
        $pdf->RadioButton('q11', 5, array(), array(), '2',  $final_data['q12'] == 2 ? true : false);
        $pdf->Cell(70, 5, 'I want you to override my wishes as my family knows best.');
        $pdf->Ln(6);

        $pdf->AddPage();

        $pdf->Ln(10);
        $pdf->writeHTMLCell(185, 5, '', '', '<b>After a person passes away, their organs and tissues (eyes, kidneys, liver, heart, skin etc.) can be donated to help other people who are ill.</b>');
        $pdf->Ln(6);

        $pdf->Ln(10);
        $pdf->RadioButton('q13', 5, array(), array(), '1',  $final_data['q13'] == 1 ? true : false);
        $pdf->Cell(70, 5, 'I do NOT want to donate my organs or tissues after I pass away');
        $pdf->Ln(10);
        $pdf->RadioButton('q13', 5, array(), array(), '2',  $final_data['q13'] == 2 ? true : false);
        $pdf->Cell(70, 5, ' I do NOT want to decide now. my proxy can decide later.');
        $pdf->Ln(10);
        $pdf->RadioButton('q13', 5, array(), array(), '3',  $final_data['q13'] == 3 ? true : false);
        $pdf->Cell(70, 5, 'I will donate any of my organs and tissues after I pass away');
        $pdf->Ln(10);
        $pdf->RadioButton('q13', 5, array(), array(), '4',  $final_data['q13'] == 4 ? true : false);
        $pdf->Cell(70, 5, 'I will donate the following organs, tissues only:');
        $pdf->Ln(10);

        $pdf->Cell(5,5,'');
        $pdf->TextField('q9_99_other', 150, 18, array('multiline'=>true, 'lineWidth'=>0, 'borderStyle'=>'none'), array('v'=> $final_data['q13_donate_following']));
        $pdf->Ln(19);

        $pdf->Ln(10);
        $pdf->writeHTMLCell(185, 5, '', '', '<b>Please check below to give permission:</b>');

        $pdf->Ln(10);
        $pdf->CheckBox('q14', 5, $final_data['q14___1'] == 1, array(), array());
        $pdf->Cell(70, 5, 'My proxy can make funeral arrangements when needed');
        $pdf->Ln(10);

        $pdf->Ln(10);
        $pdf->writeHTMLCell(185, 5, '', '', '<b>Please write other detailed instructions (attach extra pages if you need).</b>');
        $pdf->Ln(10);

        $pdf->TextField('q9_99_other', 150, 45, array('multiline'=>true, 'lineWidth'=>0, 'borderStyle'=>'none'), array('v'=>$final_data['q15']));
        $pdf->Ln(19);

        return $pdf;

    }

    public static function makeHTMLPage5($record_id, $final_data, $patient_sigfile_path, $adult_sigfile_path) {
        global $module;

        $html = <<<EOF
<head>
<style>
    .cls_section {background-color:#e1e1e1;color:#962b28;font-size:18pt; font-weight:bold}
    .cls_question {font-weight:bold }
    .cls_example {font-style:italic }
    .cls_response {
      border-bottom: 1px solid black;
      min-width: 100px;
    }
.cls_response_2 {
text-decoration: underline; 
white-space: pre;
}
.cls_response_4 {
color:#1b1fff;
}
.cls_grey_bkgd {background-color:#e1e1e1;font-weight:bold}
</style>
</head>
<body>
<div class="cls_section">
<span> Part 4: Sign the Form and have two witnesses co-sign</span></div>

<div class="">I cancel any prior Power of Attorney for Health Care or Natural Death Act Declaration. My proxy and others may use copies of this document as though they were originals.</div>
<div class="cls_question">Sign your name and write the date:</div><br>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
<tr>
    <td colspan="6">Sign your name: <img src="{$patient_sigfile_path}" alt="signature" height="36" ></td>
</tr>
<br>
<tr>
  <td colspan="3" >Print your name: <span class="cls_response_4"> {$final_data['patient_name']}</span></td>
  <td colspan="3">Date: <span class="cls_response_4"> {$final_data['patient_signdate']}</span></td>  
 </tr>
 <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['patient_address']}</span></td>
 </tr>
 <tr>
  <td colspan="2">City: <span class="cls_response_4"> {$final_data['patient_city']}</span></td>
  <td colspan="2">State: <span class="cls_response_4"> {$final_data['patient_state']}</span></td>
  <td colspan="2">Zip: <span class="cls_response_4"> {$final_data['patient_zip']}</span></td>
 </tr> 
</table>
<br>
<br>
<div class="cls_grey_bkgd pt-lg-5"><span>NOTE: If you are unable to sign, but ARE able to talk about what matters most for your
 health care an adult may sign your name with you present, asking them to sign for you
 </span></div>
 <br>
 <div>Name and signature of adult signing my name in my presence and at my direction:</div>
 <br>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
<tr>
    <td colspan="6">Signature: <img src="{$adult_sigfile_path}" alt="signature" height="36" ></td>
</tr>
<br>
<tr>
  <td colspan="3" >Name: <span class="cls_response_4"> {$final_data['signature_adult']}</span></td>
  <td colspan="3">Date: <span class="cls_response_4"> {$final_data['adult_signdate']}</span></td>  
 </tr>
</table>
</body>
EOF;

        return $html;
    }


    public static function makeHTMLPage6($record_id, $final_data, $witness1_sigfile_path,$witness2_sigfile_path) {

        $html = <<<EOF
<head>
<style>
    .cls_section {background-color:#e1e1e1;color:#962b28;font-size:18pt; font-weight:bold}
    .cls_question {font-weight:bold }
    .cls_example {font-style:italic }
    .cls_response {
      border-bottom: 1px solid black;
      min-width: 100px;
    }
.cls_response_2 {
text-decoration: underline; 
white-space: pre;
}
.cls_response_4 {
color:#1b1fff;
}
.cls_grey_bkgd {background-color:#e1e1e1;font-weight:bold}
</style>
</head>
<body>
<div class="cls_question">Have your witnesses sign their names and write the date:</div><br>
<div class="cls_grey_bkgd pt-lg-5"><span>Statement of Witnesses:
 </span></div>
 <br>
 <div class="">By signing, I promise that ____________________________________ signed this form.</div>
 <div class="pt-1">
 I am 18 years of age or older and I promise that:
<ul>
  <li>I know this person or they could prove who they were</li>
    <li>This person was thinking clearly and was not forced to sign this document.</li>
  <li>I am not their medical decision maker</li>
  <li>I am not providing health care for this person</li>
  <li>I do not work for this person’s health care provider</li>
  <li>I do not work for where they live (e.g. their nursing home if applicable)</li>
  </ul>
</div>
<div class="cls_question">Witness #1</div><br>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
<tr>
    <td colspan="6">Signature: <img src="{$witness1_sigfile_path}" alt="signature" height="36" ></td>
</tr>
<br>
<tr>
  <td colspan="3" >Print your name: <span class="cls_response_4"> {$final_data['witness1_name']}</span></td>
  <td colspan="3">Date: <span class="cls_response_4"> {$final_data['witness1_signdate']}</span></td>  
 </tr>
 <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['witness1_address']}</span></td>
 </tr>
 <tr>
  <td colspan="2">City: <span class="cls_response_4"> {$final_data['witness1_city']}</span></td>
  <td colspan="2">State: <span class="cls_response_4"> {$final_data['witness1_state']}</span></td>
  <td colspan="2">Zip: <span class="cls_response_4"> {$final_data['witness1_zip']}</span></td>
 </tr> 
</table>
<br>
<div class="cls_question">Witness #2</div><br>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
<tr>
    <td colspan="6">Signature: <img src="{$witness2_sigfile_path}" alt="signature" height="36" ></td>
</tr>
<br>
<tr>
  <td colspan="3" >Print your name: <span class="cls_response_4"> {$final_data['witness2_name']}</span></td>
  <td colspan="3">Date: <span class="cls_response_4"> {$final_data['witness2_signdate']}</span></td>  
 </tr>
  <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['witness2_address']}</span></td>
 </tr>
 <tr>
  <td colspan="2">City: <span class="cls_response_4"> {$final_data['witness2_city']}</span></td>
  <td colspan="2">State: <span class="cls_response_4"> {$final_data['witness2_state']}</span></td>
  <td colspan="2">Zip: <span class="cls_response_4"> {$final_data['witness2_zip']}</span></td>
 </tr> 
</table>

</body>
EOF;
        return $html;
    }

  public static function makeHTMLPage7($record_id, $final_data, $declaration_sigfile_path, $specialwitness_sigfile_path) {


      $html = <<<EOF
<head>
<style>
    .cls_section {background-color:#e1e1e1;color:#962b28;font-size:18pt; font-weight:bold}
    .cls_question {font-weight:bold }
    .cls_example {font-style:italic }
    .cls_response {
      border-bottom: 1px solid black;
      min-width: 100px;
    }
.cls_response_2 {
text-decoration: underline; 
white-space: pre;
}
.cls_response_4 {
color:#1b1fff;
}
    .cls_grey_bkgd {background-color:#e1e1e1;font-weight:bold}
</style>
</head>
<body>

<div class="cls_grey_bkgd pt-lg-5"><span>At least one of the above witnesses must also sign the following declaration.
 </span></div>
 <br>
 <div class="pt-1">I also promise I am not related to the person signing this What Matters Most letter directive by blood, marriage, or adoption, and to the best of my knowledge, I am not entitled to any of their money or property after they die.</div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
 <tr>
    <td colspan="6">Signature: <img src="{$declaration_sigfile_path}" alt="signature" height="36" ></td>
</tr>
<br>
 <tr>
  <td colspan="6">Print your name: </td>
 </tr>
 </table> 
 <br>
 <div class="cls_grey_bkgd pt-lg-5"><span>Skilled Nursing Facility -- Special Witness Requirement:
 </span></div>
<div class="pt-1">I further declare under penalty of perjury under the laws of the State of California that I am a patient advocate or ombudsman as designated by the State Department of Aging and am serving as a witness as required by Probate Code 4675.</div><br>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
<tr>
    <td colspan="6">Signature: <img src="{$specialwitness_sigfile_path}" alt="signature" height="36" ></td>
</tr>
<br>
<tr>
  <td colspan="3" >Name: <span class="cls_response_4"> {$final_data['specialwitness_name']}</span></td>
  <td colspan="3">Date: <span class="cls_response_4"> {$final_data['specialwitness_signdate']}</span></td>  
 </tr>
<tr>
    <td colspan="6" >Title: <span class="cls_response_4"> {$final_data['specialwitness_title']}</span></td>
 </tr>
 <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['specialwitness_address']}</span></td>
 </tr>
 <tr>
  <td colspan="2">City: <span class="cls_response_4"> {$final_data['specialwitness_city']}</span></td>
  <td colspan="2">State: <span class="cls_response_4"> {$final_data['specialwitness_state']}</span></td>
  <td colspan="2">Zip: <span class="cls_response_4"> {$final_data['specialwitness_zip']}</span></td>
 </tr>  
 <br>
 <tr>
  <td colspan="4">State of California County of <span class="cls_response_4"> {$final_data['county']} </span></td>
</tr>
</table>

</body>
EOF;

      return $html;
    }

    public function decodeRefuse($coded) {
        return ($coded == 1 ? 'Refuse' : 'Accept');

    }

}


?>