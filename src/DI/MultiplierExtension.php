<?php

namespace WebChemistry\Forms\Controls\DI;

use Nette;
use Nette\DI\CompilerExtension;

class MultiplierExtension extends CompilerExtension {

	/** @var array */
	public $defaults = [
		'name' => 'addMultiplier'
	];

	public function afterCompile(Nette\PhpGenerator\ClassType $class) {
		$init = $class->getMethods()['initialize'];
		$config = $this->validateConfig($this->defaults);

		$init->addBody('WebChemistry\Forms\Controls\Multiplier::register(?);', [$config['name']]);
	}

}
