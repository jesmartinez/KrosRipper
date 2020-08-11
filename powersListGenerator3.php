<?php
require "vendor/autoload.php";
use Masterminds\HTML5;
use QueryPath\QueryPath;
include_once "powers.php";

$GLOBALS["noPowerList"] = [];
$GLOBALS["powerList"] = [];

$lang = "FR";
function getList(){
  $url = 'http://web.archive.org/web/20191019035011/http://krosfinder.com/fr/powers';
  $html = file_get_contents($url);
  $dom = new HTML5();
  $dom = $dom->loadHTML($html);
  $qp = qp($dom, NULL, array('ignore_parser_warnings' => TRUE));

  $powers = [];
  foreach($qp->find('.list-group a') as $list) {
    if ($list->attr("href") === "") {
    } else {
      array_push($powers, ["href" => $list->attr("href")]);
    }
  }
  $powerList = [];
  $noPowerList = [];
  foreach ($powers as $key => $power) {
    $p = getPower($power["href"]);

    if($p === FALSE){
      array_push($noPowerList, $power["href"]);
    } else {
      array_push($powerList, $p);
    }

    // break; //SOLO POR QUE HAGA UNA VUELTA
  }
  $GLOBALS["powerList"] = $powerList;
  $GLOBALS["noPowerList"] = $noPowerList;
}

getList();
echo json_encode($GLOBALS["powerList"]);

$fp = fopen('./powers/'.$lang.'.json', 'w');
fwrite($fp, json_encode($GLOBALS["powerList"]));
fclose($fp);
$fp = fopen('./powers/'.$lang.'-fail.json', 'w');
fwrite($fp, json_encode($GLOBALS["noPowerList"]));
fclose($fp);
?>
