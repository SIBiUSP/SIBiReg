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

define('MAX_ROWNUM',100);

$page = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int) $_GET['page'] : 0);
$limit = 1000;
$offset = ($page * $limit);
$goahead = 0;

?>
<html>
<head><title>USP - SIBi</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
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
   <form method="get" accept-charset="UTF-8">
   <input type="text" name="q" size="100" value="<?=isset($_SESSION['q'])?htmlentities($_SESSION['q'], ENT_QUOTES):''?>"></input> &nbsp; <input type="submit" value="ok"></input>
   </form>
<?php
   
//    if(array_key_exists('q',$_SESSION) && strlen($_SESSION['q'])>0){
       $conn = oci_connect(DBUSR, DBPWD, DBURL);
       if(!$conn){ exit; }
   
        $sqlqry=<<<EOT
                SELECT MVW_RESUORCID.*
                FROM MVW_RESUORCID
                WHERE_EXPRESSION
EOT
        ;
//             ORDER BY "Nome"
//    }

    $sqlqryheads = $sqlqry;
    $sqlqryheads = str_replace("WHERE_EXPRESSION"," WHERE rownum < 1",$sqlqryheads);
    // $sqlqryheads = str_replace("ORDERINGCOWS","",$sqlqryheads);

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
	
	print "<table border='0'>\n";
	print "<thead><tr>";
	print "<td>Linha</td>\n";
	foreach($collabels as $collabel){
		print "<th>".$collabel['label']."</th>";
	}
	print "</tr></thead>";
	print "<tbody>";

	//BUILD SQL EXPRESSION	
    $sqlqrybody = $sqlqry;
    $sqlqrybody = str_replace("WHERE_EXPRESSION","where rownum < 50",$sqlqrybody);

	$totlins = 0;
	
	while(!fsqltabletr()){
        flush();
	}

	print "</tbody></table><br>\n";

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
			print "<tr>\n";
			print "<td>".$totlins."</td>\n";
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


