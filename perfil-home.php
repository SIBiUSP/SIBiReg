<?php

header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('autentica-saml.php');
include('config.php');

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");
putenv("NLS_LANG=BRAZILIAN PORTUGUESE_BRAZIL.UTF8");

if(isset($_SESSION['dadosusp']['nusp'])){

$pcodpes = intval(trim($_SESSION['dadosusp']['nusp']));

$conn = oci_connect(DBUSR, DBPWD, DBURL);

// EXCLUI IDENTIFICADOR
if(filter_input(INPUT_GET,'op') == 'del'){

	if(filter_input(INPUT_GET,'type') == 'ORCID'){

		$stmt="BEGIN perfil_sibi.exclui_identificador(:pcodpes,'ORCID',:pvalue); END;";

		$pvalue = filter_input(INPUT_GET,'val');

		$stid = oci_parse($conn, $stmt);
		if(!$stid){ exit; }

		oci_bind_by_name($stid,':pcodpes',$pcodpes);
		oci_bind_by_name($stid,':pvalue',$pvalue);

		oci_execute($stid);

		$stmt="BEGIN perfil_sibi.exclui_identificador(:pcodpes,'ORCIDTOKACC',:pvalue); END;";

		$pvalue = filter_input(INPUT_GET,'val');

		$stid = oci_parse($conn, $stmt);
		if(!$stid){ exit; }

		oci_bind_by_name($stid,':pcodpes',$pcodpes);
		oci_bind_by_name($stid,':pvalue',$pvalue);

		oci_execute($stid);

	}

	oci_commit($conn);

	oci_free_statement($stid);
	oci_close($conn);

	header('Location: perfil-home.php');
  
}
// EXCLUI IDENTIFICADOR

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
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <title>Perfil SIBiUSP</title>
        <link rel="stylesheet" href="inc/uikit-2.27.1/css/uikit.min.css" />
        <link rel="stylesheet" href="inc/style.css" />
        <link rel="stylesheet" href="inc/orcid.css" />
        <script src="inc/jquery-3.1.1.min.js"></script>
        <script src="inc/uikit-2.27.1/js/uikit.min.js"></script>
        <script src="inc/uikit-2.27.1/js/components/lightbox.js"></script>
	<script type=text/javascript>
	var oauthWindow;

	function openORCID() {
	    // var oauthWindow = window.open("orcid.php", "_blank", "toolbar=no, scrollbars=yes, width=580, height=" + (outerHeight*0.8) + ", top=" + (screenY + (outerHeight - innerHeight)*0.2) + ", left=" + (screenX + (innerWidth - 580)*0.5));
	    location.href="orcid.php";
	}
	</script>
    </head>
    <body>
	<?php include 'inc/header.inc' ?>
	<h1>
	Perfil SIBiUSP
	</h1>
		<ul>
		  <li style="line-height: 2">Nome: <b><?=$_SESSION['dadosusp']['nome']?></b></li>
		  <li style="line-height: 2">Número USP: <a href="//uspdigital.usp.br" target="_blank"><?=$_SESSION['dadosusp']['nusp']?></a></li>
<?php
if(array_key_exists('LATTES',$rperfil)){
  foreach($rperfil['LATTES'] as $kl => $vl){
?>
		  <li style="line-height: 3"><div style="line-height:1"><a href="http://lattes.cnpq.br"><img id="CNPQ Lattes logo" alt="CNPQ Lattes logo" src="img/logo-curriculo_cut.png" width="16" height="16" /></a> <a href="http://lattes.cnpq.br/<?=$rperfil['LATTES'][$kl]?>" target="_blank">http://lattes.cnpq.br/<?=$rperfil['LATTES'][$kl]?></a> (obtido do sistema corporativo)</div></li>
<?php
  }
}
if(array_key_exists('ORCID',$rperfil)){
	foreach($rperfil['ORCID'] as $ko => $vo){
?>
		  <li style="line-height: 3"><div style="line-height:1"><a href="http://<?=ORCID_HOSTBASE?>"><img alt="ORCID logo" src="//orcid.org/sites/default/files/images/orcid_16x16.png" width="16" height="16" /></a> <a href="https://<?=ORCID_HOSTBASE?>/<?=$rperfil['ORCID'][$ko]?>" target="_blank">http://<?=ORCID_HOSTBASE?>/<?=$rperfil['ORCID'][$ko]?></a> (<a href="perfil-home.php?op=del&type=ORCID&val=<?=$rperfil['ORCID'][$ko]?>"> desconectar </a>)</div></li>
<?php
	}
 // <li><a href='orcid.php' title="ORCID" >adicionar outro ORCID</a></li>
?>
<?php
} else {
?>
		  <li style="line-height: 3"><button id="connect-orcid-button" onclick="openORCID()"><img id="orcid-id-logo" src="//orcid.org/sites/default/files/images/orcid_24x24.png" width='24' height='24' alt="ORCID logo"/>Criar ou Associar seu ORCID iD</button> &nbsp; <div style="display: inline-block; vertical-align: top; background-color: #E8E8E8; padding: .8em; color: #666; font-size: .9em; width: 50%; line-height: 1">ORCID fornece um identificador digital consistente que o identifica unicamente dentre outros pesquisadores. Veja mais em <a href="http://<?=ORCID_HOSTBASE?>" target="_blank" >http://<?=ORCID_HOSTBASE?></a>.</div>
		   </li>
		  
		 <!-- ORCID: Não obtido (<a href='orcid.php' title="ORCID" >criar ou associar seu ORCID</a>)</li> <! -- data-uk-lightbox -->
<?php
}
?>
		</ul>
		<br/>
		<a href="autentica-saml.php?logout=perfil-inicio.php">sair</a>
		<br/><br/><br/><br/><br/>
		<h6>
		Problemas? Envie um e-mail para <a href="mailto:atendimento@sibi.usp.br">atendimento@sibi.usp.br</a>.
		</h6>
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
  header('Location: autentica-saml.php?logout=perfil-inicio.php');
  exit;
} ?>
