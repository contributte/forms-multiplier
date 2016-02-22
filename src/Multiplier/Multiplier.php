<?php

namespace WebChemistry\Forms\Controls;

use Nette\Application\IPresenter;
use Nette\ComponentModel\IComponent;
use Nette\Forms\IControl;
use Nette\InvalidArgumentException;
use Nette\Utils\Callback;
use WebChemistry\Forms\Container;
use Nette\Forms;

class Multiplier extends Container {

	const SUBMIT_CREATE_NAME = 'multiplier_creator';
	const SUBMIT_REMOVE_NAME = 'multiplier_remover';

	/** @var callback */
	protected $factory;

	/** @var int */
	protected $copyNumber;

	/** @var int */
	protected $number = 0;

	/** @var bool */
	protected $created = FALSE;

	/** @var array */
	protected $values = array();

	/** @var bool */
	protected $erase;

	/** @var bool */
	protected $createForce = FALSE;

	/** @var array */
	protected $components = array();

	/** @var array */
	protected $buttons = array();

	/** @var array */
	protected $httpData = array();

	/** @var bool */
	protected $returnFilled = TRUE;

	/** @var int */
	protected $maxCopies = NULL;

	/** @var int */
	protected $totalCopies = 0;

	/**
	 * @param callback $factory
	 * @param int      $copyNumber
	 * @param int      $maxCopies
	 * @param bool     $createForce
	 */
	public function __construct($factory, $copyNumber = 1, $maxCopies = NULL, $createForce = FALSE) {
		$this->factory = Callback::check($factory);
		$this->copyNumber = $copyNumber;
		$this->createForce = $createForce;
		$this->maxCopies = $maxCopies;

		$this->monitor('Nette\Application\IPresenter');
	}

	/**
	 * @param callable $factory
	 * @return self
	 */
	public function setFactory($factory) {
		$this->factory = Callback::check($factory);

		return $this;
	}

	protected function attached($obj) {
		parent::attached($obj);

		if ($obj instanceof IPresenter) {
			$this->isAttached();
		}
	}

	protected function isAttached() {
		$this->loadHttpData();

		$this->createCopies();

		if (!$this->getForm()->isSubmitted()) {
			$this->checkSubmitButtons();
		}
	}

	protected function checkSubmitButtons() {
		if ($this->totalCopies === 1 && $this->getComponent(self::SUBMIT_REMOVE_NAME, FALSE)) {
			$this->removeComponent($this->getComponent(self::SUBMIT_REMOVE_NAME));
		}

		if ($this->totalCopies === $this->maxCopies && $this->getComponent(self::SUBMIT_CREATE_NAME, FALSE)) {
			$this->removeComponent($this->getComponent(self::SUBMIT_CREATE_NAME));
		}
	}

	protected function checkMaxCopies() {
		return $this->maxCopies === NULL || $this->maxCopies > $this->totalCopies;
	}

	/************************* Buttons **************************/

	/**
	 * @param string $caption
	 * @return Multiplier
	 */
	public function addCreateSubmit($caption = NULL) {
		$this->buttons[self::SUBMIT_CREATE_NAME] = array($caption, 'onCreateSubmit');

		return $this;
	}

	/**
	 * @param string $caption
	 * @return Multiplier
	 */
	public function addRemoveSubmit($caption = NULL) {
		$this->buttons[self::SUBMIT_REMOVE_NAME] = array($caption, 'onRemoveSubmit');

		return $this;
	}

	/**
	 * @internal
	 */
	public function onSubmit() {
		$this->getForm()->cleanErrors(TRUE);
	}

	/**
	 * @internal
	 */
	public function onCreateSubmit() {
		$this->getForm()->onSuccess = array();
		$this->getForm()->onError = array();

		$this->getForm()->onSubmit = array(
			array($this, 'onSubmit')
		);

		if ($this->maxCopies === NULL || iterator_count($this->getComponents(FALSE, 'Nette\Forms\Container')) < $this->maxCopies) {
			$this->addCopy();
		}

		$this->checkSubmitButtons();
	}

	/**
	 * @internal
	 */
	public function onRemoveSubmit() {
		$this->getForm()->onSuccess = array();
		$this->getForm()->onError = array();

		$this->getForm()->onSubmit = array(
			array($this, 'onSubmit')
		);

		$components = iterator_to_array($this->getComponents(FALSE, 'Nette\Forms\Container'));

		if (count($components) > 1) {
			$this->removeComponent(end($components));

			$this->totalCopies--;
		}

		$this->checkSubmitButtons();
	}

