import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../utils/api';

const FreelancerSkills = () => {
  const navigate = useNavigate();
  const { getToken } = useAuth();
  const [jobClasses, setJobClasses] = useState([]);
  const [skills, setSkills] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    fetchJobClasses();
  }, []);

  const fetchJobClasses = async () => {
    try {
      const response = await api.get('/backend/api/freelancer/job-classes.php');
      setJobClasses(response.data.data);
    } catch (error) {
      setError('خطا در دریافت کلاس‌های شغلی');
    }
  };

  const fetchSubClasses = async (classId) => {
    try {
      const response = await api.get(`/backend/api/freelancer/job-subclasses.php?class_id=${classId}`);
      return response.data.data;
    } catch (error) {
      return [];
    }
  };

  const addSkill = () => {
    setSkills([...skills, {
      class_id: '',
      subclass_id: '',
      proficiency_level: 'beginner'
    }]);
  };

  const removeSkill = (index) => {
    setSkills(skills.filter((_, i) => i !== index));
  };

  const updateSkill = (index, field, value) => {
    const updatedSkills = [...skills];
    updatedSkills[index] = {
      ...updatedSkills[index],
      [field]: value
    };
    setSkills(updatedSkills);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const response = await api.post('/backend/api/freelancer/skills.php', {
        skills: skills
      });
      
      setSuccess('مهارت‌ها با موفقیت ثبت شدند');
      setTimeout(() => {
        navigate('/');
      }, 2000);
    } catch (error) {
      if (error.response?.data?.message) {
        setError(error.response.data.message);
      } else {
        setError('خطا در ثبت مهارت‌ها');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto">
      <div className="bg-white p-8 rounded-lg shadow-md">
        <h2 className="text-2xl font-bold text-gray-900 mb-6 text-center">
          ثبت مهارت‌های شغلی
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
          {skills.map((skill, index) => (
            <div key={index} className="border p-4 rounded-lg mb-4">
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-semibold">مهارت {index + 1}</h3>
                <button
                  type="button"
                  onClick={() => removeSkill(index)}
                  className="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                >
                  حذف
                </button>
              </div>
              
              <div className="grid md:grid-cols-3 gap-4">
                <div className="form-group">
                  <label className="form-label">کلاس شغلی</label>
                  <select
                    value={skill.class_id}
                    onChange={(e) => updateSkill(index, 'class_id', e.target.value)}
                    className="form-input"
                    required
                  >
                    <option value="">انتخاب کنید</option>
                    {jobClasses.map(cls => (
                      <option key={cls.class_id} value={cls.class_id}>
                        {cls.class_name}
                      </option>
                    ))}
                  </select>
                </div>
                
                <div className="form-group">
                  <label className="form-label">زیرکلاس (اختیاری)</label>
                  <select
                    value={skill.subclass_id}
                    onChange={(e) => updateSkill(index, 'subclass_id', e.target.value)}
                    className="form-input"
                  >
                    <option value="">انتخاب کنید</option>
                    {/* زیرکلاس‌ها بر اساس کلاس انتخاب شده لود می‌شوند */}
                  </select>
                </div>
                
                <div className="form-group">
                  <label className="form-label">سطح تسلط</label>
                  <select
                    value={skill.proficiency_level}
                    onChange={(e) => updateSkill(index, 'proficiency_level', e.target.value)}
                    className="form-input"
                    required
                  >
                    <option value="beginner">مبتدی</option>
                    <option value="intermediate">متوسط</option>
                    <option value="expert">حرفه‌ای</option>
                  </select>
                </div>
              </div>
            </div>
          ))}
          
          <div className="text-center mb-6">
            <button
              type="button"
              onClick={addSkill}
              className="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600"
            >
              افزودن مهارت جدید
            </button>
          </div>
          
          {skills.length > 0 && (
            <button
              type="submit"
              disabled={loading}
              className="w-full btn btn-primary py-3"
            >
              {loading ? 'در حال ثبت...' : 'ثبت مهارت‌ها'}
            </button>
          )}
        </form>
      </div>
    </div>
  );
};

export default FreelancerSkills; 