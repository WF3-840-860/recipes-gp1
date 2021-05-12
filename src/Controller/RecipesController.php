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
    public function add(EntityManagerInterface $em): Response
    {
        if(!empty ($_POST)){

            $article = new Articles();  // Je prépare l'instance de classe (donc un objet)

            $this->addEdit($em, $article);
            return $this->redirectToRoute('recipes_index');
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
        $article = $em->getRepository(Articles::class)->find($id); // Je récupère mon instance de classe via l'article trouvé

        if (!$article) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }
        if(!empty ($_POST)){

         
            $this->addEdit($em, $article);
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

    private function addEdit(EntityManagerInterface $em, Articles $article) :void
    {
       
        $em = $this->getDoctrine()->getManager();
      
        if (!empty($_POST)) {
            $post = array_map('trim', array_map('strip_tags', $_POST));
            if (!empty($_FILES)) {
                 $tmp_name = $_FILES["recipe_image"]["tmp_name"];
            // verification de fichier
            // TODO
                 $fichier =  uniqid() . '.jpg' ;
            // . pathinfo($image, PATHINFO_EXTENSION)

            // On va copier le fichier dans le dossier upload
                $newfile = $this->getParameter('images_directory'). "/"  . $fichier;
                 move_uploaded_file($tmp_name, $newfile);
            }else{
              


            }
            $article->setTitle($post['title']);
            $article->setContent($post['content']);
            $article->setIngredient($post['ingredient']);
            $article->setCreatedAt(new \DateTime());
            $article->setDuration($post['duration']);
            $article->setNbPerson($post['nb_person']);

            // Update de l'image que si elle existe ou si elle est différente
            if(!empty($article->getRecipeImage()) && $article->getRecipeImage() != $fichier){
                $article->setRecipeImage($fichier);
            }
            elseif(empty($article->getRecipeImage())){ // Ajout
                $article->setRecipeImage($fichier);
            }

            // ajout 
            if(empty($article->getId())){
                $em->persist($article);
            }
            

            $em->flush();

            $this->addFlash('success', 'Modification effectuée');
        }else {
            $this->addFlash('warning', 'Veuillez ressaisir les champs !');
        }

    }
}
