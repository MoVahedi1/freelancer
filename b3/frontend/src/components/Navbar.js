import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const Navbar = () => {
  const { user, isAuthenticated, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return (
    <nav className="bg-white shadow-lg">
      <div className="max-w-7xl mx-auto px-4">
        <div className="flex justify-between h-16">
          <div className="flex items-center">
            <Link to="/" className="text-xl font-bold text-gray-800">
              پلتفرم فریلنسری
            </Link>
          </div>
          
          <div className="flex items-center space-x-4 space-x-reverse">
            <Link to="/jobs" className="text-gray-600 hover:text-gray-900">
              آگهی‌ها
            </Link>
            
            {isAuthenticated ? (
              <div className="flex items-center space-x-4 space-x-reverse">
                {user?.user_type === 'freelancer' && (
                  <Link 
                    to="/freelancer/skills" 
                    className="text-gray-600 hover:text-gray-900"
                  >
                    مهارت‌های من
                  </Link>
                )}
                
                {user?.user_type === 'employer' && (
                  <Link 
                    to="/employer/post-job" 
                    className="text-gray-600 hover:text-gray-900"
                  >
                    ثبت آگهی
                  </Link>
                )}
                
                <div className="flex items-center space-x-2 space-x-reverse">
                  <span className="text-gray-600">
                    {user?.first_name} {user?.last_name}
                  </span>
                  <button
                    onClick={handleLogout}
                    className="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                  >
                    خروج
                  </button>
                </div>
              </div>
            ) : (
              <div className="flex items-center space-x-4 space-x-reverse">
                <Link 
                  to="/login" 
                  className="text-gray-600 hover:text-gray-900"
                >
                  ورود
                </Link>
                <Link 
                  to="/register" 
                  className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                >
                  ثبت‌نام
                </Link>
              </div>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar; 