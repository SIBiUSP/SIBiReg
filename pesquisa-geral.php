<?php
/*
* Jan Leduc de Lara - Sistema Integrado de Bibliotecas da USP - 11/abr/2017
*/

header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('autentica-oauth1.php');
include('config.php');

include('pesquisa-geral_pesquisas.php');

ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");
putenv("NLS_LANG=BRAZILIAN PORTUGUESE_BRAZIL.UTF8");

foreach($_GET as $k => $v){
  $_SESSION[$k] = html_entity_decode($v);
}

$_SESSION['page'] = (is_numeric(filter_input(INPUT_GET,'page')) ? (intval(filter_input(INPUT_GET,'page')) > 0 ? intval(filter_input(INPUT_GET,'page')) : 1) :1);
 
 // header("Location: ".basename(__FILE__));
 // exit;

$page = $_SESSION['page'];
$pagesize = ( (array_key_exists('pagesize',$_SESSION) && is_numeric($_SESSION['pagesize'])) ? intval($_SESSION['pagesize']) : 100 );

$minpage = ($page - 1) * $pagesize + 1;
$maxpage = $page * ($pagesize + 1);
$showprev = true;
$shownext = true;

$subconsulta = '';
$consulta = '';

if( array_key_exists('pesq',$_SESSION) && (strlen(trim($_SESSION['pesq'])) > 0) && array_key_exists($_SESSION['pesq'],$pesquisas) ){
	$subconsulta = $pesquisas[$_SESSION['pesq']]['sql']; //tabela, view ou select
	$consulta = "select subqo.* from (select rownum lin, subqi.* from ( ${subconsulta} ) subqi ) subqo where subqo.lin between ${minpage} and ${maxpage}";
}

// sub_dobaixacsv();

?>
<html>
<head>
<title>USP - SIBi</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<style>
	* {
		font-family: tahoma;
		font-size: 12px;
		color: black;
		text-decoration: none;
	}
	table, th, td
	{
		border: 1px solid black;
		border-spacing:0;
		border-collapse:collapse;
		padding: 1px 6px 1px 6px;
	}
	thead tr {
		background-color: yellow;
	}
	tbody tr:nth-child(even) {
		background-color: #FBE1CC;
	}
	tbody tr:nth-child(odd) {
		background-color: #C0CBAF;
	}
</style>
</head>
<body>

<?php
	sub_doechotit();
?>

<hr/>

<form action='pesquisa-geral.php' method='get' onsubmit="page.value=0;">

<input type='hidden' name='pesq' value="<?=$_SESSION['pesq']?>" />

<input type='hidden' name='page' value="<?=$_SESSION['page']?>" />

linhas: <select name='pagesize' onselect="submit()">
<option value="100" <?php echo(($pagesize == 100) ? 'selected' : '');  ?> >100</option>
<option value="1000" <?php echo(($pagesize == 1000) ? 'selected' : '');  ?> >1000</option>
<option value="10000" <?php echo(($pagesize == 10000) ? 'selected' : '');  ?> >10000</option>
<option value="100000" <?php echo(($pagesize == 100000) ? 'selected' : '');  ?> >100000</option>
</select>

<input type='submit' value='ok' />

</form>

<br>
<?php
sub_doechosql();
?>

<hr/>

<a id='topprev'></a>&nbsp;&nbsp;&nbsp;<a id='topnext'></a>
<?php
sub_doqrytab();
?>
<a id='botprev'></a>&nbsp;&nbsp;&nbsp;<a id='botnext'></a>
<?php
sub_navlinks();
?>

</body>
</html>
<?php

function sub_doechotit(){

	global $pesquisas, $subconsulta;

    if($subconsulta === ''){
		return;
	}

	echo "<h1>";
    echo $pesquisas[$_SESSION['pesq']]['titulo'];
	echo "</h1>";

	flush();


}

