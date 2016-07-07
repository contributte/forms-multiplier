<?php

namespace WebChemistry\Forms\Controls;

use Nette\Application\IPresenter;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\IControl;
use Nette\InvalidArgumentException;
use Nette\Object;
use Nette\Forms\Container;
use Nette\Utils\ArrayHash;

class Multiplier extends Container {

	const SUBMIT_CREATE_NAME = 'multiplier_creator',
		  SUBMIT_REMOVE_NAME = 'multiplier_remover';

	/** @var callable */
	protected $factory;

	/** @var int */
	protected $copyNumber;

	/** @var int */
	protected $number = 0;

	/** @var bool */
	protected $created = FALSE;

	/** @var array */
	protected $values = [];

	/** @var bool */
	protected $erase;

	/** @var bool */
	protected $createForce = FALSE;

	/** @var array */
	protected $components = [];

	/** @var string|bool */
	protected $createButton = FALSE;

	/** @var string|bool */
	protected $removeButton = FALSE;

	/** @var array */
	protected $httpData = [];

	/** @var bool */
	protected $returnFilled = TRUE;

	/** @var int */
	protected $maxCopies = NULL;

	/** @var int */
	protected $totalCopies = 0;

	/** @var bool */
	protected $defaultValuesForce = TRUE;

	/** @var int */
	protected $minCopies = 1;

	/** @var bool */
	protected $resetKeys = TRUE;

	/**
	 * @param callable $factory
	 * @param int $copyNumber
	 * @param int $maxCopies
	 * @param bool $createForce
	 */
	public function __construct(callable $factory, $copyNumber = 1, $maxCopies = NULL, $createForce = FALSE) {
		$this->factory = $factory;
		$this->minCopies = $this->copyNumber = $copyNumber;
		$this->createForce = $createForce;
		$this->maxCopies = $maxCopies;

		$this->monitor('Nette\Application\IPresenter');
	}

	/**
	 * @param bool $reset
	 * @return self
	 */
	public function setResetKeys($reset = TRUE) {
		$this->resetKeys = $reset;

		return $this;
	}

	/**
	 * @param int $minCopies
	 * @return self
	 */
	public function setMinCopies($minCopies) {
		$this->minCopies = $minCopies;

		return $this;
	}

	/**
	 * @param callable $factory
	 * @return self
	 */
	public function setFactory(callable $factory) {
		$this->factory = $factory;

		return $this;
	}

	/**
	 * @param bool $defaultValuesForce
	 * @return self
	 */
	public function setDefaultValuesForce($defaultValuesForce = TRUE) {
		$this->defaultValuesForce = $defaultValuesForce;

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
		if ($this->totalCopies <= $this->minCopies && $this->removeButton !== FALSE) {
			foreach ($this->getContainers() as $container) {
				if ($control = $container->getComponent(self::SUBMIT_REMOVE_NAME, FALSE)) {
					$container->removeComponent($control);
				}
			}
		}

		if ($this->totalCopies === $this->maxCopies && $this->getComponent(self::SUBMIT_CREATE_NAME, FALSE)) {
			$this->removeComponent($this->getComponent(self::SUBMIT_CREATE_NAME));
		}
	}

	/**
	 * @return bool
	 */
	protected function checkMaxCopies() {
		return $this->maxCopies === NULL || $this->maxCopies > $this->totalCopies;
	}

	/************************* Buttons **************************/

	/**
	 * @param string|bool $caption False = not showed
	 * @return self
	 */
	public function setRemoveSubmit($caption = NULL) {
		$this->removeButton = $caption;

		return $this;
	}

	/**
	 * @param string|bool $caption False = not showed
	 * @return self
	 */
	public function setCreateSubmit($caption = NULL) {
		$this->createButton = $caption;

		return $this;
	}

	/**
	 * @internal
	 */
	public function onCreateSubmit() {
		$this->getForm()->onSuccess = [];
		$this->getForm()->onError = [];
		$this->getForm()->onSubmit = [];

		if ($this->maxCopies === NULL || iterator_count($this->getComponents(FALSE, 'Nette\Forms\Container')) < $this->maxCopies) {
			$container = $this->addCopy();
			if ($this->defaultValuesForce) {
				$this->applyDefaultValues($container);
			}
		}

		$this->checkSubmitButtons();
	}

