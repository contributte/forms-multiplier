<?php

namespace WebChemistry\Forms\Controls;

use Nette\Application\IPresenter;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\InvalidArgumentException;
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

	/** @var Submitter[] */
	protected $createButtons = [];

	/** @var string|bool */
	protected $removeButton = FALSE;

	/** @var array */
	protected $httpData = [];

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
	 */
	public function __construct(callable $factory, $copyNumber = 1, $maxCopies = NULL) {
		$this->factory = $factory;
		$this->minCopies = $this->copyNumber = $copyNumber;
		$this->maxCopies = $maxCopies;

		$this->monitor('Nette\Application\IPresenter');
		$this->monitor(Form::class);
	}

	protected function attached($obj) {
		parent::attached($obj);

		if ($obj instanceof IPresenter) {
			$this->whenAttached();
		} else if ($obj instanceof Form) {
			$obj->onRender[] = function () {
				$this->whenAttached();
			};
		}
	}

	/************************* setters **************************/

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

	/************************* getters **************************/

	/**
	 * @return int|null
	 */
	public function getMaxCopies() {
		return $this->maxCopies;
	}

	/**
	 * @return int
	 */
	public function getMinCopies() {
		return $this->minCopies;
	}

	/**
	 * @return int
	 */
	public function getCopyNumber() {
		return $this->copyNumber;
	}


	/************************* Buttons **************************/

	/**
	 * @param string|bool $caption False = not showed
	 * @return self
	 */
	public function addRemoveButton($caption = NULL) {
		$this->removeButton = $caption;

		return $this;
	}

	/**
	 * @param string|bool $caption False = not showed
	 * @param int $copyCount
	 * @return self
	 */
	public function addCreateButton($caption = NULL, $copyCount = 1) {
		if ($caption !== FALSE) {
			$this->createButtons[$copyCount] = new Submitter($caption, $copyCount);
		} else {
			unset($this->createButtons[$copyCount]);
		}

		return $this;
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

	/************************* Callbacks **************************/

	/**
	 * @param Submitter $submitter
	 * @internal
	 */
	public function onCreateSubmit(Submitter $submitter) {
		$this->getForm()->onSuccess = [];
		$this->getForm()->onError = [];
		$this->getForm()->onSubmit = [];
		$count = $submitter->getCopyCount();

		if ($this->maxCopies === NULL || iterator_count($this->getComponents(FALSE, 'Nette\Forms\Container')) < $this->maxCopies) {
			while ($count >= 1) {
				if (!$this->checkMaxCopies()) {
					break;
				}
				$container = $this->addCopy();
				if ($this->defaultValuesForce) {
					$this->applyDefaultValues($container);
				}
				$count--;
			}
		}

		$this->checkSubmitButtons();
	}

	/**
	 * @param SubmitButton $submitter
	 * @internal
	 */
	public function onRemoveSubmit(SubmitButton $submitter) {
		$this->getForm()->onSuccess = [];
		$this->getForm()->onError = [];
		$this->getForm()->onSubmit = [];

		if ($this->minCopies === NULL || iterator_count($this->getContainers()) > $this->minCopies) {
			$this->removeComponent($submitter->getParent());
			$this->totalCopies--;
			$this->checkSubmitButtons();
		}
	}

	/************************* Copies **************************/

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
		$this->fillContainer($container);

		if ($this->removeButton !== FALSE) {
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
		$this->created = TRUE;

		// Create submit buttons
		foreach ($this->createButtons as $btn) {
			$this->addComponent($btn, $btn->getOwnName());
			$btn->setValidationScope([$this])
				->setOmitted();

			$btn->onClick[] = $btn->onInvalidClick[] = [$this, 'onCreateSubmit'];
		}

		// Create components with values
		if (($this->values && !$this->isSubmitted()) || $this->httpData) {
			foreach (array_keys($this->httpData ? : $this->values) as $number) {
				if (!$this->checkMaxCopies()) {
					break;
				}

				$this->addCopy($number);
			}
		}

		// Create defaults components
		if (!$this->isSubmitted() && !$this->values) {
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

			foreach ($this->createButtons as $btn) {
				if (isset($values[$btn->getOwnName()])) {
					unset($values[$btn->getOwnName()]);
				}
			}

			$this->httpData = $values;
		}
	}

	/************************* helpers **************************/

	/**
	 * @param Container $container
	 */
	protected function applyDefaultValues(Container $container) {
		$factoryContainer = new Container();
		$this->fillContainer($factoryContainer);

		foreach ($factoryContainer->getControls() as $name => $control) {
			/** @var IControl $component */
			$component = $container->getComponent($name, FALSE);
			if ($component) {
				$component->setValue($control->getValue());
			}
		}
	}

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
	 * @param Container $container
	 */
	protected function fillContainer(Container $container) {
		call_user_func($this->factory, $container, $this->getForm());
	}

	/**
	 * @return array
	 */
	protected function getHtmlName() {
		return explode('-', $this->lookupPath('Nette\Forms\Form'));
	}

	protected function whenAttached() {
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

		if ($this->maxCopies !== NULL && $this->totalCopies >= $this->maxCopies && $this->createButtons) {
			foreach ($this->createButtons as $btn) {
				$this->removeComponent($btn);
			}
			$this->createButtons = [];
		}
	}

	/**
	 * @return bool
	 */
	protected function checkMaxCopies() {
		return $this->maxCopies === NULL || $this->maxCopies > $this->totalCopies;
	}

	/************************* Nette\Forms\Container **************************/

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
	 * @return Submitter[]
	 */
	public function getCreateButtons() {
		return $this->createButtons;
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
		Container::extensionMethod($name, function ($form, $name, $factory, $copyNumber = 1, $maxCopies = NULL) {
			return $form[$name] = new Multiplier($factory, $copyNumber, $maxCopies);
		});
	}

}
