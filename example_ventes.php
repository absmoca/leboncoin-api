<?php

	include 'lib/leboncoin.php';
	$leboncoin = new LeBonCoin();

	$connect = $leboncoin->connexion("email", "pass");

	if($leboncoin->isConnected()){

		$annonces = $leboncoin->getVentes();
		echo '<pre>'.print_r($annonces, true).'</pre>';
		
	}else echo '<pre>'.print_r($connect, true).'</pre>';

?>