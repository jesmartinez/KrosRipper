<?php
require "vendor/autoload.php";
use Masterminds\HTML5;
use QueryPath\QueryPath;

function getHeroe($url){
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
    $heroe["oldURL"] = $url;
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

    //SETTING SPELLS:
    // echo "<br/>HECHIZOS";
    $arraySpells = [];
    foreach ($qp->top('h4._visible-print ~ table') as $keyParent=>$spell) {
      $arraySpells[$keyParent] = [];
      foreach ($spell->find('tr') as $keyChild=>$tr) {
        // echo "<br/>[".$keyParent."/".$keyChild."]" . " <br/> ";
        if ($keyChild === 1) {
          //DESCRIPCIÓN DEL HECHIZO
          //Link Transform
          foreach($tr->find("a") as $link){
            $link->removeAttr('data-content');
            $link->removeAttr('data-toggle');
            $link->removeAttr('sef');

            $importantUrl = substr($link->attr('href'), strpos($link->attr('href'), "spell-effects"));
            $link->attr('href', $importantUrl);
          }
          //Final result
          $arraySpells[$keyParent]["effects"] = $tr->html();
        } else if($keyChild === 0) {
          $arraySpells[$keyParent]["attr"] = [];
          foreach($tr->children() as $keyTR=>$content){
            if($keyTR === 0) {
              //TODO: MEDIA TO CONVERT - Attack type
              $arraySpells[$keyParent]["attr"]["typeMedia"] = "https:".$content->firstChild()->attr('src');
            } else if($keyTR === 1) {
              //NOMBRE DEL HECHIZO
              $arraySpells[$keyParent]["attr"]["name"] = "<br/>".$content->find("strong")->first()->text();
              //COSTE
              // echo "<br/>COSTE:";
              $costs = [];
              foreach($content->find(".pull-right strong") as $keyCost=>$cost){
                $classname = str_replace("kf-ico kf-ico-", "", $cost->siblings()->first()->attr('class'));
                $costs[$keyCost] = ["type"=>$classname, "cost"=>$cost->text()];
              }
              $arraySpells[$keyParent]["attr"]["costs"] = $costs;
              // print_r($costs);
            } else if($keyTR === 2) {
              //PODER
              $type = $classname = str_replace("damage ", "", $content->firstChild()->attr('class'));
              $arraySpells[$keyParent]["attr"]["damage"] = ["dmg"=>$content->text(), "type"=>$type];
              // echo "<br/>DAÑO:";
              // print_r(["dmg"=>$content->text(), "type"=>$type]);
            }
          }
        }
      }
    }
    $heroe["spells"] = $arraySpells;
    
    return $heroe;
}
?>
