<?php

namespace WebChemistry\Forms\Controls\DI;

use Nette;
use Nette\DI\CompilerExtension;
use WebChemistry\Forms\Controls\Macros\MultiplierMacros;
use WebChemistry\Forms\Controls\Multiplier;

class MultiplierExtension extends CompilerExtension {

	/** @var array */
	public $defaults = [
		'name' => 'addMultiplier'
	];

	public function beforeCompile() {
		$builder = $this->getContainerBuilder();

		$builder->getDefinition('latte.latteFactory')
			->addSetup(MultiplierMacros::class . '::install(?->getCompiler())', ['@self']);
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class) {
		$init = $class->getMethods()['initialize'];
		$config = $this->validateConfig($this->defaults);

		$init->addBody(Multiplier::class . '::register(?);', [$config['name']]);
	}

}
