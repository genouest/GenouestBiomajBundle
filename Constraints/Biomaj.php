<?php

namespace Genouest\Bundle\BiomajBundle\Constraints;

use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Constraint;

class Biomaj extends Constraint
{
    public $type;
    public $format = '';
    public $filterall = false;
    public $cleanup = true;
    public $message = 'This value should be one of the given choices';

    /**
     * @inheritDoc
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        if (empty($this->type)) {
            throw new ConstraintDefinitionException('"type" must be specified on constraint Biomaj');
        }
        
    }
    
    public function getTargets()
    {
    
        return self::PROPERTY_CONSTRAINT;
    }
}
