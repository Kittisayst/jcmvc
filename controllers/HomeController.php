<?php
class HomeController extends Controller
{
    public function index()
    {
        $db = Database::getInstance();
        $computers = $db->query('SELECT * FROM computer_rooms');
        return $this->render('home/index', [
            "computers" => $computers,
            'title' => 'ໜ້າຫຼັກ',
            'description' => 'ຍິນດີຕ້ອນຮັບ'
        ]);
    }

    public function show($id)
    {
        echo "User {$id}";
    }
    public function users($id, $data)
    {
        echo "User {$id} {$data}";
    }
}
