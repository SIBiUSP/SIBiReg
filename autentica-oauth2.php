<?php

include('config.php');

if(session_status() === PHP_SESSION_ACTIVE){
    /*
    session_unset();
    session_destroy();
    session_write_close();
    setcookie(session_name(),'',0,'/');
    session_regenerate_id(true);
    */
    echo 'sessao nao deve estar ativa!';
    exit;
}

$runningplace = OA2USP_RUNNINGPLACE;

$scheme = (array_key_exists('HTTPS',$_SERVER)?'https':'http');
$thisplace = $scheme.'://'.filter_input(INPUT_SERVER,'HTTP_HOST').filter_input(INPUT_SERVER,'SCRIPT_NAME').filter_input(INPUT_SERVER,'PATH_INFO');

if($thisplace === $runningplace){

	if(strlen(filter_input(INPUT_GET,'logout')) > 0){

		ini_set('session.save_handler','memcached');
		ini_set('session.save_path',MEMCACHESRVR.':'.MEMCACHEPORT);
		$kem = filter_input(INPUT_GET,'kem');
		if(!empty($kem)){
			session_id($kem);
		}
		session_start();

		foreach($_SESSION as $ks => $kv){
			$_SESSION[$ks] = NULL;
		}
	
		unset($_SESSION);
		session_unset();
		session_destroy();
		session_write_close();
		setcookie(session_name(),'',0,'/');
	
		if(strlen(filter_input(INPUT_GET,'backurl')) > 0){
			header('Location: '.filter_input(INPUT_GET,'backurl'));
			exit;
		}
		else {
			header('Location: '.filter_input(INPUT_GET,'logout'));
			exit;
		}
	}
	else {
/*
		ini_set('session.save_handler','memcached');
		ini_set('session.save_path',MEMCACHESRVR.':'.MEMCACHEPORT);
		$kem = filter_input(INPUT_GET,'kem');
		if(!empty($kem)){
			session_id($kem);
		}
		session_start();

		if (ORCID_PRODUCTION) {
		  define('OA2ORC_AUTHORIZATION_URL', 'https://orcid.org/oauth/authorize');
		  //define('OA2ORC_TOKEN_URL', 'https://pub.orcid.org/oauth/token'); // public
		  define('OA2ORC_TOKEN_URL', 'https://api.orcid.org/oauth/token'); // members
		} else {
		  define('OA2ORC_AUTHORIZATION_URL', 'https://sandbox.orcid.org/oauth/authorize');
		  //define('OA2ORC_TOKEN_URL', 'https://pub.sandbox.orcid.org/oauth/token'); // public
		  define('OA2ORC_TOKEN_URL', 'https://api.sandbox.orcid.org/oauth/token'); // members
		}

		if(strlen(filter_input(INPUT_GET,'code')) === 0) {
		  $state = bin2hex(openssl_random_pseudo_bytes(16));
		  setcookie('oauth_state', $state, time() + 3600, null, null, false, true);
		  $_SESSION['oauth_state'] = bin2hex(openssl_random_pseudo_bytes(16));
		  $url = OA2ORC_AUTHORIZATION_URL . '?' . http_build_query(array(
		      'response_type' => 'code',
		      'client_id' => OA2ORC_CLIENT_ID,
		      'redirect_uri' => OA2ORC_REDIRECT_URI,
		      'scope' => '/authenticate',
		      'state' => $_SESSION['oauth_state']
		  ));
		  header('Location: ' . $url);
		  exit();
		}

		if ( filter_input(INPUT_GET,'state') !== $_SESSION['oauth_state'] ) {
		 exit('Invalid state');
		}

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => OA2ORC_TOKEN_URL,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_HTTPHEADER => array('Accept: application/json'),
		  CURLOPT_POST => true,
		  CURLOPT_POSTFIELDS => http_build_query(array(
		    'code' => filter_input(INPUT_GET,'code'),
		    'grant_type' => 'authorization_code',
		    'client_id' => OA2ORC_CLIENT_ID,
		    'client_secret' => OA2ORC_CLIENT_SECRET
		  ))
		));
		$result = curl_exec($curl);
		$dadosusp = json_decode($result, true);
		if(isset($dadosusp)){
			$_SESSION['oa2usp_dadosusp'] = $dadosusp;
			$_SESSION['dadosusp']['orcid'] = $_SESSION['oa2usp_dadosusp']['orcid'];
		}
*/
	}
}
else {

	ini_set('session.save_handler','memcached');
	ini_set('session.save_path',MEMCACHESRVR.':'.MEMCACHEPORT);
	$kem = filter_input(INPUT_GET,'kem');
	if(!empty($kem)){
		session_id($kem);
	}
	session_start();

	if(isset($_SESSION['dadosusp'])){
			
		if(file_exists(filter_input(INPUT_GET,'logout'))){
			
			$kem = session_id();

			unset($_SESSION);
			session_unset();
			session_destroy();
			session_write_close();
			setcookie(session_name(),'',0,'/');
			
			$backurl = $scheme.'://'.filter_input(INPUT_SERVER,'HTTP_HOST').str_replace('//','/',dirname(filter_input(INPUT_SERVER,'SCRIPT_NAME')).'/').filter_input(INPUT_GET,'logout');
			
			header(isset($_SERVER['QUERY_STRING']) ? 'Location: '.$runningplace.'?'.$_SERVER['QUERY_STRING'].'&backurl='.urlencode($backurl).'&kem='.$kem : 'Location: '.$runningplace.'?backurl='.urlencode($backurl).'&kem='.$kem );
			exit;
		}

	}
	else {
		if(!isset($_SESSION['retorno'])){

			$kem = session_id();

			$_SESSION['retorno'] = $scheme.'://'.filter_input(INPUT_SERVER,'HTTP_HOST').filter_input(INPUT_SERVER,'REQUEST_URI');
			
			header('Location: '.$runningplace.'?kem='.$kem );
			exit;

		}
	}

}

