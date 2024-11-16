<?php

require_once "./core/Model.php";

class ComputerRoomModel extends Model
{
    // ກຳນົດຕາຕະລາງ
    protected string $table = 'computer_rooms';

    // ກຳນົດ primary key
    protected string $primaryKey = 'id';

    // ກຳນົດ field ທີ່ສາມາດແກ້ໄຂໄດ້
    protected array $fillable = [
        'room_number',
        'capacity',
        'description',
        'status'
    ];

    // ກຳນົດ field ທີ່ບໍ່ສາມາດແກ້ໄຂໄດ້
    protected array $guarded = ['id', 'created_at', 'updated_at'];

    // ກຳນົດຄ່າ status ທີ່ອະນຸຍາດ
    private const ALLOWED_STATUSES = ['available', 'occupied', 'maintenance'];

    /**
     * ຊອກຫາຫ້ອງທີ່ວ່າງຢູ່
     * @return array
     */
    public function getAvailableRooms(): array
    {
        return $this->where(['status' => 'available']);
    }

    /**
     * ຊອກຫາຫ້ອງທີ່ມີຄົນໃຊ້ຢູ່
     * @return array
     */
    public function getOccupiedRooms(): array
    {
        return $this->where(['status' => 'occupied']);
    }

    /**
     * ຊອກຫາຫ້ອງທີ່ກຳລັງຊ່ອມແປງ
     * @return array
     */
    public function getMaintenanceRooms(): array
    {
        return $this->where(['status' => 'maintenance']);
    }

    /**
     * ຊອກຫາຫ້ອງຕາມຄວາມຈຸ
     * @param int $minCapacity
     * @return array
     */
    public function findByCapacity(int $minCapacity): array
    {
        return $this->findWhere([
            'capacity' => ['>=', $minCapacity],
            'status' => 'available'
        ]);
    }

    /**
     * ອັບເດດສະຖານະຫ້ອງ
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::ALLOWED_STATUSES)) {
            throw new \InvalidArgumentException('Invalid status value');
        }

        return $this->update($id, ['status' => $status]);
    }

    /**
     * ກວດສອບວ່າຫ້ອງວ່າງຫຼືບໍ່
     * @param int $id
     * @return bool
     */
    public function isAvailable(int $id): bool
    {
        $room = $this->find($id);
        return $room !== null && $room['status'] === 'available';
    }

    /**
     * ດຶງຂໍ້ມູນຈຳນວນຫ້ອງແຍກຕາມສະຖານະ
     * @return array
     */
    public function getRoomStatistics(): array
    {
        $query = "
            SELECT 
                status,
                COUNT(*) as count,
                SUM(capacity) as total_capacity
            FROM {$this->table}
            GROUP BY status
        ";

        return $this->db->fetchAll($query, [], $this->connection);
    }

    /**
     * ຄົ້ນຫາຫ້ອງແບບມີເງື່ອນໄຂ
     * @param array $params
     * @return array
     */
    public function searchRooms(array $params): array
    {
        $conditions = [];
        $orderBy = [];

        // ກຳນົດເງື່ອນໄຂຕາມ params ທີ່ສົ່ງມາ
        if (isset($params['status']) && in_array($params['status'], self::ALLOWED_STATUSES)) {
            $conditions['status'] = $params['status'];
        }

        if (isset($params['min_capacity'])) {
            $conditions['capacity'] = ['>=', (int)$params['min_capacity']];
        }

        if (isset($params['room_number'])) {
            $conditions['room_number'] = $params['room_number'];
        }

        // ກຳນົດການຮຽງລຳດັບ
        if (isset($params['sort_by'])) {
            $direction = $params['sort_direction'] ?? 'ASC';
            $orderBy[$params['sort_by']] = strtoupper($direction);
        }

        return $this->findWhere(
            $conditions,
            $orderBy,
            $params['limit'] ?? null,
            $params['offset'] ?? null
        );
    }

    /**
     * ກວດສອບຂໍ້ມູນກ່ອນບັນທຶກ
     * @param array $data
     * @return bool
     */
    protected function validate(array $data): bool
    {
        if (isset($data['room_number']) && strlen($data['room_number']) > 100) {
            throw new \InvalidArgumentException('Room number is too long');
        }

        if (isset($data['capacity']) && $data['capacity'] <= 0) {
            throw new \InvalidArgumentException('Capacity must be greater than 0');
        }

        if (isset($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES)) {
            throw new \InvalidArgumentException('Invalid status value');
        }

        return true;
    }

    /**
     * Override create method to include validation
     * @param array $data
     * @return int|bool
     */
    public function create(array $data)
    {
        $this->validate($data);
        return parent::create($data);
    }

    /**
     * Override update method to include validation
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool
    {
        $this->validate($data);
        return parent::update($id, $data);
    }
}
