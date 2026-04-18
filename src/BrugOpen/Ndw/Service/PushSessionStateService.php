<?php

namespace BrugOpen\Ndw\Service;

use BrugOpen\Core\Context;
use BrugOpen\Ndw\Model\PushSessionState;

class PushSessionStateService
{
    /**
     * @var Context
     */
    private $context;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * PushSessionStateService constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Get the logger instance.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog()
    {
        if ($this->log == null) {

            $this->log = $this->context->getLogRegistry()->getLog($this);
        }

        return $this->log;
    }

    /**
     * Create a new session ID based on the current timestamp.
     *
     * @return string
     */
    public function createSessionID()
    {
        return date('YmdHis');
    }

    /**
     * Store the session state for the given session ID.
     *
     * @param string $sessionID
     * @param string $state
     */
    public function storeSessionState($sessionID, $state)
    {
        // load existing session state
        $sessionState = $this->loadCurrentSessionState();

        $sessionStateNeedsUpdate = true;

        if ($sessionState) {
            // check if session ID is the same
            if ($sessionState->getSessionID() === $sessionID) {
                // same session, check if state has changed
                if ($sessionState->getSessionState() === $state) {
                    // state has not changed, no need to update
                    $sessionStateNeedsUpdate = false;
                }
            }
        }

        if ($sessionStateNeedsUpdate) {

            $log = $this->getLog();
            $log->info("Updating session state for session ID: $sessionID to state: $state");

            // create new session state
            $sessionState = new PushSessionState();
            $sessionState->setSessionID($sessionID);
            $sessionState->setSessionState($state);

            // store session state to file
            $sessionStateFile = $this->getSessionStateFile();
            file_put_contents($sessionStateFile, json_encode([
                'sessionID' => $sessionState->getSessionID(),
                'state' => $sessionState->getSessionState()
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Load the current session state from the session state file.
     *
     * @return PushSessionState|null
     */
    public function loadCurrentSessionState()
    {
        $sessionStateFile = $this->getSessionStateFile();

        if (file_exists($sessionStateFile)) {
            $json = file_get_contents($sessionStateFile);
            $data = json_decode($json, true);

            if ($data) {
                $sessionState = new PushSessionState();
                $sessionState->setSessionID($data['sessionID']);
                $sessionState->setSessionState($data['state']);
                return $sessionState;
            }
        }

        return null;
    }

    /**
     * Get the file path for storing session state.
     */
    public function getSessionStateFile()
    {
        $sessionStateFile = $this->context->getAppRoot() . 'data/ndw/push_session_state.json';
        return $sessionStateFile;
    }
}
