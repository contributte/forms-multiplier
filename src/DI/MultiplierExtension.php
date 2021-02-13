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

		$latteFactoryDefinition = $builder->getDefinition('latte.latteFactory');
		if ($latteFactoryDefinition instanceof Nette\DI\Definitions\FactoryDefinition === false) {
			throw new Nette\DI\InvalidConfigurationException(
				sprintf(
					'latte.latteFactory service definition must be of type %s, not %s',
					Nette\DI\Definitions\FactoryDefinition::class,
					get_class($latteFactoryDefinition)
				)
			);
		}

		$resultDefinition = $latteFactoryDefinition->getResultDefinition();

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
