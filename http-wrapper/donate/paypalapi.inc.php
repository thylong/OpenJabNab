<?php
  function construit_url_paypal()
  {
    $serveur_paypal = "https://www.paypal.com/webscr&cmd=_express-checkout&token=";
    $api_paypal = 'https://api-3t.sandbox.paypal.com/nvp?'; // Site de l'API PayPal. On ajoute déjà le ? afin de concaténer directement les paramètres.
    $api_paypal = 'https://api-3t.paypal.com/nvp?';
    $version = 63.0; // Version de l'API
     
    $user = 'webmas_1361259034_biz_api1.openjabnab.fr';
    $pass = '1361259057';
    $signature = 'AiPC9BjkCyDFQXbSkoZcgqH3hpacAJpHvddwiBs9P3APYQfVIvuB1uh2';
 
    $api_paypal = $api_paypal.'VERSION='.$version.'&USER='.$user.'&PWD='.$pass.'&SIGNATURE='.$signature; // Ajoute tous les paramètres
 
    return  $api_paypal; // Renvoie la chaîne contenant tous nos paramètres.
  }

  function recup_param_paypal($resultat_paypal)
  {
    $liste_parametres = explode("&",$resultat_paypal); // Crée un tableau de paramètres
    foreach($liste_parametres as $param_paypal) // Pour chaque paramètre
    {
        list($nom, $valeur) = explode("=", $param_paypal); // Sépare le nom et la valeur
        $liste_param_paypal[$nom]=urldecode($valeur); // Crée l'array final
    }
    return $liste_param_paypal; // Retourne l'array
  }

