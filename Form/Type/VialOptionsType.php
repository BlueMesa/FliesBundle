<?php

/*
 * This file is part of the FliesBundle.
 *
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bluemesa\Bundle\FliesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * VialOptionsType class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class VialOptionsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('size', ChoiceType::class, array(
                        'choices'     => array(
                            'small'       => 'small',
                            'medium'      => 'medium',
                            'large'       => 'large',
                            'extra large' => 'xlarge'
                        ),
                        'expanded'    => true,
                        'label'       => 'Vial size',
                        'required'    => false,
                        'placeholder' => false,
                        'horizontal'  => true,
                        'data'        => 'medium'
                    )
                )
                ->add('food', FoodType::class, array(
                        'label'      => 'Food type',
                        'required'   => false,
                        'horizontal' => true,
                        'data'       => 'Normal'
                    )
                );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'inherit_data' => true
        ));
    }
}
