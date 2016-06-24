<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

class LeBonCoin {

	private $_base = "https://mobile.leboncoin.fr/";
	private $_app_id = "leboncoin_iphone";
	private $_key = "c17d5009f2de512fae68880ea4375ef8adbc34e56a7444c0248fcb63bd0ffaed9995200a46cee0176654b244c9b9f2934d935576650b15c6792621e94cbec163";
	private $_access;

	private $_account;
	private $_token;

	public $tableau_prix;
	public $tableau_categories;

	public function __construct(){

		$this->_access = "app_id={$this->_app_id}&key={$this->_key}";
		$this->tableau_prix = $this->tableauPrix();
		$this->tableau_categories = $this->updateCategories();

	}

	// Connexion à LeBoncoin
	public function connexion($email, $pass){

		$options = "action=login&app_id=leboncoin_iphone&email={$email}&password={$pass}";
		$account = json_decode($this->curl("https://apimobile.leboncoin.fr/sm", "{$options}&{$this->_access}"));
		$account->mail = $email;

		if(!isset($account->token)) return $account;
		$this->_account = $account;
		$this->_token = $this->_account->token;
		return $this->_account;

	}

	// Teste si la connexion est établie
	public function isConnected(){

		return ($this->_account && $this->_token);

	}

	// Récuperer annonces utilisateur
	public function getVentes($account = false){

		if(!$account && isset($this->_account->admin_id)){
			$account = $this->_account->admin_id;
		}elseif(!$account && !isset($this->_account->admin_id)){
			return false;
		}
		$result = $this->curl($this->_base . "/templates/api/dashboard.json?o=0&sp=0&store_id={$account}", $this->_access);
		return json_decode($result);

	}

	// Récupérer toutes les annonces
	public function getAnnonces($query, $page = 1, $options = false){
		
		if(!is_numeric($page)) return 'Page incorrecte';

		$options_string = (is_array($options))?$this->formatOptions($options):"";
		$url = $this->_base . "templates/api/list.json?q=" . urlencode($query) . "&o={$page}{$options_string}";
		$result = $this->curl($url, $this->_access);

		return array('options' => (Object) $options, 'recherche' => $query, 'page' => $page, 'url' => $url,'annonces' => json_decode($result));

	}

	// Récupérer les détails d'une annonce avec son id
	public function getAnnonce($id){

		$result = $this->curl($this->_base . "templates/api/view.json?ad_id={$id}", "ad_id={$id}&{$this->_access}");
		return json_decode($result);

	}

	/*	
		~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

							OPTIONS

		~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~		
		
			{
				search_title_only 	= it,
				categorie 			= c,
				filter (prix|date)	= sp,
				particulier 		= f = p
				pro 				= f = c
				localisation(array) = zipcode
				urgent_only			= ur
				prix_min 			= ps
				prix_max			= pe
			}

		it	 	= Titre uniquement (boolean)
		c 		= catégorie	(int)
		q 		= Mot clé (string)
		o 		= Page (int)
		sp 		= Filtrer (int) [0 = par date | 1 = par prix]
		f 		= Type de personne (string) [p = particuliers | c = pro | a = tous]
		zipcode = Code postal (string) {délimiteur: ,}
		ur		= Urgent uniquement (boolean)
		ps		= Prix min (int) {voir plus bas}
		pe		= Prix max (int) {voir plus bas}

		  Url   Prix      Url   Prix      Url   Prix      Url   Prix   
		 ----- ------ 	 ----- ------ 	 ----- ------ 	 ----- ------- 
		    0      0        4     40        8    200       12   1000   
		    1     10        5     50        9    300       13   >1000  
		    2     20        6     75       10    400                   
		    3     30        7    100       11    500                   

		*/

	private function formatOptions($options){

		if(count($options) == 0) return "";
		$options = (Object) $options;
		$it = (isset($options->search_title_only) && $options->search_title_only)?"it=1&":"";
		$c = (isset($options->categorie))?"c={$options->categorie}&":"";
		$filter = array("date" => 0, "prix" => 1);
		$sp = (isset($options->filter) && ($options->filter=="prix"||$options->filter=="date"))?"sp={" . $filter[$options->filter] . "}&":"";
		$f = (isset($options->particulier) && !$options->particulier)?"f=c&":(isset($options->pro) && !$options->pro)?"f=p&":"";
		$zipcode = (isset($options->localisation)&&is_array($options->localisation))?"zipcode=" . implode(',', $options->localisation) . "&":"";
		$ur = (isset($options->urgent_only) && $options->urgent_only)?"ur=1&":"";
		$ps = (isset($options->prix_min) && $options->prix_min <= 1000)?"ps=" . $this->nombreProche($options->prix_min, true) . "&":"ps=12&";
		$pe = (isset($options->prix_max) && $options->prix_max > 10)?"pe=" . $this->nombreProche($options->prix_max) . "&":"pe=13&";
		$construct = preg_replace("#&$#","","&{$it}{$c}{$sp}{$f}{$zipcode}{$ur}{$ps}{$pe}");
		return $construct;

	}

