<?php

namespace App\Controller;

use App\Entity\Articles;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticlesRepository;
use Doctrine\ORM\EntityManagerInterface;


class RecipesController extends AbstractController
{
    /**
     * la liste des recettes
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
     *  ajouter une recette
     * @Route("/recipes/add", name="recipes_add")
     */
    public function add(EntityManagerInterface $em): Response
    {
        // On vérifie que mon formulaire soit rempli
        if(!empty ($_POST)){

            $post = array_map('trim', array_map('strip_tags', $_POST));

            $article = new Articles();  // Je prépare l'instance de classe (donc un objet)

            $formIsValid = $this->addEdit($em, $article);

            if ($formIsValid['status'] == false){
                
                foreach($formIsValid['errors'] as $error){
                    $this->addFlash('warning', $error);
                }
                
                return $this->render('recipes/add.html.twig',  [
                    'post' => $post ?? array()
                ]);
            }
            else{
                $this->addFlash('success', 'Modification effectuée');
                return $this->redirectToRoute('recipes_index');
            }    
        }

        return $this->render('recipes/add.html.twig',  [
            'post' => $post ?? array()
        ]);

    }

    /**
     * ajouter une recette
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

            // fonction de vérification des valeurs du formulaire
            $formIsValid = $this->addEdit($em, $article);
            // Si on vérifie le status 
            if ($formIsValid['status'] == false){
                // le formulaire retourne des erreurs et on les affiche
                foreach($formIsValid['errors'] as $error){
                    $this->addFlash('warning', $error);
                }

                // redirection vers le formulaire edit avec les données existantes
                return $this->redirectToRoute('recipes_edit', [
                    'id' => $article->getId(),
                    'post' => $_POST
                ]);
            }
            else
            {
                // pas d'erreurs, et redirection vers la page show
                $this->addFlash('success', 'Modification effectuée');
                return $this->redirectToRoute('recipes_show', [
                    'id' => $article->getId(),
                    'article' => $article
                ]);
            }

        }
        return $this->render('recipes/edit.html.twig', [
            'id' => $article->getId(),
            'article' => $article
        ]);
    }



    /**
     * @Route("/recipes/show/{id}", name="recipes_show", methods={"GET"})
     */
    public function show(EntityManagerInterface $em, int $id): Response
    {
        $repository = $em->getRepository(Articles::class);
        return $this->render('recipes/show.html.twig', [
            'article' => $repository->findOneBy(['id' => $id]),
        ]);
    }

    private function addEdit(EntityManagerInterface $em, Articles $article) :array
    {
        $errors = [];
        $em = $this->getDoctrine()->getManager();
      
        if (!empty($_POST)) {

            $post = array_map('trim', array_map('strip_tags', $_POST));

            $formIsValid = $this->validForm($post);
            if ($formIsValid['status'] == false){
                // error
                $errors += $formIsValid['errors'];
            }
            else{
                $uploadIsValid =  $this->getCheckUpload($article);
                if ($uploadIsValid['status'] == false){
                    // error
                    $errors += $uploadIsValid['errors'];
                }
            }
            if(empty($errors)){
                // succes
                $article->setTitle($post['title']);
                $article->setContent($post['content']);
                $article->setIngredient($post['ingredient']);
                $article->setCreatedAt(new \DateTime());
                $article->setDuration($post['duration']);
                $article->setNbPerson($post['nb_person']);

                // Update de l'image que si elle existe ou si elle est différente
                if(!empty($article->getRecipeImage()) && $article->getRecipeImage() != $uploadIsValid['file']){
                    $article->setRecipeImage($uploadIsValid['file']);
                }
                elseif(empty($article->getRecipeImage())){ // Ajout
                    $article->setRecipeImage($uploadIsValid['file']);
                }

                // ajout 
                if(empty($article->getId())){
                    $em->persist($article);
                }
                $em->flush(); 
            }
        }
        
        // Ecriture ternaire (condition ) ? 'si vrai' : 'sinon'
        //return (!empty($errors)) ? ['status' => false, 'errors' => $errors] : ['status' => true, 'errors' => false];

        if(!empty($errors)){
            return [
                'status' => false, 
                'errors' => $errors
            ]; 
        }
        else  {
            return [
                'status' => true, 
                'errors' => []
            ];
        }
    }

