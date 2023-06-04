<?php
/*
* This file is part of the Datapool CMS package.
* @package Datapool
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2023 to today Carsten Wallenhauer
* @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-v3
*/
	
declare(strict_types=1);
	
namespace SourcePot\Ops;
	
mb_internal_encoding("UTF-8");

$rootClass='../vendor/sourcepot/datapool/src/php/Root.php';
if (is_file($rootClass)){
	// get Datapool Object Collection (oc)
	require_once $rootClass;
	$pageObj=new \SourcePot\Datapool\Root();
	$oc=$pageObj->getOc();
	// init test environment
	require_once realpath('./InitTEst.php');
	$initTestObj=new \SourcePot\Ops\Tests\IniTest($oc);
	$oc=$initTestObj->getOc();
	// output result
	$html='Datapool Object Collection created.<br/>';
	$html.=$oc['SourcePot\Ops\OpsEntries']->dataProcessor();
	echo $html;
} else {
	echo 'Failed to load '.$rootClass.'<br/>';
}
exit;
?>