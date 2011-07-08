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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * BiomajValidator validates that the value is one of the available banks.
 */
class BiomajValidator extends ConstraintValidator
{
    protected $container;
    
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function isValid($value, Constraint $constraint)
    {
        if (null === $value) {
            return true;
        }
        
        $bankManager = $this->container->get('biomaj.bank.manager');
        $choices = $this->getPathArray($bankManager->getBankList($constraint->type, $constraint->format, $constraint->cleanup));

        if (!is_array($choices)) {
            throw new ConstraintDefinitionException('The list of banks couldn\'t be generated');
        }

        if (!in_array($value, $choices, true)) {
            $this->setMessage($constraint->message, array('{{ value }}' => $value));

            return false;
        }

        return true;
    }
  
    protected function getPathArray($array)
    {
        $res = array();
        foreach ($array as $path => $name)
        {
            if (is_array($name))
                $res = array_merge($res, $this->getPathArray($name));
            else
                $res[] = $path;
        }

        return $res;
    }
}
