# Contributte\Form-multiplier

## Content

- [Usage - how use it](#usage)
	- [Register extension](#register-extension)
	- [Basic usage](#basic-usage)
	- [Adding multiple containers](#adding-multiple-containers)
	- [Macros](#macros)
	- [AJAX (Naja)](#ajax-naja)

## Usage

### Register extension

```neon
extensions:
	- Contributte\FormMultiplier\DI\MultiplierExtension
```

### Basic usage

```php
$form = new Nette\Forms\Form;
$copies = 1;
$maxCopies = 10;

$multiplier = $form->addMultiplier('multiplier', function (Nette\Forms\Container $container, Nette\Forms\Form $form) {
	$container->addText('text', 'Text')
		->setDefaultValue('My value');
}, $copies, $maxCopies);

$multiplier->addCreateButton('Add')
	->addClass('btn btn-primary');
$multiplier->addRemoveButton('Remove')
	->addClass('btn btn-danger');
```

### Adding multiple containers

```php
$multiplier->addCreateButton('Add'); // add one container
$multiplier->addCreateButton('Add 5', 5); // add five containers
```

### Macros

```latte
{form multiplier}
    <div n:multiplier="multiplier">
        <input n:name="text">
        {multiplier:remove class: myClass}
    </div>

    {multiplier:add multiplier class: myClass}
    {multiplier:add multiplier:5}
{/form}
```

### AJAX (Naja)

The create and remove buttons are ordinary submit buttons, so with [Naja](https://naja.js.org) the form is sent via AJAX and the presenter only has to redraw the snippet with the form.
When a create or remove button is clicked, the multiplier clears the form's `onSuccess`, `onError` and `onSubmit` handlers, so hook the redraw into the buttons with `addOnCreateCallback()`.

```php
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;

final class ProductPresenter extends Presenter
{

	protected function createComponentProductForm(): Form
	{
		$form = new Form();

		$multiplier = $form->addMultiplier('items', function (Container $container): void {
			$container->addText('name', 'Name')
				->setRequired();
		}, 1, 10);

		$redraw = function (): void {
			$this->redrawControl('productForm');
		};

		$multiplier->addCreateButton('Add')
			->addOnCreateCallback(function (SubmitButton $button) use ($redraw): void {
				$button->onClick[] = $redraw;
			});

		$multiplier->addRemoveButton('Remove')
			->addOnCreateCallback(function (SubmitButton $button) use ($redraw): void {
				$button->onClick[] = $redraw;
			});

		$form->addSubmit('send', 'Save');

		$form->onSuccess[] = function (Form $form, array $values): void {
			// save $values['items'] ...

			if ($this->isAjax()) {
				$this->redrawControl('productForm');
			} else {
				$this->redirect('this');
			}
		};

		// also covers the "Add" button when the current copies are invalid
		$form->onError[] = $redraw;

		return $form;
	}

}
```

Put the whole form into the snippet and add the `ajax` class to it:

```latte
{snippet productForm}
	{form productForm, class: ajax}
		<div n:multiplier="items">
			<input n:name="name">
			{multiplier:remove}
		</div>

		{multiplier:add items}
		<input n:name="send">
	{/form}
{/snippet}
```

Initialize Naja. It submits `form.ajax` forms and sends the name of the clicked button, which the multiplier needs:

```js
import naja from 'naja';

document.addEventListener('DOMContentLoaded', () => naja.initialize());
```
