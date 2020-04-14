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
        //$img = $module->getUrl('images/shc_barcode.png');  //there is a bug in tcpdf that using png only prints images in first couple of page
        $img = $module->getUrl('images/shc_barcode.jpg');

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
        $this->Image($img, 135, 8, '', 9, '', '', 'T', false, 300, '', false, false, false, false, false, false);

        $this->SetXY(105, 17);
        $this->Cell(90, 4, 'ADVANCE DIRECTIVE:', 'L', 0, 'C', 0, '', 0, false, 'T', 'C');
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
        $css = file_get_contents($module->getModulePath() . 'css/letter_project_pdf.css');


        //$module->emDebug("DOCTOR name is $doctor_name", nl2br($final_data['q1']));
        $q1 = nl2br($final_data['q1']);
        $q3 = nl2br($final_data['q3']);
        //create some HTML content
        $html = <<<EOF

<head>
<style>{$css}</style>
</head>
<body>
<div class="cls_section"><span>Part 1: Tell Us about What Matters Most to You</span></div>
<div class="cls_question"><span>Dear Doctor {$doctor_name},</span></div>
<div class="cls_question">RE: What matters most to me at the end of my life</div>
<div>I have been reading and thinking about end-of-life issues lately. I realize how important it is that I communicate my wishes to you and my family. I know that you are very busy. You may find it awkward to talk to me about my end-of-life wishes or you may feel that it is too early for me to have this conversation. So I am writing this letter to clarify what matters most to me.</div>

<div class="cls_question">Here is what matters most to me:<br>
<span style="font-weight:normal"><i>Examples: Being at home, doing gardening, traveling, going to church, playing with my grandchildren</i></span>
</div>
<div class="cls_response_4"><span class="cls_013"> {$q1}</span></div>

<div class="cls_question">Here are my important future life milestones:
<span style="font-weight:normal"><br><i>Examples: my 10th wedding anniversary, buying a home, birth of my granddaughter</i></span>
</div>
<div class="cls_response_4"><span>1. {$final_data['q2_milestone_1']} </span></div>
<div class="cls_response_4"><span>2. {$final_data['q2_milestone_2']}</span></div>
<div class="cls_response_4"><span>3. {$final_data['q2_milestone_3']}</span></div>
<div class="cls_response_4"><span>4. {$final_data['q2_milestone_4']}</span></div>
<div class="cls_question">Here is how we prefer to handle bad news in my family:
<span style="font-weight:normal"><br><i>Examples: We talk openly about it, we shield the children from it, we do not like to talk about it, we do not tell the patient</i></span>
</div>
<div class="cls_response_4">{$q3}</span></div>
</body>
EOF;

        return $html;

    }

    public static function makeHTMLPage2($record_id, $final_data) {
        global $module;
        //preserve the line feeds
        $q4 = nl2br($final_data['q4']);

        $css = file_get_contents($module->getModulePath() . 'css/letter_project_pdf.css');



        $html = <<<EOF
<head>
<style>{$css}</style>
</head>
<body>
<div class="cls_section">
<span> Part 2: Who Makes Decisions for You when You Cannot</span></div>
<div class="cls_question">Here is how we make medical decisions in our family:
<span style="font-weight:normal"><br><i>Examples: I make the decision myself, my entire family has to agree on major decisions about me, my daughter who is a nurse makes the decisions etc.</i></span>
</div>
<div class="cls_response_4"><span> {$q4} </span></div>
<div class="cls_question"><span>Here is who I want making medical decisions for me when I am not able to make my own decision:</span></div>
<div class="cls_grey_bkgd"><span>Decision maker #1</span></div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
  <tr>
  <td colspan="4">Name: <span class="cls_response_4"> {$final_data['q5_name_decision_1']}</span></td>
  <td colspan="2">Relationship: <span class="cls_response_4"> {$final_data['q5_relationship_decision_1']}</span></td>
 </tr>
 <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['q5_address_decision_1']}</span></td>
 </tr>
 <tr>
  <td colspan="3">City: <span class="cls_response_4"> {$final_data['q5_city_decision_1']}</span></td>
  <td colspan="1">State: <span class="cls_response_4"> {$final_data['q5_state_decision_1']}</span></td>
  <td colspan="1">Zip: <span class="cls_response_4"> {$final_data['q5_zip_decision_1']}</span></td> 
  </tr>
  <tr>
  <td colspan="4">Phone number: <span class="cls_response_4"> {$final_data['q5_phone_decision_1']}</span></td>
 </tr> 
