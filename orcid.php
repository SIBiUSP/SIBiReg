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
	// ini_set('session.save_handler','memcached');
	// ini_set('session.save_path',MEMCACHESRVR.':'.MEMCACHEPORT);
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

/*
if( filter_input(INPUT_SERVER,'REMOTE_ADDR') !== '200.144.210.114' ){

  echo 'Desculpe o transtorno, site em manutenção.';
  exit;

}
*/

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");
putenv("NLS_LANG=BRAZILIAN PORTUGUESE_BRAZIL.UTF8");

$kem = session_id();

if(array_key_exists('real_redirect', $_SESSION) && (strlen($_SESSION['real_redirect']) > 0)  && ($_SESSION['real_redirect'] !== 'https://www.sibi.usp.br/sibireg/orcid.php')){
	
	$scheme = (array_key_exists('HTTPS',$_SERVER)?'https':'http');

  $tmprealredirect = "".$_SESSION['real_redirect'];
  $_SESSION['real_redirect'] = '';
  unset($_SESSION['real_redirect']);

	session_write_close();
  header(empty($_SERVER['QUERY_STRING']) ? 'Location: '.$tmprealredirect.'?kem='.$kem : 'Location: '.$tmprealredirect.'?'.$_SERVER['QUERY_STRING']);
	exit;
}

if(strlen(trim((string)filter_input(INPUT_GET,'obkurl'))) >0){
  $orcbackurl = filter_input(INPUT_GET,'obkurl');
  $_SESSION['OA2ORCBACKURL'] = $orcbackurl;
}
elseif( array_key_exists('OA2ORCBACKURL', $_SESSION) && (strlen($_SESSION['OA2ORCBACKURL']) > 0) ) {
  $orcbackurl = $_SESSION['OA2ORCBACKURL'];
}
else {
  $orcbackurl = OA2ORCBACKURL;
  $_SESSION['OA2ORCBACKURL'] = $orcbackurl;
}

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");

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

/*
 echo "<!--\n";
 echo "state: [".filter_input(INPUT_GET,'state')."]\n";
 echo "oauth_state: [".$_SESSION['oauth_state']."]\n";
 echo "kem: [".$kem."]\n";
 echo "sessid: [".session_id()."]\n";
 print_r($_SESSION);
 echo "\n-->";
*/ 
 exit('invalid state');
}

$pcodpes = $_SESSION['dadosusp']['nusp'];

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

if(file_put_contents('../responses/'.$pcodpes.'_'.date("YmdHis").'.json',$result."\n") == 0){
  if( filter_input(INPUT_SERVER,'REMOTE_ADDR') !== '200.144.210.114' ){

    echo 'Desculpe o transtorno, site em manutenção.';
    exit;
  
  }
  else {
    echo "Erro... \n";
    echo getcwd();
    exit;
  }
}

$response = json_decode($result, true);

/*============================*/

$conn = oci_connect(DBUSR, DBPWD, DBURL);

/*============================*/

$sqlqry=<<<EOF
  BEGIN
    perfil_sibi.isola_identificador(:pcodpes,'ORCIDTOKNAM',:ptoknam);
    perfil_sibi.isola_identificador(:pcodpes,'ORCIDTOKCOP',:ptokcop);
    perfil_sibi.isola_identificador(:pcodpes,'ORCIDTOKXPN',:ptokxpn);
    perfil_sibi.isola_identificador(:pcodpes,'ORCIDTOKRFH',:ptokrfh);
    perfil_sibi.isola_identificador(:pcodpes,'ORCIDTOKTYP',:ptoktyp);
    perfil_sibi.isola_identificador(:pcodpes,'ORCIDTOKACC',:ptokacc);
    :rpsres := perfil_sibi.agrega_identificador_orcid(:pcodpes,:porcid);
  END;
EOF;

$stid = oci_parse($conn, $sqlqry);
if(!$stid){ exit; }

oci_bind_by_name($stid,':pcodpes',$pcodpes);
oci_bind_by_name($stid,':ptoknam',$response['name']);
oci_bind_by_name($stid,':ptokcop',$response['scope']);
oci_bind_by_name($stid,':ptokxpn',$response['expires_in']);
oci_bind_by_name($stid,':ptokrfh',$response['refresh_token']);
oci_bind_by_name($stid,':ptoktyp',$response['token_type']);
oci_bind_by_name($stid,':ptokacc',$response['access_token']);
oci_bind_by_name($stid,':porcid',$response['orcid']);

$rpsres = '';
oci_bind_by_name($stid,':rpsres',$rpsres,2000,SQLT_CHR);

$r = oci_execute($stid,OCI_NO_AUTO_COMMIT);
if (!$r) {
  $e = oci_error($stid);
  trigger_error(htmlentities($e['message']), E_USER_ERROR);
  exit;
}

/*============================*/

$r = oci_commit($conn);
if (!$r) {
  $e = oci_error($stid);
  trigger_error(htmlentities($e['message']), E_USER_ERROR);
  exit;
}


oci_free_statement($stid);
oci_close($conn);

/*
if(isset($_SESSION['OA2ORCBACKURL'])){
  $tmpOA2ORCBACKURL = $_SESSION['OA2ORCBACKURL'];
  unset($_SESSION['OA2ORCBACKURL']);
}
else {
  $tmpOA2ORCBACKURL = $orcbackurl;
}
*/

// $agourl = parse_url($tmpOA2ORCBACKURL);
$agourl = parse_url($orcbackurl);
if(array_key_exists('query',$agourl)){
  parse_str($agourl['query'],$agoparms);
} else {
  $agoparms = array();
}

// print_r($_SESSION);

header('Location: '.(array_key_exists('scheme',$agourl)?$agourl['scheme'].':':'').'//'.$agourl['host'].$agourl['path'].'?'.http_build_query(array_merge($agoparms,array(
		'orcid' => $response['orcid'] ,
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
