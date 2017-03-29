<?php
/*
* Jan Leduc de Lara - Sistema Integrado de Bibliotecas da USP - 16/jan/2014
* alterado em 28/mar/2017
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

$page = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int) $_GET['page'] : 0);
$limit = 1000;
$offset = ($page * $limit);

$sibiocnxstr = DB_BDPI_CONNSTR;

$conexao = pg_connect($sibiocnxstr) or die("Nao Conectado");

$thisfilename = basename(__FILE__);

$conexao = pg_connect($sibiocnxstr) or die("Nao Conectado");

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

$showh = array('outs' => NULL, 'tam' => 0);

switch(true){

	case (isset($_GET['biomedchid'])) :
		$sqlqry1=<<<EOT
		select '<a href="'||'/handle/BDPI/'||A.handle_id||'">'||A.handle_id||'</a>' handle_biomed_recebido_via_sword, '<a href="'||'/handle/BDPI/'||B.handle_id||'">'||B.handle_id||'</a>' handles_com_titulo_igual, A.titulo from
		(
			select distinct handle.handle_id, text_value titulo
			from metadatavalue
			inner join handle
			on (metadatavalue.item_id = handle.resource_id and handle.resource_type_id = 2 )
			inner join item i on (metadatavalue.item_id = i.item_id)
			where i.in_archive is true and i.withdrawn is false and
			metadata_field_id = 64
			and metadatavalue.item_id in
			(
				select collection2item.item_id
				from collection2item
				inner join collection
				on collection2item.collection_id = collection.collection_id
				inner join handle
				on collection.collection_id = handle.resource_id
				where handle.handle_id = 34521
			)
		) A
		left join
		(
			select handle.handle_id, trim(T.text_value) titulo from
			(select distinct item_id, text_value from metadatavalue where metadata_field_id = 64) T
			inner join
			(select distinct item_id, text_value from metadatavalue where lower(text_value) = 'biomed central' and metadata_field_id in (39,89)) P
			on T.item_id = P.item_id
			inner join handle
			on (T.item_id = handle.resource_id and handle.resource_type_id = 2 )
			inner join item i on (T.item_id = i.item_id)
			where i.in_archive is true and i.withdrawn is false
			and 
			T.item_id not in 
			(
				select collection2item.item_id
				from collection2item
				inner join collection
				on collection2item.collection_id = collection.collection_id
				inner join handle
				on collection.collection_id = handle.resource_id
				where handle.handle_id = 34521
			)
		) B
		on A.titulo = B.titulo
		where A.handle_id = $1
		order by A.titulo
EOT
;
		$showh = tabula($conexao,$sqlqry1,array($_GET['biomedchid']));
		break;
	case (count($_GET) == 0) :
		$sqlqry1=<<<EOT
			select row_number() over (
			   order by case when string_agg(cast(B.handle_id as text),';') is NULL then 1
			                 else 0
			            end, A.titulo) as linha, A.handle_id handle_biomed_recebido_via_sword, string_agg( cast(B.handle_id as text) , ';' ) handles_com_titulo_igual, A.titulo, '${thisfilename}?biomedchid='||A.handle_id as _ahref_ from
			(
				select distinct handle.handle_id, text_value titulo
				from metadatavalue
				inner join handle
				on (metadatavalue.item_id = handle.resource_id and handle.resource_type_id = 2 )
				inner join item i on (metadatavalue.item_id = i.item_id)
				where i.in_archive is true and i.withdrawn is false and
				metadata_field_id = 64 and metadatavalue.item_id in
				(
					select collection2item.item_id
					from collection2item
					inner join collection
					on collection2item.collection_id = collection.collection_id
					inner join handle
					on collection.collection_id = handle.resource_id
					where handle.handle_id = 34521
				)
			) A
			left join
			(
				select handle.handle_id, trim(T.text_value) titulo from
				(select distinct item_id, text_value from metadatavalue where metadata_field_id = 64) T
				inner join
				(select distinct item_id, text_value from metadatavalue where lower(text_value) = 'biomed central' and metadata_field_id in (39,89)) P
				on T.item_id = P.item_id
				inner join handle
				on (T.item_id = handle.resource_id and handle.resource_type_id = 2 )
				inner join item i on (T.item_id = i.item_id)
				where i.in_archive is true and i.withdrawn is false and				
				T.item_id not in 
				(
					select collection2item.item_id
					from collection2item
					inner join collection
					on collection2item.collection_id = collection.collection_id
					inner join handle
					on collection.collection_id = handle.resource_id
					where handle.handle_id = 34521
				)
			) B
			on A.titulo = B.titulo
			group by A.titulo, A.handle_id
			order by case when string_agg(cast(B.handle_id as text),';') is NULL then 1
			              else 0
				 end, A.titulo
EOT
;
		$showh = tabula($conexao,$sqlqry1,array());
		break;
}

if(strlen($showh['outs'])>0){
	prevnext();
	echo $showh['outs'];
	prevnext();
	echo "<br><br><br><br>\n";
}
else{
	echo "nada";
}



?>

</body>
</html>
<?php

function prevnext(){
	global $showh, $limit, $page, $_SERVER;
	if(($page>0) || ($showh['tam'] == $limit)){	
		$conector = '';
		$newqs = '?';
		if(strlen(trim($_SERVER["QUERY_STRING"]))>0){
			$conector =  '&';		
			$newqs .= $_SERVER["QUERY_STRING"];
			if(isset($_GET['page'])){
				$newqs = str_replace('&page='.$_GET['page'],'',$newqs);
				$newqs = str_replace('?page='.$_GET['page'],'',$newqs);
			}
		}
		if($page>0){
			echo "<a href='".$_SERVER["SCRIPT_URI"].$newqs.$conector.'page='.($page-1)."'>&lt;anterior</a>&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		if($showh['tam'] == $limit){
			echo "<a href='".$_SERVER["SCRIPT_URI"].$newqs.$conector.'page='.($page+1)."'>pr&oacute;xima&gt;</a><br>\n";
		}
	}
}

function tabula($cons,$sqls,$arrs,$alls = array()){
	$ahref = -1;
	
	$pgq = pg_query_params($cons, $sqls, $arrs) or die("nao foi possivel realizar o select");
	
	$arrout = array('outs' => '', 'tam' => 0);
	
	$arrout['outs'] = "<table>\n";
	$pgt = pg_num_fields($pgq);
	if($pgt>0){
	  $arrout['outs'] .= "<thead><tr>\n";
	  for($i=0;$i<$pgt;$i++){
		if(($ahref==-1) && strtolower(pg_field_name($pgq,$i)) == '_ahref_'){
		  $ahref = $i;
		  continue;
		}
		if(isset($alls[pg_field_name($pgq,$i)])) {
			$arrout['outs'] .= '<th>'.implode(' ',explode('_',$alls[pg_field_name($pgq,$i)])).'</th>';
			continue;
		}
		$arrout['outs'] .= '<th>'.implode(' ',explode('_',pg_field_name($pgq,$i))).'</th>';
	  }
	  $arrout['outs'] .= "</tr></thead>\n";
	}	
	$arrout['outs'] .= "<tbody>\n";
	while ($pgr=pg_fetch_row($pgq)) {
	  $arrout['outs'] .= "<tr>";
	  for($i=0; $i < count($pgr); $i++) {
	     if($ahref > -1){
	        if($ahref == $i) continue;
		$arrout['outs'] .= "<td><a href='$pgr[$ahref]'>$pgr[$i]</a></td>\n";
	     }
	     else {
		$arrout['outs'] .= '    <td>'.$pgr[$i].'</td>'."\n";
	     }
	  }
	  $arrout['outs'] .=  "</tr>\n";
	  $arrout['tam']++;
	}
	$arrout['outs'] .= "</tbody>\n</table>\n";

	return $arrout;

}


?>
