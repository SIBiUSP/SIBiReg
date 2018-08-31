<?php

header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/* trecho comentado
[INICIO do trecho para que não se exija autenticação prévia via USP]
if(strlen((string) filter_input(INPUT_GET,'code')) === 0) {
	include('config.php');
	ini_set('session.save_handler','memcached');
	ini_set('session.save_path',MEMCACHESRVR.':'.MEMCACHEPORT);
	$kem = filter_input(INPUT_GET,'kem');
	if(!empty($kem)){
		session_id($kem);
	}
	session_start();
	include('config.php');
}
else {
[FIM do trecho para que não se exija autenticação prévia via USP]
*/
	include('autentica-saml.php');
	include('config.php');
/*
}
*/

if( filter_input(INPUT_SERVER,'REMOTE_ADDR') !== '200.144.210.114' ){

  echo 'Desculpe o transtorno, site em manutenção.';
  exit;

}

$kem = session_id();
$currentBaseURL = (array_key_exists('HTTPS',$_SERVER)?'https':'http').'://'.filter_input(INPUT_SERVER,'HTTP_HOST').filter_input(INPUT_SERVER,'SCRIPT_NAME');

if(((string)filter_input(INPUT_GET,'obkurl')) === ''){
  $orcbackurl = OA2ORCBACKURL;
}
else {
  $orcbackurl = filter_input(INPUT_GET,'obkurl');
}

// if($currentBaseURL !== OA2ORC_REDIRECT_URI){
if(array_key_exists('real_redirect', $_SESSION) && (strlen($_SESSION['real_redirect']) > 0)  && ($_SESSION['real_redirect'] !== 'https://www.sibi.usp.br/sibireg/orcid.php')){
	
	$scheme = (array_key_exists('HTTPS',$_SERVER)?'https':'http');
  $_SESSION['OA2ORCBACKURL'] = $orcbackurl;
  
	session_write_close();
  // header(empty($_SERVER['QUERY_STRING']) ? 'Location: '.OA2ORC_REDIRECT_URI.'?kem='.$kem : 'Location: '.OA2ORC_REDIRECT_URI.'?'.$_SERVER['QUERY_STRING'].'&kem='.$kem);
  // echo "<!-- ";
  $tmprealredirect = "".$_SESSION['real_redirect'];
  $_SESSION['real_redirect'] = '';
  unset($_SESSION['real_redirect']);

  header(empty($_SERVER['QUERY_STRING']) ? 'Location: '.$tmprealredirect.'?kem='.$kem : 'Location: '.$tmprealredirect.'?'.$_SERVER['QUERY_STRING']);
  // echo "\n-->";
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
  $_SESSION['real_redirect'] = OA2ORC_REDIRECT_URI;
  $url = OA2ORC_AUTHORIZATION_URL . '?' . http_build_query(array(
      'response_type' => 'code',
      'client_id' => OA2ORC_CLIENT_ID,
      'redirect_uri' => 'https://www.sibi.usp.br/sibireg/orcid.php'.(empty($kem)?'':'?kem='.$kem),
      'scope' => '/read-limited /activities/update /person/update',
      'state' => $_SESSION['oauth_state'],
      'show_login' => 'true',
      'lang' => 'pt'
  ));
  session_write_close();
  header('Location: ' . $url);
/*
? >
<html>
<head>
< !-- meta http-equiv="refresh" content="10;url=<?=$url?>" /-- >
</head>
<body>
<a href="<?=$url?>"><?=$url?></a>
</body>
</html>
< ? php
*/
  exit();
}

if ( filter_input(INPUT_GET,'state') !== $_SESSION['oauth_state'] ) {
 
 echo "<!--\n";
 echo "state: [".filter_input(INPUT_GET,'state')."]\n";
 echo "oauth_state: [".$_SESSION['oauth_state']."]\n";
 echo "kem: [".$kem."]\n";
 echo "sessid: [".session_id()."]\n";
 print_r($_SESSION);
 echo "\n-->";
 
 exit('invalid state');
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

$porcid =  $response['orcid'];

/*============================*/

$ptokacc = $response['access_token'];

$pcodpes = $_SESSION['dadosusp']['nusp'];

/*
echo "<pre>\n";
echo "B. olha o OA2ORC_TOKEN_URL:[".OA2ORC_TOKEN_URL."]\n";
echo "B. olha o ORCID_PRODUCTION:[".ORCID_PRODUCTION."]\n";
echo "B. olha a url post:[".http_build_query(array(
    'code' => filter_input(INPUT_GET,'code'),
    'grant_type' => 'authorization_code',
    'client_id' => OA2ORC_CLIENT_ID,
    'client_secret' => OA2ORC_CLIENT_SECRET
  ))."]\n";
echo "B. olha o ptokacc:[".$ptokacc."]\n";
print_r($response);
print_r($_SESSION);
echo "\n</pre>";
exit;
*/

/*============================*/

$sqlqry = "BEGIN perfil_sibi.isola_identificador(:pcodpes,'ORCIDTOKACC',:ptokacc); END;";

$stid = oci_parse($conn, $sqlqry);
if(!$stid){ exit; }

oci_bind_by_name($stid,':pcodpes',$pcodpes);
oci_bind_by_name($stid,':ptokacc',$ptokacc);

oci_execute($stid,OCI_NO_AUTO_COMMIT);

/*============================*/
/*============================*/

$sqlqry = "BEGIN :rpsres := perfil_sibi.agrega_identificador_orcid(:pcodpes,:porcid); END;";

$stid = oci_parse($conn, $sqlqry);
if(!$stid){ exit; }

$pcodpes = $_SESSION['dadosusp']['nusp'];
$rpsres = '';
oci_bind_by_name($stid,':pcodpes',$pcodpes);
oci_bind_by_name($stid,':porcid',$porcid);
oci_bind_by_name($stid,':rpsres',$rpsres,2000,SQLT_CHR);

oci_execute($stid,OCI_NO_AUTO_COMMIT);

/*============================*/

oci_commit($conn);

oci_free_statement($stid);
oci_close($conn);

if(isset($_SESSION['OA2ORCBACKURL'])){
  $tmpOA2ORCBACKURL = $_SESSION['OA2ORCBACKURL'];
  unset($_SESSION['OA2ORCBACKURL']);
}
else {
  $tmpOA2ORCBACKURL = $orcbackurl;
}

$agourl = parse_url($tmpOA2ORCBACKURL);
parse_str($agourl['query'],$agoparms);

header('Location: '.(array_key_exists('scheme',$agourl)?$agourl['scheme'].':':'').'//'.$agourl['host'].$agourl['path'].'?'.http_build_query(array_merge($agoparms,array(
		'orcid' => $porcid ,
		'nome' => $_SESSION['dadosusp']['nome']
      ))));

exit;

/*
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
	parent.opener.location.href='<?=$tmpOA2ORCBACKURL?>';
	parent.close();
}
</script>
</head>
<body onload="autoload();" >
</body>
</html>
*/
