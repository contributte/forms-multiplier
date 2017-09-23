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
	protected $created = false;

	/** @var array */
	protected $values = [];

	/** @var bool */
	protected $erase;

	/** @var Submitter[] */
	protected $createButtons = [];

	/** @var array */
	protected $removeButton = [];

	/** @var array */
	protected $httpData = [];

	/** @var int */
	protected $maxCopies = null;

	/** @var int */
	protected $totalCopies = 0;

	/** @var bool */
	protected $defaultValuesForce = true;

	/** @var int */
	protected $minCopies = 1;

	/** @var bool */
	protected $resetKeys = true;

	/**
	 * @param callable $factory
	 * @param int $copyNumber
	 * @param int $maxCopies
	 */
	public function __construct(callable $factory, $copyNumber = 1, $maxCopies = null) {
		$this->factory = $factory;
		$this->minCopies = $this->copyNumber = $copyNumber;
		$this->maxCopies = $maxCopies;

		$this->monitor(IPresenter::class);
		$this->monitor(Form::class);
	}

	protected function attached($obj) {
		parent::attached($obj);

		if ($obj instanceof IPresenter) {
			$this->whenAttached();
		} else if ($obj instanceof Form) {
			if ($this->getCurrentGroup() === null) {
				$this->setCurrentGroup($obj->getCurrentGroup());
			}
			$obj->onRender[] = function () {
				if ($this->getForm(false)) {
					$this->whenAttached();
				}
			};
		}
	}
	/************************* setters **************************/

	/**
	 * @param bool $reset
	 * @return self
	 */
	public function setResetKeys($reset = true) {
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
	public function setDefaultValuesForce($defaultValuesForce = true) {
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
	 * @param callable|null $onCreate
	 * @return Multiplier
	 */
	public function addRemoveButton($caption = null, callable $onCreate = null) {
		$this->removeButton = [$caption, $onCreate];

		return $this;
	}

	/**
	 * @param string|bool $caption False = not showed
	 * @param int $copyCount
	 * @param callable|null $onCreate
	 * @return Multiplier
	 */
	public function addCreateButton($caption = null, $copyCount = 1, callable $onCreate = null) {
		if ($caption !== false) {
			$this->createButtons[$copyCount] = new Submitter($caption, $copyCount, $onCreate);
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
		$submits = iterator_to_array($this->getComponents(false, SubmitButton::class));
		if ($submits) {
			return reset($submits)->getName();
		}

		return null;
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

		if ($this->maxCopies === null || iterator_count($this->getComponents(false, Container::class)) < $this->maxCopies) {
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

		if ($this->minCopies === null || iterator_count($this->getContainers()) > $this->minCopies) {
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
	public function addCopy($number = null) {
		if (!is_numeric($number)) {
			$number = $this->createNumber();
		} else if ($component = $this->getComponent($number, false)) {
			return $component;
		}
		$this->totalCopies++;

		$container = $this->createContainer();
		$this->fillContainer($container);
		$this->attachContainer($container, $number);

		if ($this->removeButton) {
			list($caption, $onCreate) = $this->removeButton;
			$submit = $container->addSubmit(self::SUBMIT_REMOVE_NAME, $caption)
				->setValidationScope(false)
				->setOmitted();
			$submit->onClick[] = $submit->onInvalidClick[] = [$this, 'onRemoveSubmit'];

			if ($onCreate) {
				$onCreate($submit);
			}
		}

		return $container;
	}

	/**
	 * @param bool
	 */
	public function createCopies() {
		if ($this->created === true) {
			return;
		}
		$this->created = true;

		// Create submit buttons
		foreach ($this->createButtons as $btn) {
			$this->addComponent($btn, $btn->getOwnName());
			$btn->setValidationScope([$this])->setOmitted();

			$btn->onClick[] = $btn->onInvalidClick[] = [$this, 'onCreateSubmit'];
		}

		// Create components with values
		if (($this->values && !$this->isSubmitted()) || $this->httpData) {
			foreach (array_keys($this->httpData ?: $this->values) as $number) {
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
		return $this->getForm()->isAnchored() && $this->getForm()->isSubmitted();
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

			$this->createCopies();
		}
	}

	/************************* helpers **************************/

	/**
	 * @param Container $container
	 */
	protected function applyDefaultValues(Container $container) {
		$form = new Form('_foo_multiplier');
		$factoryContainer = $form->addContainer('foo');
		$this->fillContainer($factoryContainer);

		foreach ($factoryContainer->getControls() as $name => $control) {
			/** @var IControl $component */
			$component = $container->getComponent($name, false);
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
		$count = iterator_count($this->getComponents(false, Form::class));
		while ($this->getComponent($count, false)) {
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
		return explode('-', $this->lookupPath(Form::class));
	}

	protected function whenAttached() {
		$this->loadHttpData();
		$this->createCopies();

		$submitted = $this->getForm()->isSubmitted();

		if (!$submitted || ($submitted instanceof SubmitButton && $submitted->getParent() !== $this)) {
			$this->checkSubmitButtons();
		}
	}

	protected function checkSubmitButtons() {
		if ($this->totalCopies <= $this->minCopies && $this->removeButton) {
			foreach ($this->getContainers() as $container) {
				if ($control = $container->getComponent(self::SUBMIT_REMOVE_NAME, false)) {
					$container->removeComponent($control);
				}
			}
		}

		if ($this->maxCopies !== null && $this->totalCopies >= $this->maxCopies && $this->createButtons) {
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
		return $this->maxCopies === null || $this->maxCopies > $this->totalCopies;
	}

	/**
	 * @return Container
	 */
	protected function createContainer() {
		$control = new Container();
		$control->currentGroup = $this->currentGroup;

		return $control;
	}

	/**
	 * @param Container $container
	 * @param string|int $name
	 */
	protected function attachContainer(Container $container, $name) {
		$this->addComponent($container, $name, $this->getFirstSubmit());
	}

	/************************* Nette\Forms\Container **************************/

	/**
	 * @param bool $asArray
	 * @return array|\Nette\Utils\ArrayHash
	 */
	public function getValues($asArray = false) {
		if (!$this->resetKeys) {
			return parent::getValues($asArray);
		}

		$values = array_values(parent::getValues(true));

		return $asArray ? $values : ArrayHash::from($values);
	}

	/**
	 * @param $name
	 * @param bool $need
	 * @return IComponent
	 */
	public function getComponent($name, $need = true) {
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

		return $this->getComponents(false, Container::class);
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
		} else if (!is_array($values)) {
			throw new InvalidArgumentException(sprintf('First parameter must be an array, %s given.', gettype($values)));
		}

		foreach ($this->getComponents() as $name => $control) {
			if ($control instanceof IControl) {
				if (array_key_exists($name, $values)) {
					$control->setValue($values[$name]);
				} else if ($this->erase) {
					$control->setValue(null);
				}
			} else if ($control instanceof Container) {
				if (array_key_exists($name, $values)) {
					$control->setValues($values[$name], $this->erase);
				} else if ($this->erase) {
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
	public function setValues($values, $erase = false) {
		$this->values = $values;
		$this->erase = $erase;

		return $this;
	}

	/**
	 * @param string $name
	 */
	public static function register($name = 'addMultiplier') {
		Container::extensionMethod($name, function (Container $form, $name, $factory, $copyNumber = 1, $maxCopies = null) {
			$multiplier = new Multiplier($factory, $copyNumber, $maxCopies);
			$multiplier->setCurrentGroup($form->getCurrentGroup());

			return $form[$name] = $multiplier;
		});
	}

}
