<?php

namespace BrugOpen\Ndw\Model;

class PushSessionState
{
    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $sessionState;

    /**
     * Get the value of sessionId
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set the value of sessionId
     *
     * @param string $sessionId
     * @return void
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Get the value of sessionState
     *
     * @return string
     */
    public function getSessionState()
    {
        return $this->sessionState;
    }

    /**
     * Set the value of sessionState
     *
     * @param string $sessionState
     * @return void
     */
    public function setSessionState($sessionState)
    {
        $this->sessionState = $sessionState;
    }
}
