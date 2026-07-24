<?php declare(strict_types = 1);

namespace Tests\Cases\Functional;

use Contributte\FormMultiplier\DI\MultiplierExtension;
use Contributte\FormMultiplier\Multiplier;
use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\DI\Compiler;
use Nette\DI\ContainerLoader;
use Nette\DI\InvalidConfigurationException;
use Nette\Forms\Container;
use Nette\Forms\Form;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

/**
 * @param array<string, mixed>|null $multiplierConfig
 */
function initializeContainer(?array $multiplierConfig = null): void
{
	$config = [
		'services' => [
			'latte.latteFactory' => ILatteFactory::class,
		],
	];

	if ($multiplierConfig !== null) {
		$config['multiplier'] = $multiplierConfig;
	}

	$loader = new ContainerLoader(Environment::getTestDir());
	$class = $loader->load(function (Compiler $compiler) use ($config): void {
		$compiler->addExtension('multiplier', new MultiplierExtension());
		$compiler->addConfig($config);
	}, md5(serialize($multiplierConfig)) . time());
	$container = new $class();
	$container->initialize();
}

// testDefaultConfiguration
Toolkit::test(function (): void {
	initializeContainer();

	$form = new Form();
	$multiplier = $form->addMultiplier('multiplier', function (Container $container, Form $form): void {
	});
	Assert::type(Multiplier::class, $multiplier);
});

// testAlternativeConfiguration
Toolkit::test(function (): void {
	initializeContainer(['name' => 'addMultiplierAlternative']);

	$form = new Form();
	$multiplier = $form->addMultiplierAlternative('multiplier', function (Container $container, Form $form): void {
	});
	Assert::type(Multiplier::class, $multiplier);
});

// testInvalidConfiguration
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		initializeContainer(['name' => 0]);
	}, InvalidConfigurationException::class);
});
