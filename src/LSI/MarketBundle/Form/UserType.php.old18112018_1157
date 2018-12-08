<?php

namespace LSI\MarketBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('nom')
            ->add('dateInscription')
            /*->add('indicatif', TextType::class, array('required' => false))
            ->add('dateModif')
            ->add('langue', TextType::class, array('required' => false))
            ->add('telephone', TextType::class, array('required' => false))
            ->add('mairie')
            ->add('administre')
            ->add('adresse')*/
            ->add('Ajouter', SubmitType::class, array(
                'attr' => array(
                    'class' => 'form-control btn btn-primary'
                )
            ))
        ;

        $builder
            ->remove('nom')
            ->remove('dateInscription')
            ->remove('indicatif')
            ->remove('dateModif')
            ->remove('langue')
            ->remove('telephone')
            ->remove('mairie')
            ->remove('administre')
            ->remove('adresse')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'LSI\MarketBundle\Entity\User'
        ));
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    /**
     * {@inheritdoc}
     *
    public function getBlockPrefix()
    {
        return 'lsi_marketbundle_user';
    }*/


}
