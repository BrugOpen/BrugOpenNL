<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;

class VesselTypeService
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
    }

    /**
     * @return string[]
     */
    public function getVesselTypeTitleById()
    {

        $vesselTypeNameById = array();

        $connection = $this->context->getDatabaseConnectionManager()->getConnection();

        $stmt = $connection->prepare('SELECT id, title FROM bo_vessel_type');

        if ($stmt->execute()) {

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                $id = (int)$row['id'];
                $title = $row['title'];

                $vesselTypeNameById[$id] = $title;

            }

        }

        return $vesselTypeNameById;

    }

}