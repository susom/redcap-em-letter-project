<?php
/**
 * Created by PhpStorm.
 * User: jael
 * Date: 12/3/18
 * Time: 7:54 AM
 */

namespace Stanford\LetterProject;

/** @var \Stanford\LetterProject\LetterProject $module */


use REDCap;

$docs = $module->getFileData(7);

$module->uploadFileData(7, $docs, $module->getProjectSetting('final-event'));

