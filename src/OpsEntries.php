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

class OpsEntries implements \SourcePot\Datapool\Interfaces\Processor{
	
	private $oc;

	private $entryTable='';
	private $entryTemplate=array('Read'=>array('index'=>FALSE,'type'=>'SMALLINT UNSIGNED','value'=>'ALL_MEMBER_R','Description'=>'This is the entry specific Read access setting. It is a bit-array.'),
								 'Write'=>array('index'=>FALSE,'type'=>'SMALLINT UNSIGNED','value'=>'ALL_CONTENTADMIN_R','Description'=>'This is the entry specific Read access setting. It is a bit-array.'),
								 );
		
	public function __construct($oc){
		$this->oc=$oc;
		$table=str_replace(__NAMESPACE__,'',__CLASS__);
		$this->entryTable=strtolower(trim($table,'\\'));
	}
	
	public function init(array $oc){
		$this->oc=$oc;
		$this->entryTemplate=$oc['SourcePot\Datapool\Foundation\Database']->getEntryTemplateCreateTable($this->entryTable,$this->entryTemplate);
	}
	
	public function getEntryTable():string{return $this->entryTable;}

	public function dataProcessor(array $callingElementSelector=array(),string $action='info'){
		// This method is the interface of this data processing class
		// The Argument $action selects the method to be invoked and
		// argument $callingElementSelector$ provides the entry which triggerd the action.
		// $callingElementSelector ... array('Source'=>'...', 'EntryId'=>'...', ...)
		// If the requested action does not exist the method returns FALSE and 
		// TRUE, a value or an array otherwise.
		$callingElement=$this->oc['SourcePot\Datapool\Foundation\Database']->entryById($callingElementSelector,TRUE);
		switch($action){
			case 'run':
				if (empty($callingElement)){
					return TRUE;
				} else {
				return $this->runOpsEntries($callingElement,$testRunOnly=FALSE);
				}
				break;
			case 'test':
				if (empty($callingElement)){
					return TRUE;
				} else {
					return $this->runOpsEntries($callingElement,$testRunOnly=TRUE);
				}
				break;
			case 'widget':
				if (empty($callingElement)){
					return TRUE;
				} else {
					return $this->getOpsEntriesWidget($callingElement);
				}
				break;
			case 'settings':
				if (empty($callingElement)){
					return TRUE;
				} else {
					return $this->getOpsEntriesSettings($callingElement);
				}
				break;
			case 'info':
				return $this->getOpsEntriesInfo();
				break;
		}
		return FALSE;
	}

	private function getOpsEntriesWidget($callingElement){
		return $this->oc['SourcePot\Datapool\Foundation\Container']->container('Mapping','generic',$callingElement,array('method'=>'getOpsEntriesWidgetHtml','classWithNamespace'=>__CLASS__),array());
	}
	
	public function getOpsEntriesWidgetHtml($arr){
		if (!isset($arr['html'])){$arr['html']='';}
		// command processing
		$result=array();
		$formData=$this->oc['SourcePot\Datapool\Foundation\Element']->formProcessing(__CLASS__,__FUNCTION__);
		if (isset($formData['cmd']['run'])){
			$result=$this->runOpsEntries($arr['selector'],FALSE);
		} else if (isset($formData['cmd']['test'])){
			$result=$this->runOpsEntries($arr['selector'],TRUE);
		}
		// build html
		$btnArr=array('tag'=>'input','type'=>'submit','callingClass'=>__CLASS__,'callingFunction'=>__FUNCTION__);
		$matrix=array();
		$btnArr['value']='Test';
		$btnArr['key']=array('test');
		$matrix['Commands']['Test']=$btnArr;
		$btnArr['value']='Run';
		$btnArr['key']=array('run');
		$matrix['Commands']['Run']=$btnArr;
		$arr['html'].=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->table(array('matrix'=>$matrix,'style'=>'clear:left;','hideHeader'=>TRUE,'hideKeys'=>TRUE,'keep-element-content'=>TRUE,'caption'=>'Mapping widget'));
		foreach($result as $caption=>$matrix){
			$arr['html'].=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->table(array('matrix'=>$matrix,'hideHeader'=>FALSE,'hideKeys'=>FALSE,'keep-element-content'=>TRUE,'caption'=>$caption));
		}
		$arr['wrapperSettings']=array('style'=>array('width'=>'fit-content'));
		return $arr;
	}


	private function getOpsEntriesSettings($callingElement){
		$html='';
		if ($this->oc['SourcePot\Datapool\Foundation\Access']->isContentAdmin()){
			$html.=$this->oc['SourcePot\Datapool\Foundation\Container']->container('Mapping entries settings','generic',$callingElement,array('method'=>'getOpsEntriesSettingsHtml','classWithNamespace'=>__CLASS__),array());
		}
		return $html;
	}
	
	private function getOpsEntriesInfo(){
		$html='';
		$html.=__CLASS__.'::'.__FUNCTION__.' provides access to the Open Patent Service of the European Patent Office.';
		$html.='You need to have an OPS account in order to use the service.';
		return $html;
	}
	
	public function getOpsEntriesSettingsHtml($arr){
		if (!isset($arr['html'])){$arr['html']='';}
		$arr['html'].=$this->opsParams($arr['selector']);
		$arr['html'].=$this->opsRules($arr['selector']);
		return $arr;
	}

	private function opsParams($callingElement){
		$contentStructure=array('Target'=>array('htmlBuilderMethod'=>'canvasElementSelect','excontainer'=>TRUE),
								'Mode'=>array('htmlBuilderMethod'=>'select','value'=>'entries','excontainer'=>TRUE,'options'=>array('entries'=>'Entries (EntryId will be created from Name)','csv'=>'Create csv','zip'=>'Create zip')),
								'Run...'=>array('htmlBuilderMethod'=>'select','value'=>0,'excontainer'=>TRUE,'options'=>array(0=>'when triggered',86400=>'once a day',604800=>'once a week',2592000=>'once every 30 days')),
								'Save'=>array('htmlBuilderMethod'=>'element','tag'=>'button','element-content'=>'&check;','keep-element-content'=>TRUE,'value'=>'string'),
								);
		// get selctor
		$arr=$this->callingElement2arr(__CLASS__,__FUNCTION__,$callingElement,TRUE);
		$arr['selector']=$this->oc['SourcePot\Datapool\Foundation\Database']->entryByIdCreateIfMissing($arr['selector'],TRUE);
		// form processing
		$formData=$this->oc['SourcePot\Datapool\Foundation\Element']->formProcessing(__CLASS__,__FUNCTION__);
		$elementId=key($formData['val']);
		if (isset($formData['cmd'][$elementId])){
			$arr['selector']['Content']=$formData['val'][$elementId]['Content'];
			$arr['selector']=$this->oc['SourcePot\Datapool\Foundation\Database']->updateEntry($arr['selector'],TRUE);
		}
		// get HTML
		$arr['canvasCallingClass']=$callingElement['Folder'];
		$arr['contentStructure']=$contentStructure;
		$arr['caption']='Mapping control: Select ops target and type';
		$arr['noBtns']=TRUE;
		$row=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->entry2row($arr,FALSE,TRUE);
		if (empty($arr['selector']['Content'])){$row['setRowStyle']='background-color:#a00;';}
		$matrix=array('Parameter'=>$row);
		return $this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->table(array('matrix'=>$matrix,'style'=>'clear:left;','hideHeader'=>FALSE,'hideKeys'=>TRUE,'keep-element-content'=>TRUE,'caption'=>$arr['caption']));
	}
	
