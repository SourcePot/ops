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
$type=$_POST['type']??'biblio';

require_once('../../vendor/autoload.php');

// load or init credentials for OPS access
$credentialsFile='../credentials.json';
if (!is_file($credentialsFile)){
    $credentials=array('appName'=>'...','consumerKey'=>'...','consumerSecretKey'=>'...');
    $credentialsJson=json_encode($credentials);
    file_put_contents($credentialsFile,$credentialsJson);
}
$credentialsJson=file_get_contents($credentialsFile);
$credentials=json_decode($credentialsJson,TRUE);

$ops=new ops($credentials['appName'],$credentials['consumerKey'],$credentials['consumerSecretKey']);

// compile html
$html='<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" lang="en"><head><meta charset="utf-8"><title>OPS</title><link type="text/css" rel="stylesheet" href="index.css"/></head>';
$html.='<body><form name="892d183ba51083fc2a0b3d4d6453e20b" id="892d183ba51083fc2a0b3d4d6453e20b" method="post" enctype="multipart/form-data">';
$html.='<h1>Open Patent Service Evaluation Page</h1>';
$html.='<div class="control"><h2>Please enter a patent or application number</h2>';
$html.='<input type="text" value="'.$application.'" name="application" id="application" style="margin:0.25em;"/>';

$html.='<select name="type" id="type">';
foreach(['biblio'=>'biblio','legal'=>'legal'] as $id=>$name){
    $selected=($id===$type)?' selected':'';
    $html.='<option value="'.$id.'"'.$selected.'>'.$name.'</option>';
}
$html.='</select>';

$html.='<input type="submit" name="test" id="set" style="margin:0.25em;" value="Set"/></div>';
$html.='</div>';
$html.='</form>';

require_once('../php/Helper.php');

$helperObj = new Helper();

$nsResult=$ops->request('GET','rest-services/number-service/application/original/('.$application.')/docdb');
$html.=$helperObj->value2html($nsResult,'Result "Number Service"');
if (isset($nsResult['error'])){
    // Numer service failed
} else {
    //var_dump($ops->request('GET','rest-services/family/application/docdb/EP.20163530.A.20110622/legal'));
    //var_dump($ops->request('GET','rest-services/register/application/epodoc/EP20163530'));
    //var_dump($ops->request('GET','rest-services/family/priority/docdb/US.18314305.A'));
    //var_dump($ops->publishedDataServices());
    //var_dump($ops->publishedDataSearch());
    $result=$ops->request('GET','rest-services/family/application/docdb/'.$nsResult['country'].'.'.$nsResult['doc-number'].'.'.$nsResult['kind'].'.'.$nsResult['date'].'/'.$type);
    $html.=$helperObj->value2html($result,'Response "'.ucfirst($type).'"');
}

$html.='<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>';
$html.='<script src="index.js"></script>';
$html.='</body></html>';
echo $html;


?>