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

class OpsServices{
	
	private $oc;

	private $credentials=array('App Name'=>'datapool','Consumer Key'=>'...','Consumer Secret Key'=>'...');
	private $ops=array('baseUrl'=>'https://ops.epo.org');

	public function __construct($oc){
		$credentials=$this->getCredentials($oc);
		$this->credentials=$credentials['Content'];
		$this->oc=$oc;
	}
	
	private function getCredentials($oc){
		$setting=array('Class'=>'SourcePot\Ops\OpsEntries','EntryId'=>'Access');
		$setting['Content']=$this->credentials;
		return $oc['SourcePot\Datapool\Foundation\Filespace']->entryByIdCreateIfMissing($setting,TRUE);
	}

	private function getCredentialsToken(){
		if (!isset($_SESSION[__CLASS__][__FUNCTION__])){$_SESSION[__CLASS__][__FUNCTION__]=array('access_token'=>'','expires_in'=>0,'expires'=>0);}
		if ($_SESSION[__CLASS__][__FUNCTION__]['expires']<(time()-5)){
			$request=array();
			$request['header']=array('Content-Type'=>'application/x-www-form-urlencoded',
						  'user_app'=>$this->credentials['App Name'],
						  'Authorization'=>'Basic '.base64_encode($this->credentials['Consumer Key'].':'.$this->credentials['Consumer Secret Key']));
			$request['url']=$this->ops['baseUrl'];
			$request['resource']='3.2/auth/accesstoken';
			$request['data']=array('grant_type'=>'client_credentials');
			$request['query']=array();
			$request['options']=array();
			$response=$this->oc['SourcePot\Datapool\Tools\NetworkTools']->request($request);
			if (isset($response['data']['access_token']) && isset($response['data']['expires_in'])){
				$_SESSION[__CLASS__][__FUNCTION__]['access_token']=$response['data']['access_token'];
				$_SESSION[__CLASS__][__FUNCTION__]['expires_in']=$response['data']['expires_in'];
				$_SESSION[__CLASS__][__FUNCTION__]['expires']=time()+$response['data']['expires_in'];
				$_SESSION[__CLASS__][__FUNCTION__]['expires dateTime']=date('Y-m-d H:i:s',$_SESSION[__CLASS__][__FUNCTION__]['expires']);
			}
		}
		return $_SESSION[__CLASS__][__FUNCTION__];
	}
	
	public function applicationNumber2application($applicationNumber='US18/301,602',$applicationDate=''){
		$applicationDate=substr(strtr($applicationDate,array('-'=>'','_'=>'','.'=>'')),0,8);
		$credentials=$this->getCredentialsToken();
		$request=array('url'=>$this->ops['baseUrl'],'method'=>'GET','data'=>array(),'query'=>array(),'options'=>array());
		$request['header']=array('Content-Type'=>'application/x-www-form-urlencoded',
								 'user_app'=>$this->credentials['App Name'],
								 'Authorization'=>'Bearer '.$credentials['access_token']
								 );
		if (empty($applicationDate)){
			$request['resource']='rest-services/number-service/application/original/('.$applicationNumber.')/docdb';
		} else {
			$request['resource']='rest-services/number-service/application/original/('.$applicationNumber.').'.$applicationDate.'/docdb';
		}
		$response=$this->oc['SourcePot\Datapool\Tools\NetworkTools']->request($request);
		$application=array();
		// check for error
		if (isset($response['data']['ops:world-patent-data']['ops:meta'])){
			$metaStr=json_encode($response['data']['ops:world-patent-data']['ops:meta']);
			if (strpos($metaStr,'pBRE999')!==FALSE){
				return $application;
			}
		} else {
			return $application;
		}
		// map response to application
		$oneDimSep=$this->oc['SourcePot\Datapool\Tools\MiscTools']->getSeparator();
		if (isset($response['data']['ops:world-patent-data']['ops:standardization']['ops:output']['ops:application-reference']['document-id'])){
			$flatArr=$this->oc['SourcePot\Datapool\Tools\MiscTools']->arr2flat($response['data']['ops:world-patent-data']['ops:standardization']['ops:output']['ops:application-reference']['document-id']);
			foreach($flatArr as $flatKey=>$flatValue){
				$flatKey=strtr($flatKey,array('$'=>'','@'=>'',$oneDimSep=>''));
				$application[$flatKey]=$flatValue;
				if ($flatKey=='date'){
					$application['Application date']=$flatValue[0].$flatValue[1].$flatValue[2].$flatValue[3].'-'.$flatValue[4].$flatValue[5].'-'.$flatValue[6].$flatValue[7];
				}
			}
		}
		return $application;	
	}
		
	public function getApplicationData($type='family',$applicationNumber='US13/486,978',$applicationDate=''){
		// Argument $type='family' or 'legal' 
		$application=$this->applicationNumber2application($applicationNumber,$applicationDate);
		$response=array();
		if ($application){
			$credentials=$this->getCredentialsToken();
			$request=array('url'=>$this->ops['baseUrl'],'method'=>'GET','data'=>array(),'query'=>array(),'options'=>array());
			$request['header']=array('Content-Type'=>'application/x-www-form-urlencoded',
									 'user_app'=>$this->credentials['App Name'],
									 'Authorization'=>'Bearer '.$credentials['access_token']
									 );
			$request['resource']='rest-services/'.$type.'/application/'.$application['document-id-type'].'/'.$application['country'].'.'.$application['doc-number'].'.'.$application['kind'];
			$response=$this->oc['SourcePot\Datapool\Tools\NetworkTools']->request($request);
		}
		return $response;	
	}	
	
}
?>