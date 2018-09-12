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
	if(strlen($kem) > 0){
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

	header('Location: '.SAMLUSP_LOGOUTURL);
	exit;

}
else { 
	require_once('/var/simplesamlphp/lib/_autoload.php');
	$as = new SimpleSAML_Auth_Simple('default-sp');

	if(filter_input(INPUT_GET,'reauth') === 'yes'){	
		$as->requireAuth(array('ForceAuthn' => true));
	} else {
		$as->requireAuth();
	}

	$dadosusp = $as->getAttributes();

	$sssess = SimpleSAML_Session::getSessionFromRequest();
	$sssess->cleanup();

	session_destroy();
				
	// ini_set('session.save_handler','memcached');
	// ini_set('session.save_path',MEMCACHESRVR.':'.MEMCACHEPORT);
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
