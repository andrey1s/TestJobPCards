<?php

namespace Acme\GameBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Description of Game
 *
 * @author andrey <asamusev@archer-soft.com>
 */
class Game extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('Game', 'text')->add('Name', 'text');
    }

    public function getName() {
        return 'game';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Acme\GameBundle\Form\Object\Game',
        ));
    }

}
