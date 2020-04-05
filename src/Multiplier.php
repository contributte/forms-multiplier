<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier;

use Contributte\FormMultiplier\Buttons\CreateButton;
use Contributte\FormMultiplier\Buttons\RemoveButton;
use Iterator;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\Utils\ArrayHash;
use Nette\Utils\Arrays;

class Multiplier extends Container
{

	public const SUBMIT_CREATE_NAME = 'multiplier_creator';

	public const SUBMIT_REMOVE_NAME = 'multiplier_remover';

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

	/** @var mixed[] */
	protected $values = [];

	/** @var bool */
	protected $erase = false;

	/** @var CreateButton[] */
	protected $createButtons = [];

	/** @var RemoveButton|null */
	protected $removeButton;

	/** @var mixed[] */
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

	/** @var callable[] */
	public $onRemove = [];

	/** @var callable[] */
	public $onCreateComponents = [];

	/** @var Container[] */
	protected $noValidate = [];

	public function __construct(callable $factory, int $copyNumber = 1, ?int $maxCopies = null)
	{
		$this->factory = $factory;
		$this->minCopies = $this->copyNumber = $copyNumber;
		$this->maxCopies = $maxCopies;

		$this->monitor(Form::class);
		$this->monitor(self::class);
	}

	public function getForm(bool $throw = true): ?Form
	{
		if ($this->form) {
			return $this->form;
		}

		return parent::getForm($throw);
	}

