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

$autoloadFile='../vendor/autoload.php';
if (is_file($autoloadFile)){
	require_once $autoloadFile;
	$pageObj=new \SourcePot\Datapool\Root();
} else {
	echo 'Failed to load '.$autoloadFile.'<br/>';
}
exit;
?>