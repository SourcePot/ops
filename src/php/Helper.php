<?php
/*
* @package OPS
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2024 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit MIT
*/

declare(strict_types=1);

namespace SourcePot\OPS;

final class Helper{

    public const ONEDIMSEPARATOR='|[]|';
    
    function __construct()
    {
        
    }

    public function value2html(array $val,string $caption='Caption'):string
    {
        $matrix=$this->arr2matrix($val);
        $html='<table>';
        $html.='<caption>'.$caption.'</caption>';
        foreach($matrix as $key=>$values){
            $html.='<tr>';
            foreach($values as $column=>$value){
                if (is_bool($value)){
                    $value=($value)?'TRUE':'FALSE';
                }
                if ($column==='value'){
                    $html.='<td>'.$value.'</td></tr>';
                } else {
                    $html.='<td>'.$value.'</td>';
                }
            }
        }
        $html.='</table>';        
        return $html;
    }

    /**
    * @return array This method returns an array which is a matrix used to create an html-table and a representation of the provided array.
    */
    public function arr2matrix(array $arr,string $S=self::ONEDIMSEPARATOR,$previewOnly=FALSE):array
    {
        $matrix=[];
        $previewRowCount=3;
        $rowIndex=0;
        $rows=[];
        $maxColumnCount=0;
        foreach($this->arr2flat($arr) as $flatKey=>$value){
            $columns=explode($S,strval($flatKey));
            $columnCount=count($columns);
            $rows[$rowIndex]=['columns'=>$columns,'value'=>$value];
            if ($columnCount>$maxColumnCount){$maxColumnCount=$columnCount;}
            $rowIndex++;
        }
        foreach($rows as $rowIndex=>$rowArr){
            for($i=0;$i<$maxColumnCount;$i++){
                $key='';
                if (isset($rowArr['columns'][$i])){
                    if ($rowIndex===0 ){
                        $key=$rowArr['columns'][$i];
                    } else if (isset($rows[($rowIndex-1)]['columns'][$i])){
                        if (strcmp($rows[($rowIndex-1)]['columns'][$i],$rowArr['columns'][$i])===0){
                            $key='&#10149;';
                        } else {
                            $key=$rowArr['columns'][$i];
                        }
                    } else {
                        $key=$rowArr['columns'][$i];
                    }
                }
                if ($previewOnly && $rowIndex>$previewRowCount){
                    $matrix[''][$i]='...';
                } else {
                    $matrix[$rowIndex][$i]=$key;
                }
            }
            if ($previewOnly && $rowIndex>$previewRowCount){
                $matrix['']['value']='...';
            } else {
                $matrix[$rowIndex]['value']=$rowArr['value'];
            }
        }
        return $matrix;
    }

    /**
    * @return arr This method converts an array to the corresponding flat array.
    */
    public function arr2flat(array $arr,string $S=self::ONEDIMSEPARATOR):array
    {
        if (!is_array($arr)){return $arr;}
        $flat=[];
        $this->arr2flatHelper($arr,$flat,'',$S);
        return $flat;
    }
    
    private function arr2flatHelper($arr,&$flat,$oldKey='',string $S=self::ONEDIMSEPARATOR)
    {
        $result=[];
        foreach ($arr as $key=>$value){
            if (strlen(strval($oldKey))===0){$newKey=$key;} else {$newKey=$oldKey.$S.$key;}
            if (is_array($value)){
                $result[$newKey]=$this->arr2flatHelper($value,$flat,$newKey,$S);
                if (empty($value) && is_array($value)){
                    $result[$newKey]='{}';
                    $flat[$newKey]='{}';
                }
            } else {
                $result[$newKey]=$value;
                $flat[$newKey]=$value;
            }
        }
        return $result;
    }

}
?>