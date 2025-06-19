<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\Upload;
use App\Utility\Mail;
use \Core\View;

/**
 * Product controller
 */
class Product extends \Core\Controller
{

    /**
     * Affiche la page d'ajout
     * @return void
     */
    public function indexAction()
    {

        if (isset($_POST['submit'])) {

            $f = $_POST;

            $allowed_ext = array('png', 'jpeg', 'jpg');
            $pic_ext = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
            if (!in_array($pic_ext, $allowed_ext)) {
                $err_mes = "Les formats autorisés sont .jpg, .jpeg et .png";
                View::renderTemplate('Product/Add.html', ['error message' => $err_mes]);
                exit();
            } else {
                $f['user_id'] = $_SESSION['user']['id'];
                $id = Articles::save($f);

                $pic_name = Upload::uploadFile($_FILES['picture'], $id);

                Articles::attachPicture($id, $pic_name);

                header('Location: /product/' . $id);
            }
        }

        View::renderTemplate('Product/Add.html');
    }

    /**
     * Affiche la page d'un produit
     * @return void
     */
    public function showAction()
    {
        $prod_id = $this->route_params['id'];

        try {
            Articles::addOneView($prod_id);
            $suggestions = Articles::getSuggest();
            $article = Articles::getOne($prod_id);
        } catch (\Exception $e) {
            var_dump($e);
        }

        View::renderTemplate('Product/Show.html', [
            'article' => $article[0],
            'suggestions' => $suggestions
        ]);
    }

    public function contactAction()
    {
        if (!isset($_SESSION["user"])) {
            header("location: /login");
        } else {
            if ($_SERVER["REQUEST_METHOD"] == "GET") {

                $prod_id = $_GET["product_id"];
                $article = Articles::getOne($prod_id);

                $success = false;

                if (array_key_exists("success", $_GET)) {
                    $success = true;
                }

                View::renderTemplate('Product/Contact.html', [
                    'success' => $success,
                    'article' =>  $article[0]
                ]);
            } else if ($_SERVER["REQUEST_METHOD"] == "POST") {

                $mes = $_POST["message"];
                $email = $_POST["email"];

                Mail::sendMail($recv = $email, $content = $mes);

                $success = "Votre message a été envoyé avec succès !";
                header("location: " . $_SERVER['REQUEST_URI'] . "&success=true");
            }
        }
    }
}
