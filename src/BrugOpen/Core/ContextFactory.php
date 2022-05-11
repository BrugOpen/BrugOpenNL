<?php

namespace BrugOpen\Core;

interface ContextFactory
{

    /**
     *
     * @param string $appRoot
     * @return Context
     */
    public function createContext($appRoot);

}
