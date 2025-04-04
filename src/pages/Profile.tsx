
import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import Navbar from '@/components/Navbar';
import ProfileSection from '@/components/ProfileSection';

// User Profile page component
const Profile = () => {
  // Get current user from auth context
  const { user } = useAuth();
  
  // If no user is logged in, redirect to login page
  if (!user) {
    return <Navigate to="/auth" />;
  }
  
  return (
    <div className="min-h-screen bg-muted/30">
      {/* Navigation bar */}
      <Navbar />
      
      {/* Main content area with padding */}
      <div className="pt-24 pb-16">
        <div className="container px-4 md:px-6 mx-auto">
          {/* Profile section with user information and settings */}
          <ProfileSection />
        </div>
      </div>
    </div>
  );
};

export default Profile;
