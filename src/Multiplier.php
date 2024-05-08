<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier;

use Contributte\FormMultiplier\Buttons\CreateButton;
use Contributte\FormMultiplier\Buttons\RemoveButton;
use Iterator;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Container;
use Nette\Forms\Control;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Traversable;

class Multiplier extends Container
{

	public const SUBMIT_CREATE_NAME = 'multiplier_creator';

	public const SUBMIT_REMOVE_NAME = 'multiplier_remover';

	/** @var callable[] */
	public array $onCreate = [];

	/** @var callable[] */
	public array $onRemove = [];

	/** @var callable[] */
	public array $onCreateComponents = [];

	/** @var callable */
	protected $factory;

	protected int $copyNumber;

	protected int $number = 0;

	protected bool $created = false;

	/** @var mixed[] */
	protected array $values = [];

	protected bool $erase = false;

	/** @var CreateButton[] */
	protected array $createButtons = [];

	protected ?RemoveButton $removeButton = null;

	protected ?int $maxCopies = null;

	protected int $totalCopies = 0;

	protected int $minCopies = 1;

	protected bool $resetKeys = true;

	/** @var Container[] */
	protected array $noValidate = [];

	protected ComponentResolver $resolver;

	private ?Form $form = null;

	private bool $attachedCalled = false;

	public function __construct(callable $factory, int $copyNumber = 1, ?int $maxCopies = null)
	{
		$this->factory = $factory;
		$this->minCopies = $this->copyNumber = $copyNumber;
		$this->maxCopies = $maxCopies;

		$this->monitor(Form::class, function (Form $form): void {
			$this->form = $form;

			if ($this->getCurrentGroup() === null) {
				$this->setCurrentGroup($form->getCurrentGroup());
			}

			if ($form instanceof \Nette\Application\UI\Form) {
				if ($form->isAnchored()) {
					$this->whenAttached();
				} else {
					$form->onAnchor[] = function (): void {
						$this->whenAttached();
					};
				}
			}

			$form->onRender[] = function (): void {
				$this->whenAttached();
			};
		});
		$this->monitor(self::class, [$this, 'whenAttached']);
	}

	public static function register(string $name = 'addMultiplier'): void
	{
		Container::extensionMethod($name, function (Container $form, $name, $factory, $copyNumber = 1, $maxCopies = null) {
			$multiplier = new Multiplier($factory, $copyNumber, $maxCopies);
			$multiplier->setCurrentGroup($form->getCurrentGroup());

			return $form[$name] = $multiplier;
		});
	}

	public function getForm(bool $throw = true): ?Form
	{
		if ($this->form) {
			return $this->form;
		}

		return parent::getForm($throw);
	}

	public function setResetKeys(bool $reset = true): self
	{
		$this->resetKeys = $reset;

		return $this;
	}

	public function setMinCopies(int $minCopies): self
	{
		$this->minCopies = $minCopies;

		return $this;
	}

	public function setFactory(callable $factory): self
	{
		$this->factory = $factory;

		return $this;
	}

	public function getMaxCopies(): ?int
	{
		return $this->maxCopies;
	}

	public function getMinCopies(): ?int
	{
		return $this->minCopies;
	}

	public function getCopyNumber(): int
	{
		return $this->copyNumber;
	}

	public function addRemoveButton(Html|string|null $caption = null): RemoveButton
	{
		return $this->removeButton = new RemoveButton($caption);
	}

	public function addCreateButton(?string $caption = null, int $copyCount = 1): CreateButton
	{
		return $this->createButtons[$copyCount] = new CreateButton($caption, $copyCount);
	}

	/**
	 * @param Control[]|null $controls
	 */
	public function validate(?array $controls = null): void
	{
		/** @var Control[] $components */
		$components = $controls ?? $this->getComponents();

		foreach ($components as $index => $control) {
			foreach ($this->noValidate as $item) {
				if ($control === $item) {
					unset($components[$index]);
				}
			}
		}

		parent::validate($components);
	}

	/**
	 * @param mixed[]|object $defaults
	 */
	public function addCopy(?int $number = null, array|object $defaults = []): Container
	{
		if (!is_numeric($number)) {
			$number = $this->createNumber();
		} else {
			/** @var Container|null $component */
			$component = $this->getComponent((string) $number, false);
			if ($component !== null) {
				return $component;
			}
		}

		$container = $this->createContainer();
		if ($defaults) {
			$container->setDefaults($defaults, $this->erase);
		}

		$this->attachContainer($container, (string) $number);
		$this->attachRemoveButton($container);

		$this->totalCopies++;

		return $container;
	}

