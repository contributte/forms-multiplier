<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use Contributte\FormMultiplier\Latte\Extension\MultiplierExtension;
use Contributte\FormMultiplier\Multiplier;
use Contributte\Tester\Toolkit;
use Latte\Engine;
use Nette\Application\UI\Form as NetteForm;
use Nette\Bridges\FormsLatte\FormsExtension;
use Nette\Forms\Container;
use Tester\Assert;
use Tests\Mocks\FooPresenter;

require __DIR__ . '/../../bootstrap.php';

// testBtnCreate
Toolkit::test(function (): void {
	$latte = new Engine();
	$latte->addExtension(new FormsExtension());
	$latte->addExtension(new MultiplierExtension());

	$presenter = new FooPresenter();
	$form = new NetteForm();
	$form['m'] = $m = new Multiplier(function (Container $container): void {
		$container->addText('foo');
	});
	$m->addCreateButton('Create one');
	$m->addCreateButton('Create two', 2);
	$presenter['m'] = $form;

	$string = $latte->renderToString(__DIR__ . '/templates/macros.latte', ['form' => $form]);
	Assert::match('#name="m\[multiplier_creator]"#', $string);
	Assert::match('#name="m\[multiplier_creator2]"#', $string);
});
