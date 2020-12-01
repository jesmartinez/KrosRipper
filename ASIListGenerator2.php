<?php
require "vendor/autoload.php";
use Masterminds\HTML5;
use QueryPath\QueryPath;
include_once "additionalSpellInfo.php";

$GLOBALS["noAInfoList"] = [];
$GLOBALS["AInfoList"] = [];

$lang = "IT";
function getList(){
  $url = 'https://web.archive.org/web/20191020133318/http://krosfinder.com/it/spell-effects';
  $html = file_get_contents($url);
  $dom = new HTML5();
  $dom = $dom->loadHTML($html);
  $qp = qp($dom, NULL, array('ignore_parser_warnings' => TRUE));

  $ainfos = [];
  foreach($qp->find('table.table-bordered a') as $list) {
    if ($list->attr("href") === "") {
    } else {
      array_push($ainfos, ["href" => $list->attr("href")]);
    }
  }
  $AInfoList = [];
  $noAInfoList = [];
  foreach ($ainfos as $key => $ainfo) {
    $p = getAdditionalSpellInfo($ainfo["href"]);

    if($p === FALSE){
      array_push($noAInfoList, $ainfo["href"]);
    } else {
      array_push($AInfoList, $p);
    }

    // break; //SOLO POR QUE HAGA UNA VUELTA
  }
  $GLOBALS["AInfoList"] = $AInfoList;
  $GLOBALS["noAInfoList"] = $noAInfoList;
}

getList();
echo json_encode($GLOBALS["AInfoList"]);

$fp = fopen('./AdditionalSpellInfo/'.$lang.'.json', 'w');
fwrite($fp, json_encode($GLOBALS["AInfoList"]));
fclose($fp);
$fp = fopen('./AdditionalSpellInfo/'.$lang.'-fail.json', 'w');
fwrite($fp, json_encode($GLOBALS["noAInfoList"]));
fclose($fp);
?>
