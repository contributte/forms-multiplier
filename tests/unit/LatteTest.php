<?php

use Latte\Engine;
use Nette\Application\UI\Form as NetteForm;
use Nette\Application\UI\Presenter;
use Nette\Bridges\FormsLatte\FormMacros;
use Nette\Forms\Container;
use Contributte\FormMultiplier\Macros\MultiplierMacros;
use Contributte\FormMultiplier\Multiplier;

class LatteTest extends \Codeception\TestCase\Test
{

	use TTest;

	/** @var Engine */
	protected $latte;

	protected function _before()
	{
		if (version_compare(Engine::VERSION, '3.0', '>=')) {
			// Only Latte 2 supported at the moment.
			$this->markTestSkipped();
		}

		$this->latte = $latte = new Engine();
		MultiplierMacros::install($latte->getCompiler());
		FormMacros::install($latte->getCompiler());
	}

	public function testBtnCreate()
	{
		$presenter = new FooPresenter();
		$form = new NetteForm();
		$form['m'] = $m = new Multiplier(function (Container $container) {
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

class FooPresenter extends Presenter
{

	public function link(string $destination, $args = []): string
	{
		return '';
	}

}
