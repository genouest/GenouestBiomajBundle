<?php

/*
 * Copyright 2011 Anthony Bretaudeau <abretaud@irisa.fr>
 *
 * Licensed under the CeCILL License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt
 *
 */

namespace Genouest\Bundle\BiomajBundle\Constraints;

use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Constraint;

class Biomaj extends Constraint
{
    public $type;
    public $format = '';
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
    
    public function validatedBy()
    {
        return 'biomaj';
    }

}
