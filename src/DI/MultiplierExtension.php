<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier\DI;

use Contributte\FormMultiplier\Macros\MultiplierMacros;
use Contributte\FormMultiplier\Multiplier;
use Latte\Engine;
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
					get_class($factory)
				)
			);
		}

		$resultDefinition = $factory->getResultDefinition();

		if (version_compare(Engine::VERSION, '3', '<')) { // @phpstan-ignore-line
			$resultDefinition->addSetup(MultiplierMacros::class . '::install(?->getCompiler())', ['@self']);
		} else {
			$resultDefinition->addSetup('addExtension', [new Statement(\Contributte\FormMultiplier\Latte\Extension\MultiplierExtension::class)]);
		}
	}

	public function afterCompile(ClassType $class): void
	{
		/** @var stdClass $config */
		$config = $this->getConfig();
		$init = $class->getMethods()['initialize'];
		$init->addBody(Multiplier::class . '::register(?);', [$config->name]);
	}

}