</table>
<div class="cls_grey_bkgd"><span>Decision maker #2</span></div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
  <tr>
  <td colspan="4">Name: <span class="cls_response_4"> {$final_data['q5_name_decision_2']}</span></td>
  <td colspan="2">Relationship: <span class="cls_response_4"> {$final_data['q5_relationship_decision_2']}</span></td>
 </tr>
 <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['q5_address_decision_2']}</span></td>
 </tr>
 <tr>
  <td colspan="3">City: <span class="cls_response_4"> {$final_data['q5_city_decision_2']}</span></td>
  <td colspan="1">State: <span class="cls_response_4"> {$final_data['q5_state_decision_2']}</span></td>
  <td colspan="1">Zip: <span class="cls_response_4"> {$final_data['q5_zip_decision_2']}</span></td>
  </tr>
  <tr>  
  <td colspan="4">Phone: <span class="cls_response_4"> {$final_data['q5_phone_decision_2']}</span></td>
 </tr> 
</table>
<div class="cls_grey_bkgd"><span>Decision maker #3</span></div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
   <tr>
  <td colspan="4">Name: <span class="cls_response_4"> {$final_data['q5_name_decision_3']}</span></td>
  <td colspan="2">Relationship: <span class="cls_response_4"> {$final_data['q5_relationship_decision_3']}</span></td>
 </tr>
 <tr>
  <td colspan="6">Address: <span class="cls_response_4"> {$final_data['q5_address_decision_3']}</span></td>
 </tr>
 <tr>
  <td colspan="3">City: <span class="cls_response_4"> {$final_data['q5_city_decision_3']}</span></td>
  <td colspan="1">State: <span class="cls_response_4"> {$final_data['q5_state_decision_3']}</span></td>
  <td colspan="1">Zip: <span class="cls_response_4"> {$final_data['q5_zip_decision_3']}</span></td>
  </tr>
  <tr>
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
        // {$dd['q7_cpr']['field_label']}  //did not use because need to bold main and remove link formatting

        $q7_cpr_label = "<b>CPR (Cardiopulmonary Resuscitation)</b>: Using electric shocks, chest compressions and a breathing tube to try to make the heart beat again and restore breathing after it has stopped";  //{$dd['q7_cpr']['field_label']}
        $q7_cpr_inst  = nl2br($final_data['q7_cpr_inst']);

        $q7_breathing_label = "<b>Breathing machine support (ventilator)</b>";
        $q7_breathing_inst = nl2br($final_data['q7_breathing_inst']);

        $q7_dialyses_label = "<b>Kidney dialyses</b>"; //{$dd['q7_dialyses']['field_label']}
        $q7_dialyses_inst = nl2br($final_data['q7_dialyses_inst']);

        $q7_transfusions_label = "<b>Blood transfusions</b>";  //{$dd['q7_transfusions']['field_label']}
        $q7_transfusions_inst = nl2br($final_data['q7_transfusions_inst']);

        $q7_food_label = "<b>Artificial food and fluids</b> placed directly into my vein or stomach to give me liquid food.";  //{$dd['q7_food']['field_label']}
        $q7_food_inst = nl2br($final_data['q7_food_inst']);

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
        <th style="width: 10%;">Refuse/<br>Accept</th>
        <th style="width: 30%;">Specific Instructions<br>(example: for how long)</th>
    </tr>
    <tr>
        <th><span>$q7_cpr_label</span></th>
        <td>{$q7_decoded}</td>
        <td>{$q7_cpr_inst}</td>
    </tr>
    <tr>
        <th>$q7_breathing_label</th>
        <td>{$q7_breathing}</td>
        <td>{$q7_breathing_inst}</td>
    </tr>
    <tr>
        <!-- This will map the LABEL to the field nf_grants field -->
        <th>$q7_dialyses_label</th>
        <td>{$q7_dialyses}</td>
        <td>{$q7_dialyses_inst}</td>
    </tr>
    <tr>
        <th>$q7_transfusions_label</th>
        <td>{$q7_transfusions}</td>
        <td>{$q7_transfusions_inst}</td>
    </tr>
    <tr>
        <th>$q7_food_label</th>
        <td>{$q7_food}</td>
        <td>{$q7_food_inst}</td>
    </tr>
