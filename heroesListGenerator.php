<?php
require "vendor/autoload.php";
use Masterminds\HTML5;
use QueryPath\QueryPath;

function getList(){
  $html = file_get_contents('https://web.archive.org/web/20190417190320/http://krosfinder.com/es/editions');
  $dom = new HTML5();
  $dom = $dom->loadHTML($html);
  $qp = qp($dom, NULL, array('ignore_parser_warnings' => TRUE));
  $editionList = [];

  foreach($qp->find('table.table-condensed') as $list) {
    return $list->html();
  }

}

echo getList();
?>
