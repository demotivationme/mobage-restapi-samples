<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2014 DeNA Co., Ltd.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
// this is an example of calling Remote Notification API to get 
// whether remote notificaiton is enabled for specific users
require_once("External/OAuth.php");
require_once("config.php");
require_once("datastore.php");

// configuring ...
$http_method  = "GET";
$url_fragment = "/remote_notification/@app/@all";

// check whether optional ids is set
if (isset($_REQUEST['user_ids'])) {
	$url_fragment .= "/".$_REQUEST['user_ids']."/state";
} else {
	$url_fragment .= "/@me/state";
}

// for other query params
$req_params   = array();
foreach ($_REQUEST as $key => $val) {
	if ($key != "user_ids") {
		if (count($req_params) == 0) {
			$url_fragment .= "?";
		} else {
			$url_fragment .= "&";
		}
		$url_fragment .= $key . '=' . $val;
		$req_params[$key] = $val;
	}
}

// retrieve saved values
$datastore = read_array_from_session();

// creating consumer
$sig_method = new OAuthSignatureMethod_HMAC_SHA1(); // signature method
$consumer   = new OAuthConsumer($consumerKey, $consumerSecret, NULL); // Consumer

// generate Authentication Header
$endpoint = $social_endpoint . $url_fragment;
$enc_params = $req_params;
$request = NULL;
$request = OAuthRequest::from_consumer_and_token($consumer, NULL, $http_method, $endpoint, $enc_params);
$request->sign_request($sig_method, $consumer, NULL);
$auth_header =  array($request->to_header(""));

// make the call
$curl = curl_init($endpoint);
curl_setopt($curl, CURLOPT_POST, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FAILONERROR, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_ENCODING , "gzip");
curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_header);
curl_setopt($curl, CURLINFO_HEADER_OUT, true);
curl_setopt($curl, CURLOPT_HEADER, true);
$response = curl_exec($curl);

// request header
$curl_header_req      = curl_getinfo($curl, CURLINFO_HEADER_OUT);
// response header and body
$curl_header_res_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
$curl_header_res      = substr($response, 0, $curl_header_res_size);
$curl_body_res        = substr($response, $curl_header_res_size);

curl_close($curl);

if ($verbose) {
	print("<h2>Session</h2>");
	print("<pre>");
	var_dump($_SESSION);
	print("</pre>");
	
	print("<h2>Request</h2>");
	print("<pre>".$curl_header_req."</pre>");
	print("<h3>OAuth Signature Base String:</h3>");
	print("<pre>".$request->base_string."</pre>");
	
	print("<h2>Response</h2>");
	print("<pre>".$response."</pre>");
} else {
	print($curl_body_res);
}

?>
