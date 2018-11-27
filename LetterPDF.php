<?php

namespace Stanford\LetterProject;

/** @var \Stanford\LetterProject\LetterProject $module */

require __DIR__ . '/vendor/autoload.php';

use TCPDF;
use REDCap;

require_once('tcpdf_include.php');

class LetterPDF extends TCPDF
{
    public function Header2()
    {
        $this->SetFont('arial', '', 14);
        $title = utf8_encode('title');
        $subtitle = utf8_encode('sub title');
        $this->SetHeaderMargin(40);
        $this->Line(15, 23, 405, 23);
    }

    public function Footer()
    {
        $this->SetFont('arial', '', 8);
        $this->Cell(0, 5, 'Pag ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }

    public static function makeHTMLPage1($record_id, $final_data)
    {
        global $module;
        $doctor_name = $final_data['ltr_doctor_name'];

        $module->emDebug("DOCTOR name is $doctor_name", nl2br($final_data['q1']), $str);
        $q1 = nl2br($final_data['q1']);
        $q3 = nl2br($final_data['q3']);
        //create some HTML content
        $html = <<<EOF

<head>
<style>
.cls_section {background-color:#e1e1e1;color:#962b28;}
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
<div class="cls_section" style="background-color:#e1e1e1;color:#962b28;">
<span style="font-size:18pt; font-weight:bold">Part 1:</span><span style="font-size:18pt;font-weight:bold"> Tell Us about What Matters Most to You</span></div>
<div class="cls_question"><span>Dear Doctor {$doctor_name},</span></div>
<div style="font-weight:bold" class="cls_016"><span class="cls_016">RE: What matters most to me at the end of my life</span></div>
<div style="" class="cls_013"><span class="cls_013">I realize how important it is that I communicate my wishes to you and my family. I know that you are very busy. You may find it awkward to talk to me about my end-of-life wishes or you may feel that it is too early for me to have this conversation. So I am writing this letter to clarify what matters most to me.</span></div>

<div style="font-weight:bold"><span class="cls_016">Here is what matters most to me:</span></div>
<div class="cls_example">Examples: Being at home, doing gardening, traveling, going to church, playing with my grandchildren grandchildren</div>
<div style="border-bottom: 1px solid black; min-width: 100px;" class="cls_response"><span class="cls_013"> {$q1}</span></div>

<div style="position:absolute;left:54.24px;top:421.20px" class="cls_016"><span class="cls_016">Here are my important future life milestones:</span></div>
<div style="position:absolute;left:54.24px;top:434.88px" class="cls_017"><span class="cls_017">Examples: my 10th wedding anniversary, buying a home, birth of my granddaughter</span></div>
<div style="text-decoration: underline; white-space: pre;" class="cls_019"><span class="cls_019">1. {$final_data['q2_milestone_1']} </span></div>

<div class="cls_response"><span>2.  {$final_data['q2_milestone_2']}</span></div>
<div class="cls_response"><span>3.  {$final_data['q2_milestone_3']}</span></div>
<div class="cls_response_2"><span>4.  {$final_data['q2_milestone_4']}</span></div>
<div style="position:absolute;left:54.24px;top:577.44px" class="cls_016"><span class="cls_016">Here is how we prefer to handle bad news in my family:</span></div>
<div class="cls_example"><span class="cls_example">Examples: We talk openly about it, we shield the children from it, we do not like to talk about it, we do not tell the patient</span></div>
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
<div class="cls_example">Examples: I make the decision myself, my entire family has to agree on major decisions about me, my daughter who is a nurse makes the decisions etc.</div>
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
  <td colspan="2">State Zip:</td>
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
  <td colspan="2">State Zip:</td>
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
  <td colspan="2">State Zip:</td>
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


        //preserve the line feeds
        $q4 = nl2br($final_data['q4']);

        $html = <<<EOF
<head>
<style>
    .cls_section {background-color:#e1e1e1;color:#962b28;font-size:18pt; font-weight:bold}
    .cls_question {font-weight:bold }
    .cls_example {font-style:italic }
    .cls_red_header {   
    color: #962b28;
      size: large}
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

    public function decodeRefuse($coded) {
        return ($coded == 1 ? 'Refuse' : 'Accept');

    }

}


?>