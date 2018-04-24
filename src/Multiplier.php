<?php

namespace WebChemistry\Forms\Controls;

use Nette\Application\IPresenter;
use Nette\ComponentModel\IComponent;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\Forms\Container;
use Nette\Utils\ArrayHash;

class Multiplier extends Container {

	const SUBMIT_CREATE_NAME = 'multiplier_creator',
		SUBMIT_REMOVE_NAME = 'multiplier_remover';

	/** @var Form */
	private $form;

	/** @var bool */
	private $attachedCalled = false;

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

	/** @var callable[] */
	public $onCreate = [];

	/** @var Container[] */
	protected $containerStack = [];

	/** @var bool */
	protected $createButtonsCreated = false;

	/**
	 * @param callable $factory
	 * @param int $copyNumber
	 * @param int $maxCopies
	 */
	public function __construct(callable $factory, $copyNumber = 1, $maxCopies = null) {
		$this->factory = $factory;
		$this->minCopies = $this->copyNumber = $copyNumber;
		$this->maxCopies = $maxCopies;

		$this->monitor(Form::class);
	}

	public function getForm($throw = true) {
		if ($this->form) {
			return $this->form;
		}

		return parent::getForm($throw);
	}

	protected function attached($obj) {
		parent::attached($obj);

		if ($obj instanceof Form) {
			$this->form = $obj;

			if ($this->getCurrentGroup() === null) {
				$this->setCurrentGroup($obj->getCurrentGroup());
			}
			if ($obj instanceof \Nette\Application\UI\Form) {
				$obj->onAnchor[] = function () {
					$this->whenAttached();
				};
			}
			$obj->onRender[] = function () {
				$this->whenAttached();
			};
		}
	}

	protected function whenAttached() {
		if ($this->attachedCalled) {
			return;
		}
		$this->loadHttpData();
		$this->createCopies();

		$this->attachedCalled = true;
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
			$this->createButtons[$copyCount] = [$caption, $copyCount < 1 ? 1 : (int) $copyCount, $onCreate];
		} else {
			unset($this->createButtons[$copyCount]);
		}

		return $this;
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