	// Récuperer la liste des catégories
	public function updateCategories($force = false){

		if(file_exists(dirname(__FILE__) . "/categories.ser")){
			$file = unserialize(file_get_contents(dirname(__FILE__) . "/categories.ser"));
			if(!empty($file) && is_array($file) && isset($file['timestamp']) && time() - $file['timestamp'] < 7257600){
				return $this->tableau_categories = $file;
			}
		}

		$page = $this->curl("https://www.leboncoin.fr/annonces/offres/");
		preg_match_all('#<a href="" data-category="(.*?)"(.*?)>(.*?)</a>#si', $page, $m);
		$categories = array("timestamp" => time());
		foreach ($m[1] as $k => $cat) {
			$name = trim($m[3][$k]);
			if(in_array($name, $categories)) continue;
			$categories[trim($cat)] = $name;
		}
		ksort($categories);

		file_put_contents(dirname(__FILE__) . '/categories.ser', serialize($categories), FILE_APPEND);
		return $categories;

	}

	// Récuperer tableau avec code postal depuis nom de la ville
	public function localisation($ville){

		$page = $this->curl("https://www.leboncoin.fr/beta/ajax/location_list.html?city=" . urlencode($this->removeAccents($ville)));
		preg_match_all('#<li>(.*?)</li>#si', $page, $m);
		$villes = array();
		foreach ($m[1] as $content) {
			$content = trim($content);
			preg_match('#([0-9]{5})$#', $content, $p);
			$villes[$p[1]] = trim(preg_replace('#([0-9]{5})$#', '', $content));
		}
		return $villes;

	}

	// Trouve la catégorie associée avec son nom
	public function searchCategorie($name){

		$result_prob = array("code" => 0, "nom" => "introuvable", "sim" => 0);
		foreach ($this->tableau_categories as $code => $nom) {
			similar_text(mb_strtolower(utf8_decode($name), "utf-8"), mb_strtolower($nom, "utf-8"), $sim);
			if($sim > $result_prob["sim"]) 
				$result_prob = array("code" => $code, "nom" => $nom, "search" => $name, "sim" => $sim);
		}
		return (Object) $result_prob;

	}

	private function nombreProche($nombre_donne, $before = true){
		if($nombre_donne>1000) return 13;
		$array = $this->tableau_prix;
		$nombre_diff = 0;
		foreach ($array as $prix => $url) {
			if($before){
				if($prix>$nombre_donne) break;
			}else{
				if($nombre_diff>=$nombre_donne) break;
			}
			$nombre_diff = $prix;
		}
		$result = $nombre_diff;
		return $array[$result];
	}

	private function removeAccents($str){
		$str = htmlentities($str, ENT_NOQUOTES, "utf-8");
		$str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
		$str = preg_replace('#&[^;]+;#', '', $str);
	    return $str;
	}

	public function getUrl($url){
		$page = $this->curl($url, $this->_access);
		return json_decode($page);
	}

	private function tableauPrix(){
		return $this->tableau_prix = array(
			0 		=> 	0, 	10 		=> 	1, 		20 => 	2,
			30 		=> 	3, 	40 		=> 	4, 		50 => 	5,
			75 		=> 	6, 	100 	=> 	7, 		200 => 	8,
			300 	=> 	9, 	400 	=> 	10, 	500 => 	11,
			1000 	=> 	12
		);
	}

	private function curl($url, $post = false, $cookie = false, $cache = false){
		$ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $url);
	    if($post != false){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			}
	    if($cookie != false){

	    	curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	    }
	    if(!$cache){
	    	$headers = array( 
	        	"Cache-Control: no-cache", 
	        ); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	    }
	    $ip = (isset($_SERVER["HTTP_CF_CONNECTING_IP"]))?$_SERVER["HTTP_CF_CONNECTING_IP"]:$_SERVER['REMOTE_ADDR'];
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array("REMOTE_ADDR: ".$ip, "HTTP_X_FORWARDED_FOR: ".$ip));
	    curl_setopt($ch, CURLOPT_USERAGENT,'Leboncoin/3.16.1 (iPhone; iOS 10.0; Scale/2.00)');
		curl_setopt($ch, CURLOPT_REFERER, $url);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 1
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	    $output = curl_exec($ch); 
	    curl_close($ch); 
	    return $output;
	}

}

