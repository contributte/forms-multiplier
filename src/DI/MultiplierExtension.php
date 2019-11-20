<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier\DI;

use Contributte\FormMultiplier\Macros\MultiplierMacros;
use Contributte\FormMultiplier\Multiplier;
use Nette;
use Nette\DI\CompilerExtension;

class MultiplierExtension extends CompilerExtension
{

	/** @var string[] */
	public $defaults = [
		'name' => 'addMultiplier',
	];

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$resultDefinition = $builder->getDefinition('latte.latteFactory')
			->getResultDefinition();

		$resultDefinition->addSetup(MultiplierMacros::class . '::install(?->getCompiler())', ['@self']);
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class): void
	{
		$init = $class->getMethods()['initialize'];
		$config = $this->validateConfig($this->defaults);

		$init->addBody(Multiplier::class . '::register(?);', [$config['name']]);
	}

}
