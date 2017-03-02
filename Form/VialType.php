<?php

/*
 * This file is part of the FliesBundle.
 *
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bluemesa\Bundle\FliesBundle\Form;

use Bluemesa\Bundle\CoreBundle\Form\TextEntityType;
use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Bluemesa\Bundle\FliesBundle\Form\Type\VialDatesType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * VialType class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class VialType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('dates', VialDatesType::class, array(
                        'horizontal'        => false,
                        'label_render'      => false,
                        'widget_form_group' => false
                    )
                )
                ->add('notes', TextareaType::class, array(
                        'label'     => 'Notes',
                        'required'  => false
                    )
                )
                ->add('sourceVial', TextEntityType::class, array(
                        'choice_label'        => 'id',
                        'class'               => Vial::class,
                        'format'              => '%06d',
                        'required'            => false,
                        'label'               => 'Flipped from',
                        'attr'                => array('class' => 'barcode'),
                        'widget_addon_append' => array('icon' => 'qrcode')
                    )
                )
                ->add('options', Type\VialOptionsType::class, array(
                        'horizontal'        => false,
                        'label_render'      => false,
                        'widget_form_group' => false
                    )
                )
                ->addEventListener(
                    FormEvents::PRE_SET_DATA,
                    array($this, 'addOptionalFields'))
                ->addEventListener(
                    FormEvents::PRE_SUBMIT,
                    array($this, 'addOptionalFields'));
    }

    /**
     * @param FormEvent $event
     */
    public function addOptionalFields(FormEvent $event)
    {
        /** @var Vial $vial */
        $builder = $event->getForm();
        $data = $event->getData();
        $vial = is_array($data) ? $builder->getData() : $data;

        if (null === $vial->getId()) {
            $builder->add('number', NumberType::class, array(
                            'label'       => 'Number of vials',
                            'data'        => 1,
                            'mapped'      => false,
                            'constraints' => array(new Range(array('min' => 1)))
                        )
                    );
        } else {
            $builder->add('trashed', CheckboxType::class, array(
                            'label'     => '',
                            'required'  => false
                        )
                    );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Vial::class,
        ));
    }
}
