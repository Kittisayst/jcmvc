<?php
// views/error/404.php
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຂໍ້ຜິດພາດ <?= isset($code) ? $code : '404' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <div class="error-code"><?= isset($code) ? $code : '404' ?></div>
        <h1 class="h4 mb-4">
            <?php if (isset($code) && $code == '500'): ?>
                ເກີດຂໍ້ຜິດພາດຈາກເຊີບເວີ
            <?php else: ?>
                ບໍ່ພົບໜ້າທີ່ຕ້ອງການ
            <?php endif; ?>
        </h1>
        
        <p class="text-muted mb-4">
            <?php if (isset($code) && $code == '500'): ?>
                ຂໍອະໄພ, ເກີດຂໍ້ຜິດພາດຂຶ້ນໃນການດຳເນີນການ. ກະລຸນາລອງໃໝ່ອີກຄັ້ງ.
            <?php else: ?>
                ຂໍອະໄພ, ບໍ່ພົບໜ້າທີ່ທ່ານຕ້ອງການ.
            <?php endif; ?>
        </p>

        <div class="d-flex justify-content-center gap-3">
            <a href="/jcmvc" class="btn btn-primary px-4">
                ກັບໄປໜ້າຫຼັກ
            </a>
            <?php if (isset($code) && $code == '500'): ?>
                <button onclick="location.reload()" class="btn btn-outline-secondary px-4">
                    ໂຫຼດຄືນໃໝ່
                </button>
            <?php else: ?>
                <button onclick="history.back()" class="btn btn-outline-secondary px-4">
                    ກັບຄືນ
                </button>
            <?php endif; ?>
        </div>

        <?php if (isset($exception) && getenv('APP_DEBUG') == 'true'): ?>
            <div class="mt-5">
                <div class="alert alert-danger text-start">
                    <h5 class="alert-heading">Debug Information</h5>
                    <p class="mb-0"><strong>Error:</strong> <?= htmlspecialchars($exception->getMessage()) ?></p>
                    <p class="mb-0"><strong>File:</strong> <?= htmlspecialchars($exception->getFile()) ?></p>
                    <p class="mb-0"><strong>Line:</strong> <?= $exception->getLine() ?></p>
                </div>
                
                <?php if ($exception->getTrace()): ?>
                    <div class="card mt-3">
                        <div class="card-header">Stack Trace</div>
                        <div class="card-body">
                            <pre class="mb-0"><code><?= htmlspecialchars($exception->getTraceAsString()) ?></code></pre>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>