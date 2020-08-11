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
    echo $url;

    //HEROE CONSTRUCT
    $heroe = [];
    $heroe["id"] = "";
    $heroe["name"] = "";
    $heroe["rarity"] = 0;
    $heroe["classes"] = [];
    $heroe["attributes"] = [];
    $heroe["description"] = "";
    $heroe["spells"] = [];
    $heroe["powers"] = [];
    $heroe["summonList"] = [];
    $heroe["imageList"] = [];
    $heroe["figurineImage"] = "";
    $heroe["season"] = "";
    $heroe["edition"] = "";
    $heroe["oldURL"] = $url;
    $heroe["eternal"] = 0;
    $heroe["legal"] = FALSE;

    //END HEROE CONSTRUCT

    //SETTING NAME:
    foreach ($qp->top('h1[class="kmname"]') as $item) {
      $heroe["name"] = $item->text();
    }
    //SETTING SEASON, EDITION AND ID
    foreach ($qp->top('ol[class="breadcrumb"] li a') as $item) {
      if(strpos($item->attr("href"), "editions#"))
        $heroe["season"] = substr($item->attr("href"), (strpos($item->attr("href"), "editions#") + 9));
      elseif (strpos($item->attr("href"), "/ed/"))
        $heroe["edition"] = substr($item->attr("href"), (strpos($item->attr("href"), "/ed/") + 4));
    }
    $heroe["id"] = $heroe["season"]."-".$heroe["edition"]."-".substr($url, strripos($url, "/")+1);

    //SETTING RARITY
    foreach ($qp->top('div[class="nameclr-1"]') as $item) {
      $heroe["rarity"] = 3; //RARE - GOLD
    }
    foreach ($qp->top('div[class="nameclr-2"]') as $item) {
      $heroe["rarity"] = 2; //UNCOMMON - WHITE
    }
    foreach ($qp->top('div[class="nameclr-3"]') as $item) {
      $heroe["rarity"] = 1; //COMMON - BLACK
    }

    //SETTING CLASSES:
    $classes = [];
    foreach ($qp->top('h1[class="kmname"] ~ div > a') as $key=>$item) {
      $classes[$key] = ["name"=>$item->text(), "link"=>substr($item->attr('href'), strripos($item->attr('href'), "/")+1)];
    }
    $heroe["classes"] = $classes;

    //SETTING ATTRIBUTES:
    $arrayObj = [];
    foreach ($qp->top('table.hero-indicator tr > td *') as $item) {
      array_push($arrayObj, $item->text());
    }
    $arrayObj = array_reverse($arrayObj);

    $attributes = [];
    $attributes["AP"] = $arrayObj[1];
    $attributes["HP"] = $arrayObj[3];
    $attributes["MP"] = $arrayObj[5];
    $attributes["initiative"] = $arrayObj[7];
    $attributes["level"] = $arrayObj[9];
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
      $heroe["powers"] = $power->innerHTML();
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
      array_push($imgList, substr($img->attr("src"), strripos($img->attr("src"), "/")));
    }
    $heroe["imageList"] = $imgList;

    foreach ($qp->top('#headback img') as $key=>$img) {
      if (strpos($img->attr("src"), "/figurine/") !== FALSE) {
        $heroe["figurineImage"] = substr($img->attr("src"), strripos($img->attr("src"), "/"));
        break;
      }
    }

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
          $arraySpells[$keyParent]["effects"] = $tr->find("td")->innerHTML();
        } else if($keyChild === 0) {
          $arraySpells[$keyParent]["attr"] = [];
          foreach($tr->children() as $keyTR=>$content){
            if($keyTR === 0) {
              //TODO: MEDIA TO CONVERT - Attack type
              $rangeRaw = substr($content->firstChild()->attr('src'), strripos($content->firstChild()->attr('src'), "/")+1);
              $rangeType = substr($rangeRaw,0,3);
              $rangeNumber = "";
              if (strlen($rangeRaw)>7) {
                $rangeNumber = str_replace("_", " - ", substr($rangeRaw,4,-4));
              }
              $arraySpells[$keyParent]["attr"]["rangeType"] = $rangeType;
              $arraySpells[$keyParent]["attr"]["rangeNumber"] = $rangeNumber;

            } else if($keyTR === 1) {
              //NOMBRE DEL HECHIZO
              $arraySpells[$keyParent]["attr"]["name"] = $content->find("strong")->first()->text();
              $arraySpells[$keyParent]["attr"]["useBG"] = $content->find("strong")->first()->parent()->attr("class");
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

    //SETTING LEGAL
    $legalTypes = $qp->top('.panel.panel-default ul.list-group.card-lang');
    if ($legalTypes->firstChild()->firstChild()->attr("class") == "text-success")
      $heroe["eternal"] = count($legalTypes->firstChild()->lastChild()->children());

    if ($legalTypes->lastChild()->firstChild()->attr("class") == "text-success")
      $heroe["legal"] = TRUE;

    getImages($qp->top('#headback img'));
//list-group card-lang
    return $heroe;
}

function getImages($query){
  foreach ($query as $key=>$img) {
    // echo $img->attr('src') . "<br/>";
  array_push($GLOBALS["imgList"], $img->attr("src")/*substr($img->attr("src"), strripos($img->attr("src"), "/"))*/);
  }
}
?>
