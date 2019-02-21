<?php

use Nette\Forms\Controls\SubmitButton;
use Contributte\FormMultiplier\Submitter;
use WebChemistry\Testing\TUnitTest;

class CreateButtonTest extends \Codeception\TestCase\Test
{

	use TUnitTest;

	public function testSendCreate()
	{
		$response = $this->services->form->createRequest(
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

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testSendCreateOverMaxCopies()
	{
		$response = $this->services->form->createRequest(
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

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testSendCreateButtonWith5Copies()
	{
		$factory = MultiplierBuilder::create()
			->addCreateButton(5)
			->addCreateButton()
			->addRemoveButton();

		$response = $this->services->form->createRequest($factory->createForm())->setPost([
			'm' => [
				['bar' => ''],
				'multiplier_creator5' => '',
			],
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[2][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[3][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[4][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[5][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[6][bar]"]');
	}

	public function testCallback()
	{
		$factory = MultiplierBuilder::create()
			->setMinCopies(1)
			->addRemoveButton(function (SubmitButton $submitter) {
				$submitter->setHtmlAttribute('class', 'delete-btn');
			})
			->addCreateButton(5, function (Submitter $submitter) {
				$submitter->setHtmlAttribute('class', 'add-btn');
			});

		$response = $this->services->form->createRequest($factory->createForm())->setPost([
			'm' => [
				['bar' => ''],
				['bar' => ''],
			],
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input.delete-btn');
		$this->assertDomHas($dom, 'input.add-btn');
	}

	public function testFormEvents()
	{
		$factory = MultiplierBuilder::create(2)
			->setMinCopies(1)
			->addRemoveButton(function (SubmitButton $submitter) {
				$submitter->setHtmlAttribute('class', 'delete-btn');
			})
			->addCreateButton(5, function (Submitter $submitter) {
				$submitter->setHtmlAttribute('class', 'add-btn');
			});

		$called = false;
		$factory->formModifier(function ($form) use (&$called) {
			$form->onSuccess[] = $form->onError[] = $form->onSubmit[] = function () use (&$called) {
				$called = true;
			};
		});

		$req = $this->services->form->createRequest($factory->createForm());
		$response = $req->setPost([
			'm' => [
				['bar' => ''],
				'multiplier_creator' => '',
			],
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
		$this->assertTrue($called);
	}

}
