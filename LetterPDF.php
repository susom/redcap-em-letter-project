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
<div style="border-bottom: 1px solid black; min-width: 100px;" class="cls_013"><span class="cls_013"> {$q1}</span></div>

<div style="position:absolute;left:54.24px;top:421.20px" class="cls_016"><span class="cls_016">Here are my important future life milestones:</span></div>
<div style="position:absolute;left:54.24px;top:434.88px" class="cls_017"><span class="cls_017">Examples: my 10th wedding anniversary, buying a home, birth of my granddaughter</span></div>
<div style="text-decoration: underline; white-space: pre;" class="cls_019"><span class="cls_019">1. {$final_data['q2_milestone_1']} </span></div>

<div style="border-bottom: 1px solid black; min-width: 100px;" class="cls_019"><span class="cls_019">2.  {$final_data['q2_milestone_2']}</span></div>
<div style="position:absolute;left:72.24px;top:513.12px" class="cls_019"><span class="cls_019">3.  {$final_data['q2_milestone_3']}</span></div>
<div style="position:absolute;left:72.24px;top:538.32px" class="cls_020"><span class="cls_020">4  {$final_data['q2_milestone_4']}</span></div>
<div style="position:absolute;left:54.24px;top:577.44px" class="cls_016"><span class="cls_016">Here is how we prefer to handle bad news in my family:</span></div>
<div class="cls_017"><span class="cls_017">Examples: We talk openly about it, we shield the children from it, we do not like to talk about it,</span></div>
<div class="cls_017"><span class="cls_017">we do not tell the patient</span></div>
<div class="cls_013"><span class="cls_013">{$q3}</span></div>
</body>
EOF;

        return $html;

    }

    public static function makeHTMLPage2()
    {

        $html = <<<EOF
<head>
<style>
    .cls_section {background-color:#e1e1e1;color:#962b28;font-size:18pt; font-weight:bold}
    .cls_question {font-weight:bold }
    .cls_example {font-style:italic }
    .cls_grey_bkgd {background-color:#e1e1e1;font-weight:bold}
</style>
</head>
<body>
<div class="cls_section">
<span> Part 2: Who Makes Decisions for You when You Cannot</span></div>
<div class="cls_question">Here is how we make medical decisions in our family:</div>
<div class="cls_example">Examples: I make the decision myself, my entire family has to agree on major decisions about me, my daughter who is a nurse makes the decisions etc.</div>
<div style="position:absolute;left:54.24px;top:163.44px" class="cls_013"><span class="cls_013">___________________________________________________________________________</span></div>
<div style="position:absolute;left:54.24px;top:191.04px" class="cls_013"><span class="cls_013">___________________________________________________________________________</span></div>
<div style="position:absolute;left:54.24px;top:218.64px" class="cls_013"><span class="cls_013">___________________________________________________________________________</span></div>
<div class="cls_question"><span>Here is who I want making medical decisions for me when I am not able to make my own decision:</span></div>
<div class="cls_grey_bkgd"><span>Decision maker #1</span></div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
  <tr>
  <td colspan="3">Signature:</td>
  <td colspan="3">Date:</td>
 </tr>
  <tr>
  <td colspan="3">Name:</td>
  <td colspan="3">Relationship:</td>
 </tr>
 <tr>
  <td colspan="6">Address:</td>
 </tr>
 <tr>
  <td colspan="3">City:</td>
  <td colspan="1">State:</td>
  <td colspan="2">Zip:</td>
 </tr> 
</table>
<div class="cls_grey_bkgd"><span>Decision maker #2</span></div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
  <tr>
  <td colspan="3">Signature:</td>
  <td colspan="3">Date:</td>
 </tr>
  <tr>
  <td colspan="3">Name:</td>
  <td colspan="3">Relationship:</td>
 </tr>
 <tr>
  <td colspan="6">Address:</td>
 </tr>
 <tr>
  <td colspan="3">City:</td>
  <td colspan="1">State:</td>
  <td colspan="2">Zip:</td>
 </tr> 
</table>
<div class="cls_grey_bkgd"><span>Decision maker #3</span></div>
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
  <tr>
  <td colspan="3">Signature:</td>
  <td colspan="3">Date:</td>
 </tr>
  <tr>
  <td colspan="3">Name:</td>
  <td colspan="3">Relationship:</td>
 </tr>
 <tr>
  <td colspan="6">Address:</td>
 </tr>
 <tr>
  <td colspan="3">City:</td>
  <td colspan="1">State:</td>
  <td colspan="2">Zip:</td>
 </tr> 
</table>
<div class="cls_question">I want my proxy to make health decisions for me:</div>
<div>Starting right now</div>
<div>When I am not able to make decisions by myself</div>
</body>
EOF;

        return $html;
    }

}



?>