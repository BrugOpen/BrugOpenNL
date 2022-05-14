<?php
namespace BrugOpen\Core;

class EventDispatcher
{

    // execution priorities for postEvent
    const EVENT_CALLBACK_GROUP_FIRST = 0;

    const EVENT_CALLBACK_GROUP_SECOND = 1;

    const EVENT_CALLBACK_GROUP_THIRD = 2;

    /**
     * Array of observers (callbacks attached to events) that are not methods
     * of plugin classes.
     *
     * @var array
     */
    private $observers = array();

    /**
     * Array storing information for all pending events.
     * Each item in the array
     * will be an array w/ two elements:
     *
     * array(
     * 'Event.Name', // the event name
     * array('event', 'parameters') // the parameters to pass to event observers
     * )
     *
     * @var array
     */
    private $pendingEvents = array();

    /**
     *
     * @var Context
     */
    private $context;

    /**
     * Constructor.
     */
    public function __construct(Context $context, array $observers = array())
    {
        foreach ($observers as $observerInfo) {
            list ($eventName, $callback) = $observerInfo;
            $this->observers[$eventName][] = $callback;
        }
    }

    /**
     * Triggers an event, executing all callbacks associated with it.
     *
     * @param string $eventName
     *            The name of the event, ie, API.getReportMetadata.
     * @param array $params
     *            The parameters to pass to each callback when executing.
     * @param bool $pending
     *            Whether this event should be posted again for plugins
     *            loaded after the event is fired.
     */
    public function postEvent($eventName, $params = null, $pending = false)
    {
        if ($pending) {
            $this->pendingEvents[] = array(
                $eventName,
                $params
            );
        }

        $callbacks = array();

        if (isset($this->observers[$eventName])) {
            foreach ($this->observers[$eventName] as $callbackInfo) {
                list ($callback, $callbackGroup) = $this->getCallbackFunctionAndGroupNumber($callbackInfo);

                $callbacks[$callbackGroup][] = $callback;
            }
        }

        // sort callbacks by their importance
        ksort($callbacks);

        // execute callbacks in order
        foreach ($callbacks as $callbackGroup) {
            foreach ($callbackGroup as $callback) {
                call_user_func_array($callback, $params);
            }
        }
    }

    /**
     * Associates a callback that is not a plugin class method with an event
     * name.
     *
     * @param string $eventName
     * @param array|callable $callback
     *            This can be a normal PHP callback or an array
     *            that looks like this:
     *            array(
     *            'function' => $callback,
     *            'before' => true
     *            )
     *            or this:
     *            array(
     *            'function' => $callback,
     *            'after' => true
     *            )
     *            If 'before' is set, the callback will be executed
     *            before normal & 'after' ones. If 'after' then it
     *            will be executed after normal ones.
     */
    public function addObserver($eventName, $callback)
    {
        $this->observers[$eventName][] = $callback;
    }

    private function getCallbackFunctionAndGroupNumber($hookInfo)
    {
        if (is_array($hookInfo) && ! empty($hookInfo['function'])) {
            $pluginFunction = $hookInfo['function'];
            if (! empty($hookInfo['before'])) {
                $callbackGroup = self::EVENT_CALLBACK_GROUP_FIRST;
            } elseif (! empty($hookInfo['after'])) {
                $callbackGroup = self::EVENT_CALLBACK_GROUP_THIRD;
            } else {
                $callbackGroup = self::EVENT_CALLBACK_GROUP_SECOND;
            }
        } else {
            $pluginFunction = $hookInfo;
            $callbackGroup = self::EVENT_CALLBACK_GROUP_SECOND;
        }

        return array(
            $pluginFunction,
            $callbackGroup
        );
    }
}
