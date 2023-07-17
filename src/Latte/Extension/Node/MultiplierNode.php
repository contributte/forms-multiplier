<?php declare(strict_types = 1);

namespace Contributte\FormMultiplier\Latte\Extension\Node;

use Generator;
use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\Php\Scalar\StringNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

final class MultiplierNode extends StatementNode
{

	public ExpressionNode $name;

	public AreaNode $content;

	/**
	 * @return Generator<int, ?mixed[], array{AreaNode, ?Tag}, self>
	 */
	public static function create(Tag $tag): Generator
	{
		$tag->outputMode = $tag::OutputRemoveIndentation;
		$tag->expectArguments();

		$node = new static();
		$node->name = $tag->parser->parseUnquotedStringOrExpression();

		[$node->content] = yield;

		return $node;
	}

	public function print(PrintContext $context): string
	{
		return $context->format(
			'$multiplier = '
			. ($this->name instanceof StringNode
				? 'end($this->global->formsStack)[%node];'
				: 'is_object($ʟ_tmp = %node) ? $ʟ_tmp : end($this->global->formsStack)[$ʟ_tmp];')
			. 'foreach ($multiplier->getContainers() as $formContainer) {'
			. "\n"
			. '$this->global->formsStack[] = $formContainer;'
			. ' %line %node ' // content
			. 'array_pop($this->global->formsStack);'
			. "\n"
			. '}'
			. '$formContainer = end($this->global->formsStack);'
			. "\n\n",
			$this->name,
			$this->position,
			$this->content
		);
	}

	public function &getIterator(): \Generator
	{
		yield $this->name;
		yield $this->content;
	}

}
