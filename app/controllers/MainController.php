<?php

class MainController extends Controller
{
    public function indexAction()
    {
        $modelFilm = new Film();
        $this->view->films = $modelFilm->fetchAll();
    }

	public function filmsAction()
	{
        $modelFilm = new Film();
        $categModel = new Categorie();
        $FilmCategModel = new FilmCateg();

        $searchTitre = $this->_getParam('titre');
        $delete = $this->_getParam('iddelete');
        if($delete != '') {
            $this->view->delete = $modelFilm->delete($delete);
            $FilmCategModel->deleteCategFilm($delete);
        }
        $films = $modelFilm->fetchAll('titre asc');
        if($searchTitre!='') {
            $films = $modelFilm->searchByTitre($searchTitre);
            $this->view->search = $searchTitre;
        }
        $categs=[];
        foreach($films as $film){
            $categs[$film->id] =
                $categModel->categorieFilm($film->id);
        }
//print_r($categs);die();
        $this->view->films = $films;
        $this->view->categs = $categs;
        $this->view->allCateg = $categModel->fetchAll('nom asc');
        $this->view->session = $_SESSION['user']['profil'];
	}


	public function categoriesAction()
	{
        $cats = new Categorie();

        $delete = $this->_getParam('iddelete');
        if($delete != '')
            $this->view->delete = $cats->delete($delete);

        $categs = $cats->fetchAll('nom asc');
        $this->view->categs = $categs;
        $this->view->session = $_SESSION['user']['profil'];
	}

	public function categorieAction()
	{
	    $id = $_GET['id'];
	    $cats = new Categorie();
        $categ = $cats->fetchOne($id);

		$this->view->categ = $categ;
	}

	public function addcategAction()
	{
	    if($_SESSION['user']['profil']->role!=2) die("Vous n'avez pas la permission de vous trouver ici");

        if(isset($_POST['categorie'])){
            $nom  = $_POST['categorie'];
            $alias = Utils::generateSlug($nom);

            $params = [
                'nom' => $nom,
                'slug' => $alias
            ];

            $cat = new Categorie();
            Utils::message($cat->save($params),
                'la categorie a bien été ajoutée',
                'la catégorie n\'a pas pu être ajoutée');



            header('Location: '.WEB_ROOT.'/categories');
            exit();
        }
	}

	public function addfilmAction()
	{
        if($_SESSION['user']['profil']->role!=2) die("Vous n'avez pas la permission de vous trouver ici");

        $cat = new Categorie();
        $filmModel = new Film();
        $filmCateg = new FilmCateg();
        $this->view->cats = $cat->fetchAll();

        $idParam              = $this->_getParam('id');
        if($idParam!='')
            $filmUpdate = $filmModel->fetchOne($idParam);

        $titre           = $this->_getParam('titre');
        $categ           = $this->_getParam('categ');
        $description     = $this->_getParam('description');
        $date_sortie     = $this->_getParam('date_sortie');
        $prix     = $this->_getParam('prix');


        if($titre!='' && count($categ>0) && $description!='' && $date_sortie!=''){

            $alias = Utils::generateSlug($titre);
            if($_FILES['image']['name']!='') {
                $titleImg = md5(uniqid(rand(), true));
                $extensions_valides = array('jpg', 'jpeg', 'gif', 'png');
                $extension_upload = strtolower(substr(strrchr($_FILES['image']['name'], '.'), 1));
                $titleImg = $titleImg . '.' . $extension_upload;
                Utils::upload('image', 'images/film/' .$titleImg , FALSE, false);
            }elseif($idParam!='')
                $titleImg= $filmUpdate->image;
            else
                $titleImg="default.jpg";

            $date = explode('/', $date_sortie);
            $dateFormatBase = $date[2].'-'.$date[1].'-'.$date[0];


            $params = [
                'titre' => $titre,
                'image' => $titleImg,
                'slug' => $alias,
                'description' => $description,
                'prix' => $prix,
                'date_sortie' => $dateFormatBase
            ];
            if($idParam!='')$params['id'] = $idParam;

            $insertReturn = $filmModel->save($params);
            if($insertReturn>0){
                if($idParam!='')$filmCateg->deleteCategFilm($idParam);

                foreach ($categ as $cate){
                    $params=[
                        'id_film' => ($idParam)?$idParam:$insertReturn,
                        'id_categorie' => $cate
                    ];
                    $filmCateg->save($params);
                }
            }

            $this->view->reussite = $insertReturn;

        }
        if($idParam!='') {
            $this->view->film = $filmModel->fetchOne($idParam);
            $this->view->categs = $cat->categorieFilm($idParam, PDO::FETCH_ASSOC);
        }
	}

	public function filmAction (){
        $id         = $this->_getParam('id');
        $comment         = $this->_getParam('comment');

        $com = new Commentaire();
        if($comment){
            $optradio         = $this->_getParam('optradio');
            $id_user          = $_SESSION['user']['profil']->id;

            $this->view->commInsert = $com->save([
                'id_user' => $id_user,
                'id_film' => $id,
                'commentaire' => $comment,
                'note' => $optradio
            ]);
        }

        $commentaires = $com->searchByFilm($id);

        $cat = new Categorie();
        $filmModel = new Film();
        $this->view->film = $filmModel->fetchOne($id);
        $this->view->categs = $cat->categorieFilm($id);
        $this->view->comms = $commentaires;
        $this->view->nbcomms = count($commentaires);
        $this->view->id = $id;
        $this->view->user = $_SESSION['user']['profil'];
    }

}
