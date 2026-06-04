![](https://heatbadger.now.sh/github/readme/contributte/forms-multiplier/)

<p align=center>
	<a href="https://github.com/contributte/forms-multiplier/actions"><img src="https://badgen.net/github/checks/contributte/forms-multiplier"></a>
	<a href="https://coveralls.io/r/contributte/forms-multiplier"><img src="https://badgen.net/coveralls/c/github/contributte/forms-multiplier"></a>
	<a href="https://packagist.org/packages/contributte/forms-multiplier"><img src="https://badgen.net/packagist/dm/contributte/forms-multiplier"></a>
	<a href="https://packagist.org/packages/contributte/forms-multiplier"><img src="https://badgen.net/packagist/v/contributte/forms-multiplier"></a>
</p>
<p align=center>
	<a href="https://packagist.org/packages/contributte/forms-multiplier"><img src="https://badgen.net/packagist/php/contributte/forms-multiplier"></a>
	<a href="https://github.com/contributte/forms-multiplier"><img src="https://badgen.net/github/license/contributte/forms-multiplier"></a>
	<a href="https://bit.ly/ctteg"><img src="https://badgen.net/badge/support/gitter/cyan"></a>
	<a href="https://bit.ly/cttfo"><img src="https://badgen.net/badge/support/forum/yellow"></a>
	<a href="https://contributte.org/partners.html"><img src="https://badgen.net/badge/sponsor/donations/F96854"></a>
</p>

<p align=center>
	Website 🚀 <a href="https://contributte.org">contributte.org</a> | Contact 👨🏻‍💻 <a href="https://f3l1x.io">f3l1x.io</a> | Twitter 🐦 <a href="https://twitter.com/contributte">@contributte</a>
</p>

Multiplier containers for Nette Forms, including create/remove buttons and Latte macros.

## Versions

| State  | Version | Branch   | PHP     |
|--------|---------|----------|---------|
| dev    | `^4.1`  | `master` | `>=8.1` |
| stable | `^4.0`  | `v4`     | `>=8.0` |

## Installation

To install latest version of `contributte/forms-multiplier` use [Composer](https://getcomposer.org).

```bash
composer require contributte/forms-multiplier
```

## Usage

### Register Extension

```neon
extensions:
	- Contributte\FormMultiplier\DI\MultiplierExtension
```

### Basic Usage

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

### Adding Multiple Containers

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

## Development

See [how to contribute](https://contributte.org) to this package. This package is currently maintained by these authors.

<a href="https://github.com/MartkCz">
	<img width="80" height="80" src="https://avatars2.githubusercontent.com/u/10145362?v=3&s=80">
</a>

<a href="https://github.com/f3l1x">
	<img width="80" height="80" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=80">
</a>

-----

Consider to [support](https://contributte.org/partners.html) **contributte** development team.
Also thank you for using this package.
