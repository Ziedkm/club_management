
import React, { useState } from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import Navbar from '@/components/Navbar';
import AuthForms from '@/components/AuthForms';

const Auth = () => {
  const [isLogin, setIsLogin] = useState(true);
  const { user } = useAuth();
  
  // Redirect if user is already logged in
  if (user) {
    return <Navigate to="/dashboard" />;
  }
  
  return (
    <div className="min-h-screen bg-muted/30">
      <Navbar />
      
      <div className="pt-24 pb-16">
        <div className="container px-4 md:px-6 max-w-7xl mx-auto">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div className="order-2 lg:order-1">
              <div className="max-w-xl space-y-6 fade-up">
                <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">
                  {isLogin ? 'Welcome back to ClubNest' : 'Join ClubNest today'}
                </h1>
                <p className="text-muted-foreground text-lg">
                  {isLogin 
                    ? 'Sign in to continue your journey with university clubs, manage membership, and stay connected with your community.'
                    : 'Create an account to discover and join university clubs, connect with like-minded students, and enhance your campus experience.'}
                </p>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="bg-card border border-border rounded-lg p-4">
                    <h3 className="font-semibold mb-2">For Students</h3>
                    <p className="text-sm text-muted-foreground">
                      Browse and join clubs, receive notifications about events
                    </p>
                  </div>
                  <div className="bg-card border border-border rounded-lg p-4">
                    <h3 className="font-semibold mb-2">For Club Leaders</h3>
                    <p className="text-sm text-muted-foreground">
                      Manage club members, send notifications, organize events
                    </p>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="order-1 lg:order-2 fade-up" style={{ animationDelay: '200ms' }}>
              <AuthForms 
                isLogin={isLogin} 
                toggleForm={() => setIsLogin(!isLogin)} 
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Auth;
