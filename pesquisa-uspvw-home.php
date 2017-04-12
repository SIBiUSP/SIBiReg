<?php

/*
* Jan Leduc de Lara - Sistema Integrado de Bibliotecas da USP - 23/set/2013
* alterado em 27/mar/2017
*/

header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('autentica-oauth1.php');
include('config.php');

ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

putenv("NLS_SORT=BINARY_AI");
putenv("NLS_COMP=LINGUISTIC");
putenv("NLS_LANG=BRAZILIAN PORTUGUESE_BRAZIL.UTF8");

$DEFAULT_LABEL = "NOMPES";
define('MAX_ROWNUM',1000);

$collabels = array();

if(array_key_exists('q',$_GET) && strlen($_GET['q'])>0){
	$_SESSION['q']=html_entity_decode($_GET['q']);
	header("Location: ".basename(__FILE__));
	exit;
}

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
<body><!-- #FCB421 --><!-- #FCB421 -->
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

if(array_key_exists('q',$_SESSION) && strlen($_SESSION['q'])>0){


	$conn = oci_connect(DB_REPLICAUSP_USR, DB_REPLICAUSP_PWD, DB_REPLICAUSP_URL);
	if(!$conn){ exit; }

	$sqlqry=<<<EOT
SELECT VIEW_BDPI.* ORDERINGCOWS
FROM VIEW_BDPI
WHERE_EXPRESSION
EOT
;

	// utl_match.jaro_winkler_similarity(nvl(:QRYSTR,lower(nompes)),lower(nompes)) A,
	// decode(substr(lower(t ipvin),0,4),'exte',1,'auto',1,'insc',2,'cand',2,'depe',3,0) B,
	// decode(substr(decode(lower(t ipfnc),'docente','docente',lower(t ipvin)),0,5),'docen',0,'aluno',1,'servi',2,3) F,
	// decode(lower(t ipmer),'ms-6',0,'ms-5',1,'ms-4',2,'ms-3',3,'ms-2',4,'ms-1',5,'pc 1',6,'pc 2',7,'pc 3',8) G,

	$sqlorderingcols=<<<EOT
,
nvl(dtafim,to_date('2199','YYYY')) C,
nvl2(sitctousp,decode(lower(sitctousp),'ativado',0,1),0) D,
decode(sitatl,'A',0,'P',1,'D',2,3) E,
numseqpgm H
EOT
;

	// A desc,
	// B,
	// F,
	// G,
	$sqlorderby=<<<EOT
order by
C desc,
D,
E,
H desc
EOT
;

	// (case when nvl(dtafim,to_date('2199','YYYY')) > sysdate AND dtaini < sysdate then 0 else 1 end),
	$sqlqrylabels = $sqlqry;
	$sqlqrylabels = str_replace("WHERE_EXPRESSION"," WHERE rownum < 1",$sqlqrylabels);
	$sqlqrylabels = str_replace("ORDERINGCOWS","",$sqlqrylabels);

	$stid = oci_parse($conn, $sqlqrylabels);
	if(!$stid){ exit; }

	$r=oci_execute($stid);
	if(!$r){ exit; }

	$collabels = array();
	$ncols = oci_num_fields($stid);
	for($i = 1; $i <= $ncols; $i++){
		$collabels[strtoupper(oci_field_name($stid,$i))]['label']=oci_field_name($stid,$i);
		$collabels[strtoupper(oci_field_name($stid,$i))]['name']=oci_field_name($stid,$i);
		$collabels[strtoupper(oci_field_name($stid,$i))]['type']=oci_field_type($stid,$i);
		$collabels[strtoupper(oci_field_name($stid,$i))]['where']=array();
	}

	oci_free_statement($stid);

	$out=array();
	preg_match_all("/((\S+)\s*:\s*)?(\S+|\\\".+\\\")/",$_SESSION['q'],$out,PREG_PATTERN_ORDER);
/*
RECHECK

echo "\n<xmp>\n";
print_r($out);
echo "\n</xmp>\n";
*/

	for($ial=0;$ial<count($out[2]);$ial++){
		$label = strtoupper($out[2][$ial] == "" ? $DEFAULT_LABEL : $out[2][$ial]);
		if(array_key_exists($label,$collabels)){
			array_push($collabels[$label]['where'],trim($out[3][$ial]," -.;,'\""));
		}
	}

/*
RECHECK
 
echo "\n<xmp>\n";
print_r($collabels);
echo "\n</xmp>\n";

exit;
*/	
	// HTML TABLE HEADER
	
	print "<table border='0'>\n";
	print "<thead><tr>";
	print "<td>Linha</td>\n";
	foreach($collabels as $collabel){
		print "<th>".$collabel['label']."</th>";
	}
	print "</tr></thead>";
	print "<tbody>";

	//BUILD SQL EXPRESSION	

	$sqlstrf = array();
	$asqlqry = array();
	$a_binds = array();
	$licount = 7; // quantidade de critérios
	$totlins = 0;
	
	for($licrit=0;$licrit<$licount;$licrit++){
	
		$al_qry = array();
		foreach($collabels as $collabel){
			if(count($collabel['where'])>0){
				if($collabel['type'] === 'VARCHAR2' || $collabel['type'] === 'CHAR'){
					switch($licrit){
						case 0: $bindvar = ":".$collabel['label'].'_'.$licrit;
								$bindxpr = $collabel['name']." = ".$bindvar;
								array_push($al_qry,$bindxpr);
								$a_binds[$bindvar] = implode(' ',$collabel['where']);
								unset($bindvar);
								unset($bindxpr);
								break;
						
						case 1: if(count($collabel['where'])>2 && count($collabel['where'])<5){
									$bindvar = ":".$collabel['label'].'_'.$licrit;
									$bindxpr = "regexp_like(".$collabel['name'].",".$bindvar.")";
									array_push($al_qry,$bindxpr);
									$a_binds[$bindvar] = '^'.implode('\S*?\s+?',$collabel['where']).'\S*$';
									unset($bindvar);
									unset($bindxpr);
								}
								break;
						
						case 2: if(count($collabel['where'])>2 && count($collabel['where'])<5){
									$al_tmp = array();
									$i=0;
									$bindvar='';
									foreach(quickperm($collabel['where']) as $rot_tmp){
										if($i>0){
											$bindvar = ":".$collabel['label'].'_'.$i.'_'.$licrit;
											array_push($al_tmp,"regexp_like(".$collabel['name'].",".$bindvar.")");
											$a_binds[$bindvar] = '^'.implode('\S*?\s+?',$rot_tmp).'\S+$';
										}
										$i++;
									}
									if(count($al_tmp)>0) {
										$bindxpr='('.implode(' OR ',$al_tmp).')';
										array_push($al_qry,$bindxpr);
									}
									unset($al_tmp);
									unset($rot_tmp);
									unset($bindvar);
									unset($bindxpr);
									unset($i);
								}
								break;
								
						case 3: if(count($collabel['where'])>2){
									$rot_tmp = array_merge(array(),$collabel['where']);
									array_push($rot_tmp,array_shift($rot_tmp));
									$bindvar = ":".$collabel['label'].'_'.$licrit;
									$bindxpr = $collabel['name']." like ".$bindvar;
									array_push($al_qry,$bindxpr);
									$a_binds[$bindvar] = implode('% ',$rot_tmp);
									unset($rot_tmp);
									unset($bindvar);
									unset($bindxpr);
								}
								break;
								
						case 4: if(count($collabel['where'])>2){
									$rot_tmp = array_merge(array(),$collabel['where']);
									array_push($rot_tmp,array_shift($rot_tmp));
									$bindvar = ":".$collabel['label'].'_'.$licrit;
									$bindxpr = $collabel['name']." like ".$bindvar;
									array_push($al_qry,$bindxpr);
									$a_binds[$bindvar] = implode('%',$rot_tmp);
									unset($rot_tmp);
									unset($bindvar);
									unset($bindxpr);
								}
								break;
						
						case 5: for($i=0; $i<count($collabel['where']); $i++){
									$bindvar1=':'.$collabel['label'].'_s_'.$i.'_'.$licrit;
									$bindvar2=':'.$collabel['label'].'_n_'.$i.'_'.$licrit;
									array_push($al_qry,"(".$collabel['name']." like ".$bindvar1." OR ".$collabel['name']." like ".$bindvar2.")");
									$a_binds[$bindvar1] = $collabel['where'][$i].'%';
									$a_binds[$bindvar2] = '% '.$collabel['where'][$i].'%';
								}
								unset($bindvar1);
								unset($bindvar2);
								unset($bindxpr);
								unset($i);
								break;
								
						case 6: for($i=0; $i<count($collabel['where']); $i++){
									$bindvar=':'.$collabel['label'].'_'.$i.'_'.$licrit;
									$bindxpr= $collabel['name']." like ".$bindvar;
									array_push($al_qry,$bindxpr);
									$a_binds[$bindvar] = '%'.$collabel['where'][$i].'%';
								}
								unset($bindvar);
								unset($bindxpr);
								unset($i);
								break;
					}
				}
				elseif($collabel['type'] === 'NUMBER'){
					if(($licrit == 5) || ($licrit == 6)){
						$i=0;
						$bindvar="";
						foreach($collabel['where'] as $clab){
							if(is_numeric($clab)){
								$bindvar = ':'.$collabel['label'].'_'.$i.'_'.$licrit;
								array_push($al_qry," ".$collabel['name']." = ".$bindvar);
								$a_binds[$bindvar] = $clab;
								$i++;
							}
						}
						unset($bindvar);
						unset($i);
					}
				}
				else {
					if($licrit == 0){
						$i=0;
						$bindvar="";
						foreach($collabel['where'] as $clab){
							$bindvar = ':'.$collabel['label'].'_'.$i.'_'.$licrit;
							array_push($al_qry," ".$collabel['name']." = ".$bindvar);
							$a_binds[$bindvar] = $clab;
							$i++;
						}
						unset($bindvar);
						unset($i);
					}
				}
			}
		}
		
		if(count($al_qry)>0){
			array_push($asqlqry, "(".implode(" AND ", $al_qry).")");
			
			/*
			echo "<xmp>\n";			
			echo "passaqui $licrit\n";
			print_r($al_qry);
			echo "\n";
			echo $asqlqry[$licrit]."\n\n";
			echo "<br></xmp>\n<br>\n";
			*/


			$sqlstrf[$licrit] = $sqlqry;
		
			if(count($asqlqry)==0){
				$sqlstrf[$licrit] = str_replace("WHERE_EXPRESSION"," WHERE ROWNUM < 1",$sqlstrf[$licrit]);
			}
			else {
				$not_w = "";
				if(count($asqlqry) > 1){
					$not_w = implode(" OR ",array_slice($asqlqry,0,count($asqlqry)-1));
					// echo "passaqui: $not_w, ".print_r($asqlqry);
				}
				if(strlen(trim($not_w))>0){
					$sqlstrf[$licrit] = str_replace("WHERE_EXPRESSION"," WHERE ".$asqlqry[count($asqlqry)-1]." AND NOT (".$not_w.") AND ROWNUM <= ".MAX_ROWNUM,$sqlstrf[$licrit]);
				}
				else {
					$sqlstrf[$licrit] = str_replace("WHERE_EXPRESSION"," WHERE ".$asqlqry[count($asqlqry)-1]." AND ROWNUM <= ".MAX_ROWNUM,$sqlstrf[$licrit]);
				}
			}
		
			$sqlstrf[$licrit] = str_replace("ORDERINGCOWS",$sqlorderingcols,$sqlstrf[$licrit]);
		
			// a_binds[':QRYSTR']=trim(implode(' ',$collabels['NOMPES']['where']));

			fsqltabletr($sqlstrf[$licrit].' '.$sqlorderby,$conn,$a_binds,$ncols);
			flush();

			
		}
		
		unset($al_qry);
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
	unset($_SESSION['q']);

	oci_close($conn);

}

?>
<span>busca de pessoas na base replicada da USP para SIBi - seu IP.: <?=get_client_ip()?></span>
<br><br>
* obs: <br>
1. a busca se dará por padrão na coluna do nome da pessoa (<b>NOMPES</b>);<br>
2. use <b>nome_da_coluna</b>:<b>termo</b> para busca nas demais colunas <?PHP if(count($collabels) > 0){ echo '('.implode(', ',array_keys($collabels)).')'; } ?>;<br>
3. use <b>aspas</b> duplas ou simples cercando o <b>termo</b> caso desejada busca exata com espaços;<br>
4. a quantidade de registros é <b>limitada em <?=MAX_ROWNUM?></b>, buscas muito genéricas não retornarão todos os registros possíveis.
</body>
</html>
<?php

function fsqltabletr($t_sqlryf,$t_conn,$abinds,$qcols){ 

	// echo "<!-- $t_sqlryf -->\n";

	global $totlins;

	if($totlins > MAX_ROWNUM) return true;

	$ret = false;
	$stid = oci_parse($t_conn, $t_sqlryf);
	if(!$stid){ exit; }
	
	foreach(array_keys($abinds) as $kbind){
		oci_bind_by_name($stid, $kbind, $abinds[$kbind]) or print_r('<BR>\n['.'olha isso:'.$kbind.' -> '.$abinds[$kbind].']');
	}

	$r=oci_execute($stid) or print_r($t_sqlryf);
	if(!$r){ exit; }

	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
		$totlins++;
		if($totlins <= MAX_ROWNUM){
			print "<tr>\n";
			print "<td>".$totlins."</td>\n";
			$i=0;
			foreach ($row as $item) {
				$i++;
				print "    <td>" . ($item !== null ? htmlentities($item, ENT_QUOTES) : "&nbsp;") . "</td>\n";
				$ret = true;
				if($i >= $qcols) break;
			}
			print "</tr>\n";
		}
	}
	
	oci_free_statement($stid);
	
	return $ret;

}

