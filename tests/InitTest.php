<?php
/*
* This file is part of the Datapool CMS package.
* @package Datapool
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2023 to today Carsten Wallenhauer
* @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-v3
*/
declare(strict_types=1);

namespace SourcePot\Ops\Tests;

class IniTest{
	
	private $oc;
	
	public function __construct($oc){
		$oc=$this->loadSrcClass($oc);
		$this->oc=$oc;
	}
	
	public function getOc(){
		return $this->oc;
	}
	
	private function loadSrcClass($oc){
		require_once realpath('../src/OpsEntries.php');
		$oc['SourcePot\Ops\OpsEntries']=new \SourcePot\Ops\OpsEntries($oc);
		return $oc;
	}


}
?>