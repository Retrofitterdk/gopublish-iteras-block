<?php
// This file is generated. Do not modify it manually.
return array(
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
