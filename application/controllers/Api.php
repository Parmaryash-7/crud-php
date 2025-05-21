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

            // Basic validation
            if (empty($data['name']) || empty($data['email'])) {
                echo json_encode(['message' => 'Name and Email are required']);
                return;
            }

            // Image upload
            $this->load->library('upload');
            $base_url = base_url();
            $image_name = 'Default.jpg'; // Default image in case no image is uploaded.

            // Configure upload settings
            $config['upload_path'] = FCPATH . 'uploads/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config['max_size'] = 2048;
            $config['encrypt_name'] = TRUE;

            // Check if the folder exists, if not, create it
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }

            $this->upload->initialize($config);

            // Check if a file was uploaded
            if (!empty($_FILES['image']['name'])) {
                if (count($_FILES['image']['name']) != 1) {
                    echo json_encode(['status' => false, 'message' => "Multiple images not allowed!"]);
                    return;
                }

                // Prepare single image upload
                $_FILES['single_image'] = [
                    'name' => $_FILES['image']['name'][0],
                    'type' => $_FILES['image']['type'][0],
                    'tmp_name' => $_FILES['image']['tmp_name'][0],
                    'error' => $_FILES['image']['error'][0],
                    'size' => $_FILES['image']['size'][0]
                ];

                if (!$this->upload->do_upload('single_image')) {
                    echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                    return;
                } else {
                    $uploaded_data = $this->upload->data();
                    $image_name = $uploaded_data['file_name']; // Save uploaded file name
                }
            }

            // Prepare user data for insertion
            $user_data = [
                'name'  => $data['name'],
                'email' => $data['email'],
                'image' => $base_url . 'uploads/' . $image_name // Image path
            ];

            $result = $this->User_model->insert_user($user_data);
            echo json_encode($result ? ['status' => true, 'message' => 'User created'] : ['status' => false, 'message' => 'Failed to create user']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Invalid request method']);
        }
    }

    // PUT: /api/update_user/{id}
    public function update_user($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            // Get raw JSON input
            $data = json_decode(file_get_contents('php://input'), true);

            // Validate if data exists
            if (empty($data)) {
                echo json_encode(['status' => false, 'message' => 'No data provided']);
                return;
            }

            // Fetch current user data to keep old image if not updated
            $user = $this->User_model->get_users($id);
            if (!$user) {
                echo json_encode(['status' => false, 'message' => 'User not found']);
                return;
            }

            // Default values
            $image_name = $user->image; // Set the current image as the default

            // If a new image is uploaded, change it
            if (!empty($_FILES['image']['name'])) {
                if (count($_FILES['image']['name']) != 1) {
                    echo json_encode(['status' => false, 'message' => "Multiple images not allowed!"]);
                    return;
                }

                // Prepare single image upload
                $_FILES['single_image'] = [
                    'name' => $_FILES['image']['name'][0],
                    'type' => $_FILES['image']['type'][0],
                    'tmp_name' => $_FILES['image']['tmp_name'][0],
                    'error' => $_FILES['image']['error'][0],
                    'size' => $_FILES['image']['size'][0]
                ];

                $this->load->library('upload');
                $config['upload_path'] = FCPATH . 'uploads/';
                $config['allowed_types'] = 'jpg|jpeg|png|gif';
                $config['max_size'] = 2048;
                $config['encrypt_name'] = TRUE;

                // Check if the folder exists, if not, create it
                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0777, true);
                }

                $this->upload->initialize($config);

                // If upload is successful, update the image
                if (!$this->upload->do_upload('single_image')) {
                    echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                    return;
                } else {
                    $uploaded_data = $this->upload->data();
                    $image_name = $uploaded_data['file_name']; // Save new image name
                }
            }

            // Prepare updated user data
            $update_data = [
                'name'  => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'image' => base_url() . 'uploads/' . $image_name // Set the updated or existing image
            ];

            // Update user data in the database
            $result = $this->User_model->update_user($id, $update_data);
            echo json_encode($result ? ['status' => true, 'message' => 'User updated'] : ['status' => false, 'message' => 'Failed to update user']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Invalid request method']);
        }
    }

    // DELETE: /api/delete_user/{id}
    public function delete_user($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $result = $this->User_model->delete_user($id);
            echo json_encode($result ? ['status' => true, 'message' => 'User deleted'] : ['status' => false, 'message' => 'Failed to delete user']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Invalid request method']);
        }
    }
}
