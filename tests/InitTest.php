<?php
/*
* This file is part of the Datapool CMS package.
* @package OPS for Datapool
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2023 to today Carsten Wallenhauer
* @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-v3
*/
declare(strict_types=1);

namespace SourcePot\Ops\Tests;

class InitTest{
	
	private $oc;
	
	public function __construct($oc){
		$this->oc=$this->loadSrcClass($oc);
		$this->processApplication();	
	}
	
	public function getOc(){
		return $this->oc;
	}
	
	private function loadSrcClass($oc){
		require_once realpath('../src/OpsEntries.php');
		$oc['SourcePot\Ops\OpsEntries']=new \SourcePot\Ops\OpsEntries($oc);
		return $oc;
	}
	
	private function processApplication($type='legal',$application='US13/486,978'){
		$oc=$this->oc['SourcePot\Ops\OpsEntries']->getOc();
		$debugArr=array('application in'=>$application,'return type'=>$type);
		$debugArr['data']=$oc['SourcePot\Ops\OpsServices']->getApplicationData('legal','US13/486,978');
		$debugArr['quota']=$oc['SourcePot\Ops\OpsServices']->getUsedQuota();
		$oc['SourcePot\Datapool\Tools\MiscTools']->arr2file($debugArr);
	}


}
?>