<?php

namespace Tests\Unit;

use Codeception\Test\Unit as UnitTest;
use Contributte\FormMultiplier\Latte\Extension\MultiplierExtension;
use Latte\Engine;
use Nette\Application\UI\Form as NetteForm;
use Nette\Application\UI\Presenter;
use Nette\Bridges\FormsLatte\FormMacros;
use Nette\Bridges\FormsLatte\FormsExtension;
use Nette\Forms\Container;
use Contributte\FormMultiplier\Multiplier;
use Tests\Support\Helper\TTest;

class LatteTest extends UnitTest
{

	use TTest;

	/** @var Engine */
	protected $latte;

	protected function _before()
	{
		$this->latte = $latte = new Engine();
		$latte->addExtension(new FormsExtension());
		$latte->addExtension(new MultiplierExtension());
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
		$this->assertMatchesRegularExpression('#name="m\[multiplier_creator]"#', $string);
		$this->assertMatchesRegularExpression('#name="m\[multiplier_creator2]"#', $string);
	}

}

class FooPresenter extends Presenter
{

	public function link(string $destination, $args = []): string
	{
		return '';
	}

}
