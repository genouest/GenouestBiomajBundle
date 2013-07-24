<?php

namespace Genouest\Bundle\BiomajBundle\Form\Extension;

use Symfony\Component\Form\Extension\Core\ChoiceList\LazyChoiceList;

class BiomajLazyChoiceList extends LazyChoiceList
{
    /**
     * Loads the choice list
     *
     * Should be implemented by child classes.
     *
     * @return ChoiceListInterface The loaded choice list
     */
    protected function loadChoiceList()
    {
        $array = array("" => "Loading, please wait...");
        $choices = new SimpleTrustedChoiceList($array);

        return $choices;
    }
}
