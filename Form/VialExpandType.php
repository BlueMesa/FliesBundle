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

use Bluemesa\Bundle\CoreBundle\Form\HiddenEntityType;
use Bluemesa\Bundle\CoreBundle\Form\TextEntityType;
use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\NotNull;


/**
 * VialExpandType class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class VialExpandType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('source', TextEntityType::class, array(
                        'choice_label'        => 'id',
                        'class'               => Vial::class,
                        'format'              => '%06d',
                        'label'               => 'Source',
                        'attr'                => array('class' => 'barcode'),
                        'widget_addon_append' => array('icon' => 'qrcode'),
                        'constraints'         => array(new NotNull())
                    )
                )
                ->add('template', Type\VialOptionsType::class, array(
                        'horizontal'        => false,
                        'label_render'      => false,
                        'widget_form_group' => false,
                        'inherit_data'      => false,
                        'data_class'        => Vial::class
                    )
                )
                ->add('number', NumberType::class, array(
                        'label'       => 'Number of vials',
                        'constraints' => array(new Range(array('min' => 1)))
                    )
                )
                ->addEventListener(
                    FormEvents::PRE_SET_DATA,
                    array($this, 'removeSourceField'));
    }

    /**
     * @param FormEvent $event
     */
    public function removeSourceField(FormEvent $event)
    {
        /** @var Vial $vial */
        $builder = $event->getForm();
        $data = $event->getData();

        if (null !== $data['source']) {
            $builder->remove('source');
            $builder->add('source', HiddenEntityType::class, array(
                            'choice_label'        => 'id',
                            'class'               => Vial::class,
                            'constraints'         => array(new NotNull())
                        )
                    );
        }
    }
}
