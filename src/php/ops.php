<?php
/*
* This file is part of the OPS package.
* @package OPS
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2023 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit/ MIT
*/
declare(strict_types=1);

namespace SourcePot\OPS;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Stream\Stream;

class ops implements OpsInterface{

    const OPS_URL='https://ops.epo.org';
    
    private $client=NULL;
    private $helperObj=NULL;

    private $accessToken=[];
    
    public function __construct(private string $appName,private string $consumerKey,private string $consumerSecretKey)
    {
        $this->helperObj= new Helper();
        $this->client = new \GuzzleHttp\Client(['base_uri'=>self::OPS_URL]);
        $this->renewAccessToken();
    }
	
    public function getEPapplicationMeta($application):array
    {
        preg_match('/([a-zA-Z]{0,2})([0-9]+)\.{0,1}([0-9]{0,1})/',$application,$match);
        if (empty($match[0])){
            return ['error'=>'Invalid EP application format'];
        }
        $meta=['application'=>$match[0],'cc'=>strtoupper($match[1]),'number'=>$match[2],'providedCheckDigit'=>$match[3]];
        if ($meta['cc']!=='EP' && $meta['cc']!==''){
            return ['error'=>'Not an EP application'];
        }
        preg_match('/\-([0-9]{4})/',$application,$match);
        $meta['examiningDirectorate']=$match[1]??'';
        if (strlen($meta['number'])==10){
            $meta['year']=substr($meta['number'],0,4);
        } else if (strlen($meta['number'])==8){
            $meta['year']=substr($meta['number'],0,2);
            if (intval($meta['year']>76)){
                $meta['year']='19'.$meta['year'];
            } else {
                $meta['year']='20'.$meta['year'];
            }
        } else {
            $meta['error']='Invalid number format';
            return $meta;
        }
        $basenumberArr=str_split(substr($meta['number'],-8));
        $sumOfDigits=0;
        for($i=count($basenumberArr)-1;$i>=0;$i--){
            $multiplier=(($multiplier??1)==1)?2:1;
            $multResArr=str_split(strval(intval($basenumberArr[$i])*$multiplier));
            foreach($multResArr as $multRes){
                $sumOfDigits+=$multRes;
            }
        }
        $meta['checkDigit']=10-$sumOfDigits%10;
        if ($meta['providedCheckDigit']!=='' && intval($meta['providedCheckDigit'])!=$meta['checkDigit']){
            $meta['warning']='Invalid provided check digit';
        }
        return $meta;
    }

    public function renewAccessToken($uri='/3.2/auth/accesstoken'):array|bool
    {
        $options=['headers'=>['Accept'=>'application/json',
                            'content-type'=>'application/x-www-form-urlencoded',
                            'user_app'=>$this->appName,
                            'Authorization'=>'Basic '.base64_encode($this->consumerKey.':'.$this->consumerSecretKey)
                            ],
                'form_params'=>['grant_type'=>'client_credentials']
                ];
        try{
            $response=$this->client->request('POST',$uri,$options);
            $accessToken=json_decode($response->getBody()->getContents(),TRUE);
            $this->accessToken=($accessToken)?$accessToken:['error'=>'Problem decoding json-response'];
        } catch (\Exception $e){
            $this->accessToken=['error'=>trim(strip_tags($e->getMessage()))];
        }
        $this->accessToken['timestamp']=time();
        return $this->accessToken;
    }
    
    private function isValidAccessToken():bool
    {
        if (isset($this->accessToken['timestamp']) && isset($this->accessToken['expires_in'])){
            $lifeTime=$this->accessToken['timestamp']+$this->accessToken['expires_in']-time();
            if ($lifeTime>5){return TRUE;}
        }
        return FALSE;
    }

    public function publishedDataSearch(array $query=['pa'=>'Wallenhauer']):array
    {
        $response=$this->request('POST','rest-services/biblio/search',['headers'=>['Accept'=>'application/register+xml'],'body'=>'q=pa%3DWallenhauer']);
        return $response;
    }
    
    public function publishedDataServices(string $referenceType='publication',string $inputFormat='epodoc',string $endpoint='images',string $input='EP1000000.A1',array $query=[]):array
    {
        $response=$this->request('POST','rest-services/published-data/'.$referenceType.'/'.$inputFormat.'/'.$endpoint,['body'=>$input,'query'=>$query]);
        return $response;
    }
    
