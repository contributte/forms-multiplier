<?php

namespace Tests\Unit;

use Codeception\Test\Unit as UnitTest;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Contributte\FormMultiplier\Multiplier;
use Contributte\FormMultiplier\Submitter;
use Tests\Support\Helper\MultiplierBuilder;
use Tests\Support\Helper\TTest;

class CreateButtonTest extends UnitTest
{

	use TTest;

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

	public function testNoOrphanFieldsets()
	{
		$i = 1;
		$form = new Form();
		$form['members'] = $membersMultiplier = new Multiplier(function (Container $container) use ($form, &$i) {
			$group = $form->addGroup('Team member #' . $i++);
			$container->setCurrentGroup($group);
			$container->addText('name', 'Name');
		});
		$form->setCurrentGroup(null);
		$membersMultiplier->addCreateButton('add');

		$req = $this->services->form->createRequest($form);
		$response = $req->setPost([
			'members' => [
				[],
				'multiplier_creator' => '',
			],
		])->send();

		$dom = $response->toDomQuery();
		codecept_debug($response->toString());

		$this->assertCount(2, $dom->find('fieldset'), 'After adding a container, there should be two fieldsets.');
	}

}
