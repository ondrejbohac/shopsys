<?php

namespace Shopsys\CodingStandards\Tests;

use Symplify\CodingStandard\TokenRunner\Analyzer\SnifferAnalyzer\Naming;

final class ObjectIsCreatedByFactorySniff
{
    /**
     * @var \Symplify\CodingStandard\TokenRunner\Analyzer\SnifferAnalyzer\Naming
     */
    private $naming;

    /**
     * @param \Symplify\CodingStandard\TokenRunner\Analyzer\SnifferAnalyzer\Naming $naming
     */
    public function __construct(Naming $naming)
    {
    }
}
