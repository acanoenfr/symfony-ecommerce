<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ResetPasswordController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/mot-de-passe-oublie", name="reset_password")
     */
    public function index(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        if ($request->get('email')) {
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneByEmail($request->get('email'));

            if ($user) {
                $reset_password = new ResetPassword();

                $reset_password->setUser($user);
                $reset_password->setToken(uniqid());
                $reset_password->setCreatedAt(new \DateTime());

                $this->entityManager->persist($reset_password);
                $this->entityManager->flush();

                $url = $this->generateUrl('update_password', [
                    'token' => $reset_password->getToken()
                ]);

                $mail = new Mail();
                $content = "Bonjour {$user->getFirstname()},<br/>Vous avez demandé à réinitialiser votre mot de passe sur le site La Boutique Française.<br/><br/>";
                $content .= "Merci de bien vouloir cliquer sur le lien suivant pour <a href='$url'>mettre à jour votre mot de passe</a>.";

                $mail->send($user->getEmail(), $user->getFullname(), "Réinitialiser votre mot de passe sur La Boutique Française", $content);

                $this->addFlash('notice', 'Vous allez recevoir dans quelques secondes un mail avec la procédure pour réinitialiser votre mot de passe.');
            } else {
                $this->addFlash('notice', 'Cet adresse email est inconnue.');
            }
        }

        return $this->render('reset_password/index.html.twig');
    }

    /**
     * @Route("/modifier-mon-mot-de-passe/{token}", name="update_password")
     */
    public function update(Request $request, UserPasswordEncoderInterface $encoder, $token): Response
    {
        $reset_password = $this->entityManager
            ->getRepository(ResetPassword::class)
            ->findOneByToken($token);

        if (!$reset_password) {
            return $this->redirectToRoute('reset_password');
        }

        $now = new \DateTime();

        if ($now > $reset_password->getCreatedAt()->modify('+ 3 hour')) {
            $this->addFlash('notice', 'Votre demande de mot de passe a expiré. Merci de la renouveller.');
            return $this->redirectToRoute('reset_password');
        }

        $form = $this->createForm(ResetPasswordType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new_pwd = $form->get('new_password')->getData();
            $reset_password->getUser()->setPassword($encoder->encodePassword($reset_password->getUser(), $new_pwd));
            $this->entityManager->flush();

            $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/update.html.twig', [
            'form' => $form->createView()
        ]);

    }
}
