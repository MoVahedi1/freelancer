import React from 'react';

const Footer = () => {
  return (
    <footer className="bg-gray-800 text-white py-8 mt-16">
      <div className="container mx-auto px-4">
        <div className="grid md:grid-cols-3 gap-8">
          <div>
            <h4 className="text-lg font-semibold mb-4">پلتفرم فریلنسری</h4>
            <p className="text-gray-300">بهترین پلتفرم برای اتصال فریلنسرها و کارفرمایان در ایران</p>
          </div>
          <div>
            <h5 className="font-semibold mb-4">خدمات</h5>
            <ul className="space-y-2 text-gray-300">
              <li>برنامه‌نویسی</li>
              <li>طراحی گرافیک</li>
              <li>نویسندگی</li>
              <li>ترجمه</li>
            </ul>
          </div>
          <div>
            <h5 className="font-semibold mb-4">تماس</h5>
            <ul className="space-y-2 text-gray-300">
              <li>ایمیل: info@freelance.ir</li>
              <li>تلفن: 021-12345678</li>
              <li>آدرس: تهران، ایران</li>
            </ul>
          </div>
        </div>
        <div className="border-t border-gray-700 mt-8 pt-8 text-center text-gray-300">
          <p>&copy; 2025 پلتفرم فریلنسری. تمامی حقوق محفوظ است.</p>
          <p className="mt-2 text-sm">طراحی و توسعه: <span className="text-blue-400 font-semibold">محمد واحدی</span></p>
        </div>
      </div>
    </footer>
  );
};

export default Footer; 