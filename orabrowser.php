<?php

/*
* Jan Leduc de Lara - Sistema Integrado de Bibliotecas da USP - 29/mar/2017
* alterado em 29/mar/2017
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

