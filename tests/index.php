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

$rootClass='../../../../src/php/Root.php';
if (is_file($rootClass)){
	require_once $rootClass;
	$pageObj=new \SourcePot\Datapool\Root('../../../../src/www/');
	$oc=$pageObj->getOc();
	$html='Datapool Object Collection created.<br/>';
	$html.='The file system for testing was created in '.realpath('../').'<br/>';
	var_dump($oc['SourcePot\Datapool\Foundation\Backbone']->getSettings());
	echo $html;
} else {
	echo 'Failed to load '.$rootClass.'<br/>';
}
exit;
?>