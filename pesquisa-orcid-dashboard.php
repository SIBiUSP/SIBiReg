<?php
/*
* Jan Leduc de Lara - Sistema Integrado de Bibliotecas da USP - 16/jan/2014
* alterado em 27/mar/2017
*/

header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('autentica-oauth1.php');
include('config.php');

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");
putenv("NLS_LANG=BRAZILIAN PORTUGUESE_BRAZIL.UTF8");

if(isset($_SESSION['dadosusp']['nusp'])){

	?>
	<!DOCTYPE html>
	<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
			<title></title>
			<link rel="stylesheet" href="inc/uikit-2.27.1/css/uikit.min.css" />
			<link rel="stylesheet" href="inc/style.css" />
			<script src="inc/jquery-3.1.1.min.js"></script>
			<script src="inc/uikit-2.27.1/js/uikit.min.js"></script>
		</head>
		<body>
		  <?php include 'inc/header.inc' ?>
				<h1>
				Dashboard ORCID-USP
				</h1>
				<iframe src="<?php echo KIBDASH; ?>" height="2500" width="95%"></iframe>
		<br/>
		<a href="autentica-oauth1.php?logout=pesquisa-inicio.php">sair</a>
		<br/><br/><br/><br/><br/>
		<h6>
		Problemas? Envie um e-mail para <a href="mailto:atendimento@sibi.usp.br">atendimento@sibi.usp.br</a>.
		</h6>
	  </body>
	</html>
	<?php
	} else {
	
	  echo '<xmp>'."\n"; 
	
	  print_r($_SESSION);
	
	
	  echo "\n".'</xmp>'."\n";
	
	  //header('Location: autentica-oauth1.php?logout=pesquisa-inicio.php');
	  exit;
	}
