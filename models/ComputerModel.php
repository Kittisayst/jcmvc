<?php

class ComputerModel extends Model {
    // ກຳນົດຊື່ຕາຕະລາງ
    protected $table = 'computer_rooms';
    
    // ກຳນົດ field ທີ່ອະນຸຍາດໃຫ້ແກ້ໄຂ
    protected $fillable = [
        'room_number',
        'capacity',
        'description',
        'status',
        'created_at',
        'updated_at'
    ];

    // ສະຖານະທີ່ອະນຸຍາດ
    private $allowedStatuses = ['available', 'maintenance', 'reserved', 'closed'];
    
    /**
     * ກວດສອບຂໍ້ມູນກ່ອນບັນທຶກ
     */
    protected function validate($data) {
        $rules = [
            'room_number' => 'required|min_length:2|max_length:10|unique:computer_rooms',
            'capacity' => 'required|numeric|min:1|max:100',
            'description' => 'max_length:1000',
            'status' => 'required|in:' . implode(',', $this->allowedStatuses)
        ];

        // ຖ້າເປັນການອັບເດດ ບໍ່ຕ້ອງກວດ unique ຂອງ room_number ກັບ record ປັດຈຸບັນ
        if (isset($data['id'])) {
            $rules['room_number'] = 'required|min_length:2|max_length:10|unique:computer_rooms,' . $data['id'];
        }
        
        $validator = new Validator($data, $rules);
        
        if (!$validator->validate()) {
            throw new Exception(implode("\n", array_map(function($errors) {
                return $errors[0];
            }, $validator->getErrors())));
        }

        // ເພີ່ມເວລາອັບເດດ
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $data;
    }

    /**
     * Override create method
     */
    public function create($data) {
        // ເພີ່ມເວລາສ້າງ
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::create($data);
    }

    /**
     * ດຶງຫ້ອງທີ່ວ່າງ
     */
    public function getAvailableRooms() {
        return $this->where(['status' => 'available']);
    }

    /**
     * ດຶງຫ້ອງທີ່ມີຄວາມຈຸຕາມຕ້ອງການ
     */
    public function getRoomsByCapacity($minCapacity) {
        return $this->db->query(
            "SELECT * FROM {$this->table} WHERE capacity >= ? ORDER BY capacity",
            [$minCapacity]
        );
    }

    /**
     * ອັບເດດສະຖານະຫ້ອງ
     */
    public function updateStatus($id, $status) {
        if (!in_array($status, $this->allowedStatuses)) {
            throw new Exception("Invalid status: {$status}");
        }
        return $this->update($id, ['status' => $status]);
    }

    /**
     * ຈອງຫ້ອງ
     */
    public function reserve($id) {
        return $this->updateStatus($id, 'reserved');
    }

    /**
     * ຍົກເລີກການຈອງ
     */
    public function cancelReservation($id) {
        return $this->updateStatus($id, 'available');
    }

    /**
     * ຄົ້ນຫາຫ້ອງ
     */
    public function search($keyword) {
        $keyword = "%{$keyword}%";
        return $this->db->query(
            "SELECT * FROM {$this->table} 
            WHERE room_number LIKE ? OR description LIKE ?",
            [$keyword, $keyword]
        );
    }
}