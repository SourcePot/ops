<?php
/*
* This file is part of the Datapool CMS package.
* @package OPS for Datapool
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
		
	public $credentialsDef=array('Info'=>array('@tag'=>'p','@element-content'=>__CLASS__.' provides access to the Open Patent Service of the European Patent Office.<br/>You need to have an OPS account in order to use the service.','@keep-element-content'=>TRUE,'@Read'=>'ALL_CONTENTADMIN_R','@isApp'=>'Info','@hideKeys'=>TRUE),
                                 'Name'=>array('@tag'=>'p','@default'=>'OPS Credentials','@Read'=>'ALL_R','@isApp'=>'*','@hideKeys'=>TRUE),
                                 'Type'=>array('@tag'=>'p','@default'=>'settings Access','@Read'=>'ALL_R','@isApp'=>'#','@hideKeys'=>TRUE),
                                 'Content'=>array('Consumer Key'=>array('@tag'=>'input','@type'=>'text','@default'=>'','@excontainer'=>TRUE),
                                                'Consumer Secret Key'=>array('@tag'=>'input','@type'=>'password','@default'=>'','@excontainer'=>TRUE,'@hideKeys'=>TRUE),
                                                'Save'=>array('@tag'=>'button','@value'=>'save','@element-content'=>'Save','@default'=>'save','@isApp'=>'&#128274;'),
                                                ),
							);

	public function __construct($oc){
		$table=str_replace(__NAMESPACE__,'',__CLASS__);
		$this->entryTable=strtolower(trim($table,'\\'));
		// get OPS service class
		require_once 'OpsServices.php';
		$oc['SourcePot\Ops\OpsServices']=new \SourcePot\Ops\OpsServices($oc);
		$this->oc=$oc;
	}
	
	public function init(array $oc){
		$this->entryTemplate=$oc['SourcePot\Datapool\Foundation\Database']->getEntryTemplateCreateTable($this->entryTable,$this->entryTemplate);
		$oc['SourcePot\Datapool\Foundation\Definitions']->addDefintion('!'.__CLASS__,$this->credentialsDef);
	}
	
	public function getOc(){return $this->oc;}
	
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
		return $this->oc['SourcePot\Datapool\Foundation\Container']->container('OPS','generic',$callingElement,array('method'=>'getOpsEntriesWidgetHtml','classWithNamespace'=>__CLASS__),array());
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
		$arr['html'].=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->table(array('matrix'=>$matrix,'style'=>'clear:left;','hideHeader'=>TRUE,'hideKeys'=>TRUE,'keep-element-content'=>TRUE,'caption'=>'OPS widget'));
		foreach($result as $caption=>$matrix){
			$arr['html'].=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->table(array('matrix'=>$matrix,'hideHeader'=>FALSE,'hideKeys'=>FALSE,'keep-element-content'=>TRUE,'caption'=>$caption));
		}
		$arr['wrapperSettings']=array('style'=>array('width'=>'fit-content'));
		return $arr;
	}

	private function getOpsEntriesSettings($callingElement){
		$html='';
		if ($this->oc['SourcePot\Datapool\Foundation\Access']->isContentAdmin()){
			$html.=$this->oc['SourcePot\Datapool\Foundation\Container']->container('OPS entries settings','generic',$callingElement,array('method'=>'getOpsEntriesSettingsHtml','classWithNamespace'=>__CLASS__),array());
		}
		return $html;
	}
	
	private function getOpsEntriesInfo(){
		$html='';
		return $html;
	}
	
	public function getOpsEntriesSettingsHtml($arr){
		if (!isset($arr['html'])){$arr['html']='';}
		$credentials=$this->oc['SourcePot\Ops\OpsServices']->getCredentials($this->oc);
		$arr['html'].=$this->oc['SourcePot\Datapool\Foundation\Definitions']->entry2form($credentials,FALSE);
		$arr['html'].=$this->opsParams($arr['selector']);
		return $arr;
	}

	private function opsParams($callingElement){
		$contentStructure=array('Country'=>array('method'=>'keySelect','value'=>'useValue','excontainer'=>TRUE,'addSourceValueColumn'=>TRUE),
                                'Application number'=>array('method'=>'keySelect','value'=>'useValue','excontainer'=>TRUE,'addSourceValueColumn'=>TRUE),
                                'Family'=>array('method'=>'keySelect','value'=>'useValue','excontainer'=>TRUE,'addSourceValueColumn'=>TRUE),
                                'OPS Entries'=>array('method'=>'canvasElementSelect','excontainer'=>TRUE),
								'Target for changed entries'=>array('method'=>'canvasElementSelect','excontainer'=>TRUE),
								'Save'=>array('method'=>'element','tag'=>'button','element-content'=>'&check;','keep-element-content'=>TRUE,'value'=>'string'),
                                );
		$contentStructure['Country']+=$callingElement['Content']['Selector'];
        $contentStructure['Family']+=$callingElement['Content']['Selector'];
        $contentStructure['Application number']+=$callingElement['Content']['Selector'];
        // get selector
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
		$arr['caption']='OPS control: Select ops target and type';
		$arr['noBtns']=TRUE;
		$row=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->entry2row($arr,FALSE,TRUE);
		if (empty($arr['selector']['Content'])){$row['setRowStyle']='background-color:#a00;';}
		$matrix=array('Parameter'=>$row);
		return $this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->table(array('matrix'=>$matrix,'style'=>array(),'hideHeader'=>FALSE,'hideKeys'=>TRUE,'keep-element-content'=>TRUE,'caption'=>$arr['caption']));
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
		$result=array('OPS statistics'=>array('Entries'=>array('value'=>0),
											  'OPS Match'=>array('value'=>0),
											  )
					);
		// loop through entries
		foreach($this->oc['SourcePot\Datapool\Foundation\Database']->entryIterator($callingElement['Content']['Selector'],TRUE) as $sourceEntry){
            if ($entry['isSkipRow']){
                $result['OPS statistics']['Skip rows']['value']++;
                continue;
            }
            $result['OPS statistics']['Entries']['value']++;
            $result=$this->sample2family($base,$sourceEntry,$result,$testRun);
            //$result=$this->opsEntry($base,$sourceEntry,$result,$testRun);
            break;  // process only one entry per run
		}
		
        $result['Statistics']=$this->oc['SourcePot\Datapool\Foundation\Database']->statistic2matrix();
		$result['Statistics']['Script time']=array('Value'=>date('Y-m-d H:i:s'));
		$result['Statistics']['Time consumption [msec]']=array('Value'=>round((hrtime(TRUE)-$base['Script start timestamp'])/1000000));
		return $result;
	}
    
    private function sample2family($base,$sourceEntry,$result,$testRun){
        $opsArr=array();
        $params=current($base['opsparams']);
        $params=$params['Content'];
        $flatSourceEntry=$this->oc['SourcePot\Datapool\Tools\MiscTools']->arr2flat($sourceEntry);
        if (empty($flatSourceEntry[$params['Country']]) || empty($flatSourceEntry[$params['Application number']])){
            // problem recovering application number
            $msg=' unprocessed entry. Missing Country or Application number';
        } else {
            if ($flatSourceEntry[$params['Family']]){$base['Family']=$flatSourceEntry[$params['Family']];} else {$base['Family']='';}
            // get family from OPS
            $application=$flatSourceEntry[$params['Country']].$flatSourceEntry[$params['Application number']];
            $opsArr=$this->oc['SourcePot\Ops\OpsServices']->getApplicationData('family',$application,'');
            if (empty($opsArr['data'])){
                $msg=' unprocessed entry. OPS reply data missing for '.$application;
            } else {
                $msg=' processed entry '.$application;
                $result=$this->opsArr2entries($base,$opsArr,$result,$testRun);
            }
        }
        if (empty($testRun)){
            $this->oc['SourcePot\Datapool\Foundation\Database']->deleteEntries($sourceEntry,TRUE);
        }
        if (!isset($result['OPS statistics']['Deleted'.$msg]['value'])){$result['OPS statistics']['Deleted'.$msg]['value']=0;}
        $result['OPS statistics']['Deleted'.$msg]['value']++;
        return $result;
    }
    
    private function opsArr2entries($base,$opsArr,$result,$testRun){
        $S=$this->oc['SourcePot\Datapool\Tools\MiscTools']->getSeparator();
        $params=current($base['opsparams']);
        $params=$params['Content'];
        $documentType=FALSE;
        $documents=array();
        $documentIndex=0;
        // get entry template
        $entry=$base['entryTemplates'][$params['OPS Entries']];
        $entry['Owner']='EPO';
        $entry=$this->oc['SourcePot\Datapool\Foundation\Access']->addRights($entry,'ALL_R','ALL_CONTENTADMIN_R');
        // get publications
        $contentPublication=array();
        $publicationArr=$this->oc['SourcePot\Ops\OpsServices']->unifyOpsArr($opsArr,'ops:family-member','publication-reference');
        foreach($publicationArr as $index=>$publication){
            if (!isset($publication['@document-id-type'])){continue;}
            if (stripos($publication['@document-id-type'],'docdb')===FALSE){continue;}
            $contentPublication[$publication['country']][$publication['date']]=$publication['country'].' '.$publication['doc-number'].' '.$publication['kind'];
        }
        // get applications
        $applicationArr=$this->oc['SourcePot\Ops\OpsServices']->unifyOpsArr($opsArr,'ops:family-member','application-reference');
        foreach($applicationArr as $index=>$application){
            $entry['EntryId']='@doc-id='.$application['@doc-id'];
            $entry['Date']=$application['date'].' 12:00:00';
            if (isset($base['Family'])){$entry['Folder']=$base['Family'];} else {$entry['Folder']='application';}
            if (isset($application['country'])){$entry['Name']=$application['country'];} else {$entry['Name']='';}
            if (isset($application['doc-number'])){$entry['Name'].=$application['doc-number'];}
            $entry['Content']['Application']=array('Country'=>$application['country'],'Number'=>$application['doc-number'],'Kind'=>$application['kind'],'Date'=>$application['date'],'OPS doc-id'=>$application['@doc-id'],'OPS document-type'=>$application['@document-id-type']);
            if (isset($contentPublication[$application['country']])){
                $pubArr=$contentPublication[$application['country']];
                ksort($pubArr);
                $entry['Content']['All publications']=$pubArr;
            }
            $entry=$this->addLegalData($entry);
            $oldEntry=$this->oc['SourcePot\Datapool\Foundation\Database']->entryById($entry,TRUE);
            $result=$this->updateOpsEntry($entry,$result,$testRun);
            $result=$this->compareEntries($base,$entry,$oldEntry,$result,$testRun);
        }
        return $result;
    }
    
    
    private function addLegalData($entry){
        $S=$this->oc['SourcePot\Datapool\Tools\MiscTools']->getSeparator();
        $opsArr=$this->oc['SourcePot\Ops\OpsServices']->getApplicationData('legal',$entry['Name'],'');
        $entry['Content']['Legal']=array();
        $legalArr=$this->oc['SourcePot\Ops\OpsServices']->unifyOpsArr($opsArr,'ops:legal','');
        foreach($legalArr as $legalIndex=>$legal){
            $legalMsg=array();
            if (isset($legal['ops:pre'])){$legalMsg[]=$legal['ops:pre'];}
            if (isset($legal['@desc'])){$legalMsg[]=$legal['@desc'];}
            $entry['Content']['Legal'][]=implode('|',$legalMsg);
        }
        return $entry;
    }
    
    private function compareEntries($base,$entry,$oldEntry,$result,$testRun){
        if (empty($oldEntry)){
            if (!isset($result['OPS statistics']['Old entry missing']['value'])){$result['OPS statistics']['Old entry missing']['value']=0;}
            $result['OPS statistics']['Old entry missing']['value']++;
        } else {
            $targetContent=array('Changes'=>'','Additions'=>'');
            $flatEntry=$this->oc['SourcePot\Datapool\Tools\MiscTools']->arr2flat($entry['Content']);
            $flatOldEntry=$this->oc['SourcePot\Datapool\Tools\MiscTools']->arr2flat($oldEntry['Content']);
            foreach($flatEntry as $key=>$value){
                if (isset($flatOldEntry[$key])){
                    $oldValue=$flatOldEntry[$key];
                    if ($value!==$oldValue){
                        $targetContent['Changes'].=$key.' changed from '.$oldValue.' to '.$value.";\n";
                    }
                } else {
                    $targetContent['Additions'].='New '.$key.': '.$value.";\n";
                }
            }
            if (!empty($targetContent['Changes']) || !empty($targetContent['Additions'])){
                // create target entry
                $params=current($base['opsparams']);
                $params=$params['Content'];
                $this->oc['SourcePot\Datapool\Tools\MiscTools']->arr2file(array('params'=>$params,'base'=>$base));
                $targetEntry=array_merge($entry,$base['entryTemplates'][$params['Target for changed entries']]);
                $targetEntry=$this->oc['SourcePot\Datapool\Tools\MiscTools']->addEntryId($targetEntry,array('Group','Folder','Name','Type'),0);
                $targetEntry['Content']=$targetContent;
                $targetEntry=$this->updateOpsEntry($targetEntry,$result,$testRun);
                // update statistik
                if (!isset($result['OPS statistics']['Entry change detected']['value'])){$result['OPS statistics']['Entry change detected']['value']=0;}
                $result['OPS statistics']['Entry change detected']['value']++;
            }
        }
        return $result;
    }
    
    private function updateOpsEntry($entry,$result,$testRun){
        if (empty($entry['EntryId'])){
            $entry=$this->oc['SourcePot\Datapool\Tools\MiscTools']->addEntryId($entry,array('Group','Folder','Name','Type'),0);
        }
        if (empty($testRun)){
            $this->oc['SourcePot\Datapool\Foundation\Database']->updateEntry($entry,TRUE);
        }
        if (!isset($result['OPS statistics']['Updated/inserted entry']['value'])){$result['OPS statistics']['Updated/inserted entry']['value']=0;}
        $result['OPS statistics']['Updated/inserted entry']['value']++;
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