    private function validForm(array $post):array
    {

        $errors =[];

        //On vérifie que le champs title est rempli
        if (empty($post['title'])) {
            $errors[] = ' Le champ Titre est invalide';
        }
        //On vérifie que le champs title a longueur comprise entre 5 et 120 caractères
        if (strlen($post['title']) < 5 || strlen($post['title']) > 120){
            $errors[] = 'La longueur du champ Titre est comprise entre 5 et 120 caractères';
        }
        //On vérifie que le champs content est rempli
        if (empty($post['content'])) {
            $errors[] = ' Le champ Contenu est invalide';
        }
        //On vérifie que le champs content a longueur minimum de 5 caractères
        if (strlen($post['content']) < 5){
            $errors[] = 'La longueur du champ Contenu est invalide';
        }
        //On vérifie que le champs ingredient est rempli
        if (empty($post['ingredient'])) {
            $errors[] = ' Le champ Ingredient est invalide';
        }
        //On vérifie que le champs ingredient a longueur minimum de 15 caractères
        if (strlen($post['ingredient']) < 15){
            $errors[] = 'La longueur du champ Ingredient est invalide';
        }
        //On vérifie que le champs duration est rempli et que le champs duration est un entier
        if ($post['duration'] <= 0 || !is_numeric($post['duration']) ) {
            $errors[] = ' Le champ Duree est invalide';
        }

        //On vérifie que le champs nb_person est rempli et que le champs nb_person est un entier
        if ($post['nb_person'] <= 0 || !is_numeric($post['nb_person']) ) {
            $errors[] = ' Le champ Nombre de personne est invalide';
        }

        // Si des erreurs sont détectés, on revoie un status à false et un tableau d'erreurs
        // sinon on revoie un status à true et un tableau d'erreurs vide
        return (!empty($errors)) ? ['status' => false, 'errors' => $errors] : ['status' => true, 'errors' => false];  
    }

    private function getCheckUpload(Articles $article) :array
    {
        $fichier = null;
        $errors =[];

        // cas add 
        if (!empty($_FILES)) {


            $target_dir = $this->getParameter('images_directory'). "/" ; // uploads directory
            $file = basename($_FILES['recipe_image']['name']);
            $target_file = $target_dir .$file;
            $max_size = 5242880;
            $size = $_FILES['recipe_image']['size'];
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
            $extensions = array('png', 'gif', 'jpg', 'jpeg');
            $tmp_name = $_FILES["recipe_image"]["tmp_name"];
            
            // verification de l'extension du fichier à uploader
            if (!in_array($imageFileType, $extensions)){
                $errors[] = "l'extension du fichier n'est pas reconnu ['png', 'gif', 'jpg', 'jpeg']" . $file;
            }

            // verification de la taille du fichier à uploader
            if ($size > $max_size){
                $errors[] = 'La taille du fichier dépasse la taille maxi ' . $max_size;
            }

            // Si pas d'erreurs, alors on upload le fichier
            if(count($errors) == 0)
            {
                // Génère un identifiant unique
                $fichier =  uniqid() . $imageFileType ;

                // On va copier le fichier dans le dossier upload
                $newfile = $target_dir . $fichier;
                if(!move_uploaded_file($tmp_name, $newfile)){
                    $errors[] = 'Une erreur grave est survenue';
                }
            }

        }
        else 
        {
            //   cas edit
            if ($article->getRecipeImage()){
                $fichier = $article->getRecipeImage();
            }
        
        }

        // Si des erreurs sont détectés, on revoie un status à false, un tableau d'erreurs, et le fichier
        // sinon on revoie un status à true,un tableau d'erreurs vide, et le fichier
        return (!empty($errors)) ? ['status' => false, 'errors' => $errors, 'file' => $fichier] : 
                                   ['status' => true, 'errors' => false, 'file' => $fichier]; 
    }

 } //class
