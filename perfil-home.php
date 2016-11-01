<?php
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$HOMEBASE = '/sibireg/perfil-inicio.php';
include('oauth.php');
include_once('config.php');

ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");

$pcodpes = intval(trim($_SESSION['dadosusp']->loginUsuario));

$conn = oci_connect(DBUSR, DBPWD, DBURL);

// CV LATTES [INICIO]
$stmt="BEGIN perfil_sibi.atualiza_identificador_lattes(:pcodpes); END;";

$stid = oci_parse($conn, $stmt);
if(!$stid){ exit; }

oci_bind_by_name($stid,':pcodpes',$pcodpes);

oci_execute($stid);

oci_commit($conn);
// CV LATTES [FIM]

$stmt="select kprop, vprop from perfis where autid = :autid order by kprop";

$stid = oci_parse($conn, $stmt);
if(!$stid){ exit; }

oci_bind_by_name($stid,':autid',$pcodpes);

oci_execute($stid);

$rperfil = array();
while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
  $rperfil[$row['KPROP']] = $row['VPROP'];
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
	<center>
		<a href="<?=PERFILHOMEBASE?>" title="Sair">
			<img src="http://www.sibi.usp.br/wp-content/themes/sibi-usp/assets/img/logotipo-sibi-usp.jpg" alt="Logotipo SIBiUSP">
		</a>
	</center>
	<div class="barrausp"><p>&nbsp;<br/>&nbsp;<br/>&nbsp;</p></div>
    <div class="uk-container uk-container-center uk-margin-large-bottom">
		<h1>
		Perfil SIBiUSP
		</h1>

		<ul>
		  <li>Nome: <?=$_SESSION['dadosusp']->nomeUsuario?></li>
		  <li>Número USP: <?=$_SESSION['dadosusp']->loginUsuario?></li>
<?php if(!empty($rperfil['LATTES'])){ ?>
		  <li>CV Lattes: <a href="http://lattes.cnpq.br/<?=$rperfil['LATTES']?>" target="_blank"><?=$rperfil['LATTES']?></a> (obtido do sistema corporativo) </li>
<?php } ?>
<?php if(empty($rperfil['ORCID'])){ ?>
		  <li>ORCID: Não obtido (<a href='orcid.php' data-uk-lightbox title="ORCID" >criar ou associar seu ORCID</a>)</li>
<?php } else { ?>
		  <li>ORCID: <a href="https://sandbox.orcid.org/<?=$rperfil['ORCID']?>" target="_blank"><?=$rperfil['ORCID']?></a></li>
<?php } ?>
		</ul>

		<br>
		<a href="<?=$HOMEBASE?>">sair</a>
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

