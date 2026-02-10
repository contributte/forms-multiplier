<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use Contributte\FormMultiplier\Submitter;
use Contributte\Tester\Toolkit;
use Nette\Forms\Controls\SubmitButton;
use Tester\Assert;
use Tests\Helpers\FormAssert;
use Tests\Helpers\MultiplierBuilder;
use WebChemistry\Testing\Services;

require __DIR__ . '/../../bootstrap.php';

$services = new Services();

// testSendCreate
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create()
			->addCreateButton()
			->createForm()
	)->setPost([
		'm' => [
			['bar' => ''],
			['bar' => ''],
			'multiplier_creator' => '',
		],
	])->send();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[2][bar]"]');
});

// testSendCreateOverMaxCopies
Toolkit::test(function () use ($services): void {
	$response = $services->form->createRequest(
		MultiplierBuilder::create(2, 2)
			->addCreateButton()
			->createForm()
	)->setPost([
		'm' => [
			['bar' => ''],
			['bar' => ''],
			'multiplier_creator' => '',
		],
	])->send();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"]');
	FormAssert::domNotHas($dom, 'input[name="m[2][bar]"]');
});

// testSendCreateButtonWith5Copies
Toolkit::test(function () use ($services): void {
	$factory = MultiplierBuilder::create()
		->addCreateButton(5)
		->addCreateButton()
		->addRemoveButton();

	$response = $services->form->createRequest($factory->createForm())->setPost([
		'm' => [
			['bar' => ''],
			'multiplier_creator5' => '',
		],
	])->send();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[2][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[3][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[4][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[5][bar]"]');
	FormAssert::domNotHas($dom, 'input[name="m[6][bar]"]');
});

// testCallback
Toolkit::test(function () use ($services): void {
	$factory = MultiplierBuilder::create()
		->setMinCopies(1)
		->addRemoveButton(function (SubmitButton $submitter): void {
			$submitter->setHtmlAttribute('class', 'delete-btn');
		})
		->addCreateButton(5, function (Submitter $submitter): void {
			$submitter->setHtmlAttribute('class', 'add-btn');
		});

	$response = $services->form->createRequest($factory->createForm())->setPost([
		'm' => [
			['bar' => ''],
			['bar' => ''],
		],
	])->send();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input.delete-btn');
	FormAssert::domHas($dom, 'input.add-btn');
});

// testFormEvents
Toolkit::test(function () use ($services): void {
	$factory = MultiplierBuilder::create(2)
		->setMinCopies(1)
		->addRemoveButton(function (SubmitButton $submitter): void {
			$submitter->setHtmlAttribute('class', 'delete-btn');
		})
		->addCreateButton(5, function (Submitter $submitter): void {
			$submitter->setHtmlAttribute('class', 'add-btn');
		});

	$called = false;
	$factory->formModifier(function ($form) use (&$called): void {
		$form->onSuccess[] = $form->onError[] = $form->onSubmit[] = function () use (&$called): void {
			$called = true;
		};
	});

	$req = $services->form->createRequest($factory->createForm());
	$response = $req->setPost([
		'm' => [
			['bar' => ''],
			'multiplier_creator' => '',
		],
	])->send();

	$dom = $response->toDomQuery();

	FormAssert::domHas($dom, 'input[name="m[0][bar]"]');
	FormAssert::domHas($dom, 'input[name="m[1][bar]"]');
	Assert::true($called);
});
