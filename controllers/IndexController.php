<?php
class IndexController
{
    public function index()
    {
        echo <<<HTML
         <!-- Welcome Section -->
            <div class="welcome-container">
                <div class="welcome-content">
                    <h1>ຍິນດີຕ້ອນຮັບເຂົ້າສູ່ JCMVC</h1>
                    <p>ເປັນ Framework ພື້ນທາງແລະງ່າຍຕໍ່ການໃຊ້ງານ PHP ແບບ MVC</p>
                    <a href="#get-started" class="btn">Get Started</a>
                </div>
            </div>
        HTML;
    }
}
