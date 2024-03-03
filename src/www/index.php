<?php
/*
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2023 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit/ MIT
*/
	
declare(strict_types=1);
	
namespace SourcePot\OPS;
	
mb_internal_encoding("UTF-8");

require_once('../../vendor/autoload.php');
require_once('../php/OpsInterface.php');
require_once('../php/ops.php');

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

var_dump($ops->request('GET','rest-services/number-service/application/original/(EP20163530A)/docdb'));
//var_dump($ops->request('GET','rest-services/family/application/docdb/EP.20163530.A.20110622/legal'));
var_dump($ops->request('GET','rest-services/register/application/epodoc/EP20163530'));

//var_dump($ops->request('GET','rest-services/family/priority/docdb/US.18314305.A'));
//var_dump($ops->publishedDataServices());
//var_dump($ops->publishedDataSearch());


?>