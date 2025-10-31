import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../utils/api';

const PostJob = () => {
  const navigate = useNavigate();
  const { getToken } = useAuth();
  const [jobClasses, setJobClasses] = useState([]);
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    budget_type: 'range',
    budget_min: '',
    budget_max: '',
    required_skills: []
  });
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

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const addRequiredSkill = () => {
    setFormData(prev => ({
      ...prev,
      required_skills: [...prev.required_skills, {
        class_id: '',
        subclass_id: '',
        proficiency_level: 'beginner'
      }]
    }));
  };

  const removeRequiredSkill = (index) => {
    setFormData(prev => ({
      ...prev,
      required_skills: prev.required_skills.filter((_, i) => i !== index)
    }));
  };

  const updateRequiredSkill = (index, field, value) => {
    setFormData(prev => {
      const updatedSkills = [...prev.required_skills];
      updatedSkills[index] = {
        ...updatedSkills[index],
        [field]: value
      };
      return {
        ...prev,
        required_skills: updatedSkills
      };
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const response = await api.post('/backend/api/employer/jobs.php', formData);
      
      setSuccess('آگهی با موفقیت ثبت شد');
      setTimeout(() => {
        navigate('/jobs');
      }, 2000);
    } catch (error) {
      if (error.response?.data?.message) {
        setError(error.response.data.message);
      } else {
        setError('خطا در ثبت آگهی');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto">
      <div className="bg-white p-8 rounded-lg shadow-md">
        <h2 className="text-2xl font-bold text-gray-900 mb-6 text-center">
          ثبت آگهی جدید
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
            <label className="form-label">عنوان آگهی</label>
            <input
              type="text"
              name="title"
              value={formData.title}
              onChange={handleChange}
              className="form-input"
              required
            />
          </div>
          
          <div className="form-group">
            <label className="form-label">توضیحات</label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              className="form-input"
              rows="6"
              required
            />
          </div>
          
          <div className="grid md:grid-cols-3 gap-4 mb-6">
            <div className="form-group">
              <label className="form-label">نوع بودجه</label>
              <select
                name="budget_type"
                value={formData.budget_type}
                onChange={handleChange}
                className="form-input"
                required
              >
                <option value="range">بازه‌ای</option>
                <option value="negotiable">توافقی</option>
              </select>
            </div>
            
            {formData.budget_type === 'range' && (
              <>
                <div className="form-group">
                  <label className="form-label">حداقل بودجه (تومان)</label>
                  <input
                    type="number"
                    name="budget_min"
                    value={formData.budget_min}
                    onChange={handleChange}
                    className="form-input"
                    required
                  />
                </div>
                
                <div className="form-group">
                  <label className="form-label">حداکثر بودجه (تومان)</label>
                  <input
                    type="number"
                    name="budget_max"
                    value={formData.budget_max}
                    onChange={handleChange}
                    className="form-input"
                    required
                  />
                </div>
              </>
            )}
          </div>
          
          <div className="mb-6">
            <h3 className="text-lg font-semibold mb-4">مهارت‌های مورد نیاز</h3>
            
            {formData.required_skills.map((skill, index) => (
              <div key={index} className="border p-4 rounded-lg mb-4">
                <div className="flex justify-between items-center mb-4">
                  <h4 className="font-medium">مهارت {index + 1}</h4>
                  <button
                    type="button"
                    onClick={() => removeRequiredSkill(index)}
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
                      onChange={(e) => updateRequiredSkill(index, 'class_id', e.target.value)}
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
                      onChange={(e) => updateRequiredSkill(index, 'subclass_id', e.target.value)}
                      className="form-input"
                    >
                      <option value="">انتخاب کنید</option>
                    </select>
                  </div>
                  
                  <div className="form-group">
                    <label className="form-label">سطح تسلط مورد نیاز</label>
                    <select
                      value={skill.proficiency_level}
                      onChange={(e) => updateRequiredSkill(index, 'proficiency_level', e.target.value)}
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
            
            <button
              type="button"
              onClick={addRequiredSkill}
              className="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600"
            >
              افزودن مهارت مورد نیاز
            </button>
          </div>
          
          <button
            type="submit"
            disabled={loading}
            className="w-full btn btn-primary py-3"
          >
            {loading ? 'در حال ثبت...' : 'ثبت آگهی'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default PostJob; 