    public function familyService()
    {
        
    }

    public function numberService()
    {
        
    }
    
    public function registerService()
    {
        
    }
    
    public function legalService()
    {
        
    }
    
    public function classificationServices()
    {
        
    }

    public function request(string $type='GET',string $uri='rest-services/number-service/application/original/(CA2887009)/docdb',$arr=[]):array|bool
    {
        if (!$this->isValidAccessToken()){
            $this->renewAccessToken();
        }
        if (empty($this->accessToken['error'])){
            $options=['headers'=>['Accept'=>'application/json',
                                'content-type'=>'text/plain',
                                'user_app'=>$this->appName,
                                'Authorization'=>'Bearer '.$this->accessToken['access_token']
                                ]
                    ];
            $options=array_replace_recursive($options,$arr);
            try{
                $response=$this->client->request($type,'/'.$uri,$options);
                $headers=$this->header2arr($response->getHeaders());
                //var_dump($headers);
                if (isset($headers['content-type'])){
                    if (strpos($headers['content-type'],'html')!==FALSE){
                        return ['html'=>$response->getBody()->getContents()];
                    } else if (strpos($headers['content-type'],'json')!==FALSE){
                        $response=json_decode($response->getBody()->getContents(),TRUE,512,JSON_INVALID_UTF8_IGNORE);
                        return $this->response2arr($uri,$response);                    
                    } else if (strpos($headers['content-type'],'xml')!==FALSE){
                        $responseArr=$this->xml2arr($response->getBody()->getContents());
                        return $this->response2arr($uri,$responseArr);                    
                    } else {
                        return ['error'=>'"content-type" '.$headers['content-type'].' not yet implemented.'];
                    }
                } else {
                    return ['error'=>'Header "content-type" missing'];
                }
            } catch (\Exception $e){
                return ['error'=>trim(strip_tags($e->getMessage()))];
            }
        } else {
            return ['error'=>$this->accessToken['error']];
        }
    }

    private function response2arr($uri,$response):array
    {
        if (is_array($response)){
            $response=$this->helperObj->arr2flat($response);
            $response=$this->unifyOutput($uri,$response);
        } else {
            $response=['error'=>'Data format error'];
        }
        return $response;
    }
    
    private function header2arr(array $headers):array
    {
        $arr=[];
        foreach($headers as $key=>$header){
            $key=strtolower($key);
            foreach($header as $index=>$value){
                $values=explode(';',$value);
                foreach($values as $subIndex=>$keyValue){
                    $tmpComps=explode('=',$keyValue);
                    if (count($tmpComps)===2){
                        $arr[$key.'|'.$tmpComps[0]]=$tmpComps[1];
                    } else {
                        if (isset($arr[$key])){
                            if (!is_array($arr[$key])){
                                $arr[$key]=[0=>$arr[$key]];
                            }
                            $arr[$key][]=$keyValue;
                        } else {
                            $arr[$key]=$keyValue;
                        }
                    }
                }
            }
        }
        return $arr;
    }

    /*  Many thanks to 
    *   http://php.net/manual/en/class.simplexmlelement.php#108867
    */
    private function normalize_xml2array($obj,&$result)
    {
        $data=$obj;
        if (is_object($data)){
            $data=get_object_vars($data);
            foreach($obj->getDocNamespaces() as $ns_name=>$ns_uri){
                if ($ns_name===''){continue;}
                $ns_obj=$obj->children($ns_uri);
                foreach(get_object_vars($ns_obj) as $k=>$v){
                    $data[$ns_name.':'.$k]=$v;
                }
            }
        }
        if (is_array($data)){
            foreach ($data as $key=>$value){
                $res=null;
                $this->normalize_xml2array($value,$res);
                $result[$key]=$res;
            }
        } else {
            $result=$data;
        }
    }
    
