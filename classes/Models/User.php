<?php
namespace Models;

use Core\Model;
use Core\SessionManager;

class User extends Model {
    protected $table = 'users';
    protected $fillable = ['first_name', 'last_name', 'email', 'password', 'role', 'age', 'gender', 'contact_number', 'address'];

    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            SessionManager::set('user_id', $user['id']);
            SessionManager::set('user_name', $user['first_name']);
            SessionManager::set('role', 'customer');
            return true;
        }
        return false;
    }

    public function register(array $data) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->save($data);
    }

    public function isAdmin() { return SessionManager::get('role') === 'admin'; }
    public function updateProfile($id, $data) {
        $data['id'] = $id;
        return $this->save($data);
    }
}