	/**
	 * @param Container $container
	 */
	public function applyDefaultValues(Container $container) {
		$factoryContainer = new Container();
		call_user_func($this->factory, $factoryContainer);

		foreach ($factoryContainer->getControls() as $name => $control) {
			/** @var IControl $component */
			$component = $container->getComponent($name, FALSE);
			if ($component) {
				$component->setValue($control->getValue());
			}
		}
	}

	/**
	 * @internal
	 */
	public function onRemoveSubmit(SubmitButton $submitButton) {
		$this->getForm()->onSuccess = [];
		$this->getForm()->onError = [];
		$this->getForm()->onSubmit = [];

		if ($this->maxCopies === NULL || iterator_count($this->getContainers()) < $this->maxCopies) {
			$this->removeComponent($submitButton->getParent());
			$this->totalCopies--;
			$this->checkSubmitButtons();
		}
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
	 * @return Container
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
	 * @return Container
	 */
	protected function addCopy($number = NULL) {
		if (!is_numeric($number)) {
			$number = $this->createNumber();
		}
		$this->totalCopies++;
		$container = $this->addContainer($number);
		call_user_func($this->factory, $container);

		if ($this->removeButton) {
			$submit = $container->addSubmit(self::SUBMIT_REMOVE_NAME, $this->removeButton)
				->setValidationScope(FALSE)
				->setOmitted();
			$submit->onClick[] = $submit->onInvalidClick[] = [$this, 'onRemoveSubmit'];
		}

		return $container;
	}

	/**
	 * @param bool
	 */
	public function createCopies() {
		if ($this->created === TRUE) {
			return;
		}

		// Create submit buttons
		if ($this->createButton !== FALSE) {
			$submit = $this->addSubmit(self::SUBMIT_CREATE_NAME, $this->createButton)
				->setValidationScope([$this])
				->setOmitted();

			$submit->onClick[] = $submit->onInvalidClick[] = [$this, 'onCreateSubmit'];
		}

		$this->created = TRUE;

		// Create components with values
		if ($this->values || $this->httpData) {
			foreach (array_keys($this->httpData ? : $this->values) as $number) {
				if (!$this->checkMaxCopies()) {
					break;
				}

				$this->addCopy($number);
			}
		}

		// Create defaults components
		if ($this->createForce || (!$this->isSubmitted() && !$this->values)) {
			for ($i = 0; $i < $this->copyNumber; $i++) {
				if (!$this->checkMaxCopies()) {
					break;
				}

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
			$values = $this->getForm()->getHttpData();
			foreach ($this->getHtmlName() as $name) {
				if (!array_key_exists($name, $values)) {
					$values = [];
					break;
				}

				$values = $values[$name];
			}

			if (isset($values[self::SUBMIT_CREATE_NAME])) {
				unset($values[self::SUBMIT_CREATE_NAME]);
			}

			$this->httpData = $values;
		}
	}

	/************************* Nette\Forms\Container **************************/

	/**
	 * @param bool $asArray
	 * @return array|\Nette\Utils\ArrayHash
	 */
	public function getValues($asArray = FALSE) {
		if (!$this->resetKeys) {
			return parent::getValues($asArray);
		}

		$values = array_values(parent::getValues(TRUE));

		return $asArray ? $values : ArrayHash::from($values);
	}

	/**
	 * @param $name
	 * @param bool $need
	 * @return IComponent
	 */
	public function getComponent($name, $need = TRUE) {
		$this->createCopies();

		return parent::getComponent($name, $need);
	}

	/**
	 * @return \ArrayIterator|IControl[]
	 */
	public function getControls() {
		$this->createCopies();

		return parent::getControls();
	}

	/**
	 * @return Container[]|\ArrayIterator
	 */
	public function getContainers() {
		$this->createCopies();

		return $this->getComponents(FALSE, 'Nette\Forms\Container');
	}

	/**
	 * @return SubmitButton|null
	 */
	public function getCreateButton() {
		return $this->getComponent(self::SUBMIT_CREATE_NAME, FALSE);
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

			} elseif ($control instanceof Container) {
				if (array_key_exists($name, $values)) {
					$control->setValues($values[$name], $this->erase);

				} elseif ($this->erase) {
					$control->setValues([], $this->erase);
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

	/**
	 * @param string $name
	 */
	public static function register($name = 'addMultiplier') {
		Object::extensionMethod('Nette\Forms\Container::' . $name, function ($form, $name, $factory, $copyNumber = 1, $maxCopies = NULL, $createForce = FALSE) {
			return $form[$name] = new Multiplier($factory, $copyNumber, $maxCopies, $createForce);
		});
	}

}
