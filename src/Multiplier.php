<?php

namespace WebChemistry\Forms\Controls;

use Nette\ComponentModel\IComponent;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\Forms\Container;
use Nette\Utils\ArrayHash;
use Nette\Utils\Arrays;

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

	/** @var int */
	protected $minCopies = 1;

	/** @var bool */
	protected $resetKeys = true;

	/** @var callable[] */
	public $onCreate = [];

	/** @var Container[] */
	protected $noValidate = [];

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
		$this->monitor(self::class);
	}

	public function getForm($throw = true) {
		if ($this->form) {
			return $this->form;
		}

		return parent::getForm($throw);
	}

	protected function attached($obj) {
		parent::attached($obj);

		if ($obj instanceof self) {
			$this->whenAttached();
		} else if ($obj instanceof Form) {
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
			$this->createButtons[$copyCount] = [$caption, $copyCount, $onCreate];
		} else {
			unset($this->createButtons[$copyCount]);
		}

		return $this;
	}

	/************************* Callbacks **************************/

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

	protected function isValidMaxCopies() {
		return $this->maxCopies === null || $this->totalCopies < $this->maxCopies;
	}

	public function validate(array $controls = null) {
		$controls = $controls === null ? iterator_to_array($this->getComponents()) : $controls;

		foreach ($controls as $index => $control) {
			if (in_array($control, $this->noValidate)) {
				unset($controls[$index]);
			}
		}

		parent::validate($controls);
	}

	/**
	 * @param int $number
	 * @param array|ArrayHash $defaults
	 * @return Container|null
	 */
	protected function addCopy($number = null, $defaults = []) {
		if (!is_numeric($number)) {
			$number = $this->createNumber();
		} else if ($component = parent::getComponent($number, false)) {
			return $component;
		}

		$container = $this->createContainer();
		$this->fillContainer($container);
		if ($defaults) {
			$container->setDefaults($defaults, $this->erase);
		}
		$this->attachContainer($container, $number);

		return $container;
	}

	private function createComponents(ComponentResolver $resolver) {
		$containers = [];

		// Create components with values
		foreach ($resolver->getValuesForComponents() as $number => $values) {
			$containers[] = $container = $this->addCopy($number, $values);
			$this->attachRemoveButton($container);
			$this->totalCopies++;
		}

		// Default number of copies
		if (!$this->isSubmitted() && !$this->values) {
			$copyNumber = $this->copyNumber;
			while ($copyNumber > 0 && $this->isValidMaxCopies()) {
				$containers[] = $container = $this->addCopy();
				$this->attachRemoveButton($container);
				$this->applyDefaultValues($container);
				$this->totalCopies++;
				$copyNumber--;
			}
		}

		// New containers, if create button hitted
		if ($resolver->isCreateAction() && $this->form->isValid()) {
			$count = $resolver->getCreateNum();
			while ($count > 0 && $this->isValidMaxCopies()) {
				$this->noValidate[] = $containers[] = $container = $this->addCopy();
				$this->attachRemoveButton($container);
				$this->applyDefaultValues($container);
				$this->totalCopies++;
				$count--;
			}
		}

		if ($this->removeButton && $this->totalCopies <= $this->minCopies) {
			foreach ($containers as $container) {
				$this->detachRemoveButton($container);
			}
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

		$resolver = new ComponentResolver(
			$this->httpData, $this->values, $this->maxCopies, $this->minCopies
		);

		$this->attachCreateButtons();
		$this->createComponents($resolver);
		$this->detachCreateButtons();

		if ($this->maxCopies === null || $this->totalCopies < $this->maxCopies) {
			$this->attachCreateButtons();
		}

		if ($resolver->isRemoveAction() && $this->totalCopies >= $this->minCopies && !$resolver->reachedMinLimit()) {
			$container = $this->addCopy($resolver->getRemoveId());
			$this->attachRemoveButton($container);
			$container->getComponent(self::SUBMIT_REMOVE_NAME)->onClick[] = function () use ($container) {
				foreach ($container->getComponents() as $control) {
					if ($this->getCurrentGroup()) {
						$this->getCurrentGroup()->remove($control);
					}
					$container->removeComponent($control);
				}
				$this->removeComponent($container);
			};
		}

		// onCreateEvent
		$this->onCreateEvent();
	}

	private function detachCreateButtons() {
		foreach ($this->createButtons as $copyCount => $_) {
			$this->removeComponentProperly($this->getComponent(Helpers::createButtonName($copyCount)));
		}
	}

	private function attachCreateButtons() {
		foreach ($this->createButtons as $copyCount => $options) {
			$btn = new Submitter(...$options);
			$btn->setValidationScope([$this])->setOmitted();
			$btn->onClick[] = function () {
				$this->form->onSuccess = $this->form->onError = $this->form->onSubmit = [];
			};
			$this->addComponent($btn, Helpers::createButtonName($copyCount));
		}
	}

	private function detachRemoveButton(Container $container) {
		$button = $container->getComponent(self::SUBMIT_REMOVE_NAME);
		if ($this->getCurrentGroup()) {
			$this->getCurrentGroup()->remove($button);
		}

		$container->removeComponent($button);
	}

	private function attachRemoveButton(Container $container) {
		if (!$this->removeButton) {
			return;
		}

		list($caption, $onCreate) = $this->removeButton;
		$submit = $container->addSubmit(self::SUBMIT_REMOVE_NAME, $caption)
			->setValidationScope(false)
			->setOmitted();

		if ($onCreate) {
			$onCreate($submit);
		}
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
			$this->httpData = Arrays::get($this->form->getHttpData(), $this->getHtmlName(), []);
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
	 * @return Container
	 */
	protected function createContainer() {
		$control = new Container();
		$control->currentGroup = $this->currentGroup;

		return $control;
	}

	/**
	 * @return Submitter[]
	 */
	public function getCreateButtons() {
		$buttons = [];
		foreach ($this->createButtons as $copyCount => $_) {
			$buttons[$copyCount] = $this->getComponent(Helpers::createButtonName($copyCount));
		}

		return $buttons;
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
