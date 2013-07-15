<?php
/*
 * Twilio SMS Conference Chat
 *
 * Designed to be used as an SMS handler in Twilio.
 * Will distribute any SMS received to the rest of the distribution list,
 * with the sender's name prepended and a timestamp appended.
 * Can also send the message as a Pushover notification, and as an email.
 * Supports a few simple commands too; simply send your message prepended
 * with the command and the output will be distributed.
 * eg. Sending "reverse hello" --> Everyone receives "olleh"
 *
 *
 * Copyright 2013 Bryce Chidester <bryce@cobryce.com>
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
 * REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
 * INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
 * LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
 * OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
 * PERFORMANCE OF THIS SOFTWARE.
 */

$numbers = array();
$from = '';
$pushover_api_key = '';
$pushover = array();
$email = array();
require_once('config.inc.php');

// Command interpretation
$msg = trim($_POST['Body']);
$msg2 = explode(' ', $msg);
$op = strtolower(array_shift($msg2));
$msg2 = implode(' ', $msg2);	//Rebuild the query
$msg_modified = false;
if($op == "reverse")
{
	$msg_modified = true;
	$msg = strrev($msg2);
} elseif($op == "rot13")
{
	$msg_modified = true;
	$msg = str_rot13($msg2);
} elseif($op == "define")
{
	$msg_modified = true;
	$msg = my_define($msg2);
}



// SMS
require_once("twilio-php/Services/Twilio.php");
$response = new Services_Twilio_Twiml;
if(isset($numbers[$_POST['From']]))	// Verify the sender is authorized
{
	foreach($numbers as $num => $name)
	{
		// Don't send a message back to the sender
		if($num == $_POST['From'])
			continue;
		$response->sms(
			substr("{$numbers[$_POST['From']]}: {$msg} (".date('H:i:s').")", 0, 159),
			array('to' => $num,
			      'from' => TWILIO_FROM,
			      'action' => TWILIO_CALLBACK,
			      'statusCallback' => TWILIO_CALLBACK,
			      'method' => 'GET'
			     )
			);
	}
}
// Dump the Twilio response immediately
print $response;
ob_flush();
flush();



// Pushover notifications (for redundancy)
if(defined('PUSHOVER_API_KEY'))
{
	require_once("php-pushover/Pushover.php");
	$PO = new Pushover();
	$PO->setToken(PUSHOVER_API_KEY);
	$PO->setTitle("{$numbers[$_POST['From']]}");
	$PO->setMessage($msg);
	if($msg_modified)
		$PO->setMessage("{$msg}\nOriginal:\n{$msg2}");
	foreach($pushover as $p)
	{
		$PO->setUser($p);
		$PO->send();
	}
}



// Email
foreach($email as $addy)
{
	$m = $msg;
	if($msg_modified)
		$m = "{$msg}\nOriginal:\n{$msg2}";
	mail($addy, "Conference Chat", "{$numbers[$_POST['From']]}: {$m}");
}



// Logging
if(defined('DEBUG_LOG'))
{
	$fp = fopen(DEBUG_LOG, 'a');
	$start = microtime(true);
	while(!flock($fp, LOCK_EX))
	{
		// Timeout after 2 seconds
		if( (microtime(true) - $start) > 2)
			die();
	}
	fwrite($fp, "=====================================\n");
	fwrite($fp, "Datestamp\n");
	fwrite($fp, date('r').PHP_EOL);

	fwrite($fp, "GET\n");
	fwrite($fp, print_r($_GET, true));

	fwrite($fp, "POST\n");
	fwrite($fp, print_r($_POST, true));

	fwrite($fp, "Raw POST data\n");
	fwrite($fp, file_get_contents("php://input").PHP_EOL);

	fwrite($fp, "COOKIE\n");
	fwrite($fp, print_r($_COOKIE, true));

	fwrite($fp, "FILES\n");
	fwrite($fp, print_r($_FILES, true));
	foreach($_FILES as $k => $v)
	{
		fwrite($fp, "==$k -- {$v['name']}==\n");
		fwrite($fp, file_get_contents($v['tmp_name']).PHP_EOL);
	}

	fwrite($fp, "SESSION\n");
	fwrite($fp, @print_r($_SESSION, true));

	fwrite($fp, "ENV\n");
	fwrite($fp, print_r($_ENV, true));

	fwrite($fp, "GLOBALS\n");
	fwrite($fp, print_r($GLOBALS, true));

	fwrite($fp, "REQUEST\n");
	fwrite($fp, print_r($_REQUEST, true));

	fwrite($fp, "HTTP Response Header\n");
	fwrite($fp, @print_r($http_response_header, true));

	fwrite($fp, "Server\n");
	fwrite($fp, print_r($_SERVER, true));

	fflush($fp);
	flock($fp, LOCK_UN);
	fclose($fp);
}



function my_define($input)
{
	$response = file_get_contents("http://clients5.google.com/dictionary/json?" . 
		'q=' . urlencode ( $input ) .
		// These parameters were harvested from Google's dictionary lookup.
		// I have no idea their exact impact, but we keep them just in case.
		'&callback=dict_api.callbacks.id103'.
		'&sl=en'.
		'&tl=en'.
		'&restrict=pr%2Cde'.
		'&client=dict-chrome-ex'
		);
	
	$response = str_replace('dict_api.callbacks.id103(', '', $response);
	$response = str_replace(',200,null)', '', $response);
	$response = str_replace('\x', '\u00', $response);       // Javascript does \x but JSON does \u00
	$dict = json_decode($response, true);
	if($dict && isset($dict['webDefinitions']))
	{
		// Note: Sometimes there are multiple definitions given
		// $dict['webDefinitions'][0]['entries'][XX]['terms'][0]['text']
		// Hope that '0' is type=text
		$def = strip_tags(
			html_entity_decode(
				$dict['webDefinitions'][0]['entries'][0]['terms'][0]['text'],
				ENT_QUOTES,
				'utf-8'
			));
		// Hope that '1' is type=url
		if($dict['webDefinitions'][0]['entries'][0]['terms'][1]['type'] == "url")
			$sauce = ' <'.strip_tags(
				html_entity_decode(
					$dict['webDefinitions'][0]['entries'][0]['terms'][1]['text'],
					ENT_QUOTES,
					'utf-8'
				)).'>';
		else
			$sauce = null;
		return "{$input}: {$def}{$sauce}";
	} else
	{
		return "{$input}: No definitions found.";
	}
}
?>
