import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const Home = () => {
  const { isAuthenticated, user } = useAuth();

  return (
    <div className="text-center">
      <div className="max-w-4xl mx-auto">
        <h1 className="text-4xl font-bold text-gray-900 mb-6">
          به پلتفرم فریلنسری خوش آمدید
        </h1>
        
        <p className="text-xl text-gray-600 mb-8">
          بهترین مکان برای یافتن فرصت‌های شغلی و استخدام فریلنسرهای متخصص
        </p>
        
        {!isAuthenticated ? (
          <div className="space-y-4">
            <div className="grid md:grid-cols-2 gap-6">
              <div className="bg-white p-6 rounded-lg shadow-md">
                <h2 className="text-2xl font-semibold text-gray-800 mb-4">
                  کارجو هستید؟
                </h2>
                <p className="text-gray-600 mb-4">
                  مهارت‌های خود را ثبت کنید و پروژه‌های مناسب را پیدا کنید
                </p>
                <Link 
                  to="/register" 
                  className="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 inline-block"
                >
                  ثبت‌نام به عنوان کارجو
                </Link>
              </div>
              
              <div className="bg-white p-6 rounded-lg shadow-md">
                <h2 className="text-2xl font-semibold text-gray-800 mb-4">
                  کارفرما هستید؟
                </h2>
                <p className="text-gray-600 mb-4">
                  پروژه‌های خود را ثبت کنید و فریلنسرهای متخصص را استخدام کنید
                </p>
                <Link 
                  to="/register" 
                  className="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 inline-block"
                >
                  ثبت‌نام به عنوان کارفرما
                </Link>
              </div>
            </div>
            
            <div className="mt-8">
              <p className="text-gray-600 mb-4">
                قبلاً حساب کاربری دارید؟
              </p>
              <Link 
                to="/login" 
                className="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 inline-block"
              >
                ورود به حساب کاربری
              </Link>
            </div>
          </div>
        ) : (
          <div className="bg-white p-6 rounded-lg shadow-md">
            <h2 className="text-2xl font-semibold text-gray-800 mb-4">
              خوش آمدید {user?.first_name} {user?.last_name}
            </h2>
            
            {user?.user_type === 'freelancer' ? (
              <div>
                <p className="text-gray-600 mb-4">
                  مهارت‌های خود را ثبت کنید تا کارفرمایان بتوانند شما را پیدا کنند
                </p>
                <Link 
                  to="/freelancer/skills" 
                  className="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 inline-block"
                >
                  ثبت مهارت‌ها
                </Link>
              </div>
            ) : (
              <div>
                <p className="text-gray-600 mb-4">
                  پروژه‌های خود را ثبت کنید تا فریلنسرهای متخصص را پیدا کنید
                </p>
                <Link 
                  to="/employer/post-job" 
                  className="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 inline-block"
                >
                  ثبت آگهی جدید
                </Link>
              </div>
            )}
            
            <div className="mt-6">
              <Link 
                to="/jobs" 
                className="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 inline-block"
              >
                مشاهده آگهی‌ها
              </Link>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default Home; 