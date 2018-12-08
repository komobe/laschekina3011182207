<?php

namespace LSI\MarketBundle\Email;


use LSI\MarketBundle\Entity\Annonce;
use LSI\MarketBundle\Entity\User;
use LSI\MarketBundle\Entity\Contact;
use Symfony\Component\Templating\EngineInterface;

class EnvoyerMail
{

    /**
     * @var \Swift_Mailer
     */
    private $mailer;
    // Modèle du mail
    private $templating;
    // Expéditeur
    private $from;

    private $support;
    // Nom auteur mail
    private $name = 'Civilink';


    public function __construct(\Swift_Mailer $mailer, $sender, $support, EngineInterface $templating)
    {
        $this->mailer = $mailer;
        $this->from = $sender;
        $this->templating = $templating;
        $this->support = $support;
    }

    /**
     * Méthode générale pour l'envoi de mail
     * @param $to "Designe le destinataire"
     * @param $subject "le sujet du mail"
     * @param $body "le corps du mail"
     */
    public function sendMail($to, $subject, $body)
    {
        $message = \Swift_Message::newInstance()
            ->setFrom($this->from, $this->name)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body)
            ->setContentType('text/html');

        return $this->mailer->send($message);
    }

    public function sendMailWithReply($reply, $subject, $body)
    {
        $message = \Swift_Message::newInstance()
            ->setFrom($this->from, $this->name)
            ->setTo($this->support)
            ->setReplyTo($reply, $this->name)
            ->setSubject($subject)
            ->setBody($body)
            ->setContentType('text/html');
        return $this->mailer->send($message);
    }

    public function sendMailSupport(Contact $contact, $receiver = null)
    {
        if (!empty($receiver)) {
            $this->support = $receiver;
        }
        $this->name = $contact->getContactFirstname();
        $sujet = "Contact internaute";
        $template = 'LSIMarketBundle:mail:mail_contact_support.html.twig';
        $body = $this->templating->render($template, array('contact' => $contact));
       return $this->sendMailWithReply($contact->getContactEmail(), $sujet, $body);
    }

    /**
     * Pour envoyer un mail à la création d'une annonce à l'auteur.
     * @param User $user
     *
     *,, Annonce $annonce
     */
    public function sendCreationAnnonceMail(User $user)
    {
        // Le sujet du mail
        $sujet = 'Votre annonce est maintenant visible';
        // La modèle du message à envoyer
        $template = 'LSIMarketBundle:mail:mail_annonce_cree.html.twig';
        // Le destinataire
        $to = $user->getEmail();
        // Le corps du message contenant le contenu du template, avec les variables, 'annonce' => $annonce user et annonce en paramètre
        $body = $this->templating->render($template, array('user' => $user));
        // Envoi du mail
        return $this->sendMail($to, $sujet, $body);
    }

    // Pas encore testé...
    public function sendNewMessageToCumtomer(User $user, Annonce $annonce)
    {
        $message = new \Swift_Message(
            'Nouvelle réservation',
            'Vous avez effectué une réservation sur l\'annonces ' . $annonce->getTitre()
        );

        $message
            ->addTo($user->getEmail());
       return $this->mailer->send($message);
    }

    public function sendMailReservation(User $annonceur, User $client, Annonce $annonce)
    {
        $sujet    = "Nouvelle réservation [no reply]";
        $template = 'LSIMarketBundle:mail:mail_reservation_to_annonceur.html.twig';
        $body     = $this->templating->render($template, array('annonce' => $annonce, 'client' => $client, 'annonceur' => $annonceur));
        return $this->sendMail($annonceur->getEmail(),$sujet,$body);
    }
    public function sendMailConfirmationReservation(User $annonceur, User $client, Annonce $annonce)
    {
        $sujet    = "Confirmation réservation [no reply]";
        $template = 'LSIMarketBundle:mail:mail_reservation_to_client.html.twig';
        $body     = $this->templating->render($template, array('annonce' => $annonce, 'client' => $client, 'annonceur' => $annonceur));
       return  $this->sendMail($client->getEmail(),$sujet,$body);
    }


    /*public function sendNewMessageToSeller($email, Annonce $annonce){
        $message = new \Swift_Message(
            'Vous avez reçu une nouvelle demande de réservation',
            '
                    Bonjour '.$annonce->getMairie().
                    ' Vous avez reçu une réservation sur votre annonce : '.$annonce->getTitre()
        );

        $message
            ->addTo($email);
        $this->mailer->send($message);
    }*/
}