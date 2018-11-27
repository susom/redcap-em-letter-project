<?php
namespace Stanford\LetterProject;

/** @var \Stanford\LetterProject\LetterProject $module */

include_once "LetterPDF.php";
use Stanford\LetterProject\LetterPDF;
use REDCap;


if (!empty($_REQUEST['id'])) {
    $record_id = $_REQUEST['id'];
    $module->emDebug("RECORD ID is $record_id");
}

$date = date('Ymd_his', time());
$fname = 'LETTER_PROJECT_'.$record_id_.'_' . $date . '.pdf';

$pdf = $module->setupLetter($record_id);


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
