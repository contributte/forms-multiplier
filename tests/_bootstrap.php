<?php

require __DIR__ . '/../../../../vendor/autoload.php';

/** START TEST */
@mkdir(__DIR__ . '/temp');
@mkdir(__DIR__ . '/temp/log');
/*foreach (\Nette\Utils\Finder::findFiles('*')->from(__DIR__ . '/temp') as $file) {
	@unlink((string) $file);
}*/
/** END TEST */

$configurator = new Nette\Configurator;

//$configurator->enableDebugger(__DIR__ . '/temp/log');
$configurator->setTempDirectory(__DIR__ . '/temp');

$configurator->createRobotLoader()
			 ->addDirectory(__DIR__ . '/../src')
			 ->addDirectory(__DIR__ . '/../../forms/src')
			 ->addDirectory(__DIR__ . '/includes')
			 ->register();

if (file_exists(__DIR__ . '/_data/config.neon')) {
	$configurator->addConfig(__DIR__ . '/_data/config.neon');
}

$configurator->addParameters(array(
	'dataDir' => __DIR__ . '/_data'
));

$container = $configurator->createContainer();

/** START TEST */
new E($container);
/** END TEST */
class E {

	/** @var \Nette\DI\Container */
	private static $container;

	public function __construct(\Nette\DI\Container $container) {
		self::$container = $container;
	}

	/**
	 * @param $type
	 * @return object
	 */
	public static function getByType($type) {
		return self::$container->getByType($type);
	}

	public static function directory($dir) {
		return str_replace(array(
			'%www%',
			'%app%',
			'%temp%',
			'%data%',
			'%tempTest%',
			'%logTest%'
		), array(
			self::$container->parameters['wwwDir'],
			self::$container->parameters['appDir'],
			self::$container->parameters['tempDir'],
			self::$container->parameters['dataDir'],
			__DIR__ . '/temp',
			__DIR__ . '/temp/logs'
		), $dir);
	}

	public static function truncateTemp() {
		@mkdir(self::directory('%tempTestDir%'));
		@mkdir(self::directory('%logTestDir%'));
	}
	
	public static function dumpFile($name, $content) {
		@mkdir(self::directory('%data%/dumped'));

		$file = self::directory('%data%/dumped/' . $name . '.dmp');

		file_put_contents($file, $content);
	}

	public static function dumpedFile($name) {
		return self::directory('%data%/dumped/' . $name . '.dmp');
	}
}