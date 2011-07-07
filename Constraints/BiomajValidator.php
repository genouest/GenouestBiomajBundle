<?php

namespace Genouest\Bundle\BiomajBundle\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\HttpFoundation\Request;

use Genouest\Bundle\BiomajBundle\Biomaj\BankManagerAccessor;

/**
 * BiomajValidator validates that the value is one of the available banks.
 */
class BiomajValidator extends ConstraintValidator
{
    public function isValid($value, Constraint $constraint)
    {
        if (null === $value) {
            return true;
        }
        
        $bankaccessor = new BankManagerAccessor();
        $choices = $this->getPathArray($bankaccessor->prepareBankList($constraint->type, $constraint->format, $constraint->filterall, $constraint->cleanup));

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
