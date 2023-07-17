# Contributte\Form-multiplier

## Content

- [Usage - how use it](#usage)
	- [Register extension](#register-extension)
	- [Basic usage](#basic-usage)
	- [Adding multiple containers](#adding-multiple-containers)
	- [Macros](#macros)

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

