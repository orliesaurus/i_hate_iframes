<?php
/*
::What::

-> This script uses your Mailjet Account to send an email to a user with a URL, if the user clicks on the link he's added on a contact list.

::Requirements Pre-Usage::

-> The Mailjet PHP Wrapper
-> A mailjet account with provisioned API keys
-> A contact list
-> PHP
*/


include_once __DIR__ . '/vendor/autoload.php';
use Mailjet\Api as MailjetApi;
use Mailjet\Model\Apitoken;
use Mailjet\Model\Contact;

$APIKey = 'SO MUCH WIN';
$secretKey = 'HAHAHA BUSINESS';

$wrapper = new MailjetApi\Api($APIKey, $secretKey);
$newguy = "orlando+phpv01@mailjet.com";

function createContact($wrapper, $buddy) {
 $apiCall = $wrapper->contact();
 $newContact = new Contact();
 $newContact->setEmail($buddy);
 $createContact = $apiCall->create($newContact);
 echo "success - created contact\n";
 return print($createContact->getID());
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
	        'html' => '<span style=\'color:red\'>hello red planet mailing list </span>'
	    )
	));
	//Run pony! Run!
	$resp = curl_exec($curl);
	//dump some stuff for debug
	var_dump($resp);
	//errors pls?
	echo curl_error($curl);
	curl_close($curl);
}

/* If we get the confirmation email with the code we can add the address to the contactlist*/

if ( isset($_GET['confirmation']) && isset($_GET['email']) ) {
	$confirmation = $_GET['confirmation'];
	$email = $_GET['email'];
	if (checkConfirmation($confirmation,$email)) {
		//Add him..
		echo "adding him";
	}
}
elseif (isset($_POST['email'])) {
	if (strlen($_POST['email']) > 0) {
	echo "got email address ".$_POST['email'].' ';
	/*I have received an email address now I can send an email*/
	}
	else {
		echo "0 string";
	}
}
else {  echo "no email or auth code, should you be here?"; } //<- WTFFF

var_dump(checkConfirmation("21827c0cbe4e4b966be3498f4d05b92a","orlando@mailjet.com"));
//sendEmail($APIKey,$secretKey,$newguy);

//$clientid= createContact($wrapper,$newguy);

?>
