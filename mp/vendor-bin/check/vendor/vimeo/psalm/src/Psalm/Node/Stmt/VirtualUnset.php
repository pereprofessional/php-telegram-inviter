<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Unset_;
use Psalm\Node\VirtualNode;

final class VirtualUnset extends Unset_ implements VirtualNode
{

}
