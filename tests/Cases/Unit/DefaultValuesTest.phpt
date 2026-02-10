<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use Contributte\FormMultiplier\Multiplier;
use Contributte\Tester\Toolkit;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Tests\Helpers\FormAssert;
use Tests\Helpers\MultiplierBuilder;
use WebChemistry\Testing\Services;

require __DIR__ . '/../../bootstrap.php';

$services = new Services();

$defaults = [
	'm' => [
		['bar' => 'foo'],
		['bar' => 'foo'],
	],
];

$defaultNested = [
	'm' => [
		[
			'bar' => 'foo1',
			'nested' => [
				['foo' => 'bar1'],
				['foo' => 'bar2'],
			],
		],
		[
			'bar' => 'foo2',
			'nested' => [
				['foo' => 'bar3'],
				['foo' => 'bar4'],
			],
		],
	],
];

// testRender
Toolkit::test(function () use ($services, $defaults): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2, null)
			->addCreateButton()
			->addRemoveButton()
			->setMinCopies(1)
			->setFormDefaults($defaults)
			->createForm()
	)->render();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"][value="foo"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"][value="foo"]');
	FormAssert::domNotHas($dom, 'input[name="m[2][bar]"]');
});

// testRenderAndSetDefaultsInAction
Toolkit::test(function () use ($services, $defaults): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create()
			->createForm()
	)->setActionCallback(function (Form $form) use ($defaults): void {
		$form->setDefaults($defaults);
	})->render();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"][value="foo"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"][value="foo"]');
	FormAssert::domNotHas($dom, 'input[name="m[2][bar]"]');
});

// testRenderAndSetDefaultsInRender
Toolkit::test(function () use ($services, $defaults): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create()
			->createForm()
	)->setRenderCallback(function (Form $form) use ($defaults): void {
		$form->setDefaults($defaults);
	})->render();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"][value="foo"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"][value="foo"]');
	FormAssert::domNotHas($dom, 'input[name="m[2][bar]"]');
});

// testRemoveButtons
Toolkit::test(function () use ($services, $defaults): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2, null)
			->addCreateButton()
			->addRemoveButton()
			->setMinCopies(1)
			->setFormDefaults($defaults)
			->createForm()
	)->render();

	$dom = $response->toDomQuery();
	FormAssert::domHas($dom, 'input[name="m[0][' . Multiplier::SUBMIT_REMOVE_NAME . ']"]');
	FormAssert::domHas($dom, 'input[name="m[1][' . Multiplier::SUBMIT_REMOVE_NAME . ']"]');
});

// testDefaultValue
Toolkit::test(function () use ($services, $defaults): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2, null)
			->addCreateButton()
			->addRemoveButton()
			->setMinCopies(1)
			->setFormDefaults($defaults)
			->fields(['bar' => 'foo'])
			->createForm()
	)->render();

	$dom = $response->toDomQuery();
	FormAssert::domHas($dom, 'input[name="m[0][bar]"][value="foo"]');
});

// testDefaultValueSend
Toolkit::test(function () use ($services, $defaults): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2, null)
			->addCreateButton()
			->addRemoveButton()
			->setMinCopies(1)
			->setFormDefaults($defaults)
			->fields(['bar' => 'foo'])
			->createForm()
	)->setPost([
		'm' => [
			['bar' => 'bar'],
			Multiplier::SUBMIT_CREATE_NAME => '',
		],
	])->send();

	$dom = $response->toDomQuery();
	FormAssert::domHas($dom, 'input[name="m[0][bar]"][value="bar"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"][value="foo"]');
});

// testNestedMultiplier
Toolkit::test(function () use ($services, $defaultNested): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2, null)
			->containerModifier(function (Container $container): void {
				$container['nested'] = new Multiplier(function (Container $container): void {
					$container->addText('foo');
				});
			})
			->fields(['bar' => 'foo'])
			->setFormDefaults($defaultNested)
			->createForm()
	)->render();

	$dom = $response->toDomQuery();
	FormAssert::domHas($dom, 'input[name="m[0][bar]"][value="foo1"]');
	FormAssert::domHas($dom, 'input[name="m[0][nested][0][foo]"][value="bar1"]');
	FormAssert::domHas($dom, 'input[name="m[0][nested][1][foo]"][value="bar2"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"][value="foo2"]');
	FormAssert::domHas($dom, 'input[name="m[1][nested][0][foo]"][value="bar3"]');
	FormAssert::domHas($dom, 'input[name="m[1][nested][1][foo]"][value="bar4"]');
});