    private function xml2arr(string $xml):array|bool
    {
        $arr=['xml'=>$xml];
        if (extension_loaded('SimpleXML')){
            $this->normalize_xml2array(simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA),$result);
            $json=json_encode($result);
            return ['ops:world-patent-data'=>json_decode($json,TRUE)];
        } else {
            throw new \ErrorException('Function '.__FUNCTION__.': PHP extension SimpleXML missing.',0,E_ERROR,__FILE__,__LINE__);
            return FALSE;
        }
    }
    
    private function key2subkey($key):string
    {
        if (!is_array($key)){
            $helperObj= new Helper();
            $key=explode($this->helperObj::ONEDIMSEPARATOR,$key);
        }
        $subKey=array_pop($key);
        if ($subKey=='$'){$subKey=array_pop($key);}
        return trim(strval($subKey),'@');
    }
    
    private function unifyOutput($uri,$flatArr):array
    {
        $uriComps=explode('/',$uri);
        $returnFlatArr=['service'=>$uriComps[1]];
        foreach($flatArr as $key=>$value){
            $value=trim(strval($value));
            // replace warning/error codes
            $skipKey=FALSE;
            foreach(self::CODES as $code=>$typeMsgArr){
                if (strpos($value,$code)!==FALSE){
                    $value=str_replace($code,'',$value);
                    $skipKey=TRUE;
                    $returnFlatArr[$typeMsgArr['type']]=(isset($returnFlatArr[$typeMsgArr['type']]))?$returnFlatArr[$typeMsgArr['type']].'|'.$typeMsgArr['msg']:$typeMsgArr['msg'];
                }
            }
            if ($skipKey){continue;}
            // compile values
            switch ($returnFlatArr['service']){
                case 'number-service':
                    $keyComps=explode($this->helperObj::ONEDIMSEPARATOR,$key);
                    if ($keyComps[2]=='ops:output'){
                        $subKey=$this->key2subkey($keyComps);
                        $returnFlatArr[$subKey]=$value;
                    }
                    break;
                case 'family':
                    $keyComps=explode($this->helperObj::ONEDIMSEPARATOR,$key);
                    $memberIndex=(isset($keyComps[3]))?intval($keyComps[3]):0;
                    if ($keyComps[2]=='ops:family-member' && $keyComps[4]=='ops:legal' && isset($keyComps[4])){
                        $eventIndex=intval($keyComps[5]);
                        $subKey=$this->key2subkey($keyComps);
                        $returnFlatArr[$memberIndex]['events'][$eventIndex][$subKey]=$value;
                    } else if (strpos($key,'@total-result-count')!==FALSE){
                        $returnFlatArr['total-result-count']=$value;
                    } else if ((strpos($key,'document-id')!==FALSE || strpos($key,'priority-active-indicator')!==FALSE) && 
                               (strpos($key,$this->helperObj::ONEDIMSEPARATOR.'publication-reference')!==FALSE || strpos($key,$this->helperObj::ONEDIMSEPARATOR.'application-reference')!==FALSE || strpos($key,$this->helperObj::ONEDIMSEPARATOR.'priority-claim'))){
                        $docType=$keyComps[4];
                        $docKey=$keyComps[5];
                        $comps=explode($docType.$this->helperObj::ONEDIMSEPARATOR.$docKey.$this->helperObj::ONEDIMSEPARATOR,$key);
                        $subKey=$this->key2subkey($comps[1]);
                        if ($subKey=='document-id-type'){$documentIdType=$value;}
                        if (!isset($documentIdType)){$documentIdType='?';}
                        $returnFlatArr[$memberIndex][$docType][$documentIdType][$subKey]=$value;
                        if ($subKey=='date' && strlen($value)===8){
                            $returnFlatArr[$memberIndex][$docType][$documentIdType][$subKey]=substr($value,0,4).'-'.substr($value,4,2).'-'.substr($value,6,2);
                        }
                    } else if (strpos($key,'@family-id')!==FALSE){
                        $returnFlatArr['family-id']=$value;
                        $returnFlatArr[$memberIndex]['family-id']=$value;
                    } else if (strpos($key,'@doc-id')!==FALSE){
                        $returnFlatArr[$memberIndex]['doc-id']=$value;
                    }
                    break;
                case 'register':
                    $returnFlatArr[$key]=$value;
                    break;
                default:
                    $returnFlatArr[$key]=$value;
            }
        }
        return $returnFlatArr;
    }
}
?>