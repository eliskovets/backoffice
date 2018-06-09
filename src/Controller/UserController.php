<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use App\Entity\PageTypeConfig;

class UserController extends Controller
{

    /**
     * @Route("/user/login_register", name="user_login_register")
     */
    public function index()
    {
        return $this->render('user/login_register.html.twig');
    }

}
