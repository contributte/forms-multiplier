<?php

use Nette\Forms\Container;
use WebChemistry\Forms\Controls\Multiplier;
use Nette\Application\UI\Form;

class MultiplierTest extends \Codeception\TestCase\Test {

	/**
	 * @var \UnitTester
	 */
	protected $tester;

	protected function _before() {
	}

	protected function _after() {
	}

	public function testMultiplier() {
		$multiplier = $this->getControl(NULL);

		$this->assertCount(2, $multiplier->getControls());
		$this->assertInstanceOf('Nette\Forms\Container', $multiplier[0]);
		$this->assertInstanceOf('Nette\Forms\Controls\TextInput', $multiplier[0]['first']);
		$this->assertInstanceOf('Nette\Forms\Controls\TextInput', $multiplier[0]['second']);

		$multiplier = $this->getControl(NULL, 2);
		$multiplier->createCopies();

		$this->assertCount(4, $multiplier->getControls());
	}

	public function testAddCopy() {
		$multiplier = $this->getControl(NULL, 1);
		$this->assertCount(2, $multiplier->getControls());

		$multiplier->onCreateSubmit(); // @internal
		$this->assertCount(4, $multiplier->getControls());
	}

	public function testDefaults() {
		$multiplier = $this->getControl(NULL, 2);
		$multiplier->setDefaults([
			0 => [
				'first' => 'First',
				'second' => 'Second'
			],
			1 => [
				'first' => 'First 2',
				'second' => 'Second 2'
			]
		]);

		$this->assertCount(4, $multiplier->getControls());

		$this->assertSame('First', $multiplier[0]['first']->getValue());
		$this->assertSame('Second', $multiplier[0]['second']->getValue());
		$this->assertSame('First 2', $multiplier[1]['first']->getValue());
		$this->assertSame('Second 2', $multiplier[1]['second']->getValue());
	}

	public function testDefaultValue() {
		$multiplier = $this->getControl(function (Container $container) {
			$container->addText('first')
				->setDefaultValue('Value');
		}, 2);
		$multiplier->createCopies();

		$this->assertSame('Value', $multiplier[0]['first']->getValue());
		$this->assertSame('Value', $multiplier[1]['first']->getValue());

		// Add copy
		$multiplier->onCreateSubmit();

		$this->assertSame('Value', $multiplier[2]['first']->getValue());
	}

	public function testForce() {
		$multiplier = $this->getControl(function (Container $container) {
			$container->addText('first')
				->setDefaultValue('Value');
			$container->addText('second');
		}, 1, NULL, TRUE);
		$multiplier->setDefaults([
			0 => [
				'first' => 'First',
				'second' => 'Second'
			],
			1 => [
				'first' => 'First 2',
				'second' => 'Second 2'
			]
		]);
		$multiplier->createCopies();

		$this->assertCount(6, $multiplier->getControls());
	}

	public function testMaxCopies() {
		$multiplier = $this->getControl(function (Container $container) {
			$container->addText('first')
				->setDefaultValue('Value');
			$container->addText('second');
		}, 10, 3, TRUE);
		$multiplier->setDefaults([
			0 => [
				'first' => 'First',
				'second' => 'Second'
			],
			1 => [
				'first' => 'First 2',
				'second' => 'Second 2'
			]
		]);
		$multiplier->createCopies();

		$this->assertCount(6, $multiplier->getControls());

		// Add copy
		$multiplier->onCreateSubmit();

		$this->assertCount(6, $multiplier->getControls());
	}

	public function testButtons() {
		$multiplier = $this->getControl(NULL, 2);
		$multiplier->addCreateButton();
		$multiplier->addRemoveButton();

		$multiplier->createCopies();

		$this->assertCount(7, $multiplier->getControls());
		$this->assertInstanceOf('Nette\Forms\Controls\SubmitButton', $multiplier[Multiplier::SUBMIT_CREATE_NAME]);

		$this->assertInstanceOf('Nette\Forms\Controls\SubmitButton', $multiplier->getCreateButton());
		$this->assertSame(Multiplier::SUBMIT_CREATE_NAME, $multiplier->getCreateButton()->getName());
	}

	public function testGetValues() {
		$multiplier = $this->getControl(function (Container $container) {
			$container->addText('first')
				->setDefaultValue('Value');
			$container->addText('second');
		}, 2);

		// Add copy
		$multiplier->onCreateSubmit();
		// Add copy
		$multiplier->onCreateSubmit();
		$multiplier->createCopies();

		$this->assertSame([
			0 => [
				'first' => 'Value',
				'second' => ''
			],
			1 => [
				'first' => 'Value',
				'second' => ''
			],
			2 => [
				'first' => 'Value',
				'second' => ''
			],
			3 => [
				'first' => 'Value',
				'second' => ''
			],
		], $multiplier->getValues(TRUE));
	}

