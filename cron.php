<?php
/**
 * This script is intended to be run daily
 * Suggested time slot is 10:00 a.m.
 */

// OPTIONAL: don't run on the weekends
if(date('N')>=6) {
    die('no emails on the weekend');
}


require_once('97thingsdaily.php');

$oPage = new nstepsk();
$oPage->fetchItem();

$oPage->sThingText = trim($oPage->sThingText);

$sThingText = nl2br($oPage->sThingText);

// Do a bit of clean up for email
$sThingText = str_replace(array('<br />','<br>'),'</p><p>',$sThingText);
$sThingText = preg_replace('/(<p>[\s\n\r]<\/p>)?/','',$sThingText);
$sThingText = preg_replace('/(<p>[\s\n\r]<\/p>)?/','',$sThingText);
$sThingText = preg_replace('/(<p>[\s\n\r]<\/p>)?/','',$sThingText);
$sThingText = '<p>'.$sThingText.'</p>';


$sMailBody =  '<h2>'.$oPage->sThingTitle.'</h2>'.
    '<h3><a href="'.$oPage->sThingPageUrl.'">'.$oPage->sThingPageUrl.'</a></h3>'.
    $sThingText;

// Set your array of email recipients in here
$aRecipients = array(
    'anon@anon.com'
);

// Set a From Header, makes it easier to filter in your inbox
$headers = 'From:97 Things<97things@yourdomain.com>'.PHP_EOL.'content-type:text/html'.PHP_EOL;

foreach($aRecipients as $sTo) {
    mail(
        $sTo,
        '97 Things Every Programmer Should Know'.': '.strip_tags($oPage->sThingTitle),
        $sMailBody,
        $headers
    ) or die('error with email');
}

