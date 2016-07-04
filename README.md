# Formulářový multiplier

[![Build Status](https://travis-ci.org/WebChemistry/forms-multiplier.svg?branch=master)](https://travis-ci.org/WebChemistry/forms-multiplier)

## Instalace
```
composer require webchemistry/forms-multiplier
```

```yaml
extensions:
    - WebChemistry\Forms\Controls\DI\MultiplierExtension
```

## Použití

```php
$form = new Nette\Forms\Form;
$copies = 1;
$maxCopies = 10;

$multiplier = $form->addMultiplier('multiplier', function (Nette\Forms\Container $container) {
    $container->addText('text', 'Text')
                ->setDefaultValue('Moje hodnota');

    $container->addEditor('editor', 'Editor');
}, $copies, $maxCopies);

$multiplier->addCreateSubmit('Nový');
$multiplier->addRemoveSubmit('Vymazat');
```
