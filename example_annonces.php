<?php

	include 'lib/leboncoin.php';
	$leboncoin = new LeBonCoin();

	$options = array(
		"search_title_only" => 1,
		"localisation" => array("34500","34000","91000"),
		"categorie" => $leboncoin->searchCategorie("informatique")->code,
		"prix_min" => 150,
		"prix_max" => 10000,
		"particulier" => true,
		"pro" => false,
		"urgent_only" => false
	);

	$annonces = $leboncoin->getAnnonces("AlienWare", 1, $options);

	echo '<pre>'.print_r($annonces, true).'</pre>';

?>