<?php
require "vendor/autoload.php";
use Masterminds\HTML5;
use QueryPath\QueryPath;
include_once "heroes.php";

function getList(){
  $html = file_get_contents('https://web.archive.org/web/20190417190320/http://krosfinder.com/es/editions');
  $dom = new HTML5();
  $dom = $dom->loadHTML($html);
  $qp = qp($dom, NULL, array('ignore_parser_warnings' => TRUE));
  $editionList = [];
  $season = 0;
  foreach($qp->find('table.table-condensed a') as $list) {
    if ($list->attr("href") === "") {
      $season++;
    } else {
      array_push($editionList, ["href" => $list->attr("href"), "edition"=>$list->attr("title"), "season"=>$season]);
    }
  }
  // print_r($editionList);
  foreach ($editionList as $key => $edition) {
    print_r(getHeroeList($edition["href"]));
    // break; //SOLO POR QUE HAGA UNA VUELTA
  }
}

function getHeroeList($url){
  $html = file_get_contents($url);
  $dom = new HTML5();
  $dom = $dom->loadHTML($html);
  $qp = qp($dom, NULL, array('ignore_parser_warnings' => TRUE));

  $heroList = [];
  $noHeroList = [];

  foreach($qp->top('table.table-bordered tr td a') as $hero) {
    $cleanUrl = substr($url, strpos($url, "krosfinder.com"));
    $cleanLink = substr($hero->attr("href"), strpos($hero->attr("href"), "krosfinder.com"));
    if ($cleanUrl !== $cleanLink) {
        $thisHero = getHeroe($hero->attr("href"));
        if ($thisHero) {
          array_push($heroList, $thisHero);
        } else {
          array_push($noHeroList, $hero->attr("href"));
        }
    }
  }
  echo "<div style='background-color: palevioletred'>";
  print_r($noHeroList);
  echo "</div>";
  return $heroList;
}

getList();
?>
