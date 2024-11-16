# ບົດທີ 1: ພາບລວມຂອງ JCMVC Framework

## 1.1 ແນະນຳກ່ຽວກັບ JCMVC

JCMVC ແມ່ນ PHP Framework ທີ່ພັດທະນາຂຶ້ນເພື່ອສຶກສາແລະນຳໃຊ້ຫຼັກການ MVC (Model-View-Controller) ຢ່າງເຂົ້າໃຈງ່າຍ. Framework ນີ້ຖືກອອກແບບມາໃຫ້:

- ເໝາະສຳລັບຜູ້ເລີ່ມຕົ້ນຮຽນຮູ້ MVC Pattern
- ມີໂຄງສ້າງທີ່ຊັດເຈນແລະເຂົ້າໃຈງ່າຍ
- ສາມາດພັດທະນາເວັບແອັບພລິເຄຊັນຂະໜາດນ້ອຍຫາກາງ
- ຮອງຮັບພາສາລາວ

### ຈຸດເດັ່ນຂອງ JCMVC:

1. **ງ່າຍຕໍ່ການເຂົ້າໃຈ**
   - ໂຄ້ດມີຄຳອະທິບາຍເປັນພາສາລາວ
   - ໂຄງສ້າງບໍ່ຊັບຊ້ອນ
   - ເໝາະສຳລັບການຮຽນຮູ້

2. **ມີຟັງຊັນພື້ນຖານຄົບຖ້ວນ**
   - ລະບົບ Routing
   - ການຈັດການຖານຂໍ້ມູນ
   - ການຈັດການ Session
   - ການຈັດການ Request/Response
   - ລະບົບ View Template
   - ການກວດສອບຄວາມປອດໄພ

3. **ຄວາມຍືດຍຸ່ນ**
   - ສາມາດຂະຫຍາຍຟັງຊັນເພີ່ມໄດ້
   - ປັບແຕ່ງໄດ້ຕາມຕ້ອງການ
   - ໃຊ້ງານງ່າຍ

## 1.2 ຄວາມຕ້ອງການຂອງລະບົບ

### ຄວາມຕ້ອງການດ້ານຊອຟແວ:

- PHP ເວີຊັນ 7.4 ຫຼື ສູງກວ່າ
- MySQL 5.7 ຫຼື ສູງກວ່າ
- Web Server (Apache ຫຼື Nginx)
- Composer (ບໍ່ບັງຄັບ)

### ການຕັ້ງຄ່າ PHP ທີ່ຈຳເປັນ:

```ini
extension=pdo
extension=pdo_mysql
extension=mbstring
extension=json
extension=openssl
```

### ສິດທິການເຂົ້າເຖິງໄຟລ໌:
- ໂຟລເດີ cache/ ຕ້ອງສາມາດຂຽນໄດ້ (777)
- ໂຟລເດີ logs/ ຕ້ອງສາມາດຂຽນໄດ້ (777)
- ໂຟລເດີ public/uploads/ ຕ້ອງສາມາດຂຽນໄດ້ (777)

## 1.3 ການຕິດຕັ້ງ

### 1. ດາວໂຫຼດ Framework:
```bash
git clone https://github.com/yourusername/jcmvc.git
```

### 2. ສ້າງໄຟລ໌ .env:
```bash
cp .env.example .env
```

### 3. ຕັ້ງຄ່າຖານຂໍ້ມູນໃນໄຟລ໌ .env:
```env
DB_HOST=localhost
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. ກຳນົດສິດການເຂົ້າເຖິງໂຟລເດີ:
```bash
chmod 777 cache/
chmod 777 logs/
chmod 777 public/uploads/
```

### 5. ສ້າງ Virtual Host (Apache):
```apache
<VirtualHost *:80>
    ServerName jcmvc.local
    DocumentRoot /path/to/jcmvc/public
    
    <Directory /path/to/jcmvc/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## 1.4 ໂຄງສ້າງໄດເລກະທໍລີ

```plaintext
📁 jcmvc/
│
├── 📁 cache/               # ເກັບຂໍ້ມູນ cache
│
├── 📁 config/             # ໄຟລ໌ການຕັ້ງຄ່າຕ່າງໆ
│   ├── app.php           # ການຕັ້ງຄ່າພື້ນຖານ
│   ├── database.php      # ການຕັ້ງຄ່າຖານຂໍ້ມູນ
│   └── ...
│
├── 📁 controllers/        # Controllers
├── 📁 core/              # Core classes
├── 📁 middleware/         # Middleware classes
├── 📁 models/            # Models
├── 📁 public/            # Public files
├── 📁 routes/            # Route definitions
├── 📁 views/             # View files
│
├── .env                  # Environment variables
├── .htaccess            # URL rewriting rules
└── index.php            # Entry point
```

### ອະທິບາຍໂຄງສ້າງ:

- **cache/**: ເກັບຂໍ້ມູນ cache ເພື່ອເພີ່ມປະສິດທິພາບ
- **config/**: ເກັບໄຟລ໌ການຕັ້ງຄ່າຕ່າງໆຂອງລະບົບ
- **controllers/**: ເກັບ controller classes
- **core/**: ເກັບ core classes ຂອງ framework
- **middleware/**: ເກັບ middleware classes
- **models/**: ເກັບ model classes
- **public/**: ເກັບໄຟລ໌ທີ່ເຂົ້າເຖິງຈາກພາຍນອກໄດ້
- **routes/**: ເກັບການກຳນົດເສັ້ນທາງຂອງລະບົບ
- **views/**: ເກັບໄຟລ໌ template ສຳລັບສະແດງຜົນ

## 1.5 ຕົວຢ່າງການໃຊ້ງານພື້ນຖານ

### 1. ສ້າງ Controller:

```php
// controllers/HomeController.php
class HomeController extends Controller 
{
    public function index()
    {
        $data = [
            'title' => 'ໜ້າຫຼັກ',
            'message' => 'ຍິນດີຕ້ອນຮັບສູ່ JCMVC'
        ];
        
        $this->render('home/index', $data);
    }
}
```

### 2. ສ້າງ View:

```php
<!-- views/home/index.php -->
<h1><?= $title ?></h1>
<p><?= $message ?></p>
```

### 3. ກຳນົດ Route:

```php
// routes/web.php
$router->get('/', 'HomeController', 'index');
```

ເມື່ອເຂົ້າເວັບ `http://localhost/jcmvc` ຈະເຫັນໜ້າຕ້ອນຮັບພ້ອມຂໍ້ຄວາມ "ຍິນດີຕ້ອນຮັບສູ່ JCMVC".

## 1.6 ການຊ່ວຍເຫຼືອແລະຊັບພອດ

- GitHub Repository: [github.com/Kittisayst/jcmvc](https://github.com/Kittisayst/jcmvc)
- Documentation: [docs.jcmvc.local](https://docs.jcmvc.local)
- Issues & Bugs: [github.com/Kittisayst/jcmvc/issues](https://github.com/Kittisayst/jcmvc/issues)

ຖ້າພົບບັນຫາຫຼືຕ້ອງການຄວາມຊ່ວຍເຫຼືອ, ສາມາດ:
1. ເປີດ Issue ໃນ GitHub
2. ສົ່ງອີເມວຫາ support@jcmvc.local
3. ເຂົ້າຮ່ວມກຸ່ມ Facebook: JCMVC Community
