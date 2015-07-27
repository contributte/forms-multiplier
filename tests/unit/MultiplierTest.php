<?php

use WebChemistry\Forms\Form;

class MultiplierTest extends \PHPUnit_Framework_TestCase {

	protected function setUp() {
	}

	protected function tearDown() {
	}

	public function testMultiplier() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['base'];

		/** @var \WebChemistry\Forms\Controls\Multiplier $multiplier */
		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first');
			$container->addText('second');
		}, 1);

		$this->assertCount(2, $multiplier->getControls());
		$this->assertInstanceOf('WebChemistry\Forms\Container', $multiplier[0]);
		$this->assertInstanceOf('Nette\Forms\Controls\TextInput', $multiplier[0]['first']);
		$this->assertInstanceOf('Nette\Forms\Controls\TextInput', $multiplier[0]['second']);

		$multiplier = $form->addMultiplier('multiplierSecond', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first');
			$container->addText('second');
		}, 2);

		$this->assertCount(4, $multiplier->getControls());
	}

	public function testAddCopy() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['base'];

		/** @var \WebChemistry\Forms\Controls\Multiplier $multiplier */
		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first');
			$container->addText('second');
		}, 1);

		$this->assertCount(2, $multiplier->getControls());

		$multiplier->onCreateSubmit(); // @internal

		$this->assertCount(4, $multiplier->getControls());
	}

	public function testRemoveCopy() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['base'];

		/** @var \WebChemistry\Forms\Controls\Multiplier $multiplier */
		$multiplier = $form->addMultiplier('multiplier', function (\WebChemistry\Forms\Container $container) {
			$container->addText('first');
			$container->addText('second');
		}, 2);

		$this->assertCount(4, $multiplier->getControls());

		$multiplier->onRemoveSubmit(); // @internal

		$this->assertCount(2, $multiplier->getControls());
	}

	public function testDefaults() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['defaults'];

		$multi = $form['multiplier'];

		$this->assertCount(4, $multi->getControls());

		$this->assertSame('First', $multi[0]['first']->getValue());
		$this->assertSame('Second', $multi[0]['second']->getValue());
		$this->assertSame('First 2', $multi[1]['first']->getValue());
		$this->assertSame('Second 2', $multi[1]['second']->getValue());
	}

	public function testDefaultValue() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['defaultValue'];

		$multi = $form['multiplier'];

		$this->assertSame('Value', $multi[0]['first']->getValue());
		$this->assertSame('Value', $multi[1]['first']->getValue());

		// Add copy
		$multi->onCreateSubmit();

		$this->assertSame('Value', $multi[2]['first']->getValue());
	}

	public function testForce() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['force'];

		$multi = $form['multiplier'];

		$this->assertCount(6, $multi->getControls());
	}

	public function testMaxCopies() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['maxCopies'];

		$multi = $form['multiplier'];

		$this->assertCount(6, $multi->getControls());

		// Add copy
		$multi->onCreateSubmit();

		$this->assertCount(6, $multi->getControls());
	}

	public function testButtons() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['buttons'];

		$multiplier = $form['multiplier'];

		$this->assertCount(6, $multiplier->getControls());
		$this->assertInstanceOf('Nette\Forms\Controls\SubmitButton', $multiplier[\WebChemistry\Forms\Controls\Multiplier::SUBMIT_CREATE_NAME]);
		$this->assertInstanceOf('Nette\Forms\Controls\SubmitButton', $multiplier[\WebChemistry\Forms\Controls\Multiplier::SUBMIT_REMOVE_NAME]);

		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['buttonsWithoutRemove'];

		$multiplier = $form['multiplier'];

		// Remove button must not be showen
		$this->assertCount(3, $multiplier->getControls());

		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['buttonsWithoutCreate'];

		$multiplier = $form['multiplier'];

		// Create button must not be showen
		$this->assertCount(11, $multiplier->getControls());
	}

	public function testGetValues() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');
		$presenter = $presenterFactory->createPresenter('Multiplier');

		/** @var Form $form */
		$form = $presenter['getDefaultValue'];

		$multi = $form['multiplier'];

		// Add copy
		$multi->onCreateSubmit();

		// Add copy
		$multi->onCreateSubmit();

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
		), $multi->getValues(TRUE));
	}
}
