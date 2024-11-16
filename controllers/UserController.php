<?php
class UserController
{
    public function index()
    {
        echo "User Page Index";
    }
    public function show($id)
    {
        echo "User {$id}";
    }

    public function create()
    {
        echo "Create User";
    }

    public function update($id)
    {
        echo "Update User {$id}";
    }

    public function delete($id)
    {
        echo "Delete User {$id}";
    }

    public function profile()
    {
        echo "Profile";
    }
}
