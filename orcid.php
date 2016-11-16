<?php

include('config.php');

if($currentBaseURL !== OA2ORC_REDIRECT_URI){
  // caiu aqui é outra máquina, ex. bdpife5
  // setcookie('', 'perfil-home.php', 0, "/", ".sibi.usp.br");
  setcookie('OA2ORCBACKURL', OA2ORCBACKURL, 0, "/", ".sibi.usp.br");
  header(empty($_SERVER['QUERY_STRING']) ? 'Location: '.OA2ORC_REDIRECT_URI : 'Location: '.OA2ORC_REDIRECT_URI.'?'.$_SERVER['QUERY_STRING']);
  exit;
}

include('saml.php');
if(empty($_SESSION)) session_start();

// credito: https://gist.github.com/hubgit/46a868b912ccd65e4a6b

header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// include('oauth.php');

if (ORCID_PRODUCTION) {
  define('OA2ORC_AUTHORIZATION_URL', 'https://orcid.org/oauth/authorize');
  //define('OA2ORC_TOKEN_URL', 'https://pub.orcid.org/oauth/token'); // public
  define('OA2ORC_TOKEN_URL', 'https://api.orcid.org/oauth/token'); // members
} else {
  define('OA2ORC_AUTHORIZATION_URL', 'https://sandbox.orcid.org/oauth/authorize');
  //define('OA2ORC_TOKEN_URL', 'https://pub.sandbox.orcid.org/oauth/token'); // public
  define('OA2ORC_TOKEN_URL', 'https://api.sandbox.orcid.org/oauth/token'); // members
}

if(empty($_GET['code'])) {
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

if ( empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth_state']) ) {
 exit('Invalid state');
}

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => OA2ORC_TOKEN_URL,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => array('Accept: application/json'),
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query(array(
    'code' => $_GET['code'],
    'grant_type' => 'authorization_code',
    'client_id' => OA2ORC_CLIENT_ID,
    'client_secret' => OA2ORC_CLIENT_SECRET
  ))
));
$result = curl_exec($curl);
$response = json_decode($result, true);

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");

$conn = oci_connect(DBUSR, DBPWD, DBURL);

$sqlqry = "BEGIN :rpsres := perfil_sibi.agrega_identificador_orcid(:pcodpes,:pvalor); END;";

$stid = oci_parse($conn, $sqlqry);
if(!$stid){ exit; }

$pcodpes = intval($_COOKIE['SAMLUSPSIBI_DATA']['NUSP']);
$pvalor = $response['orcid'];
$rpsres = '';
oci_bind_by_name($stid,':pcodpes',$pcodpes);
oci_bind_by_name($stid,':pvalor',$pvalor);
oci_bind_by_name($stid,':rpsres',$rpsres,2000,SQLT_CHR);

oci_execute($stid,OCI_NO_AUTO_COMMIT);

oci_commit($conn);

oci_free_statement($stid);
oci_close($conn);

if(empty($_COOKIE['OA2ORCBACKURL'])){
  header('Location: '.OA2ORCBACKURL);
}
else {
  header('Location: '.$_COOKIE['OA2ORCBACKURL']);
}

/*
? >
<html>
<head>
<script language=javascript>
function godo(){
< ?php
 if($rpsres == 'NO'){
? >
        setTimeout(function() { alert('Não foi possível incluir, ORCID já existente!'); } );
< ?php
 }
? >
	if(self == top){
	 location.replace("< ?=$_COOKIE['OA2ORCBACKURL']? >");
	}
	else {
	  parent.$("body > div.uk-modal.uk-open > div > a").click();
	  parent.$("body > div.uk-modal.uk-open > div > div.uk-lightbox-content > iframe").src="";
	  parent.location.reload();
	}
}
</script>
</head>
<body onload="godo();" >
</body>
</html>
*/