function sub_doechosql(){

	global $pesquisas, $subconsulta;

    if($subconsulta === ''){
		return;
	}

	echo "Script SQL:";
	echo "<div style='min-width:280px; max-width:40%; padding: 1em; border: 1px dashed black'>";
    echo $pesquisas[$_SESSION['pesq']]['sql'];
	echo "</div>\n<br>\n";

	flush();
}

function sub_navlinks(){

	global $page, $showprev, $shownext, $subconsulta;

    if($subconsulta === ''){
		return;
	}

	$prevpage = $page - 1;
	$nextpage = $page + 1;
	
	echo "<script language='javascript'>\n";

	if($showprev){
	 if($prevpage > 0){
		echo "document.getElementById('topprev').href='".'pesquisa-geral.php?pesq='.$_SESSION['pesq'].'&page='.$prevpage."';\n";
		echo "document.getElementById('botprev').href='".'pesquisa-geral.php?pesq='.$_SESSION['pesq'].'&page='.$prevpage."';\n";
		echo "document.getElementById('topprev').innerHTML='&lt;&lt;prev';\n";
		echo "document.getElementById('botprev').innerHTML='&lt;&lt;prev';\n";
	 }
	}
	if($shownext){
	 if($nextpage > $prevpage){
		echo "document.getElementById('topnext').href='".'pesquisa-geral.php?pesq='.$_SESSION['pesq'].'&page='.$nextpage."';\n";
		echo "document.getElementById('botnext').href='".'pesquisa-geral.php?pesq='.$_SESSION['pesq'].'&page='.$nextpage."';\n";
		echo "document.getElementById('topnext').innerHTML='next&gt;&gt;';\n";
		echo "document.getElementById('botnext').innerHTML='next&gt;&gt;';\n";
	 }
	 else {
		echo "<!-- no next gt prev -->";
	 }
	}
	else {
		echo "<!-- no shownext -->";
	}
	
	echo "</script>\n";

}

function sub_doqrytab(){

  global $conn, $stid, $consulta, $r, $subconsulta;

  if($subconsulta === ''){
	return;
  }

  $conn = oci_connect(DB_ALEPHSRCH_USR, DB_ALEPHSRCH_PWD, DB_ALEPHSRCH_URL);
  if(!$conn){ exit; }

  $stid = oci_parse($conn, $consulta);
  if(!$stid){ exit; }

  $r=oci_execute($stid);
  if(!$r){ exit; }

  sub_fillth();
  sub_maketable();

  oci_free_statement($stid);
  oci_close($conn);

}

function sub_fillth(){

	global $tabhead, $stid;
	
	$tabhead  = array();

	$ncols = oci_num_fields($stid);
	for($i = 1; $i <= $ncols; $i++){
		// $tabhead[strtoupper(oci_field_name($stid,$i))]['label']=oci_field_name($stid,$i);
		$tabhead[strtoupper(oci_field_name($stid,$i))]['name']=oci_field_name($stid,$i);
		$tabhead[strtoupper(oci_field_name($stid,$i))]['type']=oci_field_type($stid,$i);
		// $tabhead[strtoupper(oci_field_name($stid,$i))]['where']=array();
	}

}

function sub_maketable(){

	global $tabhead, $stid, $pagesize, $shownext;

	echo "<table>\n";
	echo "<thead>\n";
	foreach($tabhead as $colname => $a){
		echo "<th>".$a['name']."</th>\n";
	}
	echo "</thead>\n";

	flush();

	echo "<tbody>";
	$i=0;
	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
	  $i++;
	  if($i <= $pagesize){
	   print "<tr>\n";
	   foreach ($row as $item) {
		print "<td>" . ($item !== null ? htmlentities($item, ENT_QUOTES) : "&nbsp;") . "</td>\n";
		// if($i >= $qcols) break;
	   }
	   print "</tr>\n";
	   flush();
	  }
	}
	if($i <= $pagesize){
	   $shownext = false;
	}
	echo "</tbody>";
	echo "</table>";
	flush();

}

?>