	/**
	 * Return name of first submit button
	 *
	 * @return null|string
	 */
	protected function getFirstSubmit() {
		$submits = iterator_to_array($this->getComponents(FALSE, 'Nette\Forms\Controls\SubmitButton'));

		if ($submits) {
			return reset($submits)->getName();
		}

		return NULL;
	}

	/**
	 * Create container before submit buttons
	 *
	 * @param string $name
	 * @return IComponent
	 */
	public function addContainer($name) {
		$control = new Container;
		$control->currentGroup = $this->currentGroup;
		$this->addComponent($control, $name, $this->getFirstSubmit());

		return $this[$name];
	}

	/************************* Copies **************************/

	/**
	 * Create unique number for container
	 *
	 * @return int
	 */
	protected function createNumber() {
		$count = iterator_count($this->getComponents(FALSE, 'Nette\Forms\Form'));
		while ($this->getComponent($count, FALSE)) {
			$count++;
		}

		return $count;
	}

	/**
	 * @param int $number
	 */
	protected function addCopy($number = NULL) {
		if (!is_numeric($number)) {
			$number = $this->createNumber();
		}
		$this->totalCopies++;
		$container = $this->addContainer($number);

		call_user_func($this->factory, $container);
	}

	/**
	 * @param bool
	 */
	public function createCopies() {
		if ($this->created === TRUE) {
			return;
		}

		// Create submit buttons
		foreach ($this->buttons as $name => $values) {
			$submit = $this->addSubmit($name, $values[0]);
			$submit->setValidationScope(FALSE);
			$submit->onClick[] = array($this, $values[1]);
			$submit->onInvalidClick[] = array($this, $values[1]);
		}

		$this->created = TRUE;
		$this->components = array();

		// Create components with values
		if ($this->values || $this->httpData) {
			foreach (array_keys($this->httpData ? : $this->values) as $number) {
				if (!$this->checkMaxCopies()) {
					break;
				}

				$this->components[] = $number;
				$this->addCopy($number);
			}
		}

		// Create defaults components
		if (!$this->isSubmitted() && (!$this->values || $this->createForce)) {
			for ($i = 0; $i < $this->copyNumber; $i++) {
				if (!$this->checkMaxCopies()) {
					break;
				}

				$this->components[] = $i;
				$this->addCopy();
			}
		}

		$this->setControlValues((array) $this->values);
	}

	/************************* Http data **************************/

	/**
	 * @return bool
	 */
	protected function isSubmitted() {
		return $this->getForm()->isSubmitted() && $this->getForm()->isAnchored();
	}

	/**
	 * @return array
	 */
	protected function getHtmlName() {
		return explode('-', $this->lookupPath('Nette\Forms\Form'));
	}

	protected function loadHttpData() {
		if ($this->getForm()->isSubmitted() && $this->getForm()->isAnchored()) {
			$values = $this->getForm()
						   ->getHttpData();

			foreach ($this->getHtmlName() as $name) {
				if (!array_key_exists($name, $values)) {
					$values = array();
					break;
				}

				$values = $values[$name];
			}

			foreach ($this->buttons as $name => $void) {
				unset($values[$name]);
			}

			$this->httpData = $values;
		}
	}

	/************************* Nette\Forms\Container **************************/

	/**
	 * @return \ArrayIterator
	 */
	public function getControls() {
		$this->createCopies();

		return parent::getControls();
	}

	/**
	 * @param array|\Traversable $values
	 * @return Multiplier
	 */
	protected function setControlValues($values) {
		if ($values instanceof \Traversable) {
			$values = iterator_to_array($values);

		} elseif (!is_array($values)) {
			throw new InvalidArgumentException(sprintf('First parameter must be an array, %s given.', gettype($values)));
		}

		foreach ($this->getComponents() as $name => $control) {
			if ($control instanceof IControl) {
				if (array_key_exists($name, $values)) {
					$control->setValue($values[$name]);

				} elseif ($this->erase) {
					$control->setValue(NULL);
				}

			} elseif ($control instanceof Forms\Container) {
				if (array_key_exists($name, $values)) {
					$control->setValues($values[$name], $this->erase);

				} elseif ($this->erase) {
					$control->setValues(array(), $this->erase);
				}
			}
		}
		return $this;
	}

	/**
	 * @param array|\Traversable $values
	 * @param bool $erase
	 * @return self
	 */
	public function setValues($values, $erase = FALSE) {
		$this->values = $values;
		$this->erase = $erase;

		return $this;
	}

}
