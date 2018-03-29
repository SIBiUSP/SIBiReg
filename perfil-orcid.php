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

if(isset($_SESSION['dadosusp']['nusp'])){

	$pcodpes = intval(trim($_SESSION['dadosusp']['nusp']));

	$conn = oci_connect(DBUSR, DBPWD, DBURL);

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

	if(array_key_exists('ORCID',$rperfil)){
		foreach($rperfil['ORCID'] as $ko => $vo){
			header('Location: http://'.ORCID_HOSTBASE.'/'.$rperfil['ORCID'][$ko]);
		}
	}
} else {
  header('Location: http://www.sibi.usp.br/orcid/');
  exit;
}
