<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier\DI;

use Contributte\FormMultiplier\Latte\Extension\MultiplierExtension as LatteMultiplierExtension;
use Contributte\FormMultiplier\Multiplier;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\InvalidConfigurationException;
use Nette\PhpGenerator\ClassType;
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
		$factory = $builder->getDefinitionByType(LatteFactory::class);

		if (!$factory instanceof FactoryDefinition) {
			throw new InvalidConfigurationException(
				sprintf(
					'latte.latteFactory service definition must be of type %s, not %s',
					FactoryDefinition::class,
					$factory::class
				)
			);
		}

		$resultDefinition = $factory->getResultDefinition();
		$resultDefinition->addSetup('addExtension', [new Statement(LatteMultiplierExtension::class)]);
	}

	public function afterCompile(ClassType $class): void
	{
		/** @var stdClass $config */
		$config = $this->getConfig();
		$init = $class->getMethods()['initialize'];
		$init->addBody(Multiplier::class . '::register(?);', [$config->name]);
	}

}
