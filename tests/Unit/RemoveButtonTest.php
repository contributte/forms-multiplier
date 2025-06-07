<?php

namespace Tests\Unit;

use Codeception\Test\Unit as UnitTest;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Contributte\FormMultiplier\Multiplier;
use Nette\Application\UI\Form;
use Tests\Support\Helper\MultiplierBuilder;
use Tests\Support\Helper\TTest;

class RemoveButtonTest extends UnitTest
{

	use TTest;

	private function createMultiplier(callable $factory, $copyNumber = 1, $maxCopies = null)
	{
		$form = new Form();

		$form['m'] = new Multiplier($factory, $copyNumber, $maxCopies);

		$form->addSubmit('send');

		return $form;
	}

	protected function a_before()
	{
		$form = $this->services->form;

		$form->addForm('buttons', function ($copyNumber = 2, $maxCopies = null, $removeCallback = null) {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);

			/** @var Multiplier $multiplier */
			$multiplier = $form['m'];

			$multiplier->setMinCopies(1);
			$btn = $multiplier->addRemoveButton();
			$multiplier->addCreateButton();

			if (is_callable($removeCallback)) {
				$removeCallback($btn);
			}

			return $form;
		});

		$form->addForm('base', function ($copyNumber = 1, $maxCopies = null) {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);

			/** @var Multiplier $multiplier */
			$multiplier = $form['m'];

			$multiplier->addRemoveButton();
			$multiplier->addCreateButton();

			return $form;
		});

		$form->addForm('2multipliers', function ($copyNumber = 2, $maxCopies = null) {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);

			$form['m2'] = new Multiplier(function (Container $container) {
				$container->addText('bar2');
			});

			/** @var Multiplier $multiplier */
			$multiplier = $form['m'];

			$multiplier->setMinCopies(1);
			$multiplier->addRemoveButton();
			$multiplier->addCreateButton();

			$form['m2']->addRemoveButton();
			$form['m2']->addCreateButton();

			return $form;
		});

	}

	public function testSendRemove()
	{
		$response = $this->services->form->createRequest(
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
		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testSendRemoveBelowMinCopies()
	{
		$response = $this->services->form->createRequest(
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

		$this->assertDomHas($response->toDomQuery(), 'input[name="m[0][bar]"]');
	}

	public function test2Multipliers()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(1)
				->setMinCopies(1)
				->addRemoveButton()
				->addCreateButton()
				->formModifier(function (Form $form) {
					$form['m2'] = new Multiplier(function (Container $container) {
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

		$this->assertDomHas($dom, 'input[name="m2[0][multiplier_remover]"]');
		$this->assertDomHas($dom, 'input[name="m2[1][multiplier_remover]"]');
		$this->assertDomNotHas($dom, 'input[name="m[0][multiplier_remover]"]');
	}

	public function testFormEvents()
	{
		$req = $this->services->form->createRequest(
			MultiplierBuilder::create(2)
				->setMinCopies(1)
				->addRemoveButton()
				->addCreateButton()
				->formModifier(function (Form $form) {
					$form->onSuccess[] = $form->onError[] = $form->onSubmit[] = function () {
						$this->fail('Events called');
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

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testAddClass()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2)
				->setMinCopies(1)
				->addRemoveButton(function (SubmitButton $submitter) {
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

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input.btn.btn-remove');
	}

	// bug #32
	public function testDeleteLastElementToZero()
	{
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(1)
				->setMinCopies(0)
				->addRemoveButton()
				->addCreateButton()
				->createForm()
		)->modifyForm(function (Form $form) {
			$form['m']->setValues([
				['bar' => 'foo'],
			]);
		})->setPost([
			'm' => [
				['bar' => '', 'multiplier_remover' => ''],
			],
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[multiplier_creator]"]');
		$this->assertDomNotHas($dom, 'input[name="m[0][bar]"]');
	}

	public function testOnRemoveEvent()
	{
		$called = false;
		$response = $this->services->form->createRequest(
			MultiplierBuilder::create()
				->setMinCopies(0)
				->addRemoveButton()
				->multiplierModifier(function (Multiplier $multiplier) use (&$called) {
					$multiplier->onRemove[] = function () use (&$called) {
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

		$this->assertTrue($called);
		$this->assertDomNotHas($dom, 'input[name="m[0][bar]"]');
	}

	/**
	 * Ensure filters (e.g. integer) work on submit,
	 * since they are dependent on properly set validation scope.
	 */
	public function testSendRemoveFilter()
	{
		$this->markTestIncomplete(
			'`getValues()` returns array `["m" => [], "m2" => []]`, '
			. 'even though it works just fine when sending without the button.'
		);

		$response = $this->services->form->createRequest(
			MultiplierBuilder::create(2)
				->setMinCopies(1)
				->fields([])
				->beforeFormModifier(function (Form $form) {
					$form->addInteger('num');
				})
				->multiplierModifier(function (Multiplier $multiplier) {
					$multiplier->onCreate[] = function (Container $container) {
						$container->addInteger('mnum')->setDefaultValue(47);
					};
					$multiplier->addRemoveButton();
				})
				->formModifier(function (Form $form) {
					$form['m2'] = new Multiplier(function (Container $container) {
						$container->addInteger('m2num')->setDefaultValue(72);
					});
				})
				->createForm()
		)
			->setPost([
				'num' => '11',
				'm' => [
					['mnum' => '49'],
					['mnum' => '47', 'multiplier_remover' => ''],
				],
				'm2' => [
					['m2num' => '72'],
				],
			])->send();

		$this->assertTrue($response->isSuccess());
		$this->assertSame([
				'num' => 11,
				'm' => [
					['mnum' => 49],
				],
				'm2' => [
					['m2num' => 72],
				],
			], $response->getValues());

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][mnum]"][value="49"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][mnum]"]');
	}

}
