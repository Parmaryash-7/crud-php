<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends CI_Model
{
    // Constructor to initialize the database
    public function __construct()
    {
        parent::__construct();  // Ensure CI_Model parent class is loaded
        $this->load->database(); // Explicitly load the database
    }

    // GET: Retrieve all users or a single user by ID
    public function get_users($id = null)
    {
        if ($id === null) {
            return $this->db->get('users')->result(); // Get all users
        }
        return $this->db->get_where('users', ['id' => $id])->row(); // Get single user by ID
    }

    // POST: Create a new user
    public function create_user($data)
    {
        $this->db->insert('users', $data); // Insert data into the 'users' table
        return $this->db->insert_id(); // Return the inserted user's ID
    }

    // PUT: Update a user's information
    public function update_user($id, $data)
    {
        $this->db->where('id', $id); // Specify the user by ID
        return $this->db->update('users', $data); // Update the user with the new data
    }

    // DELETE: Remove a user by ID
    public function delete_user($id)
    {
        return $this->db->delete('users', ['id' => $id]); // Delete the user with the given ID
    }
}
