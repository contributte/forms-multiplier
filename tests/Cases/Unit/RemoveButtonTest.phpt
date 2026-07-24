<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use Contributte\FormMultiplier\Multiplier;
use Contributte\Tester\Toolkit;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Tester\Assert;
use Tests\Helpers\FormAssert;
use Tests\Helpers\MultiplierBuilder;
use WebChemistry\Testing\Services;

require __DIR__ . '/../../bootstrap.php';

$services = new Services();

// testSendRemove
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2)
			->setMinCopies(1)
			->addRemoveButton()
			->addCreateButton()
			->createForm()
	)->setPost([
		'm' => [
			['bar' => ''],
			['bar' => '', 'multiplier_remover' => ''],
		],
	])->send();

	$dom = $response->toDomQuery();
	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domNotHas($dom, 'input[name="m[1][bar]"]');
});

// testSendRemoveShouldNotValidate
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2)
			->setMinCopies(1)
			->beforeFormModifier(function (Form $form): void {
				$form->addInteger('num');
			})
			->addRemoveButton()
			->createForm()
	)->setPost([
		'num' => '5+1',
		'm' => [
			['bar' => ''],
			['bar' => '', 'multiplier_remover' => ''],
		],
	])->send();

	$dom = $response->toDomQuery();
	Assert::false($response->hasErrors());
	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domNotHas($dom, 'input[name="m[1][bar]"]');
});

// testSendRemoveWithoutButton
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2)
			->setMinCopies(1)
			->addCreateButton()
			->createForm()
	)->setPost([
		'm' => [
			['bar' => ''],
			['bar' => '', 'multiplier_remover' => ''],
		],
	])->send();

	$dom = $response->toDomQuery();
	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domNotHas($dom, 'input[name="m[1][bar]"]');
});

// testSendRemoveBelowMinCopies
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(1)
			->setMinCopies(1)
			->addRemoveButton()
			->addCreateButton()
			->createForm()
	)->setPost([
		'm' => [
			['bar' => '', 'multiplier_remover' => ''],
		],
	])->send();

	FormAssert::domHas($response->toDomQuery(), 'input[name="m[0][bar]"]');
});

// test2Multipliers
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(1)
			->setMinCopies(1)
			->addRemoveButton()
			->addCreateButton()
			->formModifier(function (Form $form): void {
				$form['m2'] = new Multiplier(function (Container $container): void {
					$container->addText('bar2');
				});

				$form['m2']->addRemoveButton();
				$form['m2']->addCreateButton();
			})
			->createForm()
	)->setPost([
		'm' => [
			['bar' => ''],
		],
		'm2' => [
			['bar2' => ''],
			Multiplier::SUBMIT_CREATE_NAME => '',
		],
	])->send();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m2[0][multiplier_remover]"]');
	FormAssert::domHas($dom, 'input[name="m2[1][multiplier_remover]"]');
	FormAssert::domNotHas($dom, 'input[name="m[0][multiplier_remover]"]');
});

// testFormEvents
Toolkit::test(function () use ($services): void {
	$req = $services->form->createRequest(
		MultiplierBuilder::create(2)
			->setMinCopies(1)
			->addRemoveButton()
			->addCreateButton()
			->formModifier(function (Form $form): void {
				$form->onSuccess[] = $form->onError[] = $form->onSubmit[] = function (): void {
					Assert::fail('Events should not be called');
				};
			})
			->createForm()
	);
	$response = $req->setPost([
		'm' => [
			['bar' => ''],
			['bar' => '', 'multiplier_remover' => ''],
		],
	])->send();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domNotHas($dom, 'input[name="m[1][bar]"]');
});

// testAddClass
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2)
			->setMinCopies(1)
			->addRemoveButton(function (SubmitButton $submitter): void {
				$submitter->setHtmlAttribute('class', 'btn btn-remove');
			})
			->addCreateButton()
			->createForm()
	)->setPost([
		'm' => [
			['bar' => ''],
			['bar' => ''],
		],
	])->send();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domHas($dom, 'input.btn.btn-remove');
});

// testDeleteLastElementToZero
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(1)
			->setMinCopies(0)
			->addRemoveButton()
			->addCreateButton()
			->createForm()
	)->modifyForm(function (Form $form): void {
		$form['m']->setValues([
			['bar' => 'foo'],
		]);
	})->setPost([
		'm' => [
			['bar' => '', 'multiplier_remover' => ''],
		],
	])->send();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[multiplier_creator]"]');
	FormAssert::domNotHas($dom, 'input[name="m[0][bar]"]');
});

// testOnRemoveEvent
Toolkit::test(function () use ($services): void {
	$called = false;
	$response = $services->form->createRequest(
		MultiplierBuilder::create()
			->setMinCopies(0)
			->addRemoveButton()
			->multiplierModifier(function (Multiplier $multiplier) use (&$called): void {
				$multiplier->onRemove[] = function () use (&$called): void {
					$called = true;
				};
			})
			->createForm()
	)->setPost([
		'm' => [
			['bar' => '', 'multiplier_remover' => ''],
		],
	])->send();

	$dom = $response->toDomQuery();

	Assert::true($called);
	FormAssert::domNotHas($dom, 'input[name="m[0][bar]"]');
});
