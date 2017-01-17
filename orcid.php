<?php

header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('autentica.php');
include_once('config.php');

$currentBaseURL = (array_key_exists('HTTPS',$_SERVER)?'https':'http').'://'.filter_input(INPUT_SERVER,'HTTP_HOST').filter_input(INPUT_SERVER,'SCRIPT_NAME');

if($currentBaseURL !== OA2ORC_REDIRECT_URI){

	$kem = session_id();
	$scheme = (array_key_exists('HTTPS',$_SERVER)?'https':'http');
	$_SESSION['OA2ORCBACKURL'] = OA2ORCBACKURL;

	header(empty($_SERVER['QUERY_STRING']) ? 'Location: '.OA2ORC_REDIRECT_URI.'?kem='.$kem : 'Location: '.OA2ORC_REDIRECT_URI.'?'.$_SERVER['QUERY_STRING'].'&kem='.$kem);
	exit;
}

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");

$conn = oci_connect(DBUSR, DBPWD, DBURL);

// credito: https://gist.github.com/hubgit/46a868b912ccd65e4a6b

if(strlen(filter_input(INPUT_GET,'code')) === 0) {
  $state = bin2hex(openssl_random_pseudo_bytes(16));
  setcookie('oauth_state', $state, time() + 3600, null, null, false, true);
  $_SESSION['oauth_state'] = bin2hex(openssl_random_pseudo_bytes(16));
  $url = OA2ORC_AUTHORIZATION_URL . '?' . http_build_query(array(
      'response_type' => 'code',
      'client_id' => OA2ORC_CLIENT_ID,
      'redirect_uri' => OA2ORC_REDIRECT_URI,
      'scope' => '/read-limited /activities/update /person/update',
      'state' => $_SESSION['oauth_state'],
      'show_login' => 'true',
      'lang' => 'pt'
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
$response = json_decode($result, true);

/*============================*/

$sqlqry = "BEGIN perfil_sibi.isola_identificador(:pcodpes,'ORCIDTOKACC',:pvalor); END;";

$stid = oci_parse($conn, $sqlqry);
if(!$stid){ exit; }

/*
echo "<pre>\n";
print_r($response);
echo "\n</pre>";
exit;
*/

$pcodpes = intval($_SESSION['dadosusp']['nusp']);
$pvalor = $response['access_token'];
oci_bind_by_name($stid,':pcodpes',$pcodpes);
oci_bind_by_name($stid,':pvalor',$pvalor);

oci_execute($stid,OCI_NO_AUTO_COMMIT);

/*============================*/
/*============================*/

$sqlqry = "BEGIN :rpsres := perfil_sibi.agrega_identificador_orcid(:pcodpes,:pvalor); END;";

$stid = oci_parse($conn, $sqlqry);
if(!$stid){ exit; }

$pcodpes = intval($_SESSION['dadosusp']['nusp']);
$pvalor = $response['orcid'];
$rpsres = '';
oci_bind_by_name($stid,':pcodpes',$pcodpes);
oci_bind_by_name($stid,':pvalor',$pvalor);
oci_bind_by_name($stid,':rpsres',$rpsres,2000,SQLT_CHR);

oci_execute($stid,OCI_NO_AUTO_COMMIT);

/*============================*/

oci_commit($conn);

oci_free_statement($stid);
oci_close($conn);

/*
if(isset($_SESSION['OA2ORCBACKURL'])){
  $tmpOA2ORCBACKURL = $_SESSION['OA2ORCBACKURL'];
  unset($_SESSION['OA2ORCBACKURL']);
  header('Location: '.$tmpOA2ORCBACKURL);
else {
 header('Location: '.OA2ORCBACKURL);
}
*/
?>
<html>
<head>
<script language="javascript">
function autoload(){
<?php
 if($rpsres == 'NO'){
?>
        setTimeout(function() { alert('Não foi possível incluir, ORCID já existente!'); } );
<?php
 }
?>
	parent.opener.location.href='perfil-home.php';
	parent.close();
}
</script>
</head>
<body onload="autoload();" >
</body>
</html>
