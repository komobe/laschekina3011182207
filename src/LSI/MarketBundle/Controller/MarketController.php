<?php

namespace LSI\MarketBundle\Controller;


use LSI\MarketBundle\Entity\Contact;
use LSI\MarketBundle\Entity\User;
use LSI\MarketBundle\Form\ContactType;
use LSI\MarketBundle\Form\UserType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use LSI\MarketBundle\Entity\Annonce;
use LSI\MarketBundle\Entity\Categorie;
use LSI\MarketBundle\Entity\Reserver;
use LSI\MarketBundle\Entity\Image;
use LSI\MarketBundle\Entity\AnnoncePrix;
use LSI\MarketBundle\Entity\PublicAdministre;
use LSI\MarketBundle\Entity\PublicMairie;
use LSI\MarketBundle\Entity\Message;
use LSI\MarketBundle\Form\MessageType;
use LSI\MarketBundle\Form\AnnonceType;
use LSI\MarketBundle\Form\Annonce2Type;
use LSI\MarketBundle\Entity\Calendrier;
use LSI\MarketBundle\Form\AnnoncePrixType;
use LSI\MarketBundle\Repository\CategorieRepository;
use LSI\MarketBundle\Repository\AnnoncePrixRepository;
use LSI\MarketBundle\Repository\AnnonceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use LSI\MarketBundle\Form\ReserverType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Validator\Constraints\Regex;


class MarketController extends Controller
{

    public function indexAction(Request $request)
    {
        $repo_annonce = $this->getDoctrine()->getRepository('LSIMarketBundle:Annonce');

        $annonces = $repo_annonce->findAnnon();

        $contact = new Contact();
        $formcontact = $this->createForm(ContactType::class, $contact);
        $formcontact->handleRequest($request);

        $annadress = $annonces;

        $date =  $this->getRechercheIndexCookie();

        return $this->render('LSIMarketBundle:market:index.html.twig', array(
            'annonces' => $annonces,
            'annadress' => $annadress,
            'contactForm' => $formcontact->createView(),
            'date' => $date
        ));

    }

    public function listeOffresAction(Request $request)
    {
        // Formulaire pour la recherche avancée
        $form = $this->createFormBuilder()
            ->setMethod('GET')
            ->add('categorie', EntityType::class, array(
                'class' => Categorie::class,
                'query_builder' => function (CategorieRepository $cr) {
                    return $cr->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Choisir une categorie',
            ))
            ->add('pays', TextType::class, array('required' => false,
            ))
            ->add('ville', TextType::class,
                array(
                    'required' => false,
                ))
            ->add('mini', TextType::class,
                array(
                    'required' => false,
                    'constraints' => array(
                        new Regex(array(
                            'pattern' => '/\d+(\.\d+)?/',
                            'message' => 'Informations invalides'
                        )))
                ))
            ->add('max', TextType::class,
                array(
                    'required' => false,
                    'constraints' => array(
                        new Regex(array(
                            'pattern' => '/\d+(\.\d+)?/',
                            'message' => 'Informations invalides'
                        )))))
            ->add('debutdate', DateType::class, array(
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'html5' => true,
                    'required' => false,
                )
            )
            ->add('findate', DateType::class, array(
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'html5' => true,
                    'required' => false,
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $categ = $data['categorie'];
            $pays = $data['pays'];
            $ville = $data['ville'];
            $prixmini = $data['mini'];
            $prixmax = $data['max'];
            $datedebut = $data['debutdate'];
            $datefin = $data['findate'];


            $reposi_annonce = $this->getDoctrine()->getRepository('LSIMarketBundle:Annonce');
            $em = $this->getDoctrine()->getManager();
            $mairiename = $em->getRepository('LSIMarketBundle:User')->findNameMairie();
            $annonces = $reposi_annonce->findByannonce($categ, $pays, $ville, $prixmini, $prixmax, $datedebut, $datefin);
            $annadress = $annonces;
            return $this->render('LSIMarketBundle:market:offres.html.twig',
                array('annonces' => $annonces,
                    'mairiename' => $mairiename,
                    'annadress' => $annadress,
                    'formrechavancee' => $form->createView()));

        }
        $em = $this->getDoctrine()->getManager();

        // Recuperer toutes les annonces en bd
        $annonces = $em->getRepository('LSIMarketBundle:Annonce')->findAll();
        $mairiename = $em->getRepository('LSIMarketBundle:User')->findNameMairie();
        $annadress = $annonces;
        //dump($annonces);
        return $this->render('LSIMarketBundle:market:offres.html.twig',
            array('annonces' => $annonces,
                'mairiename' => $mairiename,
                'annadress' => $annadress,
                'formrechavancee' => $form->createView()));
    }

    // Phase 1 de la création d'une annonce
    public function ajouterAction(Request $request)
    {
        // Restreindre l'acces uniquement a la mairie
        $this->denyAccessUnlessGranted('ROLE_MAIRIE', $this->redirectToRoute('fos_user_security_login'));

        $annonce = new Annonce();
        $form = $this->createForm(AnnonceType::class, $annonce);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser()->getMairie();
            $annonce->setMairie($user);
            $em->persist($annonce);
            $em->flush();
            $request->getSession()->getFlashBag()->add('notif', 'Annonce ajoutée avec succès !');

            return $this->redirectToRoute('ls_imarket_ajouter_annonce2', array('id' => $annonce->getId()));
        }
        return $this->render('LSIMarketBundle:market:ajouter.html.twig', array('form' => $form->createView()));
    }

    // Phase 2 de la création de l'annonce
    public function ajouter2Action($id, Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_MAIRIE', $this->redirectToRoute('fos_user_security_login'));
        $em = $this->getDoctrine()->getManager();
        //recupération de l'objet
        $annonce = $em->getRepository('LSIMarketBundle:Annonce')->find($id);
        if (null === $annonce) {
            throw new NotFoundHttpException("L'annonce dont le numéro est " . $id . " n'existe pas.");
        }
        //création du formulaire
        $form = $this->createForm(Annonce2Type::class, $annonce);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            //Insertion dans la BD
            $em->persist($annonce);
            $em->flush();
            $request->getSession()->getFlashBag()->add('notice', 'Votre Annonce a été crée et publiée.');
            //Redirection vers la page de consultation
            return $this->redirectToRoute('ls_imarket_voir_annonce', array('id' => $annonce->getId(),
            ));
        }
        //création de la vue
        return $this->render('LSIMarketBundle:market:ajouter2.html.twig', array('form' => $form->createView()));
    }

    // Récupère les disponibilités d'une annonce
    public function disponibiliteAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        //recuperons l'id de annonce
        $annonce = $em->getRepository('LSIMarketBundle:Annonce')->find($id);
        if (null === $annonce) {
            throw new NotFoundHttpException("L'annonce dont le numéro est " . $id . " n'existe pas.");
        }


        $statut = $em->getRepository('LSIMarketBundle:Annonce')->findAnnoncesAvecCalendrier($id);

        $dispo;
        foreach ($statut as $disp) {
            $dispo = $disp;
        }

        return $this->render('LSIMarketBundle:market:plage.html.twig', array('dispo' => $dispo));
    }

    // Consulter une annonce
    /* public function voirAction($id){
         $em = $this->getDoctrine()->getManager();

         // Recuperer l'annonce cliquee
         $annonce = $em->getRepository('LSIMarketBundle:Annonce')->find($id);
         // Verifier pour voir s'il y a une reservation sur l'annonce
         $repo = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
         $annreserv = $repo->AnnReserv($id);

         foreach ($annreserv as $value){
             $var = $value;
         }
         if(null === $annonce){
             throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
         }
         // Recuperer l'auteur de l'annonce...
         $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                      ->findAuteurAnnonce($annonce->getMairie()->getId());


         return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
             'auteur' => $auteur,
             'var' => $var));
     }*/
    /*public function voirAction($id){
        $em = $this->getDoctrine()->getManager();

        // Recuperer l'annonce cliquee
        $annonce = $em->getRepository('LSIMarketBundle:Annonce')->find($id);

        //var_dump($annonce); die;
        //dump($annonce);
        // Verifier pour voir s'il y a une reservation sur l'annonce
        $repo = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $annreserv = $repo->AnnReserv($id);

        foreach ($annreserv as $value){
            $var = $value;
        }

        // Traitement pour fullcalendar, la gestion de la disponibilité
        $disp = $em->getRepository('LSIMarketBundle:Annonce')->disponibilite($id);

        if(!isset($disp[0])){
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auteur' => $auteur,
                'var' => $var,
            ));
        }
        $calendrier = $disp[0]->getCalendrier();
        $statut = $calendrier[0]->getStatut()->getLibelle();

        if(null === $annonce){
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }
        elseif ($statut == "Disponible"){
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = "green";
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auteur' => $auteur,
                'datedebut' => $datedebut,
                'datefin' => $datefin,
                'statut' => $statut,
                'colordispo' => $colordispo,
                'var' => $var,
            ));
        }
        elseif ($statut == "Indisponible"){
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = "red";
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auteur' => $auteur,
                'datedebut' => $datedebut,
                'datefin' => $datefin,
                'statut' => $statut,
                'colordispo' => $colordispo,
                'var' => $var,
            ));
        }
        elseif ($statut == "Reservé"){
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = "bleu";
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auteur' => $auteur,
                'datedebut' => $datedebut,
                'datefin' => $datefin,
                'statut' => $statut,
                'colordispo' => $colordispo,
                'var' => $var,
            ));
        }
        elseif ($statut == "Indeterminé"){
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = "yellow";
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auteur' => $auteur,
                'datedebut' => $datedebut,
                'datefin' => $datefin,
                'statut' => $statut,
                'colordispo' => $colordispo,
                'var' => $var,
            ));
        }
        // Recuperer l'auteur de l'annonce...
        $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
            ->findAuteurAnnonce($annonce->getMairie()->getId());

        return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
            'auteur' => $auteur,
            'var' => $var,
        ));
    }*/

    public function voirAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        // Recuperer l'annonce cliquee
        $annonce = $em->getRepository('LSIMarketBundle:Annonce')->find($id);

        // récupère le nom de la catégorie de l'annonce
        $cat = $annonce->getCategorie()->getName();
        // Récupère les autres annonces de la même catégorie que celle-ci
        $annonceParCat = $em->getRepository('LSIMarketBundle:Annonce')->findAnnonceParCategorie($cat, $id);

        // Récupère les annonces du même auteur...
        $annonceMemeAuteur = $em->getRepository('LSIMarketBundle:Annonce')
            ->findAnnoncesMemeAuteur($annonce->getMairie()->getId(), $id);

        // Récupère les annonce de même EPCI...
        $annonceMemeEpci = $em->getRepository('LSIMarketBundle:Annonce')
            ->findAnnoncesMemeEpci($annonce->getAdresse()->getVille()->getEpci()->getNom(), $id);

        // Récupère les mairies du même EPCI
        $autresMairies = $em->getRepository('LSIMarketBundle:User')
        	->findMairieEpci($annonce->getAdresse()->getVille()->getEpci()->getNom(), $annonce->getMairie()->getId());

        // Verifier pour voir s'il y a une reservation sur l'annonce
        $repo = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $annreserv = $repo->AnnReserv($id);

        foreach ($annreserv as $value) {
            $var = $value;
        }
        // Traitement pour fullcalendar, la gestion de la disponibilité à la creation de l'annonce

        $disp = $em->getRepository('LSIMarketBundle:Annonce')->disponibilite($id);

        // Traitement de l'affichage des periodes de reservations sur le calendrier

        $periodreserv = $em->getRepository('LSIMarketBundle:Reserver')->periodereserve($id);

        if (!isset($disp[0])) {
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auth' => $auteur[0],
                'periodreserv' => $periodreserv,
                'var' => $var,
                'anncat' => $annonceParCat,
                'memeAuteur' => $annonceMemeAuteur,
                'memeEpci' => $annonceMemeEpci,
                'autresMairies' => $autresMairies,
            ));
        }
        $calendrier = $disp[0]->getCalendrier();
        $statut = $calendrier[0]->getStatut()->getLibelle();

        // creation du formulaire de contact
        $contact = new Contact();
        $contact->setContactContext('annonce');
        $contact->setContactContextId($id);
        $contactForm = $this->createForm(ContactType::class, $contact)
            ->add('contactLocation', TextType::class)
            ->add('contactContext', HiddenType::class)
            ->add('contactContextId', HiddenType::class)
            ->add('contactLocation', TextType::class)
            ->add('contactPhone', TextType::class);

        if (null === $annonce) {
            throw new NotFoundHttpException("L'annonce d'id " . $id . " n'existe pas.");
        } elseif ($statut == "Disponible") {
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = "green";
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auth' => $auteur[0],
                'datedebut' => $datedebut,
                'datefin' => $datefin,
                'statut' => $statut,
                'colordispo' => $colordispo,
                'periodreserv' => $periodreserv,
                'var' => $var,
                'anncat' => $annonceParCat,
                'memeAuteur' => $annonceMemeAuteur,
                'memeEpci' => $annonceMemeEpci,
                'autresMairies' => $autresMairies,
                'contactForm' => $contactForm->createView()
            ));
        } elseif ($statut == "Indisponible") {
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = "red";
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auth' => $auteur[0],
                'datedebut' => $datedebut,
                'datefin' => $datefin,
                'statut' => $statut,
                'colordispo' => $colordispo,
                'periodreserv' => $periodreserv,
                'var' => $var,
                'anncat' => $annonceParCat,
                'memeAuteur' => $annonceMemeAuteur,
                'memeEpci' => $annonceMemeEpci,
                'autresMairies' => $autresMairies,
                'contactForm' => $contactForm->createView()
            ));
        } elseif ($statut == "Déterminé") {
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = "yellow";
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auth' => $auteur[0],
                'datedebut' => $datedebut,
                'datefin' => $datefin,
                'statut' => $statut,
                'colordispo' => $colordispo,
                'periodreserv' => $periodreserv,
                'var' => $var,
                'anncat' => $annonceParCat,
                'memeAuteur' => $annonceMemeAuteur,
                'memeEpci' => $annonceMemeEpci,
                'autresMairies' => $autresMairies,
                'contactForm' => $contactForm->createView()
            ));
        } elseif ($statut == "Indeterminé") {
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = " ";
            // Recuperer l'auteur de l'annonce...
            $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
                ->findAuteurAnnonce($annonce->getMairie()->getId());

            return $this->render('LSIMarketBundle:market:voir.html.twig', array('annonce' => $annonce,
                'auth' => $auteur[0],
                'datedebut' => $datedebut,
                'datefin' => $datefin,
                'statut' => $statut,
                'colordispo' => $colordispo,
                'periodreserv' => $periodreserv,
                'var' => $var,
                'anncat' => $annonceParCat,
                'memeAuteur' => $annonceMemeAuteur,
                'memeEpci' => $annonceMemeEpci,
                'autresMairies' => $autresMairies,
                'contactForm' => $contactForm->createView()
            ));
        }
        // Recuperer l'auteur de l'annonce...
        $auteur = $em->getRepository('LSI\MarketBundle\Entity\User')
            ->findAuteurAnnonce($annonce->getMairie()->getId());
        //dump($annonceParCat);die;
        return $this->render('LSIMarketBundle:market:voir.html.twig', array(
            'annonce' => $annonce,
            'auth' => $auteur[0],
            'periodreserv' => $periodreserv,
            'var' => $var,
            'anncat' => $annonceParCat,
            'memeAuteur' => $annonceMemeAuteur,
            'memeEpci' => $annonceMemeEpci,
            'autresMairies' => $autresMairies,
            'contactForm' => $contactForm->createView()
        ));
    }

    // Supprimer une annonce ( à revoir )

    public function supprimerAction(Request $request, Annonce $ann)
    {

        if ($request->isXmlHttpRequest() && $request->get('btn-delete')) {

            $em = $this->getDoctrine()->getManager();
            $evenement = $em->getRepository('LSIMarketBundle:Annonce')->find($ann);

            $em->remove($evenement);
            $em->flush();

            return new Response('good');
            return $this->redirectToRoute('ls_imarket_mes_annonces');
        }
        return $this->redirectToRoute('ls_imarket_mes_annonces');
    }

    // Passer une réservation
    public function reserverAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $em = $this->getDoctrine()->getManager();
        $reserver = new Reserver();

        $form = $this->createForm(ReserverType::class, $reserver);

        $annonce = $em->getRepository('LSIMarketBundle:Annonce')->find($id);

        $auth = $em->getRepository('LSIMarketBundle:User')
            ->findAuteurAnnonce($annonce->getMairie()->getId());
        $authMail = '';
        foreach ($auth as $aut) {
            $authMail = $aut->getEmail();
        }

        //dump($authMail);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $reserver->setUser($this->getUser());
            $reserver->setAnnonce($annonce);
            // Recupere la mairie connecté
            $mairie = $this->getUser()->getMairie();
            // Met à jour son budget restant
            $mairie->setBudgetRestant($reserver->getBudgetRestant());

            // Enregistre en BD
            $em->persist($mairie);
            $em->persist($reserver);
            $em->flush();

            $from = "%mailer_user%";
            $toCostumer = $this->getUser()->getEmail();
            $toSeller = $authMail;
            $subject = 'Nouvelle réservation';
            $bodyCost = 'Vous avez effectué une réservation sur l\'annonces : ' . $annonce->getTitre();
            // Envoi de mail au client...
            //sendMail($from, $toCostumer, $subject, $bodyCost);

            $bodySell = 'Vous avez reçu une réservation sur votre annonce : ' . $annonce->getTitre();
            // Envoi de mail à l'offreur...
            //sendMail($from, $toSeller, $subject, $bodySell);


            $request->getSession()->getFlashBag()->add('info', 'Reservation effectuee, un message a ete envoyer a l\'offreur');

            // Redirection dans l'espace mes réservations du user
            return $this->redirectToRoute('ls_imarket_mes_reservations');
        }

        return $this->render('LSIMarketBundle:reservation:reserver.html.twig', array(
            'form' => $form->createView(),
            'annonce' => $annonce,
        ));

    }

    public function mesReservationsAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $em = $this->getDoctrine()->getManager();

        // Recuperer l'id du User connecte
        $userId = $this->getUser()->getId();

        // Recuperer les reservations actives en BD dont l'auteur est le User connecte
        $reservations = $em->getRepository('LSIMarketBundle:Reserver')->mesReservations($userId);
        $titreAnn = [];
        $i = 0;

        if (null === $reservations) {
            throw new NotFoundHttpException("Vous n'avez aucune reservation en attente !");
        } else {
            //Recuperer le titre de chaque annonce recuperee
            foreach ($reservations as $reservation) {
                $titreAnn = $em->getRepository('LSIMarketBundle:Annonce')
                    ->titreAnnonce($reservation->getAnnonce());
                //$i++;
            }

            return $this->render('LSIMarketBundle:reservation:mesreservations.html.twig', array(
                'reservations' => $reservations,
                'titreAnnonce' => $titreAnn));
        }

        return $this->render('LSIMarketBundle:reservation:mesreservations.html.twig');
    }

    public function reservationsSurMesAnnoncesAction()
    {
        $this->denyAccessUnlessGranted('ROLE_MAIRIE', $this->redirectToRoute('fos_user_security_login'));
        $em = $this->getDoctrine()->getManager();

        $userId = $this->getUser()->getMairie()->getId();

        $annonceReservees = $em->getRepository('LSIMarketBundle:Reserver')->annonceReserver($userId);

        $titreAnn = [];
        $i = 0;

        if (null === $annonceReservees) {

        } else {
            //Recuperer le titre de chaque annonce recuperee
            foreach ($annonceReservees as $annonceR) {
                $titreAnn[$i] = $em->getRepository('LSIMarketBundle:Annonce')
                    ->titreAnnonce($annonceR->getAnnonce());
                $i++;
            }
            //dump($titreAnn);
            return $this->render('LSIMarketBundle:mairie:annonce_reserver.html.twig', array(
                'annonceReservee' => $annonceReservees,
                'titre' => $titreAnn
            ));
        }

    }

    // Renvoie la liste des annonces crees par une mairie.
    public function mesAnnoncesAction()
    {
        $this->denyAccessUnlessGranted('ROLE_MAIRIE', $this->redirectToRoute('fos_user_security_login'));
        $em = $this->getDoctrine()->getManager();

        // Recuperer le User connecte
        $userId = $this->getUser()->getMairie();

        // Recuperer les annonces en fonction du User connecte.
        $annonces = $em->getRepository('LSIMarketBundle:Annonce')->findMesAnnonces($userId);

        if (null === $annonces) {
            echo 'Vous n\'avez créer aucune annonce !';
        }

        return $this->render('LSIMarketBundle:mairie:mesannonces.html.twig', array('annonce' => $annonces));

    }

    // Remplacer le 06/11/18 13h10 par Sylvanus, car empeche les codes de gestion des budgets et remises de fonctionner
    // public function monEspaceAction(Request $request){
    //     $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->redirectToRoute('fos_user_security_login'));
    //     $em = $this->getDoctrine()->getManager();
    //     $user = $this->getUser()->getMairie();
    //     if (!isset($annonce)){
    //             $annonce = new Annonce();
    //             $form = $this->createForm(AnnonceType::class, $annonce);
    //             if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()){
    //                 //$em = $this->getDoctrine()->getManager();

    //              $annonce->setMairie($user);
    //              $em->persist($annonce);
    //              $em->flush();
    //              $request->getSession()->getFlashBag()->add('notif', 'Annonce ajoutée avec succès !');

    //              return $this->redirectToRoute('ls_imarket_ajouter_annonce2', array('id' => $annonce->getId()));
    //             }
    //             //$em = $this->getDoctrine()->getManager();
    //             $userId = $this->getUser()->getId();
    //             $reservations = $em->getRepository('LSIMarketBundle:Reserver')->mesReservations($userId);
    //             $annonceReservees = $em->getRepository('LSIMarketBundle:Reserver')->annonceReserver($userId);
    //             $annonces = $em->getRepository('LSIMarketBundle:Annonce')->findMesAnnonces($userId);
    //             return $this->render('LSIMarketBundle::monespace.html.twig', array(
    //                 'annonces' => $reservations, 'annoncesR' => $annonceReservees, 'mesannonces' => $annonces,
    //                 'form' => $form->createView()
    //                 ));
    //     }

    //     // Recuperer l'id du User connecte
    //     $userId = $this->getUser()->getId();

    //     // Recuperer les reservations actives en BD dont l'auteur est le User connecte
    //     $reservations = $em->getRepository('LSIMarketBundle:Reserver')->mesReservations($userId);
    //     $annonceReservees = $em->getRepository('LSIMarketBundle:Reserver')->annonceReserver($userId);
    //     $annonces = $em->getRepository('LSIMarketBundle:Annonce')->findMesAnnonces($userId);

    //     // Formulaire de créqtion de budget
    //     $btSubmit = $request->get('val');

    //     if (isset($btSubmit)){
    //         $budget = $request->get('budget');

    //         $msgErr = "";
    //         if ($budget >= 10000) {
    //             $mairie = $this->getUser()->getMairie();
    //             $mairie->setBudgetInitial($budget);
    //             $mairie->setBudgetRestant($budget);

    //             $em->persist($mairie);
    //             $em->flush();

    //             $request->getSession()->getFlashBag()->add('info', 'Votre Annonce a été crée et publiée.');

    //             return $this->redirect($this->generateUrl('ls_imarket_mon_espace'));
    //         }else{
    //             $msgErr = "Votre budget doit être au moins 10 000 €";
    //         }

    //         return $this->render('LSIMarketBundle::monespace.html.twig', array(
    //             'annonces' => $reservations,
    //             'annoncesR' => $annonceReservees,
    //             'mesannonces' => $annonces,/*'form' => $form->createView(),*/
    //             'erreurBudget' => $msgErr));
    //     }

    //     //Formulaire de création de remises
    //     $btRemise = $request->get('remise');

    //     if (isset($btRemise)){
    //         $remiseEpci = trim(strip_tags($request->get('remiseEpci')));
    //         $remiseAssocEpci = trim(strip_tags($request->get('remiseAsso')));

    //         $errMsg ="";

    //         if (!empty($remiseEpci) && !empty($remiseAssocEpci)){
    //             $mairie->setRemiseEpci($remiseEpci);
    //             $mairie->setRemiseAssociationEpci($remiseAssocEpci);

    //             $em->persist($mairie);
    //             $em->flush();

    //             $request->getSession()->getFlashBag()->add('info', 'Votre remise a bien été créée.');

    //             return $this->redirect($this->generateUrl('ls_imarket_mon_espace'));
    //         }elseif (!empty($remiseEpci) && empty($remiseAssocEpci)){ // Remise définit uniquement pour les mairie EPCI
    //             $mairie->setRemiseEpci($remiseEpci);

    //             $em->persist($mairie);
    //             $em->flush();

    //             $request->getSession()->getFlashBag()->add('info', 'Votre remise a bien été créée.');

    //             return $this->redirect($this->generateUrl('ls_imarket_mon_espace'));
    //         }elseif (empty($remiseEpci) && !empty($remiseAssocEpci)){ // Remise définit uniquement pour les assos EPCI
    //             $mairie->setRemiseAssociationEpci($remiseAssocEpci);

    //             $em->persist($mairie);
    //             $em->flush();

    //             $request->getSession()->getFlashBag()->add('info', 'Votre remise a bien été créée.');

    //             return $this->redirect($this->generateUrl('ls_imarket_mon_espace'));
    //         }else{
    //             $errMsg = "Veuillez renseigner l'un des champs !";
    //         }

    //         return $this->render('LSIMarketBundle::monespace.html.twig', array(
    //             'annonces' => $reservations,
    //             'annoncesR' => $annonceReservees,
    //             'mesannonces' => $annonces,
    //             'erreurRemise' => $errMsg));
    //     }

    //         return $this->render('LSIMarketBundle::monespace.html.twig', array(
    //         'annonces' => $reservations,
    //         'annoncesR' => $annonceReservees,
    //         'mesannonces' => $annonces, /*'form' => $form->createView()*/));

    // }

    public function monEspaceAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->redirectToRoute('fos_user_security_login'));
        $em = $this->getDoctrine()->getManager();
        $mairie = $this->getUser()->getMairie(); // Recupère la mairie

        // Recuperer l'id du User connecte
        $userId = $this->getUser()->getId();

        // Recuperer les reservations actives en BD dont l'auteur est le User connecte
        $reservations = $em->getRepository('LSIMarketBundle:Reserver')->mesReservations($userId);
        $annonceReservees = $em->getRepository('LSIMarketBundle:Reserver')->annonceReserver($userId);
        $annonces = $em->getRepository('LSIMarketBundle:Annonce')->findMesAnnonces($userId);

        // Recuperons id de la mairie
        $idmairie = $this->getUser()->getMairie();

        // Gestion des habilitations
        $habilitation = $em->getRepository('LSIMarketBundle:User')->habilitation($idmairie);

        // Formulaire de créqtion de budget
        $btBudget = $request->get('val');

        if (isset($btBudget)) {
            $budget = $request->get('budget');

            $msgErr = "";
            if ($budget >= 10000) {

                $mairie->setBudgetInitial($budget);
                $mairie->setBudgetRestant($budget);

                $em->persist($mairie);
                $em->flush();

                $request->getSession()->getFlashBag()->add('info', 'Votre Annonce a été crée et publiée.');

                //$this->redirectToRoute('ls_imarket_liste_annonce');
                return $this->redirect($this->generateUrl('ls_imarket_mon_espace'));
            } else {
                $msgErr = "Votre budget doit être au moins 10 000 €";
            }

            return $this->render('LSIMarketBundle::monespace.html.twig', array(
                'annonces' => $reservations,
                'annoncesR' => $annonceReservees,
                'mesannonces' => $annonces,
                'erreurBudget' => $msgErr,
                'habilitation' => $habilitation,));
        }

        //Formulaire de création de remises
        $btRemise = $request->get('remise');

        if (isset($btRemise)) {
            $remiseEpci = trim(strip_tags($request->get('remiseEpci')));
            $remiseAssocEpci = trim(strip_tags($request->get('remiseAsso')));

            $errMsg = "";

            if (!empty($remiseEpci) && !empty($remiseAssocEpci)) {
                $mairie->setRemiseEpci($remiseEpci);
                $mairie->setRemiseAssociationEpci($remiseAssocEpci);

                $em->persist($mairie);
                $em->flush();

                $request->getSession()->getFlashBag()->add('info', 'Votre remise a bien été créée.');

                return $this->redirect($this->generateUrl('ls_imarket_mon_espace'));
            } elseif (!empty($remiseEpci) && empty($remiseAssocEpci)) { // Remise définit uniquement pour les mairie EPCI
                $mairie->setRemiseEpci($remiseEpci);

                $em->persist($mairie);
                $em->flush();

                $request->getSession()->getFlashBag()->add('info', 'Votre remise a bien été créée.');

                return $this->redirect($this->generateUrl('ls_imarket_mon_espace'));
            } elseif (empty($remiseEpci) && !empty($remiseAssocEpci)) { // Remise définit uniquement pour les assos EPCI
                $mairie->setRemiseAssociationEpci($remiseAssocEpci);

                $em->persist($mairie);
                $em->flush();

                $request->getSession()->getFlashBag()->add('info', 'Votre remise a bien été créée.');

                return $this->redirect($this->generateUrl('ls_imarket_mon_espace'));
            } else {
                $errMsg = "Veuillez renseigner l'un des champs !";
            }

            return $this->render('LSIMarketBundle::monespace.html.twig', array(
                'annonces' => $reservations,
                'annoncesR' => $annonceReservees,
                'mesannonces' => $annonces,
                'erreurRemise' => $errMsg,
                'habilitation' => $habilitation,));
        }

        return $this->render('LSIMarketBundle::monespace.html.twig', array(
            'annonces' => $reservations,
            'annoncesR' => $annonceReservees,
            'mesannonces' => $annonces,
            'habilitation' => $habilitation,));

    }

    public function espaceMembreAction()
    {
        return $this->render('LSIMarketBundle::espace_membre.html.twig');
    }

    // Ajoute un membre
    public function ajouterMembreAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_MAIRIE');
        $em = $this->getDoctrine()->getManager();
        $user = new User();

        $form = $this->createForm(UserType::class, $user, array('user' => $this->getUser()));
        // Recupere l'utilisateur connecte et le passe en parametre du formulaire

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user->setRoles(array('ROLE_MEMBRE'));
            $user->setEnabled(true);
            $user->getMembre()->setMairie($this->getUser()->getMairie());
            // Definis les champs restants...

            if ($form->isValid()) {
                $em->persist($user);
                $em->flush();

                return $this->redirect($this->generateUrl('lsi_market_ajout_membre'));
            }
        }

        return $this->render('LSIMarketBundle:mairie:ajout_membre.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    // Gestion d'un membre
    public function gererMembreAction(User $user, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $btValider = $request->request->get('regHab');

        if (isset($btValider)) {
            $achat = $request->request->get('achat');
            $vente = $request->request->get('vente');
            $devis = $request->request->get('devis');
            $disabled = $request->request->get('disabled');
            $reference = $request->request->get('reference');

            if ($achat == "on")
                $user->getMembre()->setContratAchat(1);
            if ($vente == "on")
                $user->getMembre()->setVente(1);
            if ($devis == "on")
                $user->getMembre()->setDevis(1);
            if ($reference == "on")
                $user->getMembre()->setReference(1);
            if ($disabled == "on")
                $user->setEnabled(0);

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('ls_imarket_mon_espace');
        }

        return $this->render('LSIMarketBundle:mairie:gerer_membre.html.twig', array(
            'membre' => $user,
        ));
    }


    // Modifier une annonce

    public function modifierAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_MAIRIE', $this->redirectToRoute('fos_user_security_login'));
        $repo = $this->getDoctrine()->getRepository('LSIMarketBundle:Annonce');
        $annonce = $repo->find($id);
        $listimg = $repo->affichimg($id);
        //dump($annonce);
        if (null === $annonce) {
            throw new NotFoundHttpException("L'annonce dont le numéro est " . $id . " n'existe pas.");
        }

        // Fait appel au formulaire de modification

        $form = $this->createForm(AnnonceType::class);
        $form->get('titre')->setData($annonce->getTitre());
        $form->get('description')->setData($annonce->getDescription());
        $form->get('prixDefaut')->setData($annonce->getPrixDefaut());
        $form->get('regleCond')->setData($annonce->getRegleCond());
        $form->get('categorie')->setData($annonce->getCategorie());
        $form->get('typeAnnul')->setData($annonce->getTypeAnnul());
        $form->get('adresse')->setData($annonce->getAdresse());
        $form->get('images')->setData($annonce->getImages());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce->setTitre($form->get('titre')->getData());
            $annonce->setDescription($form->get('description')->getData());
            $annonce->setPrixDefaut($form->get('prixDefaut')->getData());
            $annonce->setRegleCond($form->get('regleCond')->getData());
            $annonce->setCategorie($form->get('categorie')->getData());
            $annonce->setAdresse($form->get('adresse')->getData());
            $annonce->setAnnonceUpdateAt(new \DateTime('now'));
            $em = $this->getDoctrine()->getManager();
            $em->persist($annonce);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');
            //Redirection vers la page de consultation
            return $this->redirectToRoute('ls_imarket_voir_annonce', array('id' => $annonce->getId(),
            ));

        }
        //création de la vue
        return $this->render('LSIMarketBundle:market:modifier.html.twig', array('form' => $form->createView(),
            'listimg' => $listimg,
            /* 'images'=> $annonce->getImages()->getWebPath() */));
    }

    public function dupliquerAction(Request $request, $id)
    {

        $this->denyAccessUnlessGranted('ROLE_MAIRIE', $this->redirectToRoute('fos_user_security_login'));
        //création d'un objet pour le dupliquer l'annonce avec une new image
        $newannonce = new Annonce();
        $image = new Image();

        //connection à la BD et récupération de l'annonce à créer par duplicata
        $em = $this->getDoctrine()->getManager();


        $annonce = $em->getRepository('LSIMarketBundle:Annonce')->findById($id);

        if (null === $annonce) {
            throw new NotFoundHttpException("L'annonce dont le numéro est " . $id . " n'existe pas.");
        }

        //copie du contenu de l'ancienne annonce dans la nouvelle
        foreach ($annonce as $item) {
            $newannonce->setTitre($item->getTitre());
            $newannonce->setDescription($item->getDescription());
            $newannonce->setRegleCond($item->getRegleCond());
            $newannonce->setPrixDefaut($item->getPrixDefaut());
            $newannonce->setAdresse($item->getAdresse());
            $newannonce->setTypeAnnul($item->getTypeAnnul());
            foreach ($image as $images) {
                $image = $item->getImages();
                $newannonce->setImages(new Image());
            }

            $newannonce->setCategorie($item->getCategorie());
        }

        // //création du formulaire
        $form = $this->createForm(AnnonceType::class, $newannonce);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser()->getMairie();
            $newannonce->setMairie($user);
            $em->persist($newannonce);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce crée avec succes.');

            return $this->redirectToRoute('ls_imarket_ajouter_annonce2', array('id' => $newannonce->getId(),
            ));
        }

        return $this->render('LSIMarketBundle:market:dupliquer.html.twig', array('form' => $form->createView(),
            'img' => $image->getWebPath()));
    }

    /*
        Recherche et resultat pour la barre de recherche sur toute les pages
    */

    public function rechPrincipaleAction()
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('ls_imarket_annonce_resultsearch'))
            ->setMethod('GET')
            ->add('searchbar', SearchType::class, array(
                'label' => false,
                'attr' => array('placeholder' => 'Titre ou description...')
            ))
            //->add('search', SubmitType::class)
            ->getForm();

        return $this->render('LSIMarketBundle:market:formrecherche.html.twig',
            ['form' => $form->createView()]);
    }

    // Resultat de la recherche
    /*
        public function resultatRechAction(Request $request) {
            $repository = $this->getDoctrine()->getRepository('LSIMarketBundle:Annonce');
            $form = $request->query->get('form');
            $motcle = $form['searchbar'];

            $listeannonce = $repository->findTitreDescp($motcle);
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce]);
        }*/

    public function resultatRechAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('LSIMarketBundle:Annonce');
        $form = $request->query->get('form');
        $motcle = $form['searchbar'];

        $listeannonce = $repository->findTitreDescp($motcle);
        $annadress = $listeannonce;
        return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
            ['listeannonce' => $listeannonce,
                'annadress' => $annadress,]);
    }

    // Methode traitant la recherche dans la page de index

    /*public function rechindexAction(Request $request){

	    $repo_annonce = $this->getDoctrine()->getRepository('LSIMarketBundle:Annonce');
        // Recuperer le champ titre
	    $titre = $request->get('titre_name');
	    // Recuperer le champ ville
        $ville = $request->get('ville_name');
	    // Recuperer la date debut de disponibilite de l'annonce
        $datedebut = $request->get('datedebut');
        //$newdatdebut = $datedebut->format('Y.m.d');
        // Recuperer la date fin de disponibilite de l'annonce
        $datefin = $request->get('datefin');
        //$newdatfin = date("Y-m-d", $datefin);
        $listeannonce = array();
        //
	    if ($titre != null && $ville == null ){
            $listeannonce = $repo_annonce->findrechetitreindex($titre);
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce]);
        }elseif ($ville != null && $titre == null){
            $listeannonce = $repo_annonce->findrechetitrevilleindex($ville);
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce]);
        }elseif ($titre != null && $ville != null){
            $listeannonce = $repo_annonce->findtitretitrevilleindex($titre, $ville);
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce]);
        }elseif ($datedebut != null && $datefin != null && $titre == null && $ville == null){
            $listeannonce = $repo_annonce->finddispoindex($datedebut, $datefin);
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce]);
        }elseif ($titre != null && $ville == null && $datedebut != null && $datefin != null){
            $listeannonce = $repo_annonce->titreperiodeindex($titre, $datedebut, $datefin);
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce]);
        }elseif ($titre == null && $ville != null && $datedebut != null && $datefin != null){
            $listeannonce = $repo_annonce->villeperiodeindex($ville, $datedebut, $datefin);
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce]);
        }elseif ($titre != null && $ville != null && $datedebut != null && $datefin != null){
            $listeannonce = $repo_annonce->titrevilleperiodeindex($titre, $ville, $datedebut, $datefin);
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce]);
        }

        return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
            ['listeannonce' => $listeannonce]);
    }*/

    public function rechindexAction(Request $request)
    {

        $repo_annonce = $this->getDoctrine()->getRepository('LSIMarketBundle:Annonce');

        // Recuperer le champ titre
        $titre = $request->get('titre_name');
        // Recuperer le champ ville
        $ville = $request->get('ville_name');
        // Recuperer la date debut de disponibilite de l'annonce
        $datedebut = $request->get('datedebut');
        //$newdatdebut = $datedebut->format('Y.m.d');
        // Recuperer la date fin de disponibilite de l'annonce
        $datefin = $request->get('datefin');
        //$newdatfin = date("Y-m-d", $datefin);
        $listeannonce = array();
        //
        if ($titre != null && $ville == null) {
            $listeannonce = $repo_annonce->findrechetitreindex($titre);
            $annadress = $listeannonce;
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce,
                    'annadress' => $annadress,
                ]);
        } elseif ($ville != null && $titre == null) {
            $listeannonce = $repo_annonce->findrechetitrevilleindex($ville);
            $annadress = $listeannonce;
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce,
                    'annadress' => $annadress,
                ]);
        } elseif ($titre != null && $ville != null) {
            $listeannonce = $repo_annonce->findtitretitrevilleindex($titre, $ville);
            $annadress = $listeannonce;
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce,
                    'annadress' => $annadress,
                ]);
        } elseif ($datedebut != null && $datefin != null && $titre == null && $ville == null) {
            $listeannonce = $repo_annonce->finddispoindex($datedebut, $datefin);
            $annadress = $listeannonce;
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce,
                    'annadress' => $annadress,
                ]);
        } elseif ($titre != null && $ville == null && $datedebut != null && $datefin != null) {
            $listeannonce = $repo_annonce->titreperiodeindex($titre, $datedebut, $datefin);
            $annadress = $listeannonce;
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce]);
        } elseif ($titre == null && $ville != null && $datedebut != null && $datefin != null) {
            $listeannonce = $repo_annonce->villeperiodeindex($ville, $datedebut, $datefin);
            $annadress = $listeannonce;
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce,
                    'annadress' => $annadress,
                ]);
        } elseif ($titre != null && $ville != null && $datedebut != null && $datefin != null) {
            $listeannonce = $repo_annonce->titrevilleperiodeindex($titre, $ville, $datedebut, $datefin);
            $annadress = $listeannonce;
            return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
                ['listeannonce' => $listeannonce,
                    'annadress' => $annadress,
                ]);
        }

        // Ajouté par Moro KONE --Debut
        
        $this->setRechercheIndexCookie($request, $datedebut, $datefin);

        // Ajouté par Moro KONE --fin

        return $this->render('LSIMarketBundle:market:resultat_recherche.html.twig',
            ['listeannonce' => $listeannonce]);

    }

    // Traitement pour la recherche avancée

    public function rechavanceeAction(Request $request)
    {

        // Creation du formulaire de la recherche
        $form = $this->createFormBuilder()
            ->add('categorie', EntityType::class, array(
                'label' => 'Categorie',
                'class' => 'LSIMarketBundle:Categorie',
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'Selection une categorie',
            ))
            ->add('pays', CountryType::class, array())
            ->add('departement', ChoiceType::class, array(
                'choices' => array(
                    ''
                )
            ))
            ->add('ville', ChoiceType::class, array(
                'choices' => array(
                    ''
                )
            ))
            ->add('mini', ChoiceType::class, array(
                'choices' => array(
                    ''
                )
            ))
            ->add('max', ChoiceType::class, array(
                'choices' => array(
                    ''
                )
            ))
            ->add('debutdate', ChoiceType::class, array(
                'choices' => array(
                    ''
                )
            ))
            ->add('findate', ChoiceType::class, array(
                'choices' => array(
                    ''
                )
            ))
            ->add('distance', ChoiceType::class, array(
                'choices' => array(
                    ''
                )
            ))
            ->getForm();

        return $this->render('LSIMarketBundle:marke:offres.html.twig', array(
            'formrechavancee' => $form,
        ));
    }


    public function annonceCommandeeAction()
    {
        return $this->render('LSIMarketBundle:mairie:annonce_commandee.html.twig');
    }

    // Gérer les commandes d'un utilisateur...
    public function mesCommandesAction()
    {

        return $this->render('LSIMarketBundle:commande:mes_commandes.html.twig');
    }

    /*public function monEpciAction(){
        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()){
            // Initialisation de $cp ...
            $cp = '';
            $cp = $request->query->get('cp');

            if ($cp != '' ){
                $codePostal = $em->getRepository('LSIMarketBundle:Epci')->findEpciCodePostal($cp);
                $response = new JsonResponse();
                $response->setData(array('data' => $codePostal));
                var_dump($response);die;
                return $response;
            }

            //return $response = new Response(json_encode($codePostal));
        }else{ //
            return $response = new Response('Ne correspond à aucun EPCI');
        }
    }*/

    public function conditionsAction()
    {
        $em = $this->getDoctrine()->getManager();

        $cgu = $em->getRepository('LSIMarketBundle:ConditionsGeneralesUtil')->find(1);

        return $this->render('LSIMarketBundle:market:condition_generale.html.twig', array('cgu' => $cgu));
    }

    // traitement pour consulter les réservations sur annonces

    public function voirReservationAction(Request $request, $id)
    {
        $repository = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $listreservation = $repository->voirReserver($id);

        return $this->render('LSIMarketBundle:market:voir_reservation.html.twig',
            array('listanreserv' => $listreservation));
    }

    // Action pour traiter la validation d'une réservation par l'offreur

    public function validAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        // Recuperer l'annonce reservée
        $reporeserv = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $reserve = $reporeserv->find($id);
        if ($request->isXmlHttpRequest() && $request->get('btn-valider')) {
            // Modification de l'etat de l'annonce et le statut
            $reserve->setReserveEtat('V');
            $em = $this->getDoctrine()->getManager();
            $em->persist($reserve);
            $em->flush();
        }
        return $this->redirectToRoute('ls_imarket_reservations_sur_mes_annonces');
    }

    // Action pour réfuser une réservation
    public function refuserAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $reporeserv = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $reserve = $reporeserv->find($id);
        if ($request->isXmlHttpRequest() && $request->get('btn-refuser')) {
            $reserve->setReserveEtat('R');
            // dump($reserve);
            $em = $this->getDoctrine()->getManager();
            $em->persist($reserve);
            $em->flush();
        }
        return $this->redirectToRoute('ls_imarket_reservations_sur_mes_annonces');
    }

    // Traitement de la réservation par le demandeur

    public function reservationdemandeurAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $repository = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');

        $repousernamereser = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $idannonce = $repousernamereser->idmairieannoncer($id);
        $repannonce = $this->getDoctrine()->getRepository('LSIMarketBundle:Annonce');
        $idmairie = $repannonce->idmairieannonce($idannonce[0]->getAnnonce()->getId());
        $repuser = $this->getDoctrine()->getRepository('LSIMarketBundle:User');
        $usermairie = $repuser->findUserAnnonce($idmairie[0]->getMairie()->getId());
        $offreur = $usermairie[0]->getNom();
        $userid = $this->getUser()->getId();
        $listreservation = $repository->MesReservation($idannonce[0]->getAnnonce()->getId());

        // Traitement pour fullcalendar, la gestion de la disponibilité à la creation de l'annonce
        $disp = $repannonce->disponibilite($idannonce[0]->getAnnonce()->getId());
        if (!isset($disp[0])) {
            // Recuperer l'auteur de l'annonce...
            return $this->render('LSIMarketBundle:reservation:pagetraitementreservationdemandeur.html.twig',
                array('listreservation' => $listreservation,
                    'usermairie' => $offreur));
        }


        $calendrier = $disp[0]->getCalendrier();
        $statut = $calendrier[0]->getStatut()->getLibelle();


        if ($statut == "Disponible") {
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = "green";
            return $this->render('LSIMarketBundle:reservation:pagetraitementreservationdemandeur.html.twig',
                array('listreservation' => $listreservation,
                    'usermairie' => $offreur,
                    'datedebut' => $datedebut,
                    'datefin' => $datefin,
                    'statut' => $statut,
                    'colordispo' => $colordispo,));

        } elseif ($statut == "Indisponible") {
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();
            $colordispo = "red";

            return $this->render('LSIMarketBundle:reservation:pagetraitementreservationdemandeur.html.twig',
                array('listreservation' => $listreservation,
                    'usermairie' => $offreur,
                    'datedebut' => $datedebut,
                    'datefin' => $datefin,
                    'statut' => $statut,
                    'colordispo' => $colordispo,));
        } elseif ($statut == "Déterminé") {
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = "yellow";

            return $this->render('LSIMarketBundle:reservation:pagetraitementreservationdemandeur.html.twig',
                array('listreservation' => $listreservation,
                    'usermairie' => $offreur,
                    'datedebut' => $datedebut,
                    'datefin' => $datefin,
                    'statut' => $statut,
                    'colordispo' => $colordispo,));
        } elseif ($statut == "Indeterminé") {
            $datedebut = $calendrier[0]->getDebut();
            $datefin = $calendrier[0]->getFin();

            $colordispo = " ";

            return $this->render('LSIMarketBundle:reservation:pagetraitementreservationdemandeur.html.twig',
                array('listreservation' => $listreservation,
                    'usermairie' => $offreur,
                    'datedebut' => $datedebut,
                    'datefin' => $datefin,
                    'statut' => $statut,
                    'colordispo' => $colordispo,));
        }

        return $this->render('LSIMarketBundle:reservation:pagetraitementreservationdemandeur.html.twig',
            array('listreservation' => $listreservation,
                'usermairie' => $offreur,
                'datedebut' => $datedebut,
                'datefin' => $datefin,
                'statut' => $statut,
                'colordispo' => $colordispo,));
    }

    /*
        Traitement de l'annulation d'une réservation par le demandeur après validation de l'offreur'
     */

    public function annulerReservationAction(Request $request, $id)
    {

        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $reporeserv = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $reserve = $reporeserv->find($id);

        if ($request->isXmlHttpRequest() && $request->get('btn-annuler')) {
            $reserve->setReserveEtat('An');
            $em = $this->getDoctrine()->getManager();
            $em->persist($reserve);
            $em->flush();
        }

        return $this->redirectToRoute('ls_imarket_mes_reservations');
    }

    /*Traitement pour la modification d'une réservation par le demandeur*/

    public function modifierReservationAction(Request $request, $id)
    {
        // Recuperation du formulaire de réservation
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));

        $formReservEdit = $this->createForm(ReserverType::class);


        $reporeserv = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $reserveancien = $reporeserv->find($id);
        $formReservEdit->get('debutPrestation')->setData($reserveancien->getDebutPrestation());
        $formReservEdit->get('finPrestation')->setData($reserveancien->getFinPrestation());
        $formReservEdit->get('adresseReserve')->setData($reserveancien->getAdresseReserve());
        $formReservEdit->handleRequest($request);

        if ($formReservEdit->isSubmitted() && $formReservEdit->isValid()) {
            $data = $formReservEdit->getData();
            if ($reserveancien->getReserveEtat() == "A") {

                $reserveancien->setDebutPrestation($formReservEdit->get("debutPrestation")->getData());
                $reserveancien->setFinPrestation($formReservEdit->get("finPrestation")->getData());
                $reserveancien->setAdresseReserve($formReservEdit->get("adresseReserve")->getData());

                $reserveancien->setReserveUpdateAt(new \DateTime());
                $em = $this->getDoctrine()->getManager();
                $em->persist($reserveancien);
                $em->flush();


                return $this->redirectToRoute('ls_imarket_traitement_demandeur_reservation',
                    array('id' => $reserveancien->getId()));

            }


            return $this->render('LSIMarketBundle:reservation:pagemodreservation.html.twig',
                array('formReservEdit' => $formReservEdit->createView()));
        }

        return $this->render('LSIMarketBundle:reservation:pagemodreservation.html.twig',
            array('formReservEdit' => $formReservEdit->createView()));
    }

    /* METHODE DE TRAITEMENT POUR LA  MESSAGERIE */
    public function msgEnvoyerMAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $userid = $this->getUser()->getId();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $msg = new Message();
        // Recuperer le username du destinateur
        $repousernamereser = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $destinateur = $repousernamereser->finddest($id);

        $formtchat = $this->createForm(MessageType::class, $msg);
        $formtchat->handleRequest($request);

        if ($formtchat->isSubmitted() && $formtchat->isValid()) {
            $msg->setDest($destinateur[0]->getUser()->getUsername());
            $msg->setDateAjout(new \DateTime());
            $msg->setUser($user);
            $em = $this->getDoctrine()->getManager();
            $em->persist($msg);
            $em->flush();
            return $this->redirectToRoute('ls_imarket_voir_reservation', array('id' => $destinateur[0]->getId()));

        }

        return $this->render('LSIMarketBundle:market:affichuserconnect.html.twig', array(
            'formtchat' => $formtchat->createView()));
    }

    // traitement d'envoie de message pour l'administre

    public function msgEnvoyerAAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $userid = $this->getUser()->getId();
        $msg = new Message();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        // Recuperer le username du destinateur
        $repousernamereser = $this->getDoctrine()->getRepository('LSIMarketBundle:Reserver');
        $destinateur = $repousernamereser->finddest($id);
        $idannonce = $repousernamereser->idmairieannoncer($id);
        $repannonce = $this->getDoctrine()->getRepository('LSIMarketBundle:Annonce');
        $idmairie = $repannonce->idmairieannonce($idannonce[0]->getAnnonce()->getId());
        $repuser = $this->getDoctrine()->getRepository('LSIMarketBundle:User');
        $usermairie = $repuser->find($idmairie[0]->getMairie()->getId());

        $formtchat = $this->createForm(MessageType::class, $msg);
        $formtchat->handleRequest($request);

        if ($formtchat->isSubmitted() && $formtchat->isValid()) {
            $msg->setDest($usermairie->getUsername());
            $msg->setDateAjout(new \DateTime());
            $msg->setUser($user);
            $em = $this->getDoctrine()->getManager();
            $em->persist($msg);
            $em->flush();
            return $this->redirectToRoute('ls_imarket_voir_reservation', array('id' => $destinateur[0]->getId()));

        }

        return $this->render('LSIMarketBundle:market:affichuserconnect.html.twig', array(
            'formtchat' => $formtchat->createView()));
    }


    // Methode de traitement pour les messages reçus

    public function msgRecuAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $repo = $this->getDoctrine()->getRepository('LSIMarketBundle:Message');

        $user = $this->getUser();

        $msgrecus = $repo->findByDest($user->getUsername());

        return $this->render('LSIMarketBundle:market:msgrecu.html.twig', array(
            'msgrecus' => $msgrecus
        ));
    }

    // Methode de traitement pour repondre au messages récus

    public function reponsemsgAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $msgnew = new Message();
        $reporecupmsg = $this->getDoctrine()->getRepository('LSIMarketBundle:Message');
        $msg = $reporecupmsg->find($id);
        // Recuperer id de l'utilisateur connecté
        $userid = $this->getUser()->getId();
        $user = $this->getUser();
        // Recuperer le username de la personnes à qui on veut repondre
        $repusername = $this->getDoctrine()->getRepository('LSIMarketBundle:User');
        $username = $repusername->find($msg->getUser()->getId());

        $formrepmsg = $this->createFormBuilder()
            ->add('reponse', TextareaType::class)
            ->getForm();

        $formrepmsg->handleRequest($request);

        if ($formrepmsg->isSubmitted() && $formrepmsg->isValid()) {

            $sujet = $msg->getSujet();
            $msgnew->setSujet($sujet);
            $msgnew->setDest($username->getUsername());
            $msgnew->setContenu($formrepmsg['reponse']->getData());
            $msgnew->setUser($user);
            $em = $this->getDoctrine()->getManager();
            $em->persist($msgnew);
            $em->flush();

            return $this->redirectToRoute('ls_imarket_traitement_msgrecu'
            );
        }

        return $this->render('LSIMarketBundle:market:reponsemgs.html.twig', array(
            'formrepmsg' => $formrepmsg->createView()));
    }

    // Lister les msg envoyés
    public function msgenvoyeAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));
        $repomsg = $this->getDoctrine()->getRepository('LSIMarketBundle:Message');
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $msgenvoyes = $repomsg->findmsgEnvoyes($user);
        return $this->render('LSIMarketBundle:market:pagemsgenvoyes.html.twig', array(
            'msgenvoyes' => $msgenvoyes
        ));

    }

    public function gestiondestarifAction(Request $req, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_MAIRIE', $this->redirectToRoute('fos_user_security_login'));
        $em = $this->getDoctrine()->getManager();
        $plage = new AnnoncePrix();
        $form = $this->createForm(AnnoncePrixType::class, $plage);
        $annonce = $em->getRepository('LSIMarketBundle:Annonce')->find($id);
        if ($req->isMethod('POST') && $form->handleRequest($req)->isValid()) {
            $plage->setAnnonce($annonce);
            $em->persist($plage);
            $em->flush();
            $req->getSession()->getFlashBag()->add('notice', 'vous terminée la création de votre annonce');
            return $this->redirectToRoute('ls_imarket_voir_annonce', array('id' => $annonce->getId(),
            ));
        }

        //création de la vue
        return $this->render('LSIMarketBundle:market:plagetarif.html.twig', array('form' => $form->createView()));
    }

    public function histoireAction()
    {

        return $this->render('LSIMarketBundle:une_histoire_et_des_valeurs:une_histoire_des_valeurs.html.twig');
    }

    public function aideAction()
    {

        return $this->render('LSIMarketBundle::aide.html.twig');
    }

    public function contactAction(Request $request)
    {
        $contact = new Contact();
        $contactForm = $this->createForm(ContactType::class, $contact)
            ->add('contactLocation', TextType::class)
            ->add('contactPhone', TextType::class);
        $contactForm->handleRequest($request);
        if ($request->isXmlHttpRequest()) {
            if ($contactForm->isValid()) {

                $statusCode = 200;
                $success = true;
                $data = null;
                $messageReturn = 'Votre message a été envoyé avec succes.';

                $contact = $contactForm->getData();
                $em = $this->getDoctrine()->getManager();
                $em->persist($contact);
                $mailer = $this->container->get('lsi_market.email.civilink_mailer');
                $mailer->sendMailSupport($contact);
                $em->flush();
            } else {
                $statusCode = 500;
                $success = false;
                $data = null;
                $messageReturn = 'Une erreur est survenue lors de l\'envoi du message';
            }
            return $this->json([
                'success' => $success,
                'data' => $data,
                'message' => $messageReturn
            ], $statusCode);
        } else {
            $this->redirectToRoute('ls_imarket_contact');
        }

        return $this->render('LSIMarketBundle::contact.html.twig', array(
            'contactForm' => $contactForm->createView()
        ));
    }

    public
    function marchePublicsAction()
    {

        return $this->render('LSIMarketBundle::Marche_publics.html.twig');
    }

    public
    function quiSommesNousAction()
    {

        return $this->render('LSIMarketBundle::qui_sommes_nous.html.twig');
    }

    public
    function conceptInnovantAction()
    {

        return $this->render('LSIMarketBundle::un_concept_innovant.html.twig');
    }


    public
    function envoiMailContactAction(Request $request)
    {
        $statusCode = 200;
        $success = true;
        $data = null;
        $messageReturn = 'Votre message a été envoyé avec succes.';
        $contact = new Contact();
        $contactForm = $this->createForm(ContactType::class, $contact);

        if ($request->isXmlHttpRequest() && $contactForm->handleRequest($request)->isValid()) {
            $contact = $contactForm->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($contact);
            $mailer = $this->container->get('lsi_market.email.civilink_mailer');
            $mailer->sendMailSupport($contact);
            $em->flush();
        } else {
            $statusCode = 500;
            $success = false;
            $data = null;
            $messageReturn = 'Une erreur est survenue lors de l\'envoi du message';
        }

        return $this->json([
            'success' => $success,
            'data' => $data,
            'message' => $messageReturn
        ], $statusCode);
    }

    public function envoiMailContactAnnonceAction(Request $request)
    {
        // initailisation des variables
        $statusCode = 200;
        $success = true;
        $data = null;
        $messageReturn = 'Votre message a été envoyé avec succes.';
        $contact = new Contact();
        $contactForm = $this->createForm(ContactType::class, $contact)
            ->add('contactLocation', TextType::class)
            ->add('contactContext', HiddenType::class)
            ->add('contactContextId', HiddenType::class)
            ->add('contactLocation', TextType::class)
            ->add('contactPhone', TextType::class);;
        $contactForm->handleRequest($request);
        if ($request->isXmlHttpRequest()) {
            $contact = $contactForm->getData();
            $annonceId = $contact->getContactContextId();
            $em = $this->getDoctrine()->getManager();
            $annonce = $em->getRepository('LSIMarketBundle:Annonce')->find($annonceId);
            $auteur  = $em->getRepository('LSI\MarketBundle\Entity\User')->findAuteurAnnonce($annonce->getMairie()->getId());
            if (!empty($annonce) && !empty($auteur)) {
                $em->persist($contact);
                $receiver = current(auteur)->getEmail();
                $mailer = $this->container->get('lsi_market.email.civilink_mailer');
                $mailer->sendMailSupport($contact,$receiver);
                $em->flush();
            } else {
                $statusCode = 404;
                $success = false;
                $data = null;
                $messageReturn = 'La ressource demandée est introuvable';
            }
        } else {
            $statusCode = 500;
            $success = false;
            $data = null;
            $messageReturn = 'Une erreur est survenue lors de l\'envoi du message';
        }
        return $this->json([
            'success' => $success,
            'data' => $data,
            'message' => $messageReturn
        ], $statusCode);

    }


 /**
     * @param Request $request
     * @param $datedebut
     * @param $datefin
     */
    private function setRechercheIndexCookie(Request $request, $datedebut, $datefin)
    {

        $crypt = $this->getRechercheIndexCookieKey();
        $date =['debut' =>$datedebut , 'fin' => $datefin] ;
        $content = serialize($date);
        $cookie = new Cookie($crypt, base64_encode($content), time() + 86400);
        $response = new Response();
        $response->headers->setCookie($cookie);
        $response->send();
    }

    /**
     * @param Request $request
     * @return array|null
     */
    private function getRechercheIndexCookie(Request $request)
    {
        $crypt = $this->getRechercheIndexCookieKey();

        if ($date = $request->cookies->get($crypt)) {
            return unserialize(base64_decode($date));
        }

        return null;
    }

    /**
     * @return string
     */
    private function getRechercheIndexCookieKey()
    {
        $private_key = 'marketcookiedate'; // Paramétrage de la clé de cryptage du cookie

        $crypt = $this->crypt($private_key, "dateofrech");
        return $crypt;
    }


    /* FIN  */

}