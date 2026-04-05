<?php

namespace BrugOpen\Datex\Model;

class SessionInformation
{

    /**
     *
     * @var string
     */
    private $sessionID;

    /**
     * Get the value of sessionID
     *
     * @return string
     */
    public function getSessionID()
    {
        return $this->sessionID;
    }

    /**
     * Set the value of sessionID
     *
     * @param string $sessionID
     * @return void
     */
    public function setSessionID($sessionID)
    {
        $this->sessionID = $sessionID;
    }
}
