<?php

use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use WebChemistry\Forms\Controls\Multiplier;
use Nette\Application\UI\Form;
use WebChemistry\Forms\Controls\Submitter;
use WebChemistry\Testing\TUnitTest;

class CreateButtonTest extends \Codeception\TestCase\Test {

	use TUnitTest;

	private function createMultiplier(callable $factory, $copyNumber = 1, $maxCopies = NULL) {
		$form = new Form();

		$form['m'] = new Multiplier($factory, $copyNumber, $maxCopies);

		$form->addSubmit('send');

		return $form;
	}

	protected function _before() {
		$form = $this->services->form;

		$form->addForm('buttons', function ($copyNumber = 2, $maxCopies = NULL) {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);

			/** @var Multiplier $multiplier */
			$multiplier = $form['m'];

			$multiplier->setMinCopies(1);
			$multiplier->addRemoveButton();
			$multiplier->addCreateButton();

			return $form;
		});

		$form->addForm('twoButtons', function ($copyNumber = 1, $maxCopies = NULL) {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);

			/** @var Multiplier $multiplier */
			$multiplier = $form['m'];

			$multiplier->setMinCopies(1);
			$multiplier->addRemoveButton();
			$multiplier->addCreateButton(NULL, 5);

			return $form;
		});

		$form->addForm('callback', function () {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			});

			/** @var Multiplier $multiplier */
			$multiplier = $form['m'];

			$multiplier->setMinCopies(1);
			$multiplier->addRemoveButton(null, function (SubmitButton $submitter) {
				$submitter->setHtmlAttribute('class', 'delete-btn');
			});
			$multiplier->addCreateButton(NULL, 5, function (Submitter $submitter) {
				$submitter->setHtmlAttribute('class', 'add-btn');
			});

			return $form;
		});

	}

	public function testSendCreate() {
		$response = $this->services->form->createRequest('buttons')->setPost([
			'm' => [
				['bar' => ''],
				['bar' => ''],
				'multiplier_creator' => ''
			]
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testSendCreateOverMaxCopies() {
		$response = $this->services->form->createRequest('buttons', 2, 2)->setPost([
			'm' => [
				['bar' => ''],
				['bar' => ''],
				'multiplier_creator' => ''
			]
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testSendCreateButtonWith5Copies() {
		$response = $this->services->form->createRequest('twoButtons')->setPost([
			'm' => [
				['bar' => ''],
				'multiplier_creator5' => ''
			]
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

	public function testCallback() {
		$response = $this->services->form->createRequest('callback')->setPost([
			'm' => [
				['bar' => ''],
				['bar' => ''],
			]
		])->send();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input.delete-btn');
		$this->assertDomHas($dom, 'input.add-btn');
	}

}
