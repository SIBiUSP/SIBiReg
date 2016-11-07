<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
ini_set("display_errors", 1);

include_once('config.php');

if(filter_input(INPUT_GET,'logout') == 'yes'){
  header('Location: '.SAMLUSPSIBIURL.'?logout=yes');
  exit;
}
elseif(filter_input(INPUT_GET,'login') == 'yes'){
  setcookie('SAMLUSPSIBI_DATA[SAMLUSPSIBICURRENTURL]', $_SERVER['SCRIPT_NAME'], 0, "/", ".sibi.usp.br"); 
  header('Location: '.SAMLUSPSIBIURL.'?login=yes');
  exit;
}
elseif((__FILE__ !== $_SERVER['SCRIPT_FILENAME']) && (empty($_COOKIE['SAMLUSPSIBI_DATA']) || (filter_input(INPUT_GET,'login') == 'yes') )){
  setcookie('SAMLUSPSIBI_DATA[SAMLUSPSIBICURRENTURL]', $_SERVER['SCRIPT_NAME'], 0, "/", ".sibi.usp.br");
  header('Location: '.SAMLUSPSIBIURL);
  exit;
}
elseif((__FILE__ == $_SERVER['SCRIPT_FILENAME']) && !empty($_COOKIE['SAMLUSPSIBI_DATA']) && ($_COOKIE['SAMLUSPSIBI_DATA']['ONSESS'] == 'no') ){
  header('Location: '.$_SESSION['HOMEBASE']);
  exit;
}
elseif((__FILE__ == $_SERVER['SCRIPT_FILENAME']) && !empty($_COOKIE['SAMLUSPSIBI_DATA'])){
  header('Location: '.$_COOKIE['SAMLUSPSIBI_DATA']['SAMLUSPSIBICURRENTURL']);
  exit;
}
