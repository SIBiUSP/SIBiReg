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

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
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

<br>

<table>
<thead>
<th>opa</th>
<th>show</th>
</thead>
<tbody>
<tr>
<td>teste</td>
<td>nada</td>
</tr>
<tr>
<td>teste</td>
<td>nada</td>
</tr>
<tr>
<td>teste</td>
<td>nada</td>
</tr>
</tbody>
</table>


</body>
</html>
