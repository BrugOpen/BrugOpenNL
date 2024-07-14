<?php
namespace BrugOpen\Core;

use Psr\Log\LoggerInterface;

class LogRegistry
{

    /**
     *
     * @var \BrugOpen\Core\Context
     */
    private $context;

    /**
     *
     * @var LoggerInterface[]
     */
    private $logsByName = array();

    /**
     *
     * @var LogFactory
     */
    private $logFactory;

    /**
     *
     * @param \BrugOpen\Core\Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
        $this->logFactory = new DefaultLogFactory();
    }

    /**
     *
     * @param string|object $name
     * @return LoggerInterface
     */
    public function getLog($name)
    {
        if (($name != null) && is_object($name)) {

            $name = str_replace('\\', '.', get_class($name));
        }

        if ($name != '') {

            if (! array_key_exists($name, $this->logsByName)) {

                if ($logger = $this->logFactory->createLog($name, $this->context)) {

                    $this->logsByName[$name] = $logger;
                } else {

                    $this->logsByName[$name] = null;
                }
            }

            return $this->logsByName[$name];
        }
    }
}
