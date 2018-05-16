<?php
/*
* Jan Leduc de Lara - Sistema Integrado de Bibliotecas da USP - 16/jan/2014
* alterado em 27/mar/2017
*/

$viewlayout = (array_key_exists('viewlayout',$_GET)?$_GET['viewlayout']:'html');

header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if($viewlayout == 'xls'){
	header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
	header("Content-Disposition: attachment; filename=orcid.xls");
}

include('autentica-oauth1.php');
include('config.php');

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");
putenv("NLS_LANG=BRAZILIAN PORTUGUESE_BRAZIL.UTF8");

define('MAX_ROWNUM',100000);

$page = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int) $_GET['page'] : 0);
$limit = MAX_ROWNUM;
$offset = ($page * $limit);
$goahead = 0;
$orderby = (array_key_exists('orderby',$_GET)?$_GET['orderby']:'');
$totlins = 0;
$sqlqrybody = "";
$conn = null;
$ncols = 0;
$agoratime = time();

$orcidlistmc = new Memcached('orcidlist');

if (!count($orcidlistmc->getServerList())) {
    $orcidlistmc->addServers(array(
        array(MEMCACHESRVR,MEMCACHEPORT)
    ));
}

if(null !== $orcidlistmc->get('last'.$viewlayout.'update')){
    $orcidlistmc->set('last'.$viewlayout.'update', $agoratime );
}

if(($agoratime - $orcidlistmc->get('last'.$viewlayout.'update')) > 5){
    $orcidlistmc->set('last'.$viewlayout.'update', $agoratime );
    exibe_armazena_conteudo();
}
elseif(null !== $orcidlistmc->get('last'.$viewlayout.'content')){
    exibe_armazena_conteudo();
}
else{
    echo $orcidlistmc->get('last'.$viewlayout.'content');
}