	public function testRegistration() {
		Multiplier::register();
		$form = new \Nette\Forms\Form();

		$this->assertInstanceOf('WebChemistry\Forms\Controls\Multiplier', $form->addMultiplier('name', function () {}));
		$this->assertInstanceOf('WebChemistry\Forms\Controls\Multiplier', $form['name']);
	}

	/************************* Presenter test **************************/

	public function testPresenterAdd() {
		$form = $this->sendRequestToPresenter('multiplier', ['multiplier' => [
			['first' => 'value'],
			Multiplier::SUBMIT_CREATE_NAME => 'submit'
		]], function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first');
			}))->addCreateButton();
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(2, $multiplier->getComponents(TRUE, 'Nette\Forms\Controls\TextInput'));
	}

	public function testPresenterRemove() {
		$form = $this->sendRequestToPresenter('multiplier', ['multiplier' => [
			['first' => 'value'],
			['first' => 'value'],
			[
				'first' => 'value',
				Multiplier::SUBMIT_REMOVE_NAME => 'Send'
			]
		]], function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first');
			}))->addRemoveButton('Send');
		}, 'submit');

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(2, $multiplier->getComponents(TRUE, 'Nette\Forms\Controls\TextInput'));
	}

	public function testPresenterMaxCopies() {
		$form = $this->sendRequestToPresenter('multiplier', ['multiplier' => [
			['first' => 'value'],
			['first' => 'value'],
			['first' => 'value'],
			['first' => 'value']
		]], function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first');
			}, 1, 2));
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(2, $multiplier->getComponents(TRUE, 'Nette\Forms\Controls\TextInput'));
		$this->assertSame([
			['first' => 'value'],
			['first' => 'value']
		], $multiplier->getValues(TRUE));
	}

	public function testDefaultValuesAfterAdd() {
		$form = $this->sendRequestToPresenter('multiplier', ['multiplier' => [
			['first' => 'value'],
			Multiplier::SUBMIT_CREATE_NAME => 'submit'
		]], function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first')
					->setDefaultValue('default');
			}))->addCreateButton();
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertSame([
			['first' => 'value'],
			['first' => 'default']
		], $multiplier->getValues(TRUE));
	}

	public function testValidation() {
		$form = $this->sendRequestToPresenter('multiplier', ['multiplier' => [
			['first' => 'value']
		]], function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first')
					->addRule(Form::MAX_LENGTH, NULL, 1);
			}));
		});

		$this->assertTrue($form->hasErrors());
		$this->assertSame([
			'multiplier' => [
				['first' => 'value']
			]
		], $form->getValues(TRUE));
	}

	/************************* Helpers **************************/

	protected function attachToForm(\Nette\Forms\Form $form, $factory, $copyNumber = 1, $maxCopies = NULL, $createForce = FALSE) {
		return $form['multiplier'] = new Multiplier($factory, $copyNumber, $maxCopies, $createForce);
	}

	/**
	 * @return Multiplier
	 */
	protected function getControl($factory = NULL, $copyNumber = 1, $maxCopies = NULL, $createForce = FALSE) {
		$form = new \Nette\Forms\Form();

		if ($factory === NULL) {
			$factory = function (Container $container) {
				$container->addText('first');
				$container->addText('second');
			};
		}

		return $form['multiplier'] = new Multiplier($factory, $copyNumber, $maxCopies, $createForce);
	}

	/**
	 * @param string $name
	 * @return \Nette\Application\UI\Presenter
	 */
	protected function createPresenter($name) {
		$presenterFactory = new \Nette\Application\PresenterFactory(function ($class) {
			/** @var \Nette\Application\UI\Presenter $presenter */
			$presenter = new $class();
			$presenter->injectPrimary(NULL, NULL, NULL,
				new \Nette\Http\Request(new \Nette\Http\UrlScript()), new \Nette\Http\Response(), NULL, NULL,
				new MockLatte());
			$presenter->autoCanonicalize = FALSE;

			return $presenter;
		});

		return $presenterFactory->createPresenter($name);
	}

	protected function sendRequestToPresenter($controlName = 'multiplier', $post, $factory = NULL) {
		$presenter = $this->createPresenter('Multiplier');
		if (is_callable($factory)) {
			$factory($presenter->getForm());
		}
		$presenter->run(new \Nette\Application\Request('Multiplier', 'POST', [
			'do' => $controlName . '-submit'
		], $post));
		/** @var \Nette\Application\UI\Form $form */
		$form = $presenter[$controlName];

		return $form;
	}

}
