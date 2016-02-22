# Formulářový multiplier

[![Build Status](https://travis-ci.org/WebChemistry/Forms-Multiplier.svg?branch=master)](https://travis-ci.org/WebChemistry/Forms-Multiplier)

## Instalace
```
composer require webchemistry/forms-multiplier
```

```php
WebChemistry\Forms\Controls\Multiplier::register();
```

## Použití

```php
$form = new Nette\Forms\Form;
$copies = 1;
$maxCopies = 10;

$multiplier = $form->addMultiplier('multiplier', function (WebChemistry\Forms\Container $container) {
    $container->addText('text', 'Text')
                ->setDefaultValue('Moje hodnota');

    $container->addEditor('editor', 'Editor');
}, $copies, $maxCopies);

$multiplier->addCreateSubmit('Nový');
$multiplier->addRemoveSubmit('Vymazat');
```
