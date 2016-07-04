<?php

class ExtensionTest extends \Codeception\TestCase\Test {

	/** @var \Nette\DI\Compiler */
	protected $compiler;

	protected function _before() {
		$this->compiler = new \Nette\DI\Compiler();
		$this->compiler->addExtension('multiplier', new \WebChemistry\Forms\Controls\DI\MultiplierExtension());
	}

	public function testCompile() {
		$this->compiler->compile();
	}

	public function testInitializeMethod() {
		$code = $this->compiler->compile();
		$this->assertContains('WebChemistry\Forms\Controls\Multiplier::register(\'addMultiplier\');', $code);
	}

	public function testCustomName() {
		$this->compiler->addConfig([
			'multiplier' => [
				'name' => 'addCustom'
			]
		]);
		$code = $this->compiler->compile();
		$this->assertContains('WebChemistry\Forms\Controls\Multiplier::register(\'addCustom\');', $code);
	}

}
