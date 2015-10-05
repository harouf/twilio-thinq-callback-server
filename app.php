<?php
require 'vendor/autoload.php';
use \Slim\Slim as Slim;

$default_thinQ_callback = array(
	'type' => 'sip',                          // can be 'sip' or 'phone'
	'detail' => array(                                      // SIP details here
	    'id' => '19196356566',
	    'domain' => 'wap.thinq.com',
	    'headers' => array(
	        'thinQid' => '11001',
	        'thinQtoken' => '0c82a54f22f775a3ed8b97b2dea74036'
	    )
	)
);

$app = new Slim();

$app->get('/', function (){
	echo "welcome to twiml callback server";
});

$app->post('/get_response', function () use( $app, $default_thinQ_callback ) {
    $from = $app->request()->params('From');
    $to = $app->request()->params('To');
    $callSid = $app->request()->params('CallSid');
    $accountSid = $app->request()->params('AccountSid');
	
	$app->response->headers->set("Content-Type",'application/xml');
	$response = generateResponse($to, $default_thinQ_callback);
    if(!$response){
        echo "<Response>
				<Say>
					Sorry, an error has been occurred. Please try again later.
				</Say>
			</Response>";
    }
    echo $response;
});

$app->run();

function generateResponse($to, $details){
	$twiml_response = new Services_Twilio_Twiml;
	$twiml_response->say("We are trying to connect you to our customer system.");

	if($details['type'] == 'phone') {
	    $twiml_response->dial($details['detail']['id'], array(
	        'callerId' => $to
	    ));
	}else if($details['type'] == 'sip'){
	    $headers = '';
	    if(isset($details['detail']['headers'])){
	        $headers = '?' . implode("&amp;", array_map(function($k, $v){
	                return "{$k}={$v}";
	            }, array_keys($details['detail']['headers']), $details['detail']['headers']));
	    }
	    $dial = $twiml_response->dial(NULL, array(
	        'callerId' => $to
	    ));
	    $sip = $dial->sip();
	    $sip->uri("{$details['detail']['id']}@{$details['detail']['domain']}{$headers}");
	}
	$twiml_response->say("Thank you for calling us. Goodbye.");

	return (string)$twiml_response;
}