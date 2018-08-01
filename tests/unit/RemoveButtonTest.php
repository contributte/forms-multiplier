<?php

use Nette\Forms\Container;
use WebChemistry\Forms\Controls\Multiplier;
use Nette\Application\UI\Form;
use WebChemistry\Testing\TUnitTest;

class RemoveButtonTest extends \Codeception\TestCase\Test {

	use TUnitTest;

	private function createMultiplier(callable $factory, $copyNumber = 1, $maxCopies = NULL) {
		$form = new Form();

		$form['m'] = new Multiplier($factory, $copyNumber, $maxCopies);

		$form->addSubmit('send');

		return $form;
	}

	protected function _before() {
		$form = $this->services->form;

		$form->addForm('buttons', function ($copyNumber = 2, $maxCopies = NULL, $removeCallback = null) {
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

		$form->addForm('base', function ($copyNumber = 1, $maxCopies = NULL) {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);

			/** @var Multiplier $multiplier */
			$multiplier = $form['m'];

			$multiplier->addRemoveButton();
			$multiplier->addCreateButton();

			return $form;
		});

		$form->addForm('2multipliers', function ($copyNumber = 2, $maxCopies = NULL) {
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

	public function testSendRemove() {
		$response = $this->services->form->createRequest('buttons')->setPost([
			'm' => [
				['bar' => ''],
				['bar' => '', 'multiplier_remover' => ''],
			]
		])->send();

		$dom = $response->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testSendRemoveBelowMinCopies() {
		$response = $this->services->form->createRequest('buttons', 1)->setPost([
			'm' => [
				['bar' => '', 'multiplier_remover' => ''],
			]
		])->send();

		$this->assertDomHas($response->toDomQuery(), 'input[name="m[0][bar]"]');
	}

	public function test2Multipliers() {
		$response = $this->services->form->createRequest('2multipliers', 1)->setPost([
			'm' => [
				['bar' => ''],
			],
			'm2' => [
				['bar2' => ''],
				Multiplier::SUBMIT_CREATE_NAME => '',
			]
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m2[0][multiplier_remover]"]');
		$this->assertDomHas($dom, 'input[name="m2[1][multiplier_remover]"]');
		$this->assertDomNotHas($dom, 'input[name="m[0][multiplier_remover]"]');
	}

	public function testFormEvents() {
		$req = $this->services->form->createRequest('buttons');
		$req->modifyForm(function (Form $form) {
			$form->onSuccess[] = $form->onError[] = $form->onSubmit[] = function () {
				$this->fail('Called event');
			};
		});
		$response = $req->setPost([
			'm' => [
				['bar' => ''],
				['bar' => '', 'multiplier_remover' => '']
			]
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[1][bar]"]');
	}

	public function testAddClass() {
		$response = $this->services->form->createRequest('buttons', 2, null, function (\WebChemistry\Forms\Controls\Buttons\RemoveButton $btn) {
			$btn->addClass('btn btn-remove');
		})->setPost([
			'm' => [
				['bar' => ''],
				['bar' => ''],
			]
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input.btn.btn-remove');
	}

	// bug #32
	public function testDeleteLastElementToZero() {
		$response = $this->services->form->createRequest('base', 0)->modifyForm(function (Form $form) {
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

}
