<?php
// This file is generated. Do not modify it manually.
return array(
	'block-variations' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'gopublish-iteras-block/block-variations',
		'title' => 'Iteras Block Variations (internal, not a real block)',
		'description' => 'Exists only so the build tooling (wp-scripts) discovers and bundles this directory\'s editor-only JavaScript, which registers block variations such as the Iteras Paywall Label paragraph variation. Never passed to register_block_type() — see gopublish_iteras_enqueue_block_variations() in gopublish-iteras-block.php, which enqueues the compiled script directly instead.',
		'editorScript' => 'file:./index.js',
		'textdomain' => 'gopublish-iteras-block'
	),
	'post-access-correction' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'gopublish-iteras-block/post-access-correction',
		'title' => 'Iteras Post Access Correction (internal, not a real block)',
		'description' => 'Exists only so the build tooling (wp-scripts) discovers and bundles this directory\'s frontend-only JavaScript, which corrects has-access/no-access classes client-side for posts rendered as teasers inside a query loop. Never passed to register_block_type() — see gopublish_iteras_emit_post_access_correction() in gopublish-iteras-block.php, which enqueues the compiled script directly instead, since it needs to load on any page with these classes, not just pages containing one particular block.',
		'viewScript' => 'file:./view.js',
		'textdomain' => 'gopublish-iteras-block'
	),
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
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'iteras-logout' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'gopublish-iteras-block/iteras-logout',
		'version' => '0.1.0',
		'title' => 'Iteras Logout Button',
		'category' => 'design',
		'icon' => 'exit',
		'description' => 'A button that logs the current visitor out of their Iteras subscription pass, via Iteras\' own client-side Iteras.logoutFromWall() JavaScript API. Add a link via the toolbar (same as any button) to redirect somewhere specific after logging out; otherwise it reloads the current page.',
		'keywords' => array(
			'iteras',
			'logout',
			'log out',
			'sign out',
			'subscription'
		),
		'attributes' => array(
			'text' => array(
				'type' => 'string',
				'default' => 'Log out'
			),
			'url' => array(
				'type' => 'string',
				'default' => ''
			)
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
		'viewScript' => 'file:./view.js'
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
	),
	'iteras-paywall-login' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'gopublish-iteras-block/iteras-paywall-login',
		'version' => '0.1.0',
		'title' => 'Iteras Paywall Login',
		'category' => 'design',
		'icon' => 'unlock',
		'description' => 'Embeds Iteras\' own login/subscribe iframe (Iteras.paywalliframe()) — a block equivalent of the [iteras-paywall-login] shortcode, with which paywalls it applies to and whether a free registered-user pass is accepted configurable in the editor, instead of hand-written shortcode attributes.',
		'keywords' => array(
			'iteras',
			'login',
			'paywall',
			'subscribe',
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
			),
			'accessLevel' => array(
				'type' => 'string',
				'enum' => array(
					'',
					'user'
				),
				'default' => ''
			)
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
		'render' => 'file:./render.php'
	)
);
