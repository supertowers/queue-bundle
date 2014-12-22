<?php

namespace SuperTowers\QueueBundle;

use \Symfony\Component\DependencyInjection\ContainerAwareInterface;

use \Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple implementation of ContainerAwareInterface with an assertion.
 *
 * @category QueueBundle
 * @package  Core
 * @author   Pablo Lopez Torres <pablolopeztorres@gmail.com>
 * @license  https://github.com/supertowers/queues-bundle/LICENSE.md CC-3.0
 * @link     https://github.com/supertowers/queues-bundle
 */
class ContainerAware implements ContainerAwareInterface
{
    protected $container;

    public function setContainer(ContainerInterface $container = null)
    {
            $this->container = $container;
    }

    /**
     * Asserts the container is set.
     *
     * Triggers a E_USER_ERROR if not.
     *
     * @author Pablo Lopez Torres <pablolopeztorres@gmail.com>
     * @return void
     */
    protected function assertContainerIsSet()
    {
        if ($this->container === null) {
            trigger_error('Container is not set in ' . get_class($this), E_USER_ERROR);
        }
    }
}
