<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class HasAtLeastCount extends Assertion
{
    /** @var positive-int */
    public $count;

    /** @param positive-int $count */
    public function __construct(int $count)
    {
        $this->count = $count;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new DoesNotHaveAtLeastCount($this->count);
    }

    public function __toString(): string
    {
        return 'has-at-least-' . $this->count;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveAtLeastCount && $this->count === $assertion->count;
    }
}
