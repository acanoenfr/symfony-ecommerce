<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderCancelController extends AbstractController
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
     * @Route("/commande/erreur/{stripeSessionId}", name="order_cancel")
     */
    public function index($stripeSessionId): Response
    {
        $order = $this->entityManager
            ->getRepository(Order::class)
            ->findOneByStripeSessionId($stripeSessionId);

        if (!$order || $order->getUser() != $this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $mail = new Mail();
        $content = "Bonjour ".$order->getUser()->getFirstname()."<br/>Une erreur est survenue lors du paiement de votre commande.<br/><br/>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam beatae culpa dolor doloremque harum maiores officiis porro qui rerum. Amet asperiores eius ipsa nisi vel? Dolorem enim neque quod velit?";
        $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), "Erreur lors du paiement de votre commande sur La Boutique Française", $content);

        return $this->render('order_cancel/index.html.twig', [
            'order' => $order
        ]);
    }
}