function get_client_ip() {
     $ipaddress = '';
     if (array_key_exists('HTTP_CLIENT_IP',$_SERVER) && $_SERVER['HTTP_CLIENT_IP'])
         $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
     else if(array_key_exists('HTTP_X_FORWARDED_FOR',$_SERVER) && $_SERVER['HTTP_X_FORWARDED_FOR'])
         $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
     else if(array_key_exists('HTTP_X_FORWARDED',$_SERVER) && $_SERVER['HTTP_X_FORWARDED'])
         $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
     else if(array_key_exists('HTTP_FORWARDED_FOR',$_SERVER) && $_SERVER['HTTP_FORWARDED_FOR'])
         $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
     else if(array_key_exists('HTTP_FORWARDED',$_SERVER) && $_SERVER['HTTP_FORWARDED'])
         $ipaddress = $_SERVER['HTTP_FORWARDED'];
     else if(array_key_exists('REMOTE_ADDR',$_SERVER) && $_SERVER['REMOTE_ADDR'])
         $ipaddress = $_SERVER['REMOTE_ADDR'];
     else
         $ipaddress = 'UNKNOWN';

     return $ipaddress; 
}

function quickperm($qin){

	if(!is_array($qin)) return null;

	$a=array_merge(array(),$qin);

	$qout = array();
	$iout = 0;

	$N = count($a);
	$p = array();
	for($i=0;$i<=$N;$i++){
		$p[$i] = $i;
	}
	$i=1;
	while($i < $N){
		
		$qout[$iout++] = $a;
		
		$p[$i]--;
		$j = ((($i % 2) == 0) ? 0 : $p[$i]);
		
		$temp=$a[$j];
		$a[$j]=$a[$i];
		$a[$i]=$temp;
		
		$i = 1;
		while($p[$i] == 0){
			$p[$i] = $i;
			$i++;
		}
	}

	$qout[$iout++] = $a;
	
	return $qout;

}


?>
