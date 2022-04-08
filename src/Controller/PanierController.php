<?php

namespace App\Controller;


use DateTime;
use App\Entity\User;
use App\Entity\Produit;
use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/mon-panier")
 */
class PanierController extends AbstractController
{
    /**
     * @Route("/voir-mon-panier", name="show_panier", methods={"GET"})
     */
    public function showPanier(SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);

        $total = 0;

        foreach($panier as  $item){
            $totalItem = $item['produit']->getPrice() * $item['quantity'];
            $total += $totalItem; # => $total = $total +$totalItem;
        }
        


// dd($panier);

        return $this->render("panier/show_panier.html.twig", [
            'total' => $total
        ]);
    }

    /**
     * @Route("/ajouter-un-produit/{id}", name="panier_add", methods={"GET"})
     */
    public function add(Produit $produit, SessionInterface $session): Response
    {
        // Si dans la session le panier n'existe pas, alors la méthode get() retournera le second paramètre, un array vide.
        $panier = $session->get('panier', []);

// dump($panier);

        if (!empty($panier[$produit->getId()])) {
            ++$panier[$produit->getId()]['quantity'];
        } else {
            $panier[$produit->getId()]['quantity'] = 1;
            $panier[$produit->getId()]['produit'] = $produit;
        }

        // Ici, nous devons set() le panier en session, en lui passant notre $panier[]
        $session->set('panier', $panier);

        // dd($session->getBag('attributes'));
        $this->addFlash('success', 'Le produit a été ajouté à votre panier');
        return $this->redirectToRoute('default_home');
    }
    
    /**
     * @Route("/vider-mon-panier", name="empty_panier", methods={"GET"})
     */
    public function emptyPanier(SessionInterface $session): Response

    {    
        // La méthode remove() permet de supprimer un attribut de la $session
        $session->remove('panier');
       


        return $this->redirectToRoute('show_panier');

    }

     /**
     * @Route("/retirer-du-panier/{id}", name="panier_remove", methods={"GET"})
     */
    public function delete(int $id, SessionInterface $session): Response
    {    
        $panier = $session->get('panier');

        // array_key_exists() esy une fonction native de php qui permet de verifier si une key existe dans un array.
        # cette fonction 2 arguments = la valeur à verifier, le tableau dans lequel rechercher.
        if(array_key_exists($id, $panier)) {
        // unset() est une fonction native de php qui permet de supprimer une variable. (ou une key dans un array)
            unset($panier[$id]);

        } else {
            $this->addFlash('warning', "Ce produit n'est pas dans votre panier.");
        }

        $session->set('panier', $panier);
    
       
        return $this->redirectToRoute('show_panier');

    }

    /**
     * @Route("/valider-mon-panier", name="panier_validate", methods={"GET"})
     */
    public function validate(SessionInterface $session, EntityManagerInterface $entityManager): Response
    {

        $panier = $session->get('panier', []);

        if(empty($panier)){
            
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('show_panier');


        } else {

            $commande = new Commande();
            $user = $entityManager->getRepository(User::class)->find($this->getUser());

            $commande->setCreatedAt((new DateTime()));
            $commande->setUpdatedAt((new DateTime()));

            $commande->setState('en cours');
            $commande->setUser($user);

            
            $total = 0;

            foreach($panier as  $item){
                $totalItem = $item['produit']->getPrice() * $item['quantity'];
                $total += $totalItem;

                $commande->addProduct($item['produit']);
                
            }
            
            $commande->setTotal($total);
            $commande->setQuantity(count($panier));

        

            $entityManager->persist($commande);
            $entityManager->flush();


            
            $session->remove('panier');

            $this->addFlash('success', "Félicitation, votre commande est en cours. Vous pouvez la retrouver dans Mes commandes");
            return $this->redirectToRoute('show_account');

        }


    }
}
