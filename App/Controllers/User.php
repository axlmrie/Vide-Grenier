<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\Hash;
use \Core\View;
use Exception;
use App\Utility\Mail;
use App\Models\User as UserModel;

/**
 * User controller
 */
class User extends \Core\Controller
{

    /**
     * Affiche la page de login
     */
    public function loginAction()
{
    if (isset($_POST['submit'])) {
        $f = $_POST;

        if ($this->login($f)) {
            // Si login OK, redirige vers le compte
            if (isset($f['remember'])) {
                // Si la case "Se souvenir de moi" a été cochée, définir un cookie de session pour prolonger la durée de vie de la session
                $remember_expir = time() + 60 * 60 * 24 * 30; // expire dans 30 jours
                setcookie('remember_me', '1', $remember_expir);
            }
            header('Location: /account');
            exit();
        } else {
            // Sinon, affiche un message d'erreur et redirige vers la même page
            $err_mes = "Veuillez réessayer. Le mot de passe et/ou l'email est incorrect";
            View::renderTemplate('User/login.html', ['error message' => $err_mes]);
            exit();
        }
    }

    View::renderTemplate('User/login.html');
}

    /**
     * Page de création de compte
     */
    public function registerAction()
    {
        if (isset($_POST['submit'])) {
            $f = $_POST;

            if ($f['password'] !== $f['password-check']) {
                $err[] = "Mots de passe non identique.";
            }

            if (empty($err)) {
                $this->register($f);
                $this->login($f);
                header('Location:/account');
                exit;
            }
        }

        View::renderTemplate('User/register.html', ['errors' => $err ?? []]);
    }

    /**
     * Affiche la page du compte
     */
    public function accountAction()
    {
        $articles = Articles::getByUser($_SESSION['user']['id']);

        View::renderTemplate('User/account.html', [
            'articles' => $articles
        ]);
    }

    /*
     * Fonction privée pour enregister un utilisateur
     */
    private function register($data)
    {
        try {
            // Vérifier si l'email est déjà présent dans la base de données
            $user = \App\Models\User::getByLogin($data['email']);
            if($user) {
                throw new Exception('Email déjà pris.');
            }
    
            // Générer un salt, qui sera appliqué lors du processus de hachage de mot de passe.
            $salt = Hash::generateSalt(32);
    
            $userID = \App\Models\User::createUser([
                "email" => $data['email'],
                "username" => $data['username'],
                "password" => Hash::generate($data['password'], $salt),
                "salt" => $salt
            ]);
    
            return $userID;
        } catch (Exception $ex) {
            // Gérer l'erreur et afficher un message à l'utilisateur.
            $err[] = $ex->getMessage();
            View::renderTemplate('User/register.html', ['errors' => $err ?? []]);
            exit;
        }
    }



    private function login($data)
    {
        try {
            if (!isset($data['email'])) {
                throw new Exception('TODO');
            }

            $user = \App\Models\User::getByLogin($data['email']);

            if (Hash::generate($data['password'], $user['salt']) !== $user['password']) {
                return false;
            }

            // TODO: Create a remember me cookie if the user has selected the option
            // to remained logged in on the login form.
            // https://github.com/andrewdyer/php-mvc-register-login/blob/development/www/app/Model/UserLogin.php#L86

            $_SESSION['user'] = array(
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'is_admin' => $user['is_admin'],
            );
            return true;
        } catch (Exception $ex) {
            // TODO : Set flash if error
            /* Utility\Flash::danger($ex->getMessage());*/
        }
    }


    /**
     * Logout: Delete cookie and session. Returns true if everything is okay,
     * otherwise turns false.
     * @access public
     * @return boolean
     * @since 1.0.2
     */
    public function logoutAction()
    {

        /*
        if (isset($_COOKIE[$cookie])){
            // TODO: Delete the users remember me cookie if one has been stored.
            // https://github.com/andrewdyer/php-mvc-register-login/blob/development/www/app/Model/UserLogin.php#L148
        }*/
        // Destroy all data registered to the session.

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        setcookie('PHPSESSID', 'localhost', time() - 86400, '/');

        header("Location: /");

        return true;
    }

    public function passwordForgottenAction()
    {

        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            View::renderTemplate('User/forgotten.html');
        } else {
            $password = UserModel::resetPassword($_POST["email"]);
            Mail::sendMail(
                $_POST["email"],
                "Réinitialisation de votre mot de passe",
                "Bonjour,\n\nVotre nouveau mot de passe est : $password\n\nVous pouvez le modifier à tout moment depuis votre compte utilisateur.\n\nCordialement,\nL'équipe du site Vide-Grenier."
            );
            header("location:/");
        }
    }

    /**
     * permet à l'utilisateur de paramétrer un nouveau mot de passe
     */
    public function resetPasswordAction()
    {

        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            View::renderTemplate('User/reset.html');
        } else {
            $passwd = UserModel::resetPasswordByUser($_POST["password"]);

            header("location:/");
        }
    }

    public function adminAction()
    {
        View::renderTemplate('User/admin.html');
    }
}
