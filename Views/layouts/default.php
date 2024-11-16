<?php
/**
 * views/layouts/default.php
 * Layout ຫຼັກຂອງເວັບໄຊທ໌
 * 
 * ຕົວແປທີ່ມີໃຫ້ໃຊ້:
 * @var string $content - ເນື້ອຫາຈາກ view
 * @var string|null $title - ຫົວຂໍ້ໜ້າ
 * @var array|null $meta - Meta tags
 */

$title = $title ?? 'JC MVC';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/jcmvc/public/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="/jcmvc">JC MVC</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/jcmvc">ໜ້າຫຼັກ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/jcmvc/about">ກ່ຽວກັບ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/jcmvc/contact">ຕິດຕໍ່</a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <?= htmlspecialchars($_SESSION['user']['name']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/jcmvc/profile">ໂປຣໄຟລ໌</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/jcmvc/logout">ອອກຈາກລະບົບ</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/jcmvc/login">ເຂົ້າສູ່ລະບົບ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/jcmvc/register">ລົງທະບຽນ</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="container">
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <div class="alert alert-<?= $type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container mb-4">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-auto">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> JC MVC. ສະຫງວນລິຂະສິດ.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/jcmvc/public/js/main.js"></script>
</body>
</html>