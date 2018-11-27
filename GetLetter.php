<?php
namespace Stanford\LetterProject;

/** @var \Stanford\LetterProject\LetterProject $module */

include_once "LetterPDF.php";
use Stanford\LetterProject\LetterPDF;
use REDCap;

function setupLetter($record_id) {
    global $module;

    set_time_limit(0);

    //$pdf = new LetterPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT,true, 'UTF-8', false);

    $pdf = new LetterPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

    // Set document information dictionary in unicode mode
    $pdf->SetDocInfoUnicode(true);

    $module->emDebug("LOGO", PDF_HEADER_LOGO);
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Stanford What-Matters-Most Letter Directive', null, array(150, 43, 40));

    // set header and footer fonts
    $pdf->setHeaderFont(Array('times', '', 14));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $pdf->SetFont('arial', '', 12);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // ---------------------------------------------------------
    //if record ID is set, get Data for that record

    // Use alternative passing of parameters as an associate array
    $params = array(
        'project_id' => $module->getProjectId(),
        'return_format' => 'json',
        //'exportSurveyFields'=>true,
        //'fields'=>array('dob','record_id'),
        'events' => array($module->getProjectSetting('final-event')),
        'records' => array($record_id));
    $data = REDCap::getData($params);

    //$q = \REDCap::getData($module->getProjectId(), 'json',  array($record_id), null, $module->getProjectSetting('final-event'));
    $final_data = json_decode($data, true);
    //$module->emDebug($params,$module->getProjectId(),$module->getProjectSetting('final-event'), $final_data, $record_id, "FINAL DATA");

    // ---------------------------------------------------------

    // set font
    //$pdf->SetFont('arial', '', 12);

    // add a page
    $pdf->AddPage();

    //create html for page 1
    $html = $pdf->makeHTMLPage1($record_id, current($final_data));
    $pdf->writeHTML($html, true, false, true, false, '');

    //create html for page 2
    $pdf->AddPage();
    $html = $pdf->makeHTMLPage2($record_id,current($final_data));
    $pdf->writeHTML($html, true, false, true, false, '');

    //Question 6
    $q6 = current($final_data)['q6'];
    $pdf->RadioButton('health_decisions', 5, array(), array(), '1', ($q6 == 1));
    $pdf->Cell(35, 5, 'Starting right now');
    $pdf->Ln(6);
    $pdf->RadioButton('health_decisions', 5, array(), array(), '2', $q6 == 2);
    $pdf->Cell(35, 5, 'When I am not able to make decisions by myself');
    $pdf->Ln(6);

    //create html for page 3
    $pdf->AddPage();
    $pdf = $pdf->makeHTMLPage3($record_id,current($final_data), $pdf);

    return $pdf;
}


if (!empty($_REQUEST['id'])) {
    $record_id = $_REQUEST['id'];
    $module->emDebug("RECORD ID is $record_id");
}

$date = date('Ymd_his', time());
$fname = 'LETTER_PROJECT_'.$record_id_.'_' . $date . '.pdf';

$pdf = setupLetter($record_id);


$action = $_REQUEST['action'];

switch ($action) {
    case 'P':
        $pdf->IncludeJS("print();");
        $pdf->Output($fname, 'I');
        break;
    case 'D':
        $pdf->Output($fname, 'D');
        break;
    default:
        $pdf->Output($fname, 'I');
}
