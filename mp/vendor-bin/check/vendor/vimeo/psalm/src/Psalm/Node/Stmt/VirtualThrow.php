<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Throw_;
use Psalm\Node\VirtualNode;

final class VirtualThrow extends Throw_ implements VirtualNode
{

}
