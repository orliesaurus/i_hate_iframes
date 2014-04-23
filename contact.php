<?php
/*
::What::

-> This script uses your Mailjet Account to send an email to a user with a URL, if the user clicks on the link he's added on a contact list.

::Requirements Pre-Usage::

-> A mailjet account with provisioned API keys
-> A contact list
-> PHP
*/

//Settings
require('config.php');


 function APICall($method,$res,$params) {
 	global $APIKey;
 	global $secretKey;
 	$param_array = json_decode($params,true);
	$URL = 'https://'.$APIKey.':'.$secretKey.'@'.'api.mailjet.com/v3/REST/'.$res;
	$curl = curl_init();
	if (strtolower($method) == "post") { $method = 'POST'; }
	if (strtolower($method) == "get") { $method = 'GET'; }
	//print "doing ".$method." query on ".$res." with ".$params." \r\n";
	
	curl_setopt_array($curl, array(
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_URL => $URL,
	    CURLOPT_USERAGENT => 'Orlando-PHP-Wrapper',
		CURLOPT_CUSTOMREQUEST => $method,
	    CURLOPT_POSTFIELDS => $param_array
	));
	//Run pony! Run!
	$response = curl_exec($curl);
	$answer = json_decode($response, true);
	@$reply = [ $answer["Total"], $answer["Data"] ];
	$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	echo $http_status;
	if ( $http_status != 200) {
		echo $response;

		if ( $answer['ErrorIdentifier'] != 0 ) {

			die('<h1>Request failed. Server response is that contact address already exist in contact list. Halting</h1>');
	}
		die('<h1>Request failed. Server response failed. Halting</h1>');
	}
	
	//dump some stuff for debug
	//errors pls?
	echo curl_error($curl);
	curl_close($curl);
	//RETURNS A TUPLE WITH TOTAL NUMBER OF ELEMENTS AND THE ACTUAL DATA
	return $reply;
}
/*
Take the email of the new guy, MD5 his address with a static string (mailjet) to add a tiny bit of entropy
The MD5 will be passed as a parameter of the URL he will click so that it secures the script from kids who would just try subscribe tons of people by URL
*/
function makeConfirmation($newguy) {
	$confirmCode = md5($newguy."mailjet");
	return $confirmCode;
}

function checkConfirmation($conf,$email) {
	if($conf== md5($email.'mailjet')) {
		return true;
	} else { return false; }
}
/*
This function is used to send an email to a guy that has put his email in the newsletter field.
*/

function sendEmail($APIKey,$secretKey,$buddy) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_URL => 'https://'.$APIKey.':'.$secretKey.'@'.'api.mailjet.com/v3/send',
	    CURLOPT_USERAGENT => 'MJ-PHP-Newsletter-Widget',
	    CURLOPT_POST => 1,
	    CURLOPT_POSTFIELDS => array(
	        'from' => 'orlando+send@mailjet.com',
	        'to' => $buddy,
	        /*This below is where you edit your mailing list body subject etc, you can put a personalised message in here as you wish and you can use HTML as long as u escape it :)*/
	        'subject' => 'Welcome to my mailing list',
	        'text' => 'Body of email here',
	        'html' => 'Click the confirm  URL to ensure this is your email and to be added to the mailing list <a href="'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'contact.php?confirmation=' .makeConfirmation($buddy).'&email='.urlencode($buddy).'">CONFIRM</a>'
	    )
	));
	//Run pony! Run!
	$resp = curl_exec($curl);


	//dump some stuff for debug
	//var_dump($resp);
	//errors pls?
	echo curl_error($curl);
	curl_close($curl);
}

/* If we get the confirmation email with the code we can add the address to the contactlist*/

if ( isset($_GET['confirmation']) && isset($_GET['email']) ) {
	global $mylist;
	$confirmation = $_GET['confirmation'];
	$email = rawurldecode($_GET['email']);
	if (checkConfirmation($confirmation,$email)) {
		//Add him..
		echo $email;
		$createdContact = APICall("POST","contact",'{"Email":'.$email.'}');
		$contactId  = $createdContact[1][0]['ID'];
		$addtolist = APICall("POST","listrecipient",'{"ListID":'.$mylist.',"ContactID":'.$contactId.'}');
		
		echo "Success! You've been added to the mailing list";
	}
	else {
		echo "Fail! You have not been added to the mailing list";
	}
}
elseif (isset($_POST['email'])) {
	if (strlen($_POST['email']) > 0) {
	$pemail = $_POST['email'];
	echo "got email address ".$pemail;
	sendEmail($APIKey,$secretKey,$pemail);
	echo "sent email";

	}
	else {
		echo "No Valid Email detected";
	}
}
else {  echo "no email or auth code, should you be here?"; } 




?>