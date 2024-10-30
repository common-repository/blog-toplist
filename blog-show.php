<?php
include_once('../../../wp-load.php');

$cid = $_GET['top'];

echo 'jQuery(\'#'.$cid.'\').replaceWith(\''.bloglist_show($cid).'\');';

?>