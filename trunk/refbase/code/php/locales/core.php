<?php
$f = $refbaseDir ."locales/".$locale."/common.inc";
//echo $f;
ob_start();
	readfile( $f );
	$s = "\$loc=array(".ob_get_contents().");";
	eval( $s );
ob_end_clean();
?>