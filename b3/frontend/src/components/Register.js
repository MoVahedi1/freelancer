import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../utils/api';

const Register = () => {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    first_name: '',
    last_name: '',
    user_type: 'freelancer',
    company_name: ''
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const response = await api.post('/backend/api/auth/register.php', formData);
      
      if (response.data.user_id) {
        setSuccess('ثبت‌نام با موفقیت انجام شد. در حال ورود...');
        
        // ورود خودکار پس از ثبت‌نام
        const loginResponse = await api.post('/backend/api/auth/login.php', {
          email: formData.email,
          password: formData.password
        });
        
        if (loginResponse.data.token) {
          login(loginResponse.data.user, loginResponse.data.token);
          
          // هدایت بر اساس نوع کاربر
          if (formData.user_type === 'freelancer') {
            navigate('/freelancer/skills');
          } else {
            navigate('/employer/post-job');
          }
        }
      }
    } catch (error) {
      if (error.response?.data?.message) {
        setError(error.response.data.message);
      } else {
        setError('خطا در ثبت‌نام. لطفاً دوباره تلاش کنید.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-md mx-auto">
      <div className="bg-white p-8 rounded-lg shadow-md">
        <h2 className="text-2xl font-bold text-gray-900 mb-6 text-center">
          ثبت‌نام
        </h2>
        
        {error && (
          <div className="alert alert-error mb-4">
            {error}
          </div>
        )}
        
        {success && (
          <div className="alert alert-success mb-4">
            {success}
          </div>
        )}
        
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">نوع کاربر</label>
            <select
              name="user_type"
              value={formData.user_type}
              onChange={handleChange}
              className="form-input"
              required
            >
              <option value="freelancer">کارجو</option>
              <option value="employer">کارفرما</option>
            </select>
          </div>
          
          <div className="form-group">
            <label className="form-label">نام</label>
            <input
              type="text"
              name="first_name"
              value={formData.first_name}
              onChange={handleChange}
              className="form-input"
              required
            />
          </div>
          
          <div className="form-group">
            <label className="form-label">نام خانوادگی</label>
            <input
              type="text"
              name="last_name"
              value={formData.last_name}
              onChange={handleChange}
              className="form-input"
              required
            />
          </div>
          
          <div className="form-group">
            <label className="form-label">ایمیل</label>
            <input
              type="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              className="form-input"
              required
            />
          </div>
          
          <div className="form-group">
            <label className="form-label">رمز عبور</label>
            <input
              type="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              className="form-input"
              required
              minLength="6"
            />
          </div>
          
          {formData.user_type === 'employer' && (
            <div className="form-group">
              <label className="form-label">نام شرکت (اختیاری)</label>
              <input
                type="text"
                name="company_name"
                value={formData.company_name}
                onChange={handleChange}
                className="form-input"
              />
            </div>
          )}
          
          <button
            type="submit"
            disabled={loading}
            className="w-full btn btn-primary py-3"
          >
            {loading ? 'در حال ثبت‌نام...' : 'ثبت‌نام'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default Register; 