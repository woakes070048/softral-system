<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Third Party Services
	|--------------------------------------------------------------------------
	|
	| This file is for storing the credentials for third party services such
	| as Stripe, Mailgun, Mandrill, and others. This file provides a sane
	| default location for this type of information, allowing packages
	| to have a conventional place to find your various credentials.
	|
	*/

	'mailgun' => [
		'domain' => '',
		'secret' => '',
	],

	'mandrill' => [
		'secret' => '',
	],

	'ses' => [
		'key' => '',
		'secret' => '',
		'region' => 'us-east-1',
	],

	'stripe' => [
		'model'  => 'User',
		'secret' => '',
	],
	
	'paypal' => [
				//'client_id' => 'AZ7guIG-JPHdqkkTdFccnJi1lb-2fKmoynD0rsotHUUMn0rKbuwLjC2RtfhRRZBKXkA4T27IlEuQaaOL',
				//'secret' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31ANfQ8cPyoC9I7wmqtC53wx-670NE'
				'client_id' => 'Usdoc24_api1.yahoo.com',
				'secret' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31A0DiLt7THarfWGTbuYvrwEQ3N7nn'
		],

];