</table>

EOD;

return $tbl1;

    }

    public static function makeTableNaturalDeath($final_data) {
        global $module;


        $dd = REDCap::getDataDictionary($module->getProjectId(), 'array');

        $q8_unconscious_label = nl2br($dd['q8_unconscious']['field_label']);
        $q8_unconscious_inst  = nl2br($final_data['q8_unconscious_inst']);

        $q8_confused_label = "<u>Permanently Confused:</u><ul><li>I cannot and will not be able to recognize my loved ones.</li><li>I am not able to make any health decisions</li></ul>"; //nl2br($dd['q8_confused']['field_label']);
        $q8_confused_inst = nl2br($final_data['q8_confused_inst']);

        $q8_living_label = nl2br($dd['q8_living']['field_label']);
        $q8_living_inst  = nl2br($final_data['q8_living_inst']);

        $q8_illness_label = nl2br($dd['q8_illness']['field_label']);
        $q8_illness_inst  = nl2br($final_data['q8_illness_inst']);

        $q8_unconscious = $final_data['q8_unconscious'] == 1 ? 'Yes' : 'No';
        $q8_confused = $final_data['q8_confused'] == 1 ? 'Yes' : 'No';
        $q8_living = $final_data['q8_living'] == 1 ? 'Yes' : 'No';
        $q8_illness = $final_data['q8_illness'] == 1 ? 'Yes' : 'No';

$tbl1 =
    <<<EOD
<div style="color: #962b28;"><b>Please allow natural death when: </b></div>
<table class='natural_death' cellspacing="0" cellpadding="1" border="1"  style="width: 100%;">
    <tr>
        <td width="40%" colspan="2"><span style="color: #962b28; font-weight: bold">When I become</span></td>
        <td width="60%" colspan="2"><b><span style="color: #962b28;">Allow natural death to happen</span> (do not connect me to machines or disconnect me from machines)</b></td>
    </tr>
    <tr>
        <th width="40%"></th>
        <th width="20%"></th>
        <th width="40%">Specific Instructions</th>
    </tr>    
    <tr>
        <th>{$q8_unconscious_label}</th>
        <td>{$q8_unconscious}</td>
        <td>{$q8_unconscious_inst}</td>
    </tr>
    <tr>
        <th>{$q8_confused_label}</th>
        <td>{$q8_confused}</td>
        <td>{$q8_confused_inst}</td>
    </tr>
    <tr>
        <th>{$q8_living_label}</th>
        <td>{$q8_living}</td>
        <td>{$q8_living_inst}</td>
    </tr>
    <tr>
        <th>{$q8_illness_label}</th>
        <td>{$q8_illness}</td>
        <td>{$q8_illness_inst}</td>
    </tr>
</table>

EOD;

return $tbl1;

    }

    public static function makeHTMLPage3($record_id, $final_data, $pdf) {
        global $module;

        $css = file_get_contents($module->getModulePath() . 'css/letter_project_pdf.css');
        $html = <<<EOF
<head>
<style>{$css}</style>
</head>
<body>
<div class="cls_section">
<span> Part 3: Please Write Down Your Care Choices</span>
</div>
</body>
EOF;

        $pdf->Ln(10);
        $pdf->writeHTML($html, true, false, true, false, '');

        $tbl2 = self::makeTableOne($final_data);
        $pdf->writeHTML($tbl2, true, false, false, false, '');

return $pdf;
    }

    public static function makeHTMLPage3Part2($record_id, $final_data, $pdf) {
        global $module;

        //$pdf->Cell(35,5,'<span class="cls_red_header">Please allow natural death</span>');

        $pdf->Ln(10);
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
//1, I will donate any of my organs and tissues | 2, I will donate the following organs, tissues only | 3, I do NOT want to donate my organs or tissues | 4, I do NOT want to decide now. My agent can decide later.
        $pdf->Ln(10);
        $pdf->RadioButton('q13', 5, array(), array(), '1',  $final_data['q13'] == 1 ? true : false);
        $pdf->Cell(70, 5, 'I will donate any of my organs and tissues after I pass away');
        $pdf->Ln(10);
        $pdf->RadioButton('q13', 5, array(), array(), '2',  $final_data['q13'] == 2 ? true : false);
        $pdf->Cell(70, 5, 'I will donate the following organs, tissues only:');
        $pdf->Ln(10);
        $pdf->Cell(5,5,'');
        $pdf->TextField('q13_donate_following', 150, 18, array('multiline'=>true, 'lineWidth'=>0, 'borderStyle'=>'none'), array('v'=> $final_data['q13_donate_following']));
        $pdf->Ln(19);

        $pdf->Ln(10);
        $pdf->RadioButton('q13', 5, array(), array(), '3',  $final_data['q13'] == 3 ? true : false);
        $pdf->Cell(70, 5, 'I do NOT want to donate my organs or tissues after I pass away');
        $pdf->Ln(10);
        $pdf->RadioButton('q13', 5, array(), array(), '4',  $final_data['q13'] == 4 ? true : false);
        $pdf->Cell(70, 5, ' I do NOT want to decide now. My agent can decide later.');



        $pdf->Ln(20);
        $pdf->writeHTMLCell(185, 5, '', '', '<b>Please check below to give permission:</b>');

        $pdf->Ln(10);
        $pdf->CheckBox('q14', 5, $final_data['q14___1'] == 1, array(), array());
        $pdf->Cell(70, 5, 'My agent can make funeral arrangements when needed');
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

        $css = file_get_contents($module->getModulePath() . 'css/letter_project_pdf.css');

        $html = <<<EOF
<head>
<style>{$css}</style>
</head>
<body>
<div class="cls_section">
<span> Part 4: Sign the Form and have two witnesses co-sign</span></div>

<div class="">I cancel any prior Power of Attorney for Health Care or Natural Death Act Declaration. My health care agent and others may use copies of this document as though they were originals.</div>
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
        global $module;
        $css = file_get_contents($module->getModulePath() . 'css/letter_project_pdf.css');
        $html = <<<EOF
<head>
<style>{$css}</style>
</head>
<body>
<div class="cls_question">Have your witnesses sign their names and write the date:</div><br>
<div class="cls_grey_bkgd pt-lg-5"><span>Statement of Witnesses:
 </span></div>
 <br>
 <div class="">By signing, I promise that {$final_data['ltr_name']} signed this form.</div>
 <div class="pt-1">
 I am 18 years of age or older and I promise that:
<ul>
  <li>I know this person or their identity has been proved to me with convincing evidence</li>
  <li>This person was thinking clearly and was not forced to sign this document while in my presence</li>
  <li>I am not their agent</li>
  <li>I am not providing health care for this person</li>
  <li>I do not work for this person&#39;s health care provider</li>
  <li>I do not work for the facility or the institution where they live (e.g. their nursing home if applicable)</li>
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
        global $module;
        $css = file_get_contents($module->getModulePath() . 'css/letter_project_pdf.css');

      $html = <<<EOF
<head>
<style>{$css}</style>
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