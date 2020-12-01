<?php
require "vendor/autoload.php";
use Masterminds\HTML5;
use QueryPath\QueryPath;

function getAdditionalSpellInfo($oUrl){
    $url = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
      return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
    }, $oUrl);
    // echo("-".$url);
    $url = str_replace("\\", "", $url);
    $url = str_replace("è", urlencode("è"), $url);
    echo $url;
    $headers = get_headers($url);
    $code = substr($headers[0], 9, 3);
    if ($code == 404 || $code == 502) {
      return FALSE;
    }
    $html = file_get_contents($url);
    //TODO: CATCH 404
    if ($html === FALSE) {
      return FALSE;
    }
    $dom = new HTML5();
    $dom = $dom->loadHTML($html);
    $qp = qp($dom, NULL, array('ignore_parser_warnings' => TRUE));
    echo $url."<br/>";

    $spellInfo = [];
    $spellInfo["id"] = "";
    $spellInfo["name"] = "";
    $spellInfo["description"] = "";

    $id = explode("/", $url);
    $id = $id[count($id)-1];
    if($id !== "magot-sacrè")
      $spellInfo["id"] = $id;
    else
      $spellInfo["id"] = "magot-sacre";

    foreach ($qp->top('h1') as $item) {
        $spellInfo["name"] = $item->innerHTML();
        $spellInfo["name"] = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
          return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
        }, $spellInfo["name"]);
        break;
    }
    foreach ($qp->top('.media-body')->find("p") as $item) {
        $spellInfo["description"] = $item->innerHTML();
        $spellInfo["description"] = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
          return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
        }, $spellInfo["description"]);
        break;
    }
    return $spellInfo;
}

// print_r(getPower("http:\/\/web.archive.org\/web\/20200117005404\/http:\/\/krosfinder.com\/en\/power\/magot-sacr\u00e8"));
?>
