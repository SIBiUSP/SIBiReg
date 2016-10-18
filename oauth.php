<?php

/*
session_start();
echo "\n<pre>\n";
print_r("olha:[".print_r($_SESSION)."]ok");
echo __FILE__;
echo "\n</pre><hr>\n";
phpinfo();
exit;
*/

if(__FILE__ !== $_SERVER['SCRIPT_FILENAME']){
  if(empty($oauthhomebase)){
    echo 'definir a variável $oauthhomebase com o caminho de retorno antes de incluir este arquivo.';
    exit;
  }
}
else if(!empty($_COOKIE['OA1USPBACKURL'])){
  $oa1uspbackurl = $_COOKIE['OA1USPBACKURL'];
  unset($_COOKIE['OA1USPBACKURL']);
  setcookie('OA1USPBACKURL', '', time()-3600, "/", ".sibi.usp.br");
  header(empty($_SERVER['QUERY_STRING']) ? 'Location: '.$oa1uspbackurl : 'Location: '.$oa1uspbackurl.'?'.$_SERVER['QUERY_STRING']);
  exit;
}

ini_set("display_errors", 1);
include_once('config.php');

session_start();

if(!empty($oauthhomebase) && ( $_SERVER['SCRIPT_NAME'] == $oauthhomebase )){
	session_unset();
	session_destroy();
} else if(empty($_SESSION['dadosusp'])){
 try {

// echo 'passaqui';

	$req_url = OA1USP_REQUEST_URL;
	$authurl = OA1USP_AUTHORIZE_URL;
	$acc_url = OA1USP_ACCESSTOKEN_URL;
	$api_url = OA1USP_API_URL;
	$conskey = OA1USP_CLIENT_KEY;
	$conssec = OA1USP_CLIENT_SECRET;
	$callback_id = OA1USP_CALLBACK_ID;

	$oauth = new OAuth($conskey,$conssec,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
	$oauth->enableDebug();

	if(empty($_SESSION['secret'])) {
		$request_token_info = $oauth->getRequestToken($req_url,$callback_id,'POST');
		$_SESSION['secret'] = $request_token_info['oauth_token_secret'];
		$targeturl = $authurl.'?oauth_token='.$request_token_info['oauth_token'].'&callback_id='.$callback_id;
		if(__FILE__ !== $_SERVER['SCRIPT_FILENAME']){
		  $_SESSION['redirecto'] = $_SERVER['SCRIPT_NAME'];
		}
		header('Location: '.$targeturl);
		exit;
	} else if(!empty($_SESSION['redirecto'])) {
		$oauth->setToken($_GET['oauth_token'],$_SESSION['secret']);
		$access_token_info = $oauth->getAccessToken($acc_url, NULL, NULL, 'POST');
		$_SESSION['token'] = $access_token_info['oauth_token'];
		$_SESSION['secret'] = $access_token_info['oauth_token_secret'];
		$oauth->setToken($_SESSION['token'],$_SESSION['secret']);
		$oauth->fetch($api_url, NULL, 'POST');
		$_SESSION['dadosusp'] = json_decode($oauth->getLastResponse());
		header('Location: '.$_SESSION['redirecto']);
		exit;
	} else {
		unset($_SESSION['dadosusp']);
		header('Location: $oauthhomebase?erro=tente%20novamente,erro%20de%20autenticacao');
		exit;
	}
 } catch(OAuthException $E) {
  print_r($E);
 }
}

?>
