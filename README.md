# Multiplier, replication for Nette\Forms

[![Build Status](https://travis-ci.org/WebChemistry/forms-multiplier.svg?branch=master)](https://travis-ci.org/WebChemistry/forms-multiplier)

## Installation
```
composer require webchemistry/forms-multiplier
```

```yaml
extensions:
    - WebChemistry\Forms\Controls\DI\MultiplierExtension
```

## Usage

```php
$form = new Nette\Forms\Form;
$copies = 1;
$maxCopies = 10;

$multiplier = $form->addMultiplier('multiplier', function (Nette\Forms\Container $container, Nette\Forms\Form $form) {
    $container->addText('text', 'Text')
                ->setDefaultValue('My value');
}, $copies, $maxCopies);

$multiplier->addCreateButton('Add');
$multiplier->addRemoveButton('Remove');
```

## Adding multiple containers

```php
$multiplier->addCreateButton('Add'); // add one container
$multiplier->addCreateButton('Add 5', 5); // add five containers
```

## Macros

```html
{form multiplier}
	<div n:multiplier="multiplier">
		<input n:name="text">
		{btnRemove 'class' => 'myClass'}
	</div>
	{btnCreate multiplier class => myClass}
	{btnCreate $form[multiplier]:5}
{/form}
```
