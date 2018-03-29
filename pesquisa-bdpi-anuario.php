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

$page = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int) $_GET['page'] : 0);
$limit = 1000;
$offset = ($page * $limit);

$sibiocnxstr = DB_BDPI_CONNSTR;

$bdpihostname = BDPI_HOSTNAME;

$conexao = pg_connect($sibiocnxstr) or die("Nao Conectado");

$thisfilename = basename(__FILE__);

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

	case (isset($_GET['mfidi']) && isset($_GET['tvl'])):
		$sqlqry1=<<<EOT
		select row_number() over () as linha, h.handle, 'http://${bdpihostname}/handle/'||h.handle _ahref_
		from item i
		inner join (select distinct item_id from metadatavalue where metadata_field_id = $1 and text_value = $2) m on (m.item_id = i.item_id)
		inner join handle h on (h.resource_id = i.item_id and h.resource_type_id = 2)
		where i.in_archive is true and i.withdrawn is false
		limit $limit offset $offset
EOT
;
		$showh = tabula($conexao,$sqlqry1,array($_GET['mfidi'],$_GET['tvl']));
		break;
		
	case (isset($_GET['mfidt']) && isset($_GET['mftt'])):
		$sqlqry1=<<<EOT
		select row_number() over (order by count(*) desc) as linha,
		coalesce(m.text_value,'n/a') mftttit, '${thisfilename}?mfidi='||m.metadata_field_id||'&tvl='||m.text_value _ahref_,  count(*) quantidade
		from item i
		inner join (select distinct item_id, text_value, metadata_field_id from metadatavalue where metadata_field_id = $1) m on m.item_id = i.item_id
		where i.in_archive is true and i.withdrawn is false
		group by m.text_value, m.metadata_field_id
		order by quantidade desc
		limit ${limit} offset ${offset}
EOT
;
		$showh = tabula($conexao,$sqlqry1,array($_GET['mfidt']),array('mftttit' => $_GET['mftt']));
		break;

	case (isset($_GET['sid']) && isset($_GET['sit'])):
		$sqlqry1=<<<EOT
		select row_number() over (order by coalesce($1||'.'||element||'.'||qualifier,$1||'.'||element)) as linha,
		       coalesce($1||'.'||element ||'.'||qualifier,$1||'.'||element) metadado,
		       scope_note as nota_de_escopo, '{$thisfilename}?mfidt='||metadata_field_id||'&mftt='||coalesce($1||'.'||element ||'.'||qualifier,$1||'.'||element) _ahref_
		from metadatafieldregistry
		where metadata_schema_id = $2
		order by metadado
		limit ${limit} offset ${offset}
EOT
;
		$showh = tabula($conexao,$sqlqry1,array($_GET['sit'],$_GET['sid']));
		break;

	case (isset($_GET['navby']) && ($_GET['navby']=='metadados')) :
		$sqlqry1=<<<EOT
		select namespace, short_id id, '${thisfilename}?sid='||metadata_schema_id||'&sit='||short_id as _ahref_
		from metadataschemaregistry
EOT
;
		$showh = tabula($conexao,$sqlqry1,array());
		break;
	case (isset($_GET['cid']) && array_key_exists('ano',$_GET) && array_key_exists('intl',$_GET) && array_key_exists('txa',$_GET)) :
		$sqlqry1=<<<EOT
		select row_number() over (order by name, left(A.text_value,4), B.text_value asc) as linha,
			community.name, left(A.text_value,4) ano, B.text_value intl, handle.handle, 'http://${bdpihostname}/handle/'||handle.handle as _ahref_
		from community
		inner join communities2item on communities2item.community_id = community.community_id
		       left join metadatavalue A on (communities2item.item_id = A.item_id and A.metadata_field_id = 15)
		       left join metadatavalue B on (communities2item.item_id = B.item_id and B.metadata_field_id = 105)
		       left join community2community on communities2item.community_id = community2community.child_comm_id
		       left join handle on handle.resource_type_id = 2 and resource_id = communities2item.item_id
		where community2community.child_comm_id is null
		      and community.community_id = $1
		      and coalesce(left(A.text_value,4),'') = $2
		      and coalesce(B.text_value,'') = $3
		order by name, ano, intl asc
EOT
;
		$showh = tabula($conexao,$sqlqry1,array($_GET['cid'], $_GET['ano'], $_GET['intl']));
		break;
	case (isset($_GET['navby']) && ($_GET['navby']=='qtrabxintlxano')) :
		$sqlqry1=<<<EOT
		select community.name, left(A.text_value,4) ano,
		       B.text_value intl, count(communities2item.item_id) q,
		       '${thisfilename}?cid='||community.community_id||'&ano='||coalesce(left(A.text_value,4),'')||'&intl='||coalesce(B.text_value,'')||'&txa=1' as _ahref_
		from community
		inner join communities2item on communities2item.community_id = community.community_id
		       left join metadatavalue A on (communities2item.item_id = A.item_id and A.metadata_field_id = 15)
		       left join metadatavalue B on (communities2item.item_id = B.item_id and B.metadata_field_id = 105)
		       left join community2community on communities2item.community_id = community2community.child_comm_id
		where community2community.child_comm_id is null
		group by community.community_id, community.name, left(A.text_value,4), B.text_value
		order by name, ano, intl asc
EOT
;
		$showh = tabula($conexao,$sqlqry1,array());
		break;
	case (count($_GET) == 0) :
		$sqlqry1=<<<EOT
		select A.tipo, A._ahref_ from
		(select 1 as lin, 'quantidade de trabalhos por ano (nacional/internacional)' as tipo, '${thisfilename}?navby=qtrabxintlxano' as _ahref_
		union select 3, 'navegar por metadados', '${thisfilename}?navby=metadados'
		union select 4, 'estatísticas do awstats', 'http://www.producao.usp.br/awstats/cgi-bin/awstats.pl') as A
		order by A.lin
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
	global $showh, $limit, $page, $_SERVER, $thisfilename;
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
			echo "<a href='".$thisfilename.$newqs.$conector.'page='.($page-1)."'>&lt;anterior</a>&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		if($showh['tam'] == $limit){
			echo "<a href='".$thisfilename.$newqs.$conector.'page='.($page+1)."'>pr&oacute;xima&gt;</a><br>\n";
		}
	}
}

function tabula($cons,$sqls,$arrs,$alls = array()){
	$ahref = -1;
	
	$pgq = pg_query_params($cons, $sqls, $arrs) or die("nao foi possivel realizar o select...\n".$sqls."\n");
	
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
		$arrout['outs'] .= "<td><a href='$pgr[$ahref]' target='_new'>$pgr[$i]</a></td>\n";
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
