<?php
// This file is generated. Do not modify it manually.
return array(
	'iteras-logged-in' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'gopublish-iteras-block/iteras-logged-in',
		'version' => '0.1.0',
		'title' => 'Iteras Login Status',
		'category' => 'design',
		'icon' => 'admin-users',
		'description' => 'Reveals inner content only to visitors who are (or are not) logged in with a valid Iteras subscription pass of any kind — a block equivalent of the [iteras-if-logged-in] / [iteras-if-not-logged-in] shortcodes for places shortcodes can\'t be used. For gating content by a specific paywall, use the Iteras Paywall block instead.',
		'keywords' => array(
			'iteras',
			'logged in',
			'login',
			'subscriber'
		),
		'attributes' => array(
			'showWhen' => array(
				'type' => 'string',
				'enum' => array(
					'logged-in',
					'not-logged-in'
				),
				'default' => 'logged-in'
			)
		),
		'layout' => array(
			'type' => 'constrained'
		),
		'supports' => array(
			'html' => false,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'align' => array(
				'wide',
				'full'
			)
		),
		'textdomain' => 'gopublish-iteras-block',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'iteras-paywall' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'gopublish-iteras-block/iteras-paywall',
		'version' => '0.1.0',
		'title' => 'Iteras Paywall',
		'category' => 'design',
		'icon' => 'lock',
		'description' => 'Reveals inner content only to visitors with an active Iteras subscription. All other visitors see nothing.',
		'keywords' => array(
			'iteras',
			'paywall',
			'subscription',
			'access'
		),
		'attributes' => array(
			'paywallIds' => array(
				'type' => 'array',
				'items' => array(
					'type' => 'string'
				),
				'default' => array(
					
				)
			)
		),
		'layout' => array(
			'type' => 'constrained'
		),
		'supports' => array(
			'html' => false,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'align' => array(
				'wide',
				'full'
			)
		),
		'textdomain' => 'gopublish-iteras-block',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);