	public function createCopies(bool $forceValues = false): void
	{
		if ($this->created === true) {
			return;
		}

		$this->created = true;

		if (!isset($this->resolver)) {
			$this->resolver = new ComponentResolver($this->values, $this->maxCopies, $this->minCopies);
		}

		$this->attachCreateButtons();
		$this->createComponents($forceValues);
		$this->detachCreateButtons();

		if ($this->maxCopies === null || $this->totalCopies < $this->maxCopies) {
			$this->attachCreateButtons();
		}

		if (
			$this->form !== null &&
			$this->resolver->isRemoveAction() &&
			$this->totalCopies >= $this->minCopies &&
			!$this->resolver->reachedMinLimit()
		) {
			$this->form->setSubmittedBy($this->removeButton->create($this));

			$this->resetFormEvents();

			$this->onRemoveEvent();
		}

		// onCreateEvent
		$this->onCreateEvent();
	}

	/**
	 * @return Submitter[]
	 */
	public function getCreateButtons(): array
	{
		if ($this->maxCopies !== null && $this->totalCopies >= $this->maxCopies) {
			return [];
		}

		$buttons = [];
		foreach ($this->createButtons as $button) {
			$buttons[$button->getCopyCount()] = $this->getComponent($button->getComponentName());
		}

		return $buttons;
	}

	/**
	 * @internal
	 */
	public function resetFormEvents(): void
	{
		if ($this->form === null) {
			return;
		}

		$this->form->onSuccess = $this->form->onError = $this->form->onSubmit = [];
	}

	/**
	 * @param  string|object|bool|null  $returnType  'array' for array
	 * @param  Control[]|null  $controls
	 * @return object|mixed[]
	 */
	public function getValues(string|object|bool|null $returnType = null, ?array $controls = null): object|array
	{
		if (!$this->resetKeys) {
			return parent::getValues($returnType, $controls);
		}

		/** @var mixed[] $values */
		$values = parent::getValues(self::Array, $controls);
		$values = array_values($values);

		if ($returnType === true) {
			trigger_error(static::class . '::' . __FUNCTION__ . "(true) is deprecated, use getValues('array').", E_USER_DEPRECATED);
			$returnType = self::Array;
		}

		return $returnType === self::Array ? $values : ArrayHash::from($values);
	}

	/**
	 * @return Iterator|Control[]
	 */
	public function getControls(): Iterator
	{
		$this->createCopies();

		return parent::getControls();
	}

	/**
	 * @return array<int|string,Container>
	 */
	public function getContainers(): iterable
	{
		$this->createCopies();

		/** @var array<int|string,Container> $containers */
		$containers = array_filter($this->getComponents(), fn ($component) => $component instanceof Container);

		return $containers;
	}

	/**
	 * @param mixed[]|object $values
	 * @internal
	 */
	public function setValues(array|object $values, bool $erase = false, bool $onlyDisabled = false): static
	{
		$values = $values instanceof Traversable ? iterator_to_array($values) : (array) $values;

		$this->values = $values;
		$this->erase = $erase;

		if ($this->created) {
			foreach ($this->getContainers() as $container) {
				$this->removeComponent($container);
				$this->totalCopies--;
			}

			$this->created = false;
			$this->detachCreateButtons();
			$this->resolver = new ComponentResolver($this->values, $this->maxCopies, $this->minCopies);
			$this->createCopies();
		}

		return $this;
	}

	protected function whenAttached(): void
	{
		if ($this->attachedCalled) {
			return;
		}

		$this->loadHttpData();
		$this->createCopies();

		$this->attachedCalled = true;
	}

	protected function onCreateEvent(): void
	{
		foreach ($this->onCreate as $callback) {
			foreach ($this->getContainers() as $container) {
				$callback($container);
			}
		}
	}

	protected function onRemoveEvent(): void
	{
		foreach ($this->onRemove as $callback) {
			$callback($this);
		}
	}

	protected function isValidMaxCopies(): bool
	{
		return $this->maxCopies === null || $this->totalCopies < $this->maxCopies;
	}

	protected function isFormSubmitted(): bool
	{
		return $this->getForm() !== null && $this->getForm()->isAnchored() && $this->getForm()->isSubmitted();
	}

