<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\WebProfilerBundle\Twig;

use Symfony\Component\HttpKernel\DataCollector\Util\ValueExporter;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Twig extension for the profiler
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since version 2.8, to be removed in 3.0.
 */
class WebProfilerExtension extends \Twig_Extension
{
    /**
     * @var ValueExporter
     */
    private $valueExporter;

    /**
     * @var ClonerInterface
     */
    private $cloner;

    /**
     * Constructs a new data extractor.
     */
    public function __construct(ClonerInterface $cloner = null)
    {
        $this->cloner = $cloner ?: new VarCloner();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('profiler_dump', array($this, 'dumpValue')),
        );
    }

    public function dumpValue($value)
    {
        @trigger_error('The '.__CLASS__.' class and profiler_dump twig function are deprecated since version 2.8 and will be removed in 3.0. Use the VarDumper component and its dump function method instead.', E_USER_DEPRECATED);

        $dump = fopen('php://memory', 'r+b');
        $dumper = new HtmlDumper($dump);

        $dumper->dump($this->cloner->cloneVar($value));
        rewind($dump);

        return stream_get_contents($dump);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'profiler';
    }
}
