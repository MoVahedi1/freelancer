import axios from 'axios';

// تنظیم baseURL برای اتصال به بک‌اند
const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://project.php/b3.8/b3',
  timeout: 15000, // افزایش timeout
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor برای اضافه کردن توکن به درخواست‌ها
api.interceptors.request.use(
  (config) => {
    console.log('Request:', config.method?.toUpperCase(), config.url);
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    console.error('Request Error:', error);
    return Promise.reject(error);
  }
);

// Interceptor برای مدیریت خطاها
api.interceptors.response.use(
  (response) => {
    console.log('Response:', response.status, response.config.url);
    return response;
  },
  (error) => {
    console.error('Response Error:', error);
    
    if (error.code === 'ECONNABORTED') {
      console.error('Request timeout - سرور پاسخ نمی‌دهد');
    } else if (error.code === 'ERR_NETWORK') {
      console.error('Network error - مشکل در اتصال شبکه');
    } else if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    
    return Promise.reject(error);
  }
);

export default api; 