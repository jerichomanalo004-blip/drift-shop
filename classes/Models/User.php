<?php
namespace Models;

use Core\Model;
use Core\SessionManager;

class User extends Model {
    protected $table = 'users';
    protected $fillable = ['first_name', 'last_name', 'birthdate', 'age', 'email', 'password', 'gender', 'contact_number', 'address'];

    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            SessionManager::set('user_id', $user['id']);
            SessionManager::set('user_name', $user['first_name']);
            return true;
        }
        return false;
    }

    public function register(array $data) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        // If address is not provided, set it to null
        if (!isset($data['address']) || empty($data['address'])) {
            $data['address'] = null;
        }
        return $this->save($data);
    }

    public function isAdmin() { 
        return SessionManager::get('role') === 'admin'; 
    }
    
    public function updateProfile($id, $data) {
        $data['id'] = $id;
        return $this->save($data);
    }
}
?>