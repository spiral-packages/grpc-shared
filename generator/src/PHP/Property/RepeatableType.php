<?php

declare(strict_types=1);

namespace Generator\PHP\Property;

final readonly class RepeatableType extends Type
{
    public function __construct(public Type $iterableType)
    {
        parent::__construct('array', $this->iterableType->type . '[]');
    }
}
