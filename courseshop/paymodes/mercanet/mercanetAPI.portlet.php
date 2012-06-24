<?php

$logo_id2 = "logo_barchen.jpg";
$return_context = 'mercanetback' . '-' .$this->shopblock->instance->id. '-'.(0 + $this->shopblock->pinned).'-' .$portlet->transactionid;

//		Affectation des param�tres obligatoires

$parms[] = "merchant_id=".$CFG->block_courseshop_mercanet_merchant_id;
$parms[] = "merchant_country=".strtolower(substr($CFG->block_courseshop_sellerbillingcountry, 0, 2));
$parms[] = "amount=".floor($portlet->amount * 100);
$parms[] = "currency_code=".$CFG->block_courseshop_mercanet_currency_code;

// Initialisation du chemin du fichier pathfile (� modifier)
    //   ex :
    //    -> Windows : $parm="$parm pathfile=c:/repertoire/pathfile";
    //    -> Unix    : $parm="$parm pathfile=/home/repertoire/pathfile";

$os = (preg_match('/Linux/i', $CFG->os)) ? 'linux' : 'win' ;
$parms[] = 'pathfile='.$this->get_pathfile($os);

	//		Si aucun transaction_id n'est affect�, request en g�n�re
	//		un automatiquement � partir de heure/minutes/secondes
	//		R�f�rez vous au Guide du Programmeur pour
	//		les r�serves �mises sur cette fonctionnalit�
	//
$parms[] = 'transaction_id='.$portlet->onlinetransactionid;

	//		Affectation dynamique des autres param�tres
	// 		Les valeurs propos�es ne sont que des exemples
	// 		Les champs et leur utilisation sont expliqu�s dans le Dictionnaire des donn�es
	//
$parms[] = 'normal_return_url='.$portlet->returnurl;
$parms[] = 'cancel_return_url='.$portlet->cancelurl;
$parms[] = 'automatic_response_url='.$portlet->ipnurl;
$parm = "language=".strtolower(substr(current_language(), 0, 2));
	//		$parm="$parm payment_means=CB,2,VISA,2,MASTERCARD,2";
	//		$parm="$parm header_flag=no";
	//		$parm="$parm capture_day=";
	//		$parm="$parm capture_mode=";
	//		$parm="$parm bgcolor=";
	//		$parm="$parm block_align=";
	//		$parm="$parm block_order=";
	//		$parm="$parm textcolor=";
	//		$parm="$parm receipt_complement=";
	//		$parm="$parm caddie=mon_caddie";
$parms[] = 'customer_id=';
$parms[] = 'customer_email='.$portlet->customer->email;
	//		$parm="$parm customer_ip_address=";
	// 		$parms[] = 'data=';
$parms[] = 'return_context='.$return_context;
	//		$parm="$parm target=";
	//		$parm="$parm order_id=";


	//		Les valeurs suivantes ne sont utilisables qu'en pr�-production
	//		Elles n�cessitent l'installation de vos fichiers sur le serveur de paiement
	//
	// 		$parm="$parm normal_return_logo=";
	// 		$parm="$parm cancel_return_logo=";
	// 		$parm="$parm submit_logo=";
	// 		$parm="$parm logo_id=";
	// 		$parm="$parm logo_id2=";
	// 		$parm="$parm advert=";
	// 		$parm="$parm background_id=";
	// 		$parm="$parm templatefile=";


	//		insertion de la commande en base de donn�es (optionnel)
	//		A d�velopper en fonction de votre syst�me d'information

	// Initialisation du chemin de l'executable request (� modifier)
	// ex :
	// -> Windows : $path_bin = "c:/repertoire/bin/request";
	// -> Unix    : $path_bin = "/home/repertoire/bin/request";
	//

	$path_bin = $this->get_request_bin($os);

	if (!is_file($path_bin) || !is_executable($path_bin)){
  		$APIcallerrorstr = get_string('errorcallingAPI', 'block_courseshop', $path_bin);
	  	echo ("<br/><center>$APIcallerrorstr</center><br/>");
	  	return;
	}

	//	Appel du binaire request
	// La fonction escapeshellcmd() est incompatible avec certaines options avanc�es
  	// comme le paiement en plusieurs fois qui n�cessite  des caract�res sp�ciaux 
  	// dans le param�tre data de la requ�te de paiement.
  	// Dans ce cas particulier, il est pr�f�rable d.ex�cuter la fonction escapeshellcmd()
  	// sur chacun des param�tres que l.on veut passer � l.ex�cutable sauf sur le param�tre data.
	$parmstring = escapeshellcmd(implode(' ', $parms));
	$result = exec("{$path_bin} $parmstring");

	//	sortie de la fonction : $result=!code!error!buffer!
	//	    - code=0	: la fonction g�n�re une page html contenue dans la variable buffer
	//	    - code=-1 	: La fonction retourne un message d'erreur dans la variable error

	//On separe les differents champs et on les met dans une variable tableau

	$mercanetanswer = explode ("!", "$result");
	
	//	r�cup�ration des param�tres

	$code = $mercanetanswer[1];
	$error = $mercanetanswer[2];
	$message = $mercanetanswer[3];

	//  analyse du code retour

  	if (($code == "") && ($error == "") ){
  		$APIcallerrorstr = get_string('errorcallingAPI2', 'block_courseshop', $path_bin);
	  	echo ("<br/><center>$APIcallerrorstr</center><br/>");
	  	return;
	}

	//	Erreur, affiche le message d'erreur

	else if ($code != 0){
		$mercanetapierrorstr = get_string('mercanetapierror', 'block_courseshop');
		echo "<center><b>$mercanetapierrorstr</b></center>";
		echo '<br/><br/>';
		print_string('mercaneterror', 'block_courseshop', $error);
	}

	//	OK, affiche le formulaire HTML
	else {
		echo '<br/><br/>';
		# OK, affichage du mode DEBUG si activ�
		echo $error.'<br/>';
		echo $message.'<br/>';
	}

?>

