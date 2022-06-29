<?php
namespace BrugOpen\Service;

class OperationEventProcessor
{

    public function onOperationUpdate($operationId)
    {

        // get current operations for bridgeId
        // update lastStartedOperationId if needed
        // might be different than operationId

    }

    
    public function checkOperationApproaches($bridgeId, $operationId, $operationStart, $operationEnd)
    {
        $sql = 'UPDATE bo_bridge_approach SET operation_id = ' . ((int) $operationId) . ' WHERE bridge_id = ' . ((int) $bridgeId) . ' AND actual_pass_timestamp BETWEEN ' . ($operationStart + 10) . ' AND ' . ($operationEnd - 10) . ' AND operation_id IS NULL';

        $this->dataStore->executeQuery($sql);
    }


}
