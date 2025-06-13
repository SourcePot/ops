<?php
/*
* @package OPS
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2024 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit MIT
*/

declare(strict_types=1);

namespace SourcePot\OPS;

final class Biblio{

    const ONEDIMSEPARATOR='|[]|';

    private $opsObj=NULL;
    private $helperObj=NULL;

    public function __construct(private string $appName,private string $consumerKey,private string $consumerSecretKey)
    {
        $this->opsObj = new ops($appName,$consumerKey,$consumerSecretKey);
        $this->helperObj = new helper();
    }

    public function legal(string $application):array
    {
        $result=['warnings'=>[],];
        // check provided application with number service
        $doctype='application';
        $result=$this->opsObj->request('GET','rest-services/number-service/application/original/('.$application.')/docdb');
        if (!empty($result['error'])){
            // check provided publication with number service
            $doctype='publication';
            $result=$this->opsObj->request('GET','rest-services/number-service/publication/original/('.$application.')/docdb');
        }
        if (!empty($result['error'])){return $result;}
        // get legal family data
        $legalResult=$this->opsObj->request('GET','rest-services/family/'.$doctype.'/docdb/'.$result['country'].'.'.$result['doc-number'].'.'.$result['kind'].'.'.$result['date'].'/legal');
        if (!empty($legalResult['error'])){
            $result['error'][$doctype][$application]=$legalResult['error'];
            return $result;
        }
        $currentKey=FALSE;
        $documents=[];
        for($docIndex=0;$docIndex<intval($legalResult['total-result-count']);$docIndex++){
            $docBiblio=$legalResult[$docIndex]['publication-reference']['docdb'];
            $currentKey=($docBiblio['country']??'').' '.($docBiblio['doc-number']??'').' '.($docBiblio['kind']??'');
            $priorityClaim=trim(($legalResult[$docIndex]['priority-claim']['docdb']['country']??'').' '.($legalResult[$docIndex]['priority-claim']['docdb']['doc-number']??'').' '.($legalResult[$docIndex]['priority-claim']['docdb']['kind']??''));
            $documents[$currentKey]=['date'=>$docBiblio['date'],'priority-claim'=>$priorityClaim,'family-id'=>$legalResult[$docIndex]['family-id']];
            foreach($legalResult[$docIndex]['events']??[] as $eventIndex=>$event){
                $desc=$event['desc']??'';
                if (!empty($event['ops:L520EP'])){
                    $desc=' '.$event['ops:L520EP'].(isset($event['ops:L518EP'])?'('.$event['ops:L518EP'].')':'');
                } else {
                    $desc.=(isset($event['ops:pre'])?("\n".$event['ops:pre']):'');
                    for($descIndex=0;$descIndex<10;$descIndex++){
                        $desc.=(isset($event[$descIndex])?("\n".$event[$descIndex]):'');
                    }
                    $desc.=(isset($event['descData'])?("\n".$event['descData']):'');
                }
                $effectiveDate=(isset($event['ops:L525EP']))?(substr($event['ops:L525EP'],0,4).'-'.substr($event['ops:L525EP'],4,2).'-'.substr($event['ops:L525EP'],6,2)):$event['ops:L007EP'];
                $eventArr=['code'=>$event['code'],'date'=>$effectiveDate,'legal status'=>($event['ops:L501EP']??'').' '.($event['ops:L502EP']??''),'desc'=>preg_replace('/[ ]+/',' ',$desc),];
                if (!empty($event['ops:L504EP']) && !empty($event['ops:L503EP'])){
                    // validation with new publication number from national office
                    $nationalKey=trim($event['ops:L504EP'].' '.$event['ops:L503EP'].' '.($event['ops:L505EP']??''));
                    $documents[$nationalKey]=['date'=>$effectiveDate,'family-id'=>$legalResult[$docIndex]['family-id'],'root'=>$currentKey,'events'=>($documents[$nationalKey]['events']??[$eventArr])];
                    if (stripos($desc,'LAPS')!==FALSE || stripos($desc,'CEASE')!==FALSE){
                        $documents[$nationalKey]['lapsed']=$effectiveDate;
                    }
                } else if (!empty($event['ops:L501EP']) && (strpos($desc,'REFERENCE TO A NATIONAL CODE')!==FALSE || $event['code']=='PG25')){
                    // validation without new publication number from national office
                    $country=($event['ops:L501EP']==='CH')?[$event['ops:L501EP'],'LI']:[$event['ops:L501EP']];
                    foreach($country as $cc){
                        $nationalKey=trim($cc.' '.$docBiblio['doc-number'].' '.($event['ops:L505EP']??''));
                        $documents[$nationalKey]=['date'=>$effectiveDate,'family-id'=>$legalResult[$docIndex]['family-id'],'root'=>$currentKey,'events'=>($documents[$nationalKey]['events']??[$eventArr])];
                        if (stripos($desc,'LAPS')!==FALSE || stripos($desc,'CEASE')!==FALSE){
                            $documents[$nationalKey]['lapsed']=$effectiveDate;
                        }    
                    }
                } else {
                    // all other cases
                    if (stripos($desc,'LAPS')!==FALSE || stripos($desc,'CEASE')!==FALSE){
                        $documents[$currentKey]['lapsed']=$effectiveDate;
                    }
                    if (stripos($desc,'OPT-OUT')!==FALSE){
                        $documents[$currentKey]['opt-out']=$effectiveDate;
                    }                
                    $documents[$currentKey]['events'][$eventIndex]=$eventArr;
                }
            }
        }
        return $documents;
    }

}
?>