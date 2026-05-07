<?php
namespace Controllers;

use Core\Auth;
use Core\SessionManager;
use Models\User;
use Models\Review;

class HomeController {
    public function index() {
        $is_customer = Auth::check();
        $is_admin = Auth::isAdmin();
        $display_name = "User";
        
        if ($is_customer) {
            $userId = SessionManager::get('user_id');
            $userModel = new User();
            $user = $userModel->find($userId);
            $display_name = $user['first_name'] ?? "User";
        }
        
        // Shop link: now uses relative path from index.php
        $shop_link = ($is_customer || $is_admin) ? "store.php" : "index.php?page=login";
        
        $testimonials = (new Review())->getLatest(3);
        $page = $_GET['page'] ?? 'home';
        
        $viewData = compact('is_customer', 'is_admin', 'display_name', 'shop_link', 'testimonials', 'page');
        extract($viewData);
        require __DIR__ . '/../views/layouts/main.php';
    }
}