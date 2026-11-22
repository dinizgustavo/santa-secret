<?php

namespace App\Draw;

use RuntimeException;

/**
 * No assignment satisfies the constraints (for example, too many exclusions).
 */
final class ImpossibleDraw extends RuntimeException {}
