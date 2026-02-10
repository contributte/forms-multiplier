<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Contributte\FormMultiplier\Multiplier;
use Nette\Application\UI\Form;
use Nette\Forms\Container;

class MultiplierBuilder
{

	/** @var array<string, string> */
	public array $fields = [
		'bar' => '',
	];

	/** @var callable[] */
	protected array $formModifiers = [];

	/** @var callable[] */
	protected array $beforeFormModifiers = [];

	/** @var callable[] */
	protected array $multiplierModifiers = [];

	/** @var callable[] */
	protected array $containerModifiers = [];

	/** @var array<int, mixed> */
	protected array $multiplierArgs = [];

	public function __construct(int $copyNumber = 1, ?int $maxCopies = null)
	{
		$this->multiplierArgs = [$copyNumber, $maxCopies];
	}

	public static function create(int $copyNumber = 1, ?int $maxCopies = null): self
	{
		return new self($copyNumber, $maxCopies);
	}

	public function factory(Container $container): void
	{
		foreach ($this->fields as $field => $value) {
			$container->addText($field)
				->setDefaultValue($value);
		}

		foreach ($this->containerModifiers as $modifier) {
			$modifier($container);
		}
	}

	/**
	 * @param array<string, string> $fields
	 */
	public function fields(array $fields): self
	{
		$this->fields = $fields;

		return $this;
	}

	public function addRemoveButton(?callable $onCreate = null): self
	{
		$this->multiplierModifiers[] = function (Multiplier $multiplier) use ($onCreate): void {
			$btn = $multiplier->addRemoveButton('add');
			if ($onCreate !== null) {
				$btn->addOnCreateCallback($onCreate);
			}
		};

		return $this;
	}

	public function addCreateButton(int $copyCount = 1, ?callable $onCreate = null): self
	{
		$this->multiplierModifiers[] = function (Multiplier $multiplier) use ($copyCount, $onCreate): void {
			$btn = $multiplier->addCreateButton('add', $copyCount);
			if ($onCreate !== null) {
				$btn->addOnCreateCallback($onCreate);
			}
		};

		return $this;
	}

	public function setMinCopies(int $minCopies): self
	{
		$this->multiplierModifiers[] = function (Multiplier $multiplier) use ($minCopies): void {
			$multiplier->setMinCopies($minCopies);
		};

		return $this;
	}

	public function createForm(): Form
	{
		$form = new Form();

		foreach ($this->beforeFormModifiers as $modifier) {
			$modifier($form);
		}

		$form['m'] = $multiplier = new Multiplier([$this, 'factory'], ...$this->multiplierArgs);

		foreach ($this->multiplierModifiers as $modifier) {
			$modifier($multiplier);
		}

		foreach ($this->formModifiers as $modifier) {
			$modifier($form);
		}

		$form->addSubmit('send');

		$form->onSuccess[] = function (Form $form): void {
		};

		return $form;
	}

	public function formModifier(callable $callback): self
	{
		$this->formModifiers[] = $callback;

		return $this;
	}

	public function beforeFormModifier(callable $callback): self
	{
		$this->beforeFormModifiers[] = $callback;

		return $this;
	}

	public function multiplierModifier(callable $callback): self
	{
		$this->multiplierModifiers[] = $callback;

		return $this;
	}

	public function containerModifier(callable $callback): self
	{
		$this->containerModifiers[] = $callback;

		return $this;
	}

	/**
	 * @param array<string, mixed> $defaults
	 */
	public function setFormDefaults(array $defaults): self
	{
		$this->formModifiers[] = function (Form $form) use ($defaults): void {
			$form->setDefaults($defaults);
		};

		return $this;
	}

}
