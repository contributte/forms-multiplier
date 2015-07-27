# Multiplier pro WebChemistry\Forms\Form

## Instalace

**Composer**

```
composer require forms-multiplier
```

## Použití

```php
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
