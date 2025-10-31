import React, { useState, useEffect } from 'react';
import api from '../utils/api';

const JobList = () => {
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  useEffect(() => {
    fetchJobs();
  }, [page]);

  const fetchJobs = async () => {
    try {
      setLoading(true);
      const response = await api.get(`/backend/api/jobs.php?page=${page}&limit=10`);
      
      if (response.data.data) {
        if (page === 1) {
          setJobs(response.data.data);
        } else {
          setJobs(prev => [...prev, ...response.data.data]);
        }
        
        setHasMore(response.data.data.length === 10);
      }
    } catch (error) {
      setError('خطا در دریافت آگهی‌ها');
    } finally {
      setLoading(false);
    }
  };

  const formatBudget = (job) => {
    if (job.budget_type === 'range') {
      return `${job.budget_min?.toLocaleString()} تا ${job.budget_max?.toLocaleString()} تومان`;
    } else {
      return 'توافقی';
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fa-IR');
  };

  const loadMore = () => {
    setPage(prev => prev + 1);
  };

  if (loading && page === 1) {
    return (
      <div className="text-center py-8">
        <div className="text-xl">در حال بارگذاری...</div>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto">
      <h1 className="text-3xl font-bold text-gray-900 mb-8 text-center">
        آگهی‌های شغلی
      </h1>
      
      {error && (
        <div className="alert alert-error mb-6">
          {error}
        </div>
      )}
      
      {jobs.length === 0 && !loading ? (
        <div className="text-center py-8">
          <div className="text-xl text-gray-600">هیچ آگهی‌ای یافت نشد.</div>
        </div>
      ) : (
        <div className="space-y-6">
          {jobs.map((job) => (
            <div key={job.job_id} className="bg-white rounded-lg shadow-md p-6">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h2 className="text-xl font-semibold text-gray-900 mb-2">
                    {job.title}
                  </h2>
                  <p className="text-gray-600 mb-2">
                    توسط: {job.first_name} {job.last_name}
                    {job.company_name && ` (${job.company_name})`}
                  </p>
                  <p className="text-sm text-gray-500">
                    تاریخ ثبت: {formatDate(job.created_at)}
                  </p>
                </div>
                <div className="text-left">
                  <span className="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                    {formatBudget(job)}
                  </span>
                </div>
              </div>
              
              <div className="mb-4">
                <p className="text-gray-700 leading-relaxed">
                  {job.description}
                </p>
              </div>
              
              {job.required_skills && job.required_skills.length > 0 && (
                <div className="border-t pt-4">
                  <h3 className="text-lg font-semibold text-gray-900 mb-3">
                    مهارت‌های مورد نیاز:
                  </h3>
                  <div className="flex flex-wrap gap-2">
                    {job.required_skills.map((skill, index) => (
                      <span
                        key={index}
                        className="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm"
                      >
                        {skill.class_name}
                        {skill.subclass_name && ` - ${skill.subclass_name}`}
                        {` (${skill.proficiency_level === 'beginner' ? 'مبتدی' : 
                           skill.proficiency_level === 'intermediate' ? 'متوسط' : 'حرفه‌ای'})`}
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </div>
          ))}
          
          {hasMore && (
            <div className="text-center py-6">
              <button
                onClick={loadMore}
                disabled={loading}
                className="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 disabled:opacity-50"
              >
                {loading ? 'در حال بارگذاری...' : 'نمایش بیشتر'}
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default JobList; 