		if (!$this->getForm()->hasErrors()) {
			while ($count >= 1) {
				$container = $this->addCopy();
				if ($container === null) {
					break;
				}
				if ($this->defaultValuesForce) {
					$this->applyDefaultValues($container);
				}
				$count--;
			}
		}
	}

	/**
	 * @param SubmitButton $submitter
	 * @internal
	 */
	public function onRemoveSubmit(SubmitButton $submitter) {
		$this->getForm()->onSuccess = [];
		$this->getForm()->onError = [];
		$this->getForm()->onSubmit = [];

		$this->removeCopy($submitter->getParent());
	}

	/**
	 * @internal
	 */
	public function onCreateEvent() {
		foreach ($this->onCreate as $callback) {
			foreach ($this->getContainers() as $container) {
				$callback($container);
			}
		}
	}

	/************************* Copies **************************/

	/**
	 * @param int $number
	 * @param array|ArrayHash $defaults
	 * @return Container|null
	 */
	public function addCopy($number = null, $defaults = []) {
		if (!$this->checkMaxCopies()) {
			return null;
		}

		if (!is_numeric($number)) {
			$number = $this->createNumber();
		} else if ($component = parent::getComponent($number, false)) {
			return $component;
		}
		$this->totalCopies++;

		$container = $this->createContainer();
		$this->fillContainer($container);
		if ($defaults) {
			$container->setDefaults($defaults, $this->erase);
		}
		$this->attachContainer($container, $number);

		$this->checkButtonsAfterAdd($container);

		return $container;
	}

	/**
	 * @param IContainer $component
	 */
	public function removeCopy(IContainer $component) {
		if ($this->minCopies === null || iterator_count($this->getContainers()) > $this->minCopies) {
			$this->removeComponentProperly($component);
			$this->totalCopies--;

			$this->checkButtonsAfterRemove();
		}
	}

	protected function checkButtonsAfterRemove() {
		if ($this->totalCopies <= $this->minCopies && $this->removeButton) {
			foreach ($this->getContainers() as $container) {
				if ($control = $container->getComponent(self::SUBMIT_REMOVE_NAME, false)) {
					if ($this->getCurrentGroup()) {
						$this->getCurrentGroup()->remove($control);
					}
					$container->removeComponent($control);
				}
			}
		}

		$this->checkCreateButton();
	}

	protected function checkButtonsAfterAdd(Container $container) {
		$this->containerStack[] = $container;

		if ($this->totalCopies > $this->minCopies && $this->removeButton) {
			foreach ($this->containerStack as $container) {
				list($caption, $onCreate) = $this->removeButton;
				$submit = $container->addSubmit(self::SUBMIT_REMOVE_NAME, $caption)
					->setValidationScope(false)
					->setOmitted();
				$submit->onClick[] = $submit->onInvalidClick[] = [$this, 'onRemoveSubmit'];

				if ($onCreate) {
					$onCreate($submit);
				}
			}
			$this->containerStack = [];
		}

		$this->checkCreateButton();
	}

	protected function checkCreateButton() {
		if ($this->maxCopies === null && !$this->createButtonsCreated) {
			$this->createCreateButtons();
		} else if ($this->maxCopies !== null && $this->totalCopies >= $this->maxCopies && $this->createButtonsCreated) {
			$this->createButtonsCreated = false;
			// remove create buttons
			foreach ($this->createButtons as $copyCount => $_) {
				$this->removeComponentProperly($this[Helpers::createButtonName($copyCount)]);
			}
		} else if ($this->totalCopies < $this->maxCopies && !$this->createButtonsCreated) {
			$this->createCreateButtons();
		}
	}

	private function createCreateButtons() {
		$this->createButtonsCreated = true;
		// Create submit buttons
		foreach ($this->createButtons as $copyCount => $options) {
			$this->addComponent($btn = new Submitter(...$options), Helpers::createButtonName($copyCount));
			$btn->setValidationScope([$this])->setOmitted();

			$btn->onClick[] = $btn->onInvalidClick[] = [$this, 'onCreateSubmit'];
		}
	}

	/**
	 * @param bool
	 */
	public function createCopies() {
		if ($this->created === true) {
			return;
		}
		$this->created = true;

		// Create components with values
		if (($this->values && !$this->isSubmitted()) || $this->httpData) {
			foreach ($this->httpData ?: $this->values as $number => $values) {
				if (!$this->checkMaxCopies()) {
					break;
				}

				$this->addCopy($number, $values);
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

		// onCreateEvent
		/** @var SubmitButton $submitter */
		$submitter = $this->getForm()->isSubmitted();
		if (is_object($submitter) && $this->isInThisMultiplier($submitter)) {
			$submitter->onClick[] = function () {
				$this->onCreateEvent();
			};
		} else {
			$this->onCreateEvent();
		}
	}

	/**
	 * @param BaseControl $control
	 * @return bool
	 */
	protected function isInThisMultiplier(BaseControl $control) {
		while ($control->getParent()) {
			if ($control->getParent() === $this) {
				return true;
			}

			$control = $control->getParent();
		}

		return false;
	}

	/************************* Http data **************************/

	/**
	 * @return bool
	 */
	protected function isSubmitted() {
		return $this->getForm()->isAnchored() && $this->getForm()->isSubmitted();
	}

	protected function loadHttpData() {
		if ($this->isSubmitted()) {
			$values = $this->getForm()->getHttpData();
			foreach ($this->getHtmlName() as $name) {
				if (!array_key_exists($name, $values)) {
					$values = [];
					break;
				}

				$values = $values[$name];
			}

			foreach ($this->createButtons as $copyCount => $_) {
				if (isset($values[$name = Helpers::createButtonName($copyCount)])) {
					unset($values[$name]);
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

	/**
	 * @param Container $container
	 * @param string|int $name
	 */
	protected function attachContainer(Container $container, $name) {
		$this->addComponent($container, $name, $this->getFirstSubmit());
	}

	protected function removeComponentProperly(IComponent $component) {
		if ($this->getCurrentGroup()) {
			$this->getCurrentGroup()->remove($component);
		}
		$this->removeComponent($component);
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
