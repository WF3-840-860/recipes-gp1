<?php

namespace App\Controller;

use App\Entity\Articles;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticlesRepository;
// use Symfony\Component\HttpFoundation\Session\Session;
// use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;


class RecipesController extends AbstractController
{
    /**
     * @Route("/recipes", name="recipes_index")
     */
    public function index(EntityManagerInterface $em): Response
    {
        $repository = $em->getRepository(Articles::class);

        return $this->render('recipes/index.html.twig', [
            'articles' => $repository->findAll(),
        ]);
    }

    /**
     * @Route("/recipes/add", name="recipes_add")
     */
    public function add(): Response
    {
        if (!empty($_POST)) {
            // dd($_POST);
            $post = array_map('trim', array_map('strip_tags', $_POST));

            /* foreach($_POST as $key => $value){
                $post[$key] = trim(strip_tags($value));  
            }*/

            if (
                !empty($post['title']) && strlen($post['title']) > 5 &&
                !empty($post['content']) && strlen($post['content']) > 5 &&
                !empty($post['ingredient']) && strlen($post['ingredient']) > 5 &&
                !empty($post['duration']) && is_numeric($post['duration']) && $post['duration'] > 0 &&
                !empty($post['nb_person']) && is_numeric($post['nb_person'])  && $post['nb_person'] > 0
            ) {


                // Equivalent du new PDO('mysql:....'); ou du manager
                $entityManager = $this->getDoctrine()->getManager();

                // Equivalent de la requete INSERT INTO
                $recipe = new Articles();
                $recipe->setTitle($post['title']); // Equivalent d'un bindParam()
                $recipe->setContent($post['content']);
                $recipe->setIngredient($post['ingredient']);
                $recipe->setCreatedAt(new DateTime());
                $recipe->setDuration($post['duration']);
                $recipe->setNbPerson($post['nb_person']);

                // Equivalent prepare()
                $entityManager->persist($recipe);

                // equivalent du execute() ou exec()
                $entityManager->flush();

                $this->addFlash('success', 'Recette postée avec succes!');
                return $this->redirectToRoute('recipes_index');
            } else {
                $this->addFlash('warning', 'Veuillez resaissir les champs !');
            }
        }

        return $this->render('recipes/add.html.twig');
    }

    /**
     * @Route("/recipes/show/{id}", name="recipes_show", methods={"GET"})
     */
    public function show(EntityManagerInterface $em, int $id): Response
    {
        $repository = $em->getRepository(Articles::class);
        // dd($repository->findOneBy(['id' => $id]));
        return $this->render('recipes/show.html.twig', [
            'article' => $repository->findOneBy(['id' => $id]),
        ]);
    }







    /**
     * @Route("/recipes/edit/{id}", name="recipes_edit", methods={"POST","GET"} )
     */
    public function edit(EntityManagerInterface $em, int $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $article = $em->getRepository(Articles::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }
        if (!empty($_POST)) {
            $post = array_map('trim', array_map('strip_tags', $_POST));


            $article->setTitle($post['title']);
            $article->setContent($post['content']);
            $article->setIngredient($post['ingredient']);
            $article->setCreatedAt(new \DateTime());
            $article->setDuration($post['duration']);
            $article->setNbPerson($post['nb_person']);

            $em->flush();

            $this->addFlash('success', 'Modification effectuée');
            return $this->redirectToRoute('recipes_show', [
            'id' => $article->getId(),
            'article' => $article
        ]);
        }
        return $this->render('recipes/edit.html.twig', [
            'id' => $article->getId(),
            'article' => $article
        ]);
    }
}
