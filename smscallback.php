<?php
/*
 * A callback handler for Twilio SMS messages.
 * Currently does nothing except log the request.
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

require_once('config.inc.php');
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
?>
