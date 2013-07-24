<?php

namespace Genouest\Bundle\BiomajBundle\Form\Extension;

use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;

class SimpleTrustedChoiceList extends SimpleChoiceList
{
    /**
     * Creates a new simple choice list.
     * This is to be used when you don't want to ensure the values sent by the user are present
     * in the initial choice list of the widget. A constraint should still be added to check the validity of the value.
     * Example use-case: when the select widget is populated by some user-side JS code.
     *
     * @param array $choices The array of choices with the choices as keys and
     *                       the labels as values. Choices may also be given
     *                       as hierarchy of unlimited depth by creating nested
     *                       arrays. The title of the sub-hierarchy is stored
     *                       in the array key pointing to the nested array.
     * @param array $preferredChoices A flat array of choices that should be
     *                                presented to the user with priority.
     */
    public function __construct(array $choices, array $preferredChoices = array())
    {
        parent::__construct($choices, $preferredChoices);
    }

    /**
     * {@inheritdoc}
     */
    public function getChoicesForValues(array $values)
    {
        $values = $this->fixValues($values);

        // The values are identical to the choices, so we can just return them
        // to improve performance a little bit
        return $this->fixChoices($values, $this->getValues());
    }

    /**
     * {@inheritdoc}
     */
    public function getValuesForChoices(array $choices)
    {
        $choices = $this->fixChoices($choices);

        // The choices are identical to the values, so we can just return them
        // to improve performance a little bit
        return $this->fixValues($choices);
    }

    /**
     * Converts the choice to a valid PHP array key.
     *
     * @param mixed $choice The choice.
     *
     * @return string|integer A valid PHP array key.
     */
    protected function fixChoice($choice)
    {
        return $this->fixIndex($choice);
    }

    /**
     * {@inheritdoc}
     */
    protected function fixChoices(array $choices)
    {
        return $this->fixIndices($choices);
    }
}