function exibe_armazena_conteudo(){
    global $orcidlistmc, $page, $limit, $offset, $goahead, $orderby, $totlins, $sqlqrybody, $conn, $ncols, $viewlayout;
    ob_start();

	if($viewlayout == 'html'){

?>
<!DOCTYPE html5>
<html lang="pt_BR">
<head><title>USP - SIBi</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<style>

	table, th, td
	{
		border: 1px solid black;
		border-spacing:0;
		border-collapse:collapse;
	}
	thead tr {
		background-color: #aaaaaa;
	}
	tbody tr:nth-child(even) {
		background-color: #cccccc;
	}
	tbody tr:nth-child(odd) {
		background-color: #ffffff;
	}

	* {
		font-family: tahoma;
		font-size: 12px;
	}
	/* DivTable.com */
	.divTable{
		display: table;
		width: 100%;
	}
	.divTableRow {
		display: table-row;
	}
	.divTableHeading {
		background-color: #EEE;
		display: table-header-group;
	}
	.divTableCell, .divTableHead {
		border: 1px solid #999999;
		display: table-cell;
		padding: 3px 10px;
	}
	.divTableHeading {
		background-color: #EEE;
		display: table-header-group;
		font-weight: bold;
	}
	.divTableFoot {
		background-color: #EEE;
		display: table-footer-group;
		font-weight: bold;
	}
	.divTableBody {
		display: table-row-group;
	}
</style>

</head>
<body>
<?php
}


    if(!isset($_SESSION['oa1usp_dadosusp']->vinculo)){
		echo "usuário não autenticado!";
		if($viewlayout == 'html'){
			echo "</body></html>";
		}
        exit;
    }
   
   foreach($_SESSION['oa1usp_dadosusp']->vinculo as $k => $v){
       // if(($v->tipoVinculo === 'SERVIDOR') && ($v->codigoUnidade === 69)){
			$goahead = 1;
       // }
   }
   
   if($goahead === 0){
	echo "usuário não habilitado para realizar esta consulta";
	if($viewlayout == 'html'){
		echo "</body></html>";
	}
    exit;
   }


if($viewlayout == 'html'){
?>
   <!-- #FCB421 --><!-- #FCB421 -->
   <div style="background: url('bgusp.png') repeat-x fixed left top; padding: 5px;">
   <!--span style="font-size:20px;color:#3F3F3F;font-weight:bold">busc</span-->
   <span style="font-size:20px;color:#1694AB;font-weight:bold">USP</span>
   <br>
   <span style="font-size:18px;color:white;font-weight:bold">SIBi</span>
   </div>
   <br>

<a href='<?=$_SERVER['SCRIPT_NAME'].(array_key_exists('PATH_INFO',$_SERVER)?$_SERVER['PATH_INFO']:'').(array_key_exists('QUERY_STRING',$_SERVER)?'?'.str_replace($_SERVER['QUERY_STRING'],'viewlayout='.$viewlayout,'viewlayout=xls'):'')?>'>exportar para excel</a>
<br><br>

<?php

/*
   <form method="get" accept-charset="UTF-8">
	<input type="text" name="q" size="100" value="<?=isset($_SESSION['q'])?htmlentities($_SESSION['q'], ENT_QUOTES):''?>"></input>
 	&nbsp; 
 	<input type="submit" value="ok"></input>&nbsp;<?php if($page > 0){ ?><a href="?page=<?=($page-1)?>">&lt;&lt;prev</a><?php } ?>&nbsp;<a href="?page=<?=($page+1)?>">next&gt;&gt;</a>
   </form>
*/
}

//    if(array_key_exists('q',$_SESSION) && strlen($_SESSION['q'])>0){
       $conn = oci_connect(DB_ORCID_USR, DB_ORCID_PWD, DB_ORCID_URL);
	   if(!$conn){ exit; }
	   
	   $stid = oci_parse($conn,"ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY'");
	   oci_execute($stid);
	   oci_commit($conn);
   
        $sqlqry=<<<EOT
		SELECT
		EXT_MVW_RESUORCID.EXTRN "Linha",
		EXT_MVW_RESUORCID.NUSP,
		EXT_MVW_RESUORCID.ORCID,
		EXT_MVW_RESUORCID."Data da Coleta",
		EXT_MVW_RESUORCID."Nome",
		EXT_MVW_RESUORCID."Categoria",
		EXT_MVW_RESUORCID."Unidade",
		EXT_MVW_RESUORCID."Setor"
		FROM (
			SELECT
			ROW_NUMBER() OVER ( ORDER BY MVW_RESUORCID."Nome" ) AS EXTRN,
			MVW_RESUORCID.NUSP,
			MVW_RESUORCID.ORCID,
			to_char(MVW_RESUORCID."Data da Coleta",'YYYY-MM-DD') "Data da Coleta",
			MVW_RESUORCID."Nome",
			MVW_RESUORCID."Categoria",
			MVW_RESUORCID."Unidade",
			MVW_RESUORCID."Setor"		
			FROM MVW_RESUORCID
		) EXT_MVW_RESUORCID
		WHERE_EXPRESSION
EOT
        ;
//             ORDER BY "Nome"
//    }

    $sqlqryheads = $sqlqry;
    $sqlqryheads = str_replace("WHERE_EXPRESSION"," WHERE rownum < 1",$sqlqryheads);
    // $sqlqryheads = str_replace("ORDERINGCOWS","",$sqlqryheads);

	// echo $sqlqryheads; exit;

    $stid = oci_parse($conn, $sqlqryheads);
    if(!$stid){ exit; }

    $r=oci_execute($stid);
    if(!$r){ exit; }

    $collabels = array();
    $ncols = oci_num_fields($stid);
    for($i = 1; $i <= $ncols; $i++){
        $collabels[strtoupper(oci_field_name($stid,$i))]['label']=oci_field_name($stid,$i);
    }

    oci_free_statement($stid);

	if($viewlayout == 'html'){
		// print "<div class='divTable'>\n";
		print "<table>\n";
		// print "<div class='divTableHeading'><div class='divTableRow'>";
		print "<thead>\n";
		// print "<td>Linha</td>\n";

		foreach($collabels as $collabel){
			// [inicio] implementacao de order by em andamento
			// print "<th><a href=\"?orderby=".urlencode('"'.$collabel['label'].'"')."\">".$collabel['label']."</a></th>";
			// [fim]
			print "<th>".$collabel['label']."</th>";
		}

		// print "</div></div>";
		print "</thead>";
	// print "<div class='divTableBody'>";
		print "<tbody>";
	}
	elseif($viewlayout == 'xls'){
		print "<table>\n";
		print "<thead>\n";
		foreach($collabels as $collabel){
			print "<th>".$collabel['label']."</th>";
		}
		print "</thead>";
		print "<tbody>";
	}

	//BUILD SQL EXPRESSION	
    $sqlqrybody = $sqlqry;
	// $sqlqrybody = str_replace("WHERE_EXPRESSION","WHERE EXTRN BETWEEN ".($page*$limit + 1)." AND ".(($page + 1)*$limit),$sqlqrybody);
	if(strlen($orderby) > 0 ){
		$sqlqrybody = str_replace("WHERE_EXPRESSION","WHERE ROWNUM < 100 ORDER BY ".$orderby,$sqlqrybody);
/*
		echo "<xmp>\n";
        echo $sqlqrybody;
		echo "\n";
		echo "</xmp>\n";
*/

	}
	else {
		$sqlqrybody = str_replace("WHERE_EXPRESSION","",$sqlqrybody);
	}

	$totlins = 0;
	
	while(!fsqltabletr()){
        flush();
	}

	if($viewlayout == 'html'){
		// print "</div></div><br>\n";
		print "</tbody></table><br>\n";
		print "</body>\n</html>\n";
	}
	elseif($viewlayout == 'xls'){
		print "</tbody></table>\n";
	}

/*
RECHECK

	echo "<xmp>\n";
        echo "[sqlstrf:\n";
	print_r($sqlstrf);
	echo "\n]\n";
        echo "[a_binds:\n";
	print_r($a_binds);
	echo "\n]FIM\n";
	echo "</xmp>\n";
*/
// 	unset($_SESSION['q']);

	oci_close($conn);

    $orcidlistmc->set('last'.$viewlayout.'content',ob_get_contents());
    
    ob_end_flush();

}

function fsqltabletr(){ 

	global $totlins, $sqlqrybody, $conn, $ncols, $viewlayout;

	if($totlins > MAX_ROWNUM) return true;

	$ret = false;
	$stid = oci_parse($conn, $sqlqrybody);
	if(!$stid){ exit; }
    
    /*
	foreach(array_keys($abinds) as $kbind){
		oci_bind_by_name($stid, $kbind, $abinds[$kbind]) or print_r('<BR>\n['.'olha isso:'.$kbind.' -> '.$abinds[$kbind].']');
    }
    */

	$r=oci_execute($stid) or print_r($sqlqrybody);
	if(!$r){ exit; }

	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
		$totlins++;
		if($totlins <= MAX_ROWNUM){
			// print "<div class='divTableRow'>\n";
			print "<tr>\n";
			// print "<td>".$totlins."</td>\n";
			$i=0;
			foreach ($row as $icol) {
				$i++;
				print "    <td>" . ($icol !== null ? htmlentities($icol, ENT_QUOTES) : "&nbsp;") . "</td>\n";
				$ret = true;
				if($i >= $ncols) break;
			}
			print "</tr>\n";
		}
	}
	
	oci_free_statement($stid);
	
	return $ret;

}

