<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier\DI;

use Contributte\FormMultiplier\Macros\MultiplierMacros;
use Contributte\FormMultiplier\Multiplier;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;

class MultiplierExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'name' => Expect::string()->default('addMultiplier'),
		]);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$resultDefinition = $builder->getDefinition('latte.latteFactory')
			->getResultDefinition();

		$resultDefinition->addSetup(MultiplierMacros::class . '::install(?->getCompiler())', ['@self']);
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class): void
	{
		/** @var stdClass $config */
		$config = $this->getConfig();
		$init = $class->getMethods()['initialize'];
		$init->addBody(Multiplier::class . '::register(?);', [$config->name]);
	}

}
