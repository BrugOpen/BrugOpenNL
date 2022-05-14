<?php
namespace BrugOpen\Core;

interface ContextFactory
{

    /**
     *
     * @param string $appRoot
     * @return \BrugOpen\Core\Context
     */
    public function createContext($appRoot);
}
