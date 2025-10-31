<?php
// بررسی احراز هویت ادمین
session_start();
header("Content-Type: text/html; charset=UTF-8");

// اگر کاربر لاگین نیست، به صفحه لاگین هدایت شود
if (!isset($_SESSION['admin_token'])) {
    // نمایش صفحه لاگین ادمین
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ورود ادمین - پلتفرم فریلنسری</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;500;600;700&display=swap");
            body { font-family: "Vazir", Tahoma, Arial, sans-serif; }
        </style>
    </head>
    <body class="bg-gradient-to-br from-blue-900 to-purple-900 min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">ورود ادمین</h1>
                <p class="text-gray-600">برای دسترسی به پنل مدیریت وارد شوید</p>
            </div>
            
            <form id="adminLoginForm" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="mohammadvahediorg@gmail.com">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">رمز عبور</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="رمز عبور">
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    ورود به پنل ادمین
                </button>
            </form>
            
            <div id="message" class="mt-4 text-center text-sm"></div>
            
            <div class="mt-6 text-center">
                <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm">بازگشت به صفحه اصلی</a>
            </div>
        </div>

        <script>
            document.getElementById('adminLoginForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const messageDiv = document.getElementById('message');
                
                try {
                    const response = await fetch('backend/api/auth/admin-login.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ email, password })
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok) {
                        // ذخیره توکن در localStorage و session
                        localStorage.setItem('admin_token', data.token);
                        
                        // ارسال توکن به سرور برای ذخیره در session
                        await fetch('backend/api/auth/admin-session.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ token: data.token })
                        });
                        
                        messageDiv.innerHTML = '<span class="text-green-600">ورود موفقیت‌آمیز! در حال انتقال...</span>';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        messageDiv.innerHTML = '<span class="text-red-600">' + data.message + '</span>';
                    }
                } catch (error) {
                    messageDiv.innerHTML = '<span class="text-red-600">خطا در اتصال به سرور</span>';
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

// اگر ادمین لاگین است، هدایت به داشبورد
header('Location: pages/admin-dashboard.html');
exit();
?>