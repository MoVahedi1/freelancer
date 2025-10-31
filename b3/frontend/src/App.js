import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import Home from './components/Home';
import Register from './components/Register';
import Login from './components/Login';
import FreelancerSkills from './components/FreelancerSkills';
import PostJob from './components/PostJob';
import JobList from './components/JobList';
import { AuthProvider, useAuth } from './contexts/AuthContext';

// کامپوننت برای محافظت از مسیرها
const ProtectedRoute = ({ children, allowedUserTypes = [] }) => {
  const { user, isAuthenticated } = useAuth();
  
  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }
  
  if (allowedUserTypes.length > 0 && !allowedUserTypes.includes(user?.user_type)) {
    return <Navigate to="/" replace />;
  }
  
  return children;
};

function App() {
  return (
    <AuthProvider>
      <Router>
        <div className="min-h-screen bg-gray-50 flex flex-col">
          <Navbar />
          <div className="flex-1 container mx-auto px-4 py-8">
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="/register" element={<Register />} />
              <Route path="/login" element={<Login />} />
              <Route 
                path="/freelancer/skills" 
                element={
                  <ProtectedRoute allowedUserTypes={['freelancer']}>
                    <FreelancerSkills />
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/employer/post-job" 
                element={
                  <ProtectedRoute allowedUserTypes={['employer']}>
                    <PostJob />
                  </ProtectedRoute>
                } 
              />
              <Route path="/jobs" element={<JobList />} />
            </Routes>
          </div>
          <Footer />
        </div>
      </Router>
    </AuthProvider>
  );
}

export default App; 