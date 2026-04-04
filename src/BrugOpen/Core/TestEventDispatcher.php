<?php

namespace BrugOpen\Core;

class TestEventDispatcher extends EventDispatcher
{

    /**
     *
     * @var array
     */
    private $postedEvents = array();

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Core\EventDispatcher::postEvent()
     */
    public function postEvent($eventName, $params = null, $pending = false)
    {
        $event = array();
        $event['name'] = $eventName;
        $event['params'] = $params;
        if ($pending !== false) {
            $event['pending'] = $pending;
        }
        $this->postedEvents[] = $event;
    }

    /**
     *
     * @return array
     */
    public function getPostedEvents()
    {
        return $this->postedEvents;
    }

    /**
     * Clears the list of posted events.
     * This is useful to reset the state of the event dispatcher between test cases.
     */
    public function clearPostedEvents()
    {
        $this->postedEvents = array();
    }
}
