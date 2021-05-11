<?php

namespace App\Controller;

use App\Entity\Articles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class RecipesController extends AbstractController
{
    /**
     * @Route("/recipes", name="recipes")
     */
    public function index(): Response
    {
        return $this->render('recipes/index.html.twig', [
            'controller_name' => 'RecipesController',
        ]);
    }
    /**
     * @Route("/recipes/add", name="add_recipes")
     */
    public function add(): Response
    {
        if (!empty($_POST)) {

            $post = array_map('trim', array_map('strip_tags', $_POST));

           /* foreach($_POST as $key => $value){
                $post[$key] = trim(strip_tags($value));  
            }*/

            if (!empty($post['title']) && strlen($post['title']) > 5 ) {

                
                // Equivalent du new PDO('mysql:....'); ou du manager
                $entityManager = $this->getDoctrine()->getManager();
                
                // Equivalent de la requete INSERT INTO
                $recipe = new Articles();
                $recipe->setTitle($post['title']); // Equivalent d'un bindParam()
                $recipe->setContent($post['content']);
                $recipe->setIngredient($post['ingredient']);
                $recipe->setCreatedAt(new \DateTime());
                $recipe->setDuration($post['duration']);
                $recipe->setNbPerson($post['nb_person']);

                // Equivalent prepare()
                $entityManager->persist($recipe);

                // equivalent du execute() ou exec()
                $entityManager->flush();

                return $this->redirectToRoute('recipes');


            }
        }

        return $this->render('recipes/add.html.twig');
      
    }
}
