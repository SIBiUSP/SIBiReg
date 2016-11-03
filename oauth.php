<?php
ini_set("display_errors", 1);
include_once('config.php');

if((__FILE__ == $_SERVER['SCRIPT_FILENAME']) && !empty($_COOKIE['OA1USPBACKURL'])){
  $oa1uspbackurl = $_COOKIE['OA1USPBACKURL'];
  unset($_COOKIE['OA1USPBACKURL']);
  setcookie('OA1USPBACKURL', '', time()-3600, "/", ".sibi.usp.br");
  header(empty($_SERVER['QUERY_STRING']) ? 'Location: '.$oa1uspbackurl : 'Location: '.$oa1uspbackurl.'?'.$_SERVER['QUERY_STRING']);
  exit;
}

session_start();

if( $_SERVER['SCRIPT_NAME'] == dirname($_SERVER['SCRIPT_NAME']).'/'.$HOMEBASE ){
	session_unset();
	session_destroy();
} else if(empty($_SESSION['dadosusp'])){
 try {
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
	} else if(!empty($_SESSION['redirecto']) && !empty($_GET['oauth_token'])) {
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
		unset($_SESSION['token']);
		unset($_SESSION['redirecto']);
		unset($_SESSION['secret']);
		unset($_SESSION['dadosusp']);
		header('Location: '.$HOMEBASE);
		exit;
	}
 } catch(OAuthException $E) {
  print_r($E);
 }
}

