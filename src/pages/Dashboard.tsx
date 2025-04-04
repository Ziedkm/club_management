
import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import Navbar from '@/components/Navbar';
import DashboardComponent from '@/components/Dashboard';

// Main Dashboard page component
const Dashboard = () => {
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
          {/* Dashboard component with all the dashboard functionality */}
          <DashboardComponent />
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
