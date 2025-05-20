<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        header('Content-Type: application/json');

        // Optional CORS headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
    }

    // GET: /api/users or /api/users/{id}
    public function users($id = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $data = $id ? $this->User_model->get_users($id) : $this->User_model->get_users();
            echo json_encode($data ?: ['message' => 'User not found']);
        }
    }

    // POST: /api/create_user
    public function create_user()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get raw JSON input
            $data = json_decode(file_get_contents('php://input'), true);

            // print_r($data);
            // die;

            // Basic validation
            if (empty($data['name']) || empty($data['email'])) {
                echo json_encode(['message' => 'Name and Email are required']);
                return;
            }

            $result = $this->User_model->insert_user($data);
            echo json_encode($result ? ['message' => 'User created'] : ['message' => 'Failed to create user']);
        } else {
            echo json_encode(['message' => 'Invalid request method']);
        }
    }


    // PUT: /api/update_user/{id}
    public function update_user($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);

            // print_r($data);
            // die;
            if (empty($data)) {
                echo json_encode(['message' => 'No data provided']);
                return;
            }

            $result = $this->User_model->update_user($id, $data);
            echo json_encode($result ? ['message' => 'User updated'] : ['message' => 'Failed to update user']);
        }
    }

    // DELETE: /api/delete_user/{id}
    public function delete_user($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $result = $this->User_model->delete_user($id);
            echo json_encode($result ? ['message' => 'User deleted'] : ['message' => 'Failed to delete user']);
        } else {
            echo json_encode(['message' => 'Invalid request method']);
        }
    }

}
