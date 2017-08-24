<?php

use Nette\Forms\Container;
use WebChemistry\Forms\Controls\Multiplier;
use Nette\Application\UI\Form;
use WebChemistry\Testing\TUnitTest;

class DefaultValuesTest extends \Codeception\TestCase\Test {

	use TUnitTest;

	/** @var array */
	private static $defaults = [
		'm' => [
			['bar' => 'foo'],
			['bar' => 'foo'],
		]
	];

	private function createMultiplier(callable $factory, $copyNumber = 1, $maxCopies = NULL) {
		$form = new Form();

		$form['m'] = new Multiplier($factory, $copyNumber, $maxCopies);

		$form->addSubmit('send');

		return $form;
	}

	protected function _before() {
		$form = $this->services->form;

		$form->addForm('base', function ($copyNumber = 1, $maxCopies = NULL) {
			return $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);
		});

		$form->addForm('defaults', function ($copyNumber = 2, $maxCopies = NULL) {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar');
			}, $copyNumber, $maxCopies);

			/** @var Multiplier $multiplier */
			$multiplier = $form['m'];

			$multiplier->setMinCopies(1);
			$multiplier->addRemoveButton();
			$multiplier->addCreateButton();

			$form->setDefaults(self::$defaults);

			return $form;
		});

		$form->addForm('defaultValue', function ($copyNumber = 1, $maxCopies = NULL) {
			$form = $this->createMultiplier(function (Container $container) {
				$container->addText('bar')
					->setDefaultValue('foo');
			}, $copyNumber, $maxCopies);

			/** @var Multiplier $multiplier */
			$multiplier = $form['m'];

			$multiplier->setMinCopies(1);
			$multiplier->addRemoveButton();
			$multiplier->addCreateButton();

			$form->setDefaults(self::$defaults);

			return $form;
		});

	}

	public function testRender() {
		$response = $this->services->form->createRequest('defaults')->render();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"][value="foo"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testRenderAndSetDefaultsInAction() {
		$response = $this->services->form->createRequest('base')->setActionCallback(function (Form $form) {
			$form->setDefaults(self::$defaults);
		})->render();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"][value="foo"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testRenderAndSetDefaultsInRender() {
		$response = $this->services->form->createRequest('base')->setRenderCallback(function (Form $form) {
			$form->setDefaults(self::$defaults);
		})->render();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"][value="foo"]');
		$this->assertDomNotHas($dom, 'input[name="m[2][bar]"]');
	}

	public function testRemoveButtons() {
		$response = $this->services->form->createRequest('defaults')->render();

		$dom = $response->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][' . Multiplier::SUBMIT_REMOVE_NAME . ']"]');
		$this->assertDomHas($dom, 'input[name="m[1][' . Multiplier::SUBMIT_REMOVE_NAME . ']"]');
	}

	public function testDefaultValue() {
		$response = $this->services->form->createRequest('defaultValue')->render();

		$dom = $response->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="foo"]');
	}

	public function testDefaultValueSend() {
		$response = $this->services->form->createRequest('defaultValue')->setPost([
			'm' => [
				['bar' => 'bar'],
				Multiplier::SUBMIT_CREATE_NAME => '',
			]
		])->send();

		$dom = $response->toDomQuery();
		$this->assertDomHas($dom, 'input[name="m[0][bar]"][value="bar"]');
		$this->assertDomHas($dom, 'input[name="m[1][bar]"][value="foo"]');
	}

}
