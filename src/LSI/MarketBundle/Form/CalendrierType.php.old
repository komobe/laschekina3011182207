<?php

namespace LSI\MarketBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CalendrierType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('debut', DateType::class, array(
                'label' => 'Date début pour la plage',
                'placeholder' => array(
                    'year' => 'Année', 'month' => 'Mois', 'day' => 'Jour', )
            ))
            ->add('fin', DateType::class, array(
                'label' => 'Date fin pour la plage',
                'placeholder' => array(
                    'year' => 'Année', 'month' => 'Mois', 'day' => 'Jour',)
            ))
            ->add('statut', EntityType::class, array(
                'label' => 'satut de la plage',
                'class' => 'LSIMarketBundle:Statut',
                'choice_label' => 'libelle',
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'Sélectionner le statut',
                )
            );
            //->add('annonce');
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'LSI\MarketBundle\Entity\Calendrier',
            'csrf_protection' => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'lsi_marketbundle_calendrier';
    }


}
