<?php
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('autentica-oauth1.php');
include('config.php');

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
            Pesquisas ALEPH/DEDALUS - USP
            </h1>
	    <h6>
	    <a href='http://www.dedalus.usp.br' target='_new'><i>DEDALUS - Universidade de São Paulo</i></a>
	    </h6>
	<ul>
	 <li><a href="pesquisa-geral.php?pesq=desdobramento_periodicos">ALEPH - Desdobramento de periódicos</a></li>
	 <li><a href="pesquisa-geral.php?pesq=desdobramento_periodicos_periodo">ALEPH - Desdobramento de periódicos por período</a></li>
	</ul>
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

