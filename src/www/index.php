<?php
/*
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2023 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit/ MIT
*/
	
declare(strict_types=1);
	
namespace SourcePot\OPS;
	
mb_internal_encoding("UTF-8");

$application=$_POST['application']??'EP20163530A';
$doctype=$_POST['doctype']??'application';
$type=$_POST['type']??'biblio';

require_once('../../vendor/autoload.php');

// load or initialize credentials for OPS access
$credentialsFile='../credentials.json';
if (!is_file($credentialsFile)){
    $credentials=['appName'=>'...','consumerKey'=>'...','consumerSecretKey'=>'...'];
    $credentialsJson=json_encode($credentials);
    file_put_contents($credentialsFile,$credentialsJson);
}
$credentialsJson=file_get_contents($credentialsFile);
$credentials=json_decode($credentialsJson,TRUE);

// compile html
$html='<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" lang="en"><head><meta charset="utf-8"><title>OPS</title><link type="text/css" rel="stylesheet" href="index.css"/></head>';
$html.='<body><form name="892d183ba51083fc2a0b3d4d6453e20b" id="892d183ba51083fc2a0b3d4d6453e20b" method="post" enctype="multipart/form-data">';
$html.='<h1>Open Patent Service Evaluation Page</h1>';
$html.='<div class="control"><h2>Please enter a patent publication or application number</h2>';
$html.='<input type="text" value="'.$application.'" name="application" id="application" style="margin:0.25em;"/>';

$html.='<select name="doctype" id="doctype">';
foreach(['application'=>'application','publication'=>'publication'] as $id=>$name){
    $selected=($id===$doctype)?' selected':'';
    $html.='<option value="'.$id.'"'.$selected.'>'.$name.'</option>';
}
$html.='</select>';

$html.='<select name="type" id="type">';
foreach(['biblio'=>'biblio','legal'=>'legal','Biblio / legal'=>'Biblio / legal'] as $id=>$name){
    $selected=($id===$type)?' selected':'';
    $html.='<option value="'.$id.'"'.$selected.'>'.$name.'</option>';
}
$html.='</select>';

$html.='<input type="submit" name="test" id="set" style="margin:0.25em;" value="Set"/></div>';
$html.='</div>';
$html.='</form>';

require_once('../php/Helper.php');

$helperObj = new Helper();

if ($type==='Biblio / legal'){
    $biblio=new biblio($credentials['appName'],$credentials['consumerKey'],$credentials['consumerSecretKey']);
    $result=$biblio->legal($application);
    $html.=$helperObj->value2html($result,$type.' response');
} else {
    $ops=new ops($credentials['appName'],$credentials['consumerKey'],$credentials['consumerSecretKey']);
    $nsResult=$ops->request('GET','rest-services/number-service/'.$doctype.'/original/('.$application.')/docdb');
    $html.=$helperObj->value2html($nsResult,'Result "Number Service"');
    if ($doctype==='application'){
        $epMeta=$ops->getEPapplicationMeta($application);
        $html.=$helperObj->value2html($epMeta,'EP application meta');
    }
    if (empty($nsResult['error'])){
        // Number service OK
        $result=$ops->request('GET','rest-services/family/'.$doctype.'/docdb/'.$nsResult['country'].'.'.$nsResult['doc-number'].'.'.$nsResult['kind'].'.'.$nsResult['date'].'/'.$type);
        $html.=$helperObj->value2html($result,'Response "'.ucfirst($type).'"');
    } else {
        // Number service failed
        $html.=$helperObj->value2html($nsResult,'Error');
    }
}
$html.='<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>';
$html.='<script src="index.js"></script>';
$html.='</body></html>';
echo $html;


?>