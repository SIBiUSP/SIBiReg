<?php
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
$_SESSION['HOMEBASE'] = 'perfil-inicio.php';
include('saml.php');
include_once('config.php');

ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");

if(!empty($_COOKIE['SAMLUSPSIBI_DATA']['NUSP']) && $_COOKIE['SAMLUSPSIBI_DATA']['ONSESS'] == 'yes' ){

$pcodpes = intval(trim($_COOKIE['SAMLUSPSIBI_DATA']['NUSP']));

$conn = oci_connect(DBUSR, DBPWD, DBURL);

// CV LATTES [INICIO]
$stmt="BEGIN perfil_sibi.atualiza_identificador_lattes(:pcodpes); END;";

$stid = oci_parse($conn, $stmt);
if(!$stid){ exit; }

oci_bind_by_name($stid,':pcodpes',$pcodpes);

oci_execute($stid);

oci_commit($conn);
// CV LATTES [FIM]

$stmt="select kprop, iprop, vprop from perfis where autid = :autid order by kprop, iprop";

$stid = oci_parse($conn, $stmt);
if(!$stid){ exit; }

oci_bind_by_name($stid,':autid',$pcodpes);

oci_execute($stid);

$rperfil = array();
while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
  $rperfil[$row['KPROP']][$row['IPROP']] = $row['VPROP'];
}

oci_free_statement($stid);
oci_close($conn);

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Perfil SIBiUSP</title>
        <link rel="stylesheet" href="inc/uikit-2.27.1/css/uikit.min.css" />
        <link rel="stylesheet" href="inc/style.css" />
        <script src="inc/jquery-3.1.1.min.js"></script>
        <script src="inc/uikit-2.27.1/js/uikit.min.js"></script>
        <script src="inc/uikit-2.27.1/js/components/lightbox.js"></script>
    </head>
    <body>
	<?php include 'inc/header.inc' ?>
	<h1>
	Perfil SIBiUSP
	</h1>
		<ul>
		  <li>Nome: <?=$_COOKIE['SAMLUSPSIBI_DATA']['NOME']?></li>
		  <li>Número USP: <?=$_COOKIE['SAMLUSPSIBI_DATA']['NUSP']?></li>
<?php
if(array_key_exists('LATTES',$rperfil)){
  foreach($rperfil['LATTES'] as $kl => $vl){
?>
		  <li>CV Lattes: <a href="http://lattes.cnpq.br/<?=$rperfil['LATTES'][$kl]?>" target="_blank"><?=$rperfil['LATTES'][$kl]?></a> (obtido do sistema corporativo) </li>
<?php
  }
}
if(array_key_exists('ORCID',$rperfil)){
	foreach($rperfil['ORCID'] as $ko => $vo){
?>
		  <li>ORCID: <a href="https://sandbox.orcid.org/<?=$rperfil['ORCID'][$ko]?>" target="_blank"><?=$rperfil['ORCID'][$ko]?></a></li>
<?php
	}
 // <li><a href='orcid.php' title="ORCID" >adicionar outro ORCID</a></li>
?>
<?php
} else {
?>
		  <li>ORCID: Não obtido (<a href='orcid.php' title="ORCID" >criar ou associar seu ORCID</a>)</li><!-- data-uk-lightbox -->
<?php
}
?>
		</ul>
		<br>
		<a href="<?=$_SESSION['HOMEBASE']?>">sair</a>
		<br>    
    </div>
<?php
/*
<pre>
< ?
print_r($rperfil);
? >
</pre>
*/
?>
    </body>
</html>
<?php
} else {
  header('Location: '.$_SESSION['HOMEBASE'].'?logout=yes');
  exit;
} ?>
