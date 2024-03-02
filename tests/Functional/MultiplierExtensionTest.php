<?php

declare(strict_types = 1);

namespace Tests\Functional;

use Codeception\Test\Unit as UnitTest;
use Contributte\FormMultiplier\DI\MultiplierExtension;
use Contributte\FormMultiplier\Multiplier;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\DI\Compiler;
use Nette\DI\ContainerLoader;
use Nette\DI\InvalidConfigurationException;
use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Utils\FileSystem;

class MultiplierExtensionTest extends UnitTest
{

	private const TEMP_DIR = __DIR__ . '/tmp';

	public function testDefaultConfiguration()
	{
		$this->initializeContainer();

		$form = new Form();
		$multiplier = $form->addMultiplier('multiplier', function (Container $container, Form $form) {});
		$this->assertInstanceOf(Multiplier::class, $multiplier);
	}

	public function testAlternativeConfiguration()
	{
		$this->initializeContainer(['name' => 'addMultiplierAlternative']);

		$form = new Form();
		$multiplier = $form->addMultiplierAlternative('multiplier', function (Container $container, Form $form) {});
		$this->assertInstanceOf(Multiplier::class, $multiplier);
	}

	public function testInvalidConfiguration()
	{
		try {
			$this->initializeContainer(['name' => 0]);
			$e = null;
		} catch (InvalidConfigurationException $e) {}

		$this->assertNotNull($e);
	}

	protected function _before()
	{
		FileSystem::createDir(self::TEMP_DIR);
	}

	protected function _after()
	{
		FileSystem::delete(self::TEMP_DIR);
	}

	private function initializeContainer(?array $multiplierConfig = null): void
	{
		$config = [
			'services' => [
				'latte.latteFactory' => ILatteFactory::class,
			],
		];
		if ($multiplierConfig !== null) {
			$config['multiplier'] = $multiplierConfig;
		}

		$loader = new ContainerLoader(self::TEMP_DIR);
		$class = $loader->load(function (Compiler $compiler) use ($config) {
			$compiler->addExtension('multiplier', new MultiplierExtension());
			$compiler->addConfig($config);
		}, md5(serialize($multiplierConfig)) . time());
		$container = new $class();
		$container->initialize();
	}

}
