<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Extension\DataCollector;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\DataCollector\Util\ValueExporter;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Default implementation of {@link FormDataExtractorInterface}.
 *
 * @since  2.4
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FormDataExtractor implements FormDataExtractorInterface
{
    /**
     * @var ClonerInterface
     */
    private $cloner;

    /**
     * Constructs a new data extractor.
     *
     * @param ClonerInterface $cloner
     */
    public function __construct($cloner = null)
    {
        if ($cloner instanceof ValueExporter) {
            @trigger_error('Passing a ValueExporter to the '.__CLASS__.' constructor is deprecated since version 2.8 and will be removed in 3.0. Give it an implementation of ClonerInterface instead.', E_USER_DEPRECATED);
            $this->cloner = new VarCloner();
        } else {
            $this->cloner = $cloner ?: new VarCloner();
        }

    }

    /**
     * {@inheritdoc}
     */
    public function extractConfiguration(FormInterface $form)
    {
        $data = array(
            'id' => $this->buildId($form),
            'name' => $form->getName(),
            'type' => $form->getConfig()->getType()->getName(),
            'type_class' => get_class($form->getConfig()->getType()->getInnerType()),
            'synchronized' => $this->exportValue($form->isSynchronized()),
            'passed_options' => array(),
            'resolved_options' => array(),
        );

        foreach ($form->getConfig()->getAttribute('data_collector/passed_options', array()) as $option => $value) {
            $data['passed_options'][$option] = $this->exportValue($value);
        }

        foreach ($form->getConfig()->getOptions() as $option => $value) {
            $data['resolved_options'][$option] = $this->exportValue($value);
        }

        ksort($data['passed_options']);
        ksort($data['resolved_options']);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function extractDefaultData(FormInterface $form)
    {
        $data = array(
            'default_data' => array(
                'norm' => $this->exportValue($form->getNormData()),
            ),
            'submitted_data' => array(),
        );

        if ($form->getData() !== $form->getNormData()) {
            $data['default_data']['model'] = $this->exportValue($form->getData());
        }

        if ($form->getViewData() !== $form->getNormData()) {
            $data['default_data']['view'] = $this->exportValue($form->getViewData());
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function extractSubmittedData(FormInterface $form)
    {
        $data = array(
            'submitted_data' => array(
                'norm' => $this->exportValue($form->getNormData()),
            ),
            'errors' => array(),
        );

        if ($form->getViewData() !== $form->getNormData()) {
            $data['submitted_data']['view'] = $this->exportValue($form->getViewData());
        }

        if ($form->getData() !== $form->getNormData()) {
            $data['submitted_data']['model'] = $this->exportValue($form->getData());
        }

        foreach ($form->getErrors() as $error) {
            $errorData = array(
                'message' => $error->getMessage(),
                'origin' => is_object($error->getOrigin())
                    ? spl_object_hash($error->getOrigin())
                    : null,
                'trace' => array(),
            );

            $cause = $error->getCause();

            while (null !== $cause) {
                if ($cause instanceof ConstraintViolationInterface) {
                    $errorData['trace'][] = array(
                        'class' => $this->exportValue(get_class($cause)),
                        'root' => $this->exportValue($cause->getRoot()),
                        'path' => $this->exportValue($cause->getPropertyPath()),
                        'value' => $this->exportValue($cause->getInvalidValue()),
                    );

                    $cause = method_exists($cause, 'getCause') ? $cause->getCause() : null;

                    continue;
                }

                if ($cause instanceof \Exception) {
                    $errorData['trace'][] = array(
                        'class' => $this->exportValue(get_class($cause)),
                        'message' => $this->exportValue($cause->getMessage()),
                    );

                    $cause = $cause->getPrevious();

                    continue;
                }

                $errorData['trace'][] = $cause;

                break;
            }

            $data['errors'][] = $errorData;
        }

        $data['synchronized'] = $this->exportValue($form->isSynchronized());

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function extractViewVariables(FormView $view)
    {
        $data = array();

        // Set the ID in case no FormInterface object was collected for this
        // view
        if (!isset($data['id'])) {
            $data['id'] = isset($view->vars['id']) ? $view->vars['id'] : null;
        }

        if (!isset($data['name'])) {
            $data['name'] = isset($view->vars['name']) ? $view->vars['name'] : null;
        }

        foreach ($view->vars as $varName => $value) {
            $data['view_vars'][$varName] = $this->exportValue($value);
        }

        ksort($data['view_vars']);

        return $data;
    }

    /**
     * Recursively builds an HTML ID for a form.
     *
     * @param FormInterface $form The form
     *
     * @return string The HTML ID
     */
    private function buildId(FormInterface $form)
    {
        $id = $form->getName();

        if (null !== $form->getParent()) {
            $id = $this->buildId($form->getParent()).'_'.$id;
        }

        return $id;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private function exportValue($value)
    {
        $dump = fopen('php://memory', 'r+b');
        $dumper = new HtmlDumper($dump);

        $dumper->dump($this->cloner->cloneVar($value));
        rewind($dump);

        return stream_get_contents($dump);
    }
}
