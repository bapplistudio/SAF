<?php
namespace SAF\Framework;

use SAF\AOP;

$config['framework'] = [

	// top core plugins are loaded first, before the session is opened
	// this array must stay empty : top core plugins must be set into the index.php script
	'top_core' => [],

	//------------------------------------------------------------------------------------------ core
	// core plugins are registered first on session creation
	// they are activated first, at the beginning of each script
	// here must be only plugins that are needed in 100% scripts, as a lot of them may consume time
	'core' => [
		Router::class,     // must be the first core plugins as others plugins need it
		AOP\Weaver::class, // must be declared before any plugin that uses AOP
		Builder::class,    // every classes before Builder will not be replaceable
		Application_Updater::class, // check for update at each script call
		Xdebug::class               // remove xdebug parameters at each script call
		/*
		Error_Handlers::class => [
			[E_ALL,               Fatal_Error_Handler::class),
			[E_ALL & !E_NOTICE,   Main_Error_Handler::class),
			[E_RECOVERABLE_ERROR, To_Exception_Error_Handler::class),
		)
		*/
	],

	// other priorities plugins are activated only when needed
	// the priority says if what programming level their AOP orders will be executed :
	// ie if two plugins have the same pointcut, the highest priority advice will be executed,
	// and the lowest priority advice will be executed only if the highest processes wants it.

	//----------------------------------------------------------------------------------------- lower
	'lowest' => [],
	'lower'  => [],
	'low'    => [],

	//---------------------------------------------------------------------------------------- normal
	'normal'  => [
		Dao::class => [
			'class'    => Mysql_Link::class,
			'host'     => 'localhost',
			'login'    => 'saf',
			'password' => 'saf'
		],
		Html_Cleaner::class,
		Html_Translator::class,
		Loc::class,
		Locale::class => [
			'date'     => 'm/d/Y',
			'language' => 'en',
			'number'   => [
				'decimal_minimal_count' => 2,
				'decimal_maximal_count' => 4,
				'decimal_separator'     => '.',
				'thousand_separator'    => ',',
			]
		],
		Mysql_Maintainer::class,
		Php_Compiler::class => [
			AOP\Compiler::class,
			Builder\Compiler::class
		],
		Translation_String_Composer::class,
		View::class => [
			'class' => Html_View_Engine::class,
			'css' => 'default'
		]
	],

	//---------------------------------------------------------------------------------------- higher
	'high'    => [],
	'higher'  => [],
	'highest' => []

];
