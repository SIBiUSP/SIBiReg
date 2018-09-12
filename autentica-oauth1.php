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

if(strlen(filter_input(INPUT_GET,'logout')) > 0){

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

	// ini_set('session.save_handler','memcached');
	// ini_set('session.save_path',MEMCACHESRVR.':'.MEMCACHEPORT);
	$kem = filter_input(INPUT_GET,'kem');
	if(strlen($kem) > 0){
		session_id($kem);
	}
	session_start();

	/*
	echo "<pre>\n";
	print_r($_SESSION);
	echo "\n<\pre>";
	exit;
	*/

	if(!isset($_SESSION['OA1USP_CALLBACK_ID'])){
		$_SESSION['OA1USP_CALLBACK_ID'] = OA1USP_CALLBACK_ID;
	}
	if(!isset($_SESSION['retorno'])){
		$_SESSION['retorno'] = (array_key_exists('HTTPS',$_SERVER)?'https':'http').'://'.filter_input(INPUT_SERVER,'HTTP_HOST').filter_input(INPUT_SERVER,'REQUEST_URI');
	}

	if(!isset($_SESSION['dadosusp'])){
		try {
			$req_url = OA1USP_REQUEST_URL;
			$authurl = OA1USP_AUTHORIZE_URL;
			$acc_url = OA1USP_ACCESSTOKEN_URL;
			$api_url = OA1USP_API_URL;
			$conskey = OA1USP_CLIENT_KEY;
			$conssec = OA1USP_CLIENT_SECRET;
			$callback_id = $_SESSION['OA1USP_CALLBACK_ID'];

			$oauth = new OAuth($conskey,$conssec,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
			$oauth->enableDebug();

			$tmpty = filter_input(INPUT_GET,'oauth_token');
			if(strlen($tmpty)>0) {
				$oauth->setToken(filter_input(INPUT_GET,'oauth_token'),$_SESSION['oa1usp_secret']);
				$access_token_info = $oauth->getAccessToken($acc_url, NULL, NULL, 'POST');
				$_SESSION['oa1usp_token'] = $access_token_info['oauth_token'];
				$_SESSION['oa1usp_secret'] = $access_token_info['oauth_token_secret'];
				$oauth->setToken($_SESSION['oa1usp_token'],$_SESSION['oa1usp_secret']);
				$oauth->fetch($api_url, NULL, 'POST');
				$dadosusp = json_decode($oauth->getLastResponse());
				if(isset($dadosusp)){
					$_SESSION['oa1usp_dadosusp'] = $dadosusp;
					$_SESSION['dadosusp']['nusp'] = $_SESSION['oa1usp_dadosusp']->loginUsuario;
					$_SESSION['dadosusp']['nome'] = $_SESSION['oa1usp_dadosusp']->nomeUsuario;
					if(isset($_SESSION['retorno'])){
						header('Location: '.$_SESSION['retorno']);
						exit;
					}				
				}
			}
			else {
				$request_token_info = $oauth->getRequestToken($req_url,$callback_id,'POST');
				$_SESSION['oa1usp_secret'] = $request_token_info['oauth_token_secret'];
				$targeturl = $authurl.'?oauth_token='.$request_token_info['oauth_token'].'&callback_id='.$callback_id;
				header('Location: '.$targeturl);
				exit;
			}

		} catch(OAuthException $E) {
			print_r($E);
			exit;
		}
	}

}



