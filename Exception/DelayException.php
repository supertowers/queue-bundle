<?php

namespace SuperTowers\QueueBundle\Exception;

use \Exception;

/**
 * Exception thrown to slow down the queues speed.
 *
 * @category QueueBundle
 * @package  Exception
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class DelayException extends Exception
{
}
