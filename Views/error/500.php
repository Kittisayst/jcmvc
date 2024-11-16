<?php
// 500.php
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເກີດຂໍ້ຜິດພາດຈາກເຊີບເວີ</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="text-center p-8">
        <h1 class="text-9xl font-bold text-gray-300">500</h1>
        <h2 class="text-2xl font-semibold mt-4">ເກີດຂໍ້ຜິດພາດຈາກເຊີບເວີ</h2>
        <p class="text-gray-600 mt-2"><?= $message ?? 'ຂໍອະໄພ, ເກີດຂໍ້ຜິດພາດຂຶ້ນໃນການດຳເນີນການ. ກະລຸນາລອງໃໝ່ອີກຄັ້ງ.' ?></p>
        <div class="mt-6 space-x-4">
            <a href="/" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                ກັບໄປໜ້າຫຼັກ
            </a>
            <a href="javascript:location.reload()" class="inline-block px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                ໂຫຼດຄືນໃໝ່
            </a>
        </div>
    </div>
</body>
</html>