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
	
	public function getFamily(){
		$credentials=$this->getCredentialsToken();
		$request=array();
		$request['header']=array('Content-Type'=>'application/x-www-form-urlencoded',
								 'user_app'=>$this->credentials['App Name'],
								 'Authorization'=>'Bearer '.$credentials['access_token']
								 );
		$request['method']='GET';
		$request['url']=$this->ops['baseUrl'];
		$request['resource']='rest-services/family/publication/docdb/EP.1000000.A1/biblio,legal';
		$request['data']=array();
		$request['query']=array();
		$request['options']=array();
		$response=$this->oc['SourcePot\Datapool\Tools\NetworkTools']->request($request);
		//$this->oc['SourcePot\Datapool\Tools\MiscTools']->arr2file($response);
		return $response;
	}
	
	
}
?>