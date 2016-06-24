# Leboncoin-api
***
#### Api du plus célèbre site entre particuliers **Leboncoin**

+ Connexion au compte
    - Récupérer les ventes
+ Faire une recherche avancée
+ Récupérer une annonce

### Initialiser la classe
``` 
    $leboncoin = new LeBonCoin();
```

### Connexion au compte
``` 
    $connect = $leboncoin->connexion("email", "pass");
```

#### Récuperer ventes
``` 
    // Si connexion établie
    $annonces = $leboncoin->getVentes();
    
    // D'un utilisateur avec son id
    $annonces = $leboncoin->getVentes(192138137);
```

### Récupérer annonces avec options
``` 
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
```

### Récupérer annonce
``` 
    $annonce = $leboncoin->getAnnonce(968604617);
```

*Thomas Cauquil | http://thomascauquil.fr*
*2016*