	protected function loadHttpData(): void
	{
		if ($this->isFormSubmitted()) {
			/** @var array<mixed> The other types from the union can only be returned when the htmlName argument is passed. https://github.com/nette/forms/pull/333 */
			$httpData = $this->getForm()->getHttpData();
			$httpData = Arrays::get($httpData, $this->getHtmlName(), []);
			$this->resolver = new ComponentResolver($httpData ?? [], $this->maxCopies, $this->minCopies);
		}
	}

	protected function createNumber(): int
	{
		$count = count(array_filter($this->getComponents(), fn ($component) => $component instanceof Form));
		while ($this->getComponent((string) $count, false)) {
			$count++;
		}

		return $count;
	}

	protected function fillContainer(Container $container): void
	{
		call_user_func($this->factory, $container, $this->getForm());
	}

	/**
	 * @return string[]
	 * @throws InvalidStateException when not attached.
	 */
	protected function getHtmlName(): array
	{
		return explode('-', $this->lookupPath(Form::class));
	}

	protected function createContainer(): Container
	{
		$control = new Container();
		$control->currentGroup = $this->currentGroup;
		$this->fillContainer($control);

		return $control;
	}

	/**
	 * Return name of first submit button
	 */
	protected function getFirstSubmit(): ?string
	{
		$submits = array_filter($this->getComponents(), fn ($component) => $component instanceof SubmitButton);
		if ($submits) {
			return reset($submits)->getName();
		}

		return null;
	}

	protected function attachContainer(Container $container, ?string $name): void
	{
		$this->addComponent($container, $name, $this->getFirstSubmit());
	}

	protected function removeComponentProperly(IComponent $component): void
	{
		if ($this->getCurrentGroup() !== null && $component instanceof Control) {
			$this->getCurrentGroup()->remove($component);
		}

		$this->removeComponent($component);
	}

	private function createComponents(bool $forceValues = false): void
	{
		$containers = [];
		$containerDefaults = $this->createContainer()->getValues(self::Array);

		// Components from httpData
		if ($this->isFormSubmitted() && !$forceValues) {
			foreach ($this->resolver->getValues() as $number => $_) {
				$containers[] = $container = $this->addCopy($number);

				/** @var BaseControl $control */
				foreach ($container->getComponents(false, Control::class) as $control) {
					$control->loadHttpData();
				}
			}
		} else { // Components from default values
			foreach ($this->resolver->getValues() as $number => $values) {
				$containers[] = $container = $this->addCopy($number, $values);
				$container->setValues($values);
			}
		}

		// Default number of copies
		if (!$this->values) {
			$copyNumber = $this->copyNumber;
			while ($copyNumber > 0 && $this->isValidMaxCopies() && $this->totalCopies < $this->minCopies) {
				$containers[] = $container = $this->addCopy();
				$container->setValues($containerDefaults);
				$copyNumber--;
			}
		}

		// Dynamic
		foreach ($this->onCreateComponents as $callback) {
			$callback($this);
		}

		// New containers, if create button hitted
		if ($this->form !== null && $this->resolver->isCreateAction() && $this->form->isValid()) {
			$count = $this->resolver->getCreateNum();
			while ($count > 0 && $this->isValidMaxCopies()) {
				$this->noValidate[] = $containers[] = $container = $this->addCopy();
				$container->setValues($containerDefaults);
				$count--;
			}
		}

		if ($this->removeButton && $this->totalCopies <= $this->minCopies) {
			foreach ($containers as $container) {
				$this->detachRemoveButton($container);
			}
		}
	}

	private function detachCreateButtons(): void
	{
		foreach ($this->createButtons as $button) {
			$this->removeComponentProperly($this->getComponent($button->getComponentName()));
		}
	}

	private function attachCreateButtons(): void
	{
		foreach ($this->createButtons as $button) {
			$this->addComponent($button->create($this), $button->getComponentName());
		}
	}

	private function detachRemoveButton(Container $container): void
	{
		$button = $container->getComponent(self::SUBMIT_REMOVE_NAME);
		if ($this->getCurrentGroup() !== null) {
			$this->getCurrentGroup()->remove($button);
		}

		$container->removeComponent($button);
	}

	private function attachRemoveButton(Container $container): void
	{
		if (!$this->removeButton) {
			return;
		}

		$container->addComponent($this->removeButton->create($this), self::SUBMIT_REMOVE_NAME);
	}

}
