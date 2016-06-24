<?php

	include 'lib/leboncoin.php';
	$leboncoin = new LeBonCoin();

	$annonce = $leboncoin->getAnnonce(968604617);
	echo '<pre>'.print_r($annonce, true).'</pre>';

?>