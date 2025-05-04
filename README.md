# PHP API for the Open Patent Service

OPS is a web service which provides access to the European Patent Office's data. You need to have an user account with the European Patent Office to access the service. 
See EPO OPS web page for details: [Link to the EPA web page](https://www.epo.org/en/searching-for-patents/data/web-services/ops 'Link to the EPA web page')

This API consists of a PHP class and an interface as well as helper files. For evaluation purposes this respository can be installed as a "stand-alone" web application on your server by running: `composer create-project sourcepot/ops {add your target directory here}` This allows you to run the evaluation web page.

Alternatively, you can add this respository to your project by adding `..."sourcepot/ops":">=v..."... ` to the list of required external components to your projects `composer.json` file.

## Sample code

```
namespace SourcePot\OPS;
	
mb_internal_encoding("UTF-8");

require_once('../../vendor/autoload.php');

// create the ops object using the login credentials
$ops=new ops($appName,$consumerKey,$consumerSecretKey);

// number service request 
$nService=$ops->request('GET','rest-services/number-service/application/original/(EP20163530A)/docdb');

// family service, get biliographic data
$biblio=$ops->request('GET','rest-services/family/application/docdb/'.$nService['country'].'.'.$nService['doc-number'].'.'.$nService['kind'].'.'.$nService['date'].'/biblio');

var_dump($biblio);
```

## Evaluation Web Page

An evaluation web page is provided with this package. Here is a screenshot of the evaluation web page:

<img src="./assets/evaluation-page.png" alt="Evaluation web page" style="width:100%"/>