<?php

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

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

$runningplace = SAMLUSP_RUNNINGPLACE;

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
		require_once('/var/simplesamlphp/lib/_autoload.php');
		$as = new SimpleSAML_Auth_Simple('default-sp');
		$as->requireAuth();
		$dadosusp = $as->getAttributes();

		$sssess = SimpleSAML_Session::getSessionFromRequest();
		$sssess->cleanup();

		session_destroy();
					
		ini_set('session.save_handler','memcached');
		ini_set('session.save_path',MEMCACHESRVR.':'.MEMCACHEPORT);
		$kem = filter_input(INPUT_GET,'kem');
		if(strlen($kem) > 0){
			session_id($kem);
		}
		session_start();
		
		if(isset($dadosusp)){
			$_SESSION['saml_dadosusp'] = $dadosusp;
			$_SESSION['dadosusp']['nome'] = $_SESSION['saml_dadosusp']['urn:oid:2.5.4.3'][0];
			$_SESSION['dadosusp']['nusp'] = $_SESSION['saml_dadosusp']['urn:oid:2.16.840.1.113730.3.1.3'][0];
		}
		
		if(isset($_SESSION['retorno'])){
			header('Location: '.$_SESSION['retorno']);
			exit;
		}
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
			$_SESSION['retorno'] = $scheme.'://'.filter_input(INPUT_SERVER,'HTTP_HOST').filter_input(INPUT_SERVER,'REQUEST_URI');
		}

		$kem = session_id();

		header('Location: '.$runningplace.'?kem='.$kem );
		exit;

	}

}

