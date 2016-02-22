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

	public function testRemoveCopy() {
		$multiplier = $this->getControl(NULL, 2);
		$this->assertCount(4, $multiplier->getControls());

		$multiplier->onRemoveSubmit(); // @internal
		$this->assertCount(2, $multiplier->getControls());
	}

	public function testDefaults() {
		$multiplier = $this->getControl(NULL, 2);
		$multiplier->setDefaults(array(
			0 => array(
				'first' => 'First',
				'second' => 'Second'
			),
			1 => array(
				'first' => 'First 2',
				'second' => 'Second 2'
			)
		));

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
		$multiplier->setDefaults(array(
			0 => array(
				'first' => 'First',
				'second' => 'Second'
			),
			1 => array(
				'first' => 'First 2',
				'second' => 'Second 2'
			)
		));
		$multiplier->createCopies();

		$this->assertCount(6, $multiplier->getControls());
	}

	public function testMaxCopies() {
		$multiplier = $this->getControl(function (Container $container) {
			$container->addText('first')
				->setDefaultValue('Value');
			$container->addText('second');
		}, 10, 3, TRUE);
		$multiplier->setDefaults(array(
			0 => array(
				'first' => 'First',
				'second' => 'Second'
			),
			1 => array(
				'first' => 'First 2',
				'second' => 'Second 2'
			)
		));
		$multiplier->createCopies();

		$this->assertCount(6, $multiplier->getControls());

		// Add copy
		$multiplier->onCreateSubmit();

		$this->assertCount(6, $multiplier->getControls());
	}

	public function testButtons() {
		$multiplier = $this->getControl(NULL, 2);
		$multiplier->addCreateSubmit();
		$multiplier->addRemoveSubmit();

		$multiplier->createCopies();

		$this->assertCount(6, $multiplier->getControls());
		$this->assertInstanceOf('Nette\Forms\Controls\SubmitButton', $multiplier[Multiplier::SUBMIT_CREATE_NAME]);
		$this->assertInstanceOf('Nette\Forms\Controls\SubmitButton', $multiplier[Multiplier::SUBMIT_REMOVE_NAME]);

		// Without remove button
		$multiplier = $this->getControl(NULL, 1);
		$multiplier->addCreateSubmit();

		$this->assertCount(3, $multiplier->getControls());
		$this->assertInstanceOf('Nette\Forms\Controls\SubmitButton', $multiplier[Multiplier::SUBMIT_CREATE_NAME]);
		$this->assertNull($multiplier->getComponent(Multiplier::SUBMIT_REMOVE_NAME, FALSE));

		// Without submit button
		$multiplier = $this->getControl(NULL, 5);
		$multiplier->addRemoveSubmit();

		$this->assertCount(11, $multiplier->getControls());
		$this->assertInstanceOf('Nette\Forms\Controls\SubmitButton', $multiplier[Multiplier::SUBMIT_REMOVE_NAME]);
		$this->assertNull($multiplier->getComponent(Multiplier::SUBMIT_CREATE_NAME, FALSE));
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

		$this->assertSame(array(
			0 => array(
				'first' => 'Value',
				'second' => ''
			),
			1 => array(
				'first' => 'Value',
				'second' => ''
			),
			2 => array(
				'first' => 'Value',
				'second' => ''
			),
			3 => array(
				'first' => 'Value',
				'second' => ''
			),
		), $multiplier->getValues(TRUE));
	}

	public function testRegistration() {
		Multiplier::register();
		$form = new \Nette\Forms\Form();

		$this->assertInstanceOf('WebChemistry\Forms\Controls\Multiplier', $form->addMultiplier('name', function () {}));
		$this->assertInstanceOf('WebChemistry\Forms\Controls\Multiplier', $form['name']);
	}

	/************************* Presenter test **************************/

	public function testPresenterAdd() {
		$form = $this->sendRequestToPresenter('multiplier', array('multiplier' => array(
			array('first' => 'value'),
			Multiplier::SUBMIT_CREATE_NAME => 'submit'
		)), function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first');
			}))->addCreateSubmit();
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(2, $multiplier->getComponents(TRUE, 'Nette\Forms\Controls\TextInput'));
	}

	public function testPresenterRemove() {
		$form = $this->sendRequestToPresenter('multiplier', array('multiplier' => array(
			array('first' => 'value'),
			array('first' => 'value'),
			array('first' => 'value'),
			Multiplier::SUBMIT_REMOVE_NAME => 'submit'
		)), function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first');
			}))->addRemoveSubmit();
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(2, $multiplier->getComponents(TRUE, 'Nette\Forms\Controls\TextInput'));
	}

	public function testPresenterMaxCopies() {
		$form = $this->sendRequestToPresenter('multiplier', array('multiplier' => array(
			array('first' => 'value'),
			array('first' => 'value'),
			array('first' => 'value'),
			array('first' => 'value')
		)), function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first');
			}, 1, 2));
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(2, $multiplier->getComponents(TRUE, 'Nette\Forms\Controls\TextInput'));
		$this->assertSame(array(
			array('first' => 'value'),
			array('first' => 'value')
		), $multiplier->getValues(TRUE));
	}

	public function testDefaultValuesAfterAdd() {
		$form = $this->sendRequestToPresenter('multiplier', array('multiplier' => array(
			array('first' => 'value'),
			Multiplier::SUBMIT_CREATE_NAME => 'submit'
		)), function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first')
					->setDefaultValue('default');
			}))->addCreateSubmit();
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertSame(array(
			array('first' => 'value'),
			array('first' => 'default')
		), $multiplier->getValues(TRUE));
	}

	public function testPresenterForceCreate() {
		$form = $this->sendRequestToPresenter('multiplier', array('multiplier' => array(
			array('first' => 'value')
		)), function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first');
			}, 2, NULL, TRUE));
		});

		/** @var Multiplier $multiplier */
		$multiplier = $form['multiplier'];

		$this->assertCount(3, $multiplier->getControls());
	}

	public function testValidation() {
		$form = $this->sendRequestToPresenter('multiplier', array('multiplier' => array(
			array('first' => 'value')
		)), function (Form $form) {
			$form['multiplier'] = (new Multiplier(function (Container $container) {
				$container->addText('first')
					->addRule(Form::MAX_LENGTH, NULL, 1);
			}));
		});

		$this->assertTrue($form->hasErrors());
		$this->assertSame(array(
			'multiplier' => array(
				array('first' => 'value')
			)
		), $form->getValues(TRUE));
	}

	/************************* Helpers **************************/

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

	protected function sendRequestToPresenter($controlName = 'multiplier', $post, $factory = NULL, $submitter = NULL) {
		if ($submitter) {
			$submitter = array(
				'multiplier' => array(
					$submitter => 'submit'
				)
			);
		}
		$presenter = $this->createPresenter('Multiplier');
		if (is_callable($factory)) {
			$factory($presenter->getForm());
		}
		$presenter->run(new \Nette\Application\Request('Multiplier', 'POST', array_merge(array(
			'do' => $controlName . '-submit'
		), (array) $submitter), $post));
		/** @var \Nette\Application\UI\Form $form */
		$form = $presenter[$controlName];

		return $form;
	}

}