	protected function attached(IComponent $obj): void
	{
		parent::attached($obj);

		if ($obj instanceof self) {
			$this->whenAttached();
		} elseif ($obj instanceof Form) {
			$this->form = $obj;

			if ($this->getCurrentGroup() === null) {
				$this->setCurrentGroup($obj->getCurrentGroup());
			}
			if ($obj instanceof \Nette\Application\UI\Form) {
				if ($obj->isAnchored()) {
					$this->whenAttached();
				} else {
					$obj->onAnchor[] = function (): void {
						$this->whenAttached();
					};
				}
			}
			$obj->onRender[] = function (): void {
				$this->whenAttached();
			};
		}
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

	public function addRemoveButton(?string $caption = null): RemoveButton
	{
		return $this->removeButton = new RemoveButton($caption);
	}

	public function addCreateButton(?string $caption = null, int $copyCount = 1): CreateButton
	{
		return $this->createButtons[$copyCount] = new CreateButton($caption, $copyCount);
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

	/**
	 * @param IControl[]|null $controls
	 */
	public function validate(?array $controls = null): void
	{
		$controls = $controls ?? iterator_to_array($this->getComponents());

		foreach ($controls as $index => $control) {
			foreach ($this->noValidate as $item) {
				if ($control === $item) {
					unset($controls[$index]);
				}
			}
		}

		parent::validate($controls);
	}

	/**
	 * @param mixed[]|object $defaults
	 */
	public function addCopy(?int $number = null, $defaults = []): ?Container
	{
		if (!is_numeric($number)) {
			$number = $this->createNumber();
		} elseif ($component = parent::getComponent((string) $number, false)) {
			return $component;
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

	private function createComponents(ComponentResolver $resolver): void
	{
		$containers = [];

		// Components from httpData
		if ($this->isFormSubmitted()) {
			foreach ($resolver->getValues() as $number => $_) {
				$containers[] = $container = $this->addCopy($number);

				/** @var BaseControl $control */
				foreach ($container->getControls() as $control) {
					$control->loadHttpData();
				}
			}

		} else { // Components from default values
			foreach ($resolver->getDefaults() as $number => $values) {
				$containers[] = $this->addCopy($number, $values);
			}

		}

		// Default number of copies
		if (!$this->isFormSubmitted() && !$this->values) {
			$copyNumber = $this->copyNumber;
			while ($copyNumber > 0 && $this->isValidMaxCopies()) {
				$containers[] = $container = $this->addCopy();
				$copyNumber--;
			}
		}

		// Dynamic
		foreach ($this->onCreateComponents as $callback) {
			$callback($this);
		}

		// New containers, if create button hitted
		if ($resolver->isCreateAction() && $this->form->isValid()) {
			$count = $resolver->getCreateNum();
			while ($count > 0 && $this->isValidMaxCopies()) {
				$this->noValidate[] = $containers[] = $container = $this->addCopy();
				$container->setValues($this->createContainer()->getValues());
				$count--;
			}
		}

		if ($this->removeButton && $this->totalCopies <= $this->minCopies) {
			foreach ($containers as $container) {
				$this->detachRemoveButton($container);
			}
		}
	}

	public function createCopies(): void
	{
		if ($this->created === true) {
			return;
		}
		$this->created = true;

		$resolver = new ComponentResolver($this->httpData, $this->values, $this->maxCopies, $this->minCopies);

		$this->attachCreateButtons();
		$this->createComponents($resolver);
		$this->detachCreateButtons();

		if ($this->maxCopies === null || $this->totalCopies < $this->maxCopies) {
			$this->attachCreateButtons();
		}

		if ($resolver->isRemoveAction() && $this->totalCopies >= $this->minCopies && !$resolver->reachedMinLimit()) {
			$this->resetFormEvents();

			$this->onRemoveEvent();
		}

		// onCreateEvent
		$this->onCreateEvent();
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
		if ($this->getCurrentGroup()) {
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

	protected function isFormSubmitted(): bool
	{
		return $this->getForm()->isAnchored() && $this->getForm()->isSubmitted();
	}

	protected function loadHttpData(): void
	{
		if ($this->isFormSubmitted()) {
			$this->httpData = Arrays::get($this->form->getHttpData(), $this->getHtmlName(), []);
		}
	}


	protected function createNumber(): int
	{
		$count = iterator_count($this->getComponents(false, Form::class));
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
	 * @return Submitter[]
	 */
	public function getCreateButtons(): array
	{
		$buttons = [];
		foreach ($this->createButtons as $button) {
			$buttons[$button->getCopyCount()] = $this->getComponent($button->getComponentName());
		}

		return $buttons;
	}

	/**
	 * Return name of first submit button
	 */
	protected function getFirstSubmit(): ?string
	{
		$submits = iterator_to_array($this->getComponents(false, SubmitButton::class));
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
		if ($this->getCurrentGroup()) {
			$this->getCurrentGroup()->remove($component);
		}
		$this->removeComponent($component);
	}

	/**
	 * @internal
	 */
	public function resetFormEvents(): void
	{
		$this->form->onSuccess = $this->form->onError = $this->form->onSubmit = [];
	}

	/**
	 * @param string|null $returnType
	 * @return object|mixed[]
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getValues($returnType = null)
	{
		if (!$this->resetKeys) {
			return parent::getValues($returnType);
		}

		$values = array_values(parent::getValues('array'));

		return $returnType === true || $returnType === 'array' ? $values : ArrayHash::from($values);
	}

	/**
	 * @return Iterator|IControl[]
	 */
	public function getControls(): Iterator
	{
		$this->createCopies();

		return parent::getControls();
	}

	/**
	 * @return Container[]|Iterator
	 */
	public function getContainers(): Iterator
	{
		$this->createCopies();

		return $this->getComponents(false, Container::class);
	}

	/**
	 * @param mixed[]|object $values
	 */
	public function setValues($values, bool $erase = false): self
	{
		$this->values = $values;
		$this->erase = $erase;

		if ($this->created) {
			foreach ($this->getContainers() as $container) {
				$this->removeComponent($container);
			}

			$this->created = false;
			$this->detachCreateButtons();
			$this->createCopies();
		}

		return $this;
	}

	public static function register(string $name = 'addMultiplier'): void
	{
		Container::extensionMethod($name, function (Container $form, $name, $factory, $copyNumber = 1, $maxCopies = null) {
			$multiplier = new Multiplier($factory, $copyNumber, $maxCopies);
			$multiplier->setCurrentGroup($form->getCurrentGroup());

			return $form[$name] = $multiplier;
		});
	}

}