	private function opsRules($callingElement){
		$contentStructure=array('Target value or...'=>array('htmlBuilderMethod'=>'element','tag'=>'input','type'=>'text','excontainer'=>TRUE),
								'...value selected by'=>array('htmlBuilderMethod'=>'keySelect','excontainer'=>TRUE,'value'=>'useValue','addSourceValueColumn'=>TRUE,'addColumns'=>array('Linked file'=>'Linked file')),
								'Target data type'=>array('htmlBuilderMethod'=>'select','excontainer'=>TRUE,'value'=>'string','options'=>$this->dataTypes),
								'Target column'=>array('htmlBuilderMethod'=>'keySelect','excontainer'=>TRUE,'value'=>'Name','standardColumsOnly'=>TRUE),
								'Target key'=>array('htmlBuilderMethod'=>'element','tag'=>'input','type'=>'text','excontainer'=>TRUE),
								'Use rule if Compare value'=>array('htmlBuilderMethod'=>'select','excontainer'=>TRUE,'value'=>'always','options'=>$this->skipCondition),
								'Compare value'=>array('htmlBuilderMethod'=>'element','tag'=>'input','type'=>'text','excontainer'=>TRUE),
								);
		$contentStructure['...value selected by']+=$callingElement['Content']['Selector'];
		$contentStructure['Target column']+=$callingElement['Content']['Selector'];
		$arr=$this->callingElement2arr(__CLASS__,__FUNCTION__,$callingElement,FALSE);
		$arr['canvasCallingClass']=$callingElement['Folder'];
		$arr['contentStructure']=$contentStructure;
		$arr['caption']='Mapping rules: Map selected entry values or constants (Source value) to target entry values';
		$html=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->entryListEditor($arr);
		return $html;
	}

	private function runOpsEntries($callingElement,$testRun=FALSE){
		$base=array('Script start timestamp'=>hrtime(TRUE));
		$entriesSelector=array('Source'=>$this->entryTable,'Name'=>$callingElement['EntryId']);
		foreach($this->oc['SourcePot\Datapool\Foundation\Database']->entryIterator($entriesSelector,TRUE,'Read','EntryId',TRUE) as $entry){
			$key=explode('|',$entry['Type']);
			$key=array_pop($key);
			$base[$key][$entry['EntryId']]=$entry;
			// entry template
			foreach($entry['Content'] as $contentKey=>$content){
				if (is_array($content)){continue;}
				if (strpos($content,'EID')!==0 || strpos($content,'eid')===FALSE){continue;}
				$template=$this->oc['SourcePot\Datapool\Foundation\DataExplorer']->entryId2selector($content);
				if ($template){$base['entryTemplates'][$content]=$template;}
			}
		}
		// loop through source entries and parse these entries
		$this->oc['SourcePot\Datapool\Foundation\Database']->resetStatistic();
		$result=array('Mapping statistics'=>array('Entries'=>array('value'=>0),
												  'CSV-Entries'=>array('value'=>0),
												  'Files added to zip'=>array('value'=>0),
												  'Skip rows'=>array('value'=>0),
												  'Output format'=>array('value'=>'Entries'),
												 )
					);
		// loop through entries
		foreach($this->oc['SourcePot\Datapool\Foundation\Database']->entryIterator($callingElement['Content']['Selector'],TRUE) as $sourceEntry){
		
		}
		$result['Statistics']=$this->oc['SourcePot\Datapool\Foundation\Database']->statistic2matrix();
		$result['Statistics']['Script time']=array('Value'=>date('Y-m-d H:i:s'));
		$result['Statistics']['Time consumption [msec]']=array('Value'=>round((hrtime(TRUE)-$base['Script start timestamp'])/1000000));
		return $result;
	}
		
	private function callingElement2arr($callingClass,$callingFunction,$callingElement){
		if (!isset($callingElement['Folder']) || !isset($callingElement['EntryId'])){return array();}
		$type=$this->oc['SourcePot\Datapool\Root']->class2source(__CLASS__);
		$type.='|'.$callingFunction;
		$entry=array('Source'=>$this->entryTable,'Group'=>$callingFunction,'Folder'=>$callingElement['Folder'],'Name'=>$callingElement['EntryId'],'Type'=>strtolower($type));
		$entry=$this->oc['SourcePot\Datapool\Tools\MiscTools']->addEntryId($entry,array('Group','Folder','Name','Type'),0);
		$entry=$this->oc['SourcePot\Datapool\Foundation\Access']->addRights($entry,'ALL_R','ALL_CONTENTADMIN_R');
		$entry['Content']=array();
		$arr=array('callingClass'=>$callingClass,'callingFunction'=>$callingFunction,'selector'=>$entry);
		return $arr;
	}

}
?>