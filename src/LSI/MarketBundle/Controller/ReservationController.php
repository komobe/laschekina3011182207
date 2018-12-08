<?php
namespace LSI\MarketBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

use LSI\MarketBundle\Entity\Reserver;

use LSI\MarketBundle\Form\ReserverType;

use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\Intl\DateFormatter\IntlDateFormatter;



class ReservationController extends BaseController {

    // Passer une réservation

    public function reserverAction(Request $request, $id) {

        $this->denyAccessUnlessGranted(['ROLE_MAIRIE', 'ROLE_PART'], $this->redirectToRoute('fos_user_security_login'));

        $em = $this->getDoctrine()->getManager();

        $reserver = new Reserver();

        $annonce = $em->getRepository('LSIMarketBundle:Annonce')->find($id);

        $auth = $em->getRepository('LSIMarketBundle:User')

            ->findAuteurAnnonce($annonce->getMairie()->getId());

        if($request->isMethod('GET')){

            // Recuperation des date saisies lors de la derniere recherche(S'il y a )
            $dates =  $this->getRechercheIndexCookie($request);

            if ($dates!==null) {

                $today = new \DateTime('now');
//                $today = $today->format('Y-m-d');
                $tomorrow = new \DateTime('tomorrow');
//                $tomorrow = $tomorrow->format('Y-m-d');
                $datedebut = new \DateTime($dates['debut']);

                if($datedebut <= $today){
                    $datedebut = $tomorrow;
                }

                $datefin = new \DateTime($dates['fin']);

                $interval = $datefin->diff($datedebut)->format('%a'); // Nombre de jours
                $montant_reservation =  $annonce->getPrixDefaut() * $interval;
                $budget_restant = $auth[0]->getMairie()->getBudgetRestant() - $montant_reservation;

                $reserver->setDebutPrestation( $datedebut);
                $reserver->setFinPrestation( $datefin);
                $reserver->setMontantReservation($montant_reservation);
                $reserver->setBudgetRestant($budget_restant);

            }
        }

        $form = $this->createForm(ReserverType::class, $reserver);

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



            $mailer = $this->container->get('lsi_market.email.civilink_mailer');

            $userClient  = $auth[0];

            $userAnnonceur  = $this->getUser();

            $mailer->sendMailReservation($userAnnonceur,$userClient,$annonce);

            $mailer->sendMailConfirmationReservation($userAnnonceur,$userClient,$annonce);



            $request->getSession()->getFlashBag()->add('info', 'Reservation effectuee, un message a ete envoyer a l\'offreur');



            // Redirection dans l'espace mes réservations du user

            return $this->redirectToRoute('ls_imarket_mes_reservations');

        }


        return $this->render('LSIMarketBundle:reservation:reserver.html.twig', array(

            'form' => $form->createView(),

            'annonce' => $annonce,

        ));



    }



    public function montantReservationAction(Request $request){

        $em = $this->getDoctrine()->getManager();

        if ($request->isXmlHttpRequest()){

            // Récupération des variables...

            $debut = $request->request->get('debutPrest');

            $fin = $request->request->get('fin');

            $prix = $request->request->get('prixPrest');

            $budget = $request->request->get('budget');

            // Fin récupération des variables...


            $dt1 = new \DateTime($debut); // reformatage des dates au format aaaa-mm-jj

            $dt2 = new \DateTime($fin);// reformatage des dates au format aaaa-mm-jj


            $duree = $dt1->diff($dt2); // Calcul de la durée de la prestation

            $duree = $duree->format('%R%a');

            //dump($dt1);



            $prixHt = $duree * $prix; // Calcul du prix de la prestation

            $budget = $budget - $prixHt; // Calcul du budget restant



            $results = array('prixHt' => $prixHt, 'budgetRest' => $budget);



            return new JsonResponse(array(

                'status' => 'OK',

                'message' => $results),

                200);

        }



        return new JsonResponse(array(

            'status' => 'Error',

            'message' => 'Error'),

            400);

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



    public function reservationsSurMesAnnoncesAction() {

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



    // traitement pour consulter les réservations sur annonces

    public function voirReservationAction(Request $request, $id) {

        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('LSIMarketBundle:Reserver');

        $reserver = $repository->voirReserver($id);



        // Récupère les villes du même EPCI

        $villesEpci = $em->getRepository('LSIMarketBundle:Ville')

            ->findVillesEpci($reserver[0]->getAnnonce()->getAdresse()->getVille()->getEpci()->getId());



        $debut = $reserver[0]->getDebutPrestation();

        $fin = $reserver[0]->getFinPrestation();

        $prix = $reserver[0]->getAnnonce()->getPrixDefaut();

        // Formattage des dates

        $formatter = new IntlDateFormatter("en", IntlDateFormatter::MEDIUM,

            IntlDateFormatter::NONE, 'Europe/Paris',IntlDateFormatter::GREGORIAN, 'yyyy-MM-dd');



        $deb = $formatter->format($debut);

        $finn = $formatter->format($fin);



        $start = new \DateTime($deb);

        $end = new \DateTime($finn);



        $duree = $start->diff($end); // Calcul de la durée de la prestation

        $duree = $duree->format('%d');



        $prixHt = $duree * $prix;



        return $this->render('LSIMarketBundle:market:voir_reservation.html.twig',

            array('reservation' => $reserver[0], 'duree' => $duree, 

                'prixHt' => $prixHt, 'villesEpci' => $villesEpci));

    }



    // Action pour traiter la validation d'une réservation par l'offreur

    public function validerReservationAction(Request $request, $id)

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

    public function refuserReservationAction(Request $request, $id)

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



    // Traitement de l'annulation d'une réservation par le demandeur après validation de l'offreur'

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





    public function reservationAttenteValidationAction(){

        $em = $this->getDoctrine()->getManager();



        $userId = $this->getUser()->getMairie()->getId();



        $reservationAttente = $em->getRepository('LSIMarketBundle:Reserver')->findReservationAttente($userId);



        return $this->render('LSIMarketBundle:reservation:reservation_attente.html.twig', array(

            'reservationAttente' => $reservationAttente,

        ));

    }



    public function reservationValideeAction(){

        $em = $this->getDoctrine()->getManager();

        $userId = $this->getUser();



        $id = "";

        if ($userId->getMairie() !== null) $id = $userId->getMairie()->getId();

        else if ($userId->getAdministre() !== null) $id = $userId->getAdministre()->getId();



        $reservationValidee = $em->getRepository('LSIMarketBundle:Reserver')->findReservationValidee($id);



        return $this->render('LSIMarketBundle:reservation:reservation_validee.html.twig', array(

            'reservationValidee' => $reservationValidee,

        ));

    }



    public function reservationRefuseeAction(){

        $em = $this->getDoctrine()->getManager();

        $userId = $this->getUser();



        $id = "";

        if ($userId->getMairie() !== null) $id = $userId->getMairie()->getId();

        else if ($userId->getAdministre() !== null) $id = $userId->getAdministre()->getId();

        

        $reservationRefusee = $em->getRepository('LSIMarketBundle:Reserver')->findReservationRefusee($id);



        return $this->render('LSIMarketBundle:reservation:reservation_refusee.html.twig', array(

            'reservationRefusee' => $reservationRefusee,

        ));

    }



}