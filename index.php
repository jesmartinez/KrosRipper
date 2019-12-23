<?php
  require "vendor/autoload.php";
  use Masterminds\HTML5;
  use QueryPath\QueryPath;

  $heroe = [];

  $html = file_get_contents('https://web.archive.org/web/20190417190134/http://krosfinder.com/es/ed/botf/2');
  $dom = new HTML5();
  $dom = $dom->loadHTML($html);
  $qp = qp($dom, NULL, array('ignore_parser_warnings' => TRUE));

  //SETTING NAME:
  foreach ($qp->top('h1[class="kmname"]') as $item) {
    $heroe["name"] = $item->text();
    echo "Name: ".$item->text()."<br/>";
  }
  //SETTING CLASSES:
  echo "Classes: ";
  $classes = [];
  foreach ($qp->top('h1[class="kmname"] ~ div > a') as $key=>$item) {
    $classes[$key] = ["name"=>$item->text(), "link"=>$item->attr('href')];
    // echo "[".$item->text()."] - Link: ". $item->attr('href') . "<br/>";
  }
  print_r($classes);
  echo "<br/>";

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
  echo "Attributes: ";
  print_r($attributes);
  echo "<br/>";

  //SETTING DESCRIPTION:
  foreach ($qp->top('em[itemprop="description"]') as $item) {
    $heroe["description"] = $item->text();
    echo "Description: ".$item->text()."<br/>";
  }

  //SETTING SPELLS:
  $arrayObj = [];
  foreach ($qp->top('h4._visible-print ~ table') as $key=>$item) {
    foreach ($item->children() as $key=>$spell) {
      foreach ($spell->children() as $key=>$child) {
        echo print_r($child->text()) . " - ";
      }
    }
  }
  // print_r($arrayObj);

?>
