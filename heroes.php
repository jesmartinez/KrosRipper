<?php
require "vendor/autoload.php";
use Masterminds\HTML5;
use QueryPath\QueryPath;

function getHeroe($url){
    $html = file_get_contents($url);
    //TODO: CATCH 404
    if ($html === FALSE) {
      return FALSE;
    }
    $dom = new HTML5();
    $dom = $dom->loadHTML($html);
    $qp = qp($dom, NULL, array('ignore_parser_warnings' => TRUE));

    //HEROE CONSTRUCT
    $heroe = [];
    $heroe["name"] = "";
    $heroe["classes"] = [];
    $heroe["attributes"] = [];
    $heroe["description"] = "";
    $heroe["spells"] = [];
    $heroe["powers"] = [];
    $heroe["summonList"] = [];
    $heroe["imageList"] = [];
    //END HEROE CONSTRUCT

    //SETTING NAME:
    foreach ($qp->top('h1[class="kmname"]') as $item) {
      $heroe["name"] = $item->text();
    }

    //SETTING CLASSES:
    $classes = [];
    foreach ($qp->top('h1[class="kmname"] ~ div > a') as $key=>$item) {
      $classes[$key] = ["name"=>$item->text(), "link"=>$item->attr('href')];
    }
    $heroe["classes"] = $classes;

    //SETTING ATTRIBUTES:
    $arrayObj = [];
    foreach ($qp->top('table.hero-indicator tr > td *') as $item) {
      array_push($arrayObj, $item->text());
    }
    $arrayObj = array_reverse($arrayObj);

    $attributes = [];
    $attributes[$arrayObj[0]] = $arrayObj[1];
    $attributes[$arrayObj[2]] = $arrayObj[3];
    $attributes[$arrayObj[4]] = $arrayObj[5];
    $attributes[$arrayObj[6]] = $arrayObj[7];
    $attributes[$arrayObj[8]] = $arrayObj[9];
    $heroe["attributes"] = $attributes;

    //SETTING DESCRIPTION:
    foreach ($qp->top('em[itemprop="description"]') as $item) {
      $heroe["description"] = $item->text();
    }

    //POWERS:
    foreach ($qp->top('h4._visible-print ~ div.panel-info .panel-body') as $key=>$power) {
      foreach($power->find("a") as $link){
        //Link Transform
        $link->removeAttr('data-content');
        $link->removeAttr('data-toggle');
        $link->removeAttr('sef');

        $importantUrl = substr($link->attr('href'), strpos($link->attr('href'), "power"));
        $link->attr('href', $importantUrl);
      }
      $heroe["powers"] = $power->html();
    }

    //SUMMON:
    $summonList = [];
    foreach ($qp->top('h4._visible-print ~ div.panel-deafult .panel-body') as $key=>$summon) {
      foreach($summon->find("a") as $link){
        //TODO: REVISAR LOS LINKS QUE ESTAN MAL COMO LOS DEL "Emperador Gelax"
        $importantUrl = substr($link->attr('href'), strpos($link->attr('href'), "token"));
        $link->attr("href", $importantUrl);
        array_push($summonList, $link->attr("href"));
      }
    }
    $heroe["summonList"] = $summonList;

    //IMAGENES:
    $imgList = [];
    foreach ($qp->top('h5.visible-print ~ img') as $key=>$img) {
      array_push($imgList, $img->attr("src"));
    }
    $heroe["imageList"] = $imgList;

    return $heroe;
}
?>
