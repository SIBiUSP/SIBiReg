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

define('MAX_ROWNUM',100000);

$page = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int) $_GET['page'] : 0);
$limit = MAX_ROWNUM;
$offset = ($page * $limit);
$goahead = 0;

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
		background-color: yellow;
	}
	tbody tr:nth-child(even) {
		background-color: #aacccc;
	}
	tbody tr:nth-child(odd) {
		background-color: #13ffff;
	}

	* {
		font-family: tahoma;
		font-size: 12px;
		color: black;
		text-decoration: none;
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

    if(!isset($_SESSION['oa1usp_dadosusp']->vinculo)){
        echo "usuário não autenticado!";
        echo "</body></html>";
        exit;
    }
   
   foreach($_SESSION['oa1usp_dadosusp']->vinculo as $k => $v){
       if(($v->tipoVinculo === 'SERVIDOR') && ($v->codigoUnidade === 69)){
           $goahead = 1;
       }
   }
   
   if($goahead === 0){
    echo "usuário não habilitado para realizar esta consulta";
    echo "</body></html>";
    exit;
   }
   
?>
   <!-- #FCB421 --><!-- #FCB421 -->
   <div style="background: url('bgusp.png') repeat-x fixed left top; padding: 5px;">
   <!--span style="font-size:20px;color:#3F3F3F;font-weight:bold">busc</span-->
   <span style="font-size:20px;color:#1694AB;font-weight:bold">USP</span>
   <br>
   <span style="font-size:18px;color:white;font-weight:bold">SIBi</span>
   </div>
   <br>
<?php

/*
   <form method="get" accept-charset="UTF-8">
	<input type="text" name="q" size="100" value="<?=isset($_SESSION['q'])?htmlentities($_SESSION['q'], ENT_QUOTES):''?>"></input>
 	&nbsp; 
 	<input type="submit" value="ok"></input>&nbsp;<?php if($page > 0){ ?><a href="?page=<?=($page-1)?>">&lt;&lt;prev</a><?php } ?>&nbsp;<a href="?page=<?=($page+1)?>">next&gt;&gt;</a>
   </form>
*/
   
//    if(array_key_exists('q',$_SESSION) && strlen($_SESSION['q'])>0){
       $conn = oci_connect(DB_ORCID_USR, DB_ORCID_PWD, DB_ORCID_URL);
       if(!$conn){ exit; }
   
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
			TO_CHAR(MVW_RESUORCID."Data da Coleta",'DD/MM/YYYY HH24:MI:SS') "Data da Coleta",
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
	
	// print "<div class='divTable'>\n";
	print "<table>\n";
	// print "<div class='divTableHeading'><div class='divTableRow'>";
	print "<thead>\n";
	// print "<td>Linha</td>\n";
	foreach($collabels as $collabel){
		// print "<div class='divTableHead'>".$collabel['label']."</div>";
		print "<th>".$collabel['label']."</th>";
	}
	// print "</div></div>";
	print "</thead>";

	// print "<div class='divTableBody'>";
	print "<tbody>";

	//BUILD SQL EXPRESSION	
    $sqlqrybody = $sqlqry;
    $sqlqrybody = str_replace("WHERE_EXPRESSION","WHERE EXTRN BETWEEN ".($page*$limit + 1)." AND ".(($page + 1)*$limit),$sqlqrybody);

	$totlins = 0;
	
	while(!fsqltabletr()){
        flush();
	}

	// print "</div></div><br>\n";
	print "</tbody></table><br>\n";
	print "</body>\n</html>\n";

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

function fsqltabletr(){ 

	global $totlins, $sqlqrybody, $conn, $ncols;

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
				// print "    <div class='divTableCell'>" . ($icol !== null ? htmlentities($icol, ENT_QUOTES) : "&nbsp;") . "</div>\n";
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


