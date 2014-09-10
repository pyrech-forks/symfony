<?php

namespace Symfony\Component\Kernel\Tests\Fixtures\ExtensionPresentBundle\Command;

use Symfony\Component\Console\Command\Command;

/**
 * This command has a required parameter on the constructor and will be ignored by the default Bundle implementation.
 *
 * @see Symfony\Component\Kernel\Bundle\Bundle::registerCommands
 */
class BarCommand extends Command
{
    public function __construct($example, $name = 'bar')
    {

    }
}
