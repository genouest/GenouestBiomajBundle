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

class BiomajPrefixValidator extends ConstraintValidator
{
    public function isValid($value, Constraint $constraint)
    {
        if (null === $value) {
            return true;
        }
        
        $realPath = realpath(dirname($value)); // The path may not be complete (blast path don't contain the file extension)

        if ($realPath === false) {
            $this->setMessage($constraint->messageNotFound, array('{{ value }}' => $value));

            return false;
        }

        if (substr($realPath, 0, strlen($constraint->prefix)) !== $constraint->prefix) {
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
