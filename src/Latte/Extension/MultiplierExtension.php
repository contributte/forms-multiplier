<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier\Latte\Extension;

use Contributte\FormMultiplier\Latte\Extension\Node\MultiplierAddNode;
use Contributte\FormMultiplier\Latte\Extension\Node\MultiplierNode;
use Contributte\FormMultiplier\Latte\Extension\Node\MultiplierRemoveNode;
use Latte\Extension;

final class MultiplierExtension extends Extension
{

	/**
	 * @return array<string, callable>
	 */
	public function getTags(): array
	{
		return [
			'multiplier' => [MultiplierNode::class, 'create'],
			'n:multiplier' => [MultiplierNode::class, 'create'],
			'multiplier:remove' => [MultiplierRemoveNode::class, 'create'],
			'multiplier:add' => [MultiplierAddNode::class, 'create'],
		];
	}

}
