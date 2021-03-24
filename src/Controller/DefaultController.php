<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="default")
     */
    public function index(): Response
    {
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }

    /**
     * @Route("/mail", name="send_mail")
     */
    public function sendMail(MailerInterface $mailer)
    {
        $email = (new TemplatedEmail())
            ->to('david.annebicque@gmail.com')
            ->from('david.annebicque@univ-reims.fr')
            ->subject('Test d un mail')
            ->htmlTemplate('mail/mail.html.twig')
            ->context([
                'prenom' => 'david',
                'nom' => 'Annebicque',
            ]);

        $mailer->send($email);
    }
}
