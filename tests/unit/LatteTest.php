<?php

use WebChemistry\Testing\TUnitTest;

class LatteTest extends \Codeception\TestCase\Test {

	use TUnitTest;

	/** @var \Latte\Engine */
	protected $latte;

	protected function _before() {
		$this->latte = $latte = new \Latte\Engine();
		\WebChemistry\Forms\Controls\Macros\MultiplierMacros::install($latte->getCompiler());
		\Nette\Bridges\FormsLatte\FormMacros::install($latte->getCompiler());
	}

	public function testBtnCreate() {
		$presenter = new FooPresenter();
		$form = new \Nette\Application\UI\Form();
		$form['m'] = $m = new \WebChemistry\Forms\Controls\Multiplier(function (\Nette\Forms\Container $container) {
			$container->addText('foo');
		});
		$m->addCreateButton('Create one');
		$m->addCreateButton('Create two', 2);
		$presenter['m'] = $form;

		$string = $this->latte->renderToString(__DIR__ . '/templates/macros.latte', ['form' => $form]);
		$this->assertRegExp('#name="m\[multiplier_creator]"#', $string);
		$this->assertRegExp('#name="m\[multiplier_creator2]"#', $string);
	}

}

class FooPresenter extends \Nette\Application\UI\Presenter {

	public function link($destination, $args = []) {
		return '';
	}

}
