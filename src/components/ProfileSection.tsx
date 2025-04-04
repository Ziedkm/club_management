
import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/context/AuthContext';
import { toast } from 'sonner';
import { AtSign, User, Lock, Save } from 'lucide-react';

// User Profile section component
const ProfileSection = () => {
  const { user, updateProfile, logout } = useAuth();
  
  const [formData, setFormData] = useState({
    username: user?.username || '',
    email: user?.email || '',
    password: '',
    confirmPassword: '',
  });
  
  const [isUpdating, setIsUpdating] = useState(false);
  
  // Handle form field changes
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };
  
  // Handle profile update submission
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    // Basic validation
    if (!formData.username.trim()) {
      toast.error('Username is required');
      return;
    }
    
    if (!formData.email.trim() || !/\S+@\S+\.\S+/.test(formData.email)) {
      toast.error('Valid email is required');
      return;
    }
    
    // Check if passwords match for password update
    if (formData.password && formData.password !== formData.confirmPassword) {
      toast.error('Passwords do not match');
      return;
    }
    
    setIsUpdating(true);
    
    try {
      // Prepare update data - only include password if provided
      const updateData: any = {
        username: formData.username,
        email: formData.email,
      };
      
      if (formData.password) {
        updateData.password = formData.password;
      }
      
      await updateProfile(updateData);
      toast.success('Profile updated successfully');
      
      // Clear password fields after successful update
      setFormData({
        ...formData,
        password: '',
        confirmPassword: '',
      });
    } catch (error) {
      console.error('Update error:', error);
      toast.error('Failed to update profile');
    } finally {
      setIsUpdating(false);
    }
  };
  
  // Role-specific content
  const getRoleContent = () => {
    switch (user?.role) {
      case 'admin':
        return {
          title: 'Club Administrator',
          description: 'You have full access to manage all university clubs and their activities.',
        };
      case 'club_leader':
        return {
          title: 'Club Leader',
          description: 'You can manage your club members, post announcements, and organize events.',
        };
      default:
        return {
          title: 'Student',
          description: 'You can join clubs, participate in events, and receive club notifications.',
        };
    }
  };
  
  const roleContent = getRoleContent();
  
  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold mb-2">My Profile</h1>
        <p className="text-muted-foreground">
          View and update your account information
        </p>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {/* Account Information Card */}
        <Card className="md:col-span-2">
          <CardHeader>
            <CardTitle>Account Information</CardTitle>
            <CardDescription>Update your personal information and password</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Username field */}
              <div className="space-y-2">
                <label htmlFor="username" className="block text-sm font-medium">
                  Username
                </label>
                <div className="relative">
                  <Input
                    id="username"
                    name="username"
                    type="text"
                    value={formData.username}
                    onChange={handleChange}
                    className="pl-10"
                    required
                  />
                  <User className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={18} />
                </div>
              </div>
              
              {/* Email field */}
              <div className="space-y-2">
                <label htmlFor="email" className="block text-sm font-medium">
                  Email
                </label>
                <div className="relative">
                  <Input
                    id="email"
                    name="email"
                    type="email"
                    value={formData.email}
                    onChange={handleChange}
                    className="pl-10"
                    required
                  />
                  <AtSign className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={18} />
                </div>
              </div>
              
              {/* Password field */}
              <div className="space-y-2">
                <label htmlFor="password" className="block text-sm font-medium">
                  New Password (leave blank to keep current)
                </label>
                <div className="relative">
                  <Input
                    id="password"
                    name="password"
                    type="password"
                    value={formData.password}
                    onChange={handleChange}
                    className="pl-10"
                  />
                  <Lock className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={18} />
                </div>
              </div>
              
              {/* Confirm Password field */}
              <div className="space-y-2">
                <label htmlFor="confirmPassword" className="block text-sm font-medium">
                  Confirm New Password
                </label>
                <div className="relative">
                  <Input
                    id="confirmPassword"
                    name="confirmPassword"
                    type="password"
                    value={formData.confirmPassword}
                    onChange={handleChange}
                    className="pl-10"
                  />
                  <Lock className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={18} />
                </div>
              </div>
              
              {/* Submit button */}
              <Button 
                type="submit" 
                className="btn-primary flex items-center"
                disabled={isUpdating}
              >
                {isUpdating ? 'Updating...' : (
                  <>
                    <Save className="mr-2 h-4 w-4" />
                    Save Changes
                  </>
                )}
              </Button>
            </form>
          </CardContent>
        </Card>
        
        {/* Account Summary Card */}
        <Card>
          <CardHeader>
            <CardTitle>Account Summary</CardTitle>
            <CardDescription>Your current role and account details</CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* User avatar and name */}
            <div className="flex flex-col items-center text-center">
              <div className="h-20 w-20 rounded-full bg-club-light text-club flex items-center justify-center text-2xl font-bold mb-2">
                {user?.username.charAt(0).toUpperCase()}
              </div>
              <h3 className="font-semibold text-lg">{user?.username}</h3>
              <p className="text-sm text-muted-foreground">{user?.email}</p>
            </div>
            
            {/* Role information */}
            <div className="bg-muted/50 p-4 rounded-lg">
              <h4 className="font-medium mb-1">{roleContent.title}</h4>
              <p className="text-sm text-muted-foreground">
                {roleContent.description}
              </p>
            </div>
            
            {/* Account actions */}
            <div className="pt-2">
              <Button 
                variant="outline" 
                className="w-full text-destructive hover:text-destructive"
                onClick={logout}
              >
                Sign Out
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default ProfileSection;
