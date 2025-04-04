
import React, { useState } from 'react';
import { useAuth } from '@/context/AuthContext';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { AtSign, Lock, User, Eye, EyeOff } from 'lucide-react';

type AuthFormProps = {
  isLogin: boolean;
  toggleForm: () => void;
};

const AuthForms = ({ isLogin, toggleForm }: AuthFormProps) => {
  const [showPassword, setShowPassword] = useState(false);
  const { login, register } = useAuth();
  
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    password: '',
  });
  
  const [errors, setErrors] = useState({
    username: '',
    email: '',
    password: '',
  });
  
  const [isLoading, setIsLoading] = useState(false);
  
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    // Clear error when user starts typing
    if (errors[name as keyof typeof errors]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };
  
  const validateForm = () => {
    let valid = true;
    const newErrors = { ...errors };
    
    // Validate username for register
    if (!isLogin && !formData.username.trim()) {
      newErrors.username = 'Username is required';
      valid = false;
    }
    
    // Validate email
    if (!formData.email.trim()) {
      newErrors.email = 'Email is required';
      valid = false;
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Invalid email format';
      valid = false;
    }
    
    // Validate password
    if (!formData.password) {
      newErrors.password = 'Password is required';
      valid = false;
    } else if (!isLogin && formData.password.length < 6) {
      newErrors.password = 'Password must be at least 6 characters';
      valid = false;
    }
    
    setErrors(newErrors);
    return valid;
  };
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    setIsLoading(true);
    
    try {
      if (isLogin) {
        await login(formData.email, formData.password);
        toast.success('Successfully logged in');
      } else {
        await register(formData.username, formData.email, formData.password);
        toast.success('Account created successfully');
        // Auto-login after register
        await login(formData.email, formData.password);
      }
    } catch (error) {
      console.error('Auth error:', error);
      toast.error(isLogin 
        ? 'Failed to login. Please check your credentials.' 
        : 'Failed to create account. Please try again.'
      );
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="w-full max-w-md mx-auto p-6 space-y-6 bg-card rounded-2xl shadow-md border border-border">
      <div className="text-center space-y-2">
        <h2 className="text-2xl font-bold">{isLogin ? 'Welcome Back' : 'Create Account'}</h2>
        <p className="text-muted-foreground">
          {isLogin 
            ? 'Sign in to access your ClubNest account' 
            : 'Join ClubNest to discover and manage university clubs'}
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        {!isLogin && (
          <div className="space-y-2">
            <div className="relative">
              <Input
                id="username"
                name="username"
                type="text"
                placeholder="Username"
                value={formData.username}
                onChange={handleChange}
                className={`pl-10 py-6 ${errors.username ? 'border-destructive' : ''}`}
              />
              <User className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={18} />
            </div>
            {errors.username && <p className="text-destructive text-sm">{errors.username}</p>}
          </div>
        )}

        <div className="space-y-2">
          <div className="relative">
            <Input
              id="email"
              name="email"
              type="email"
              placeholder="Email address"
              value={formData.email}
              onChange={handleChange}
              className={`pl-10 py-6 ${errors.email ? 'border-destructive' : ''}`}
            />
            <AtSign className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={18} />
          </div>
          {errors.email && <p className="text-destructive text-sm">{errors.email}</p>}
        </div>

        <div className="space-y-2">
          <div className="relative">
            <Input
              id="password"
              name="password"
              type={showPassword ? 'text' : 'password'}
              placeholder="Password"
              value={formData.password}
              onChange={handleChange}
              className={`pl-10 pr-10 py-6 ${errors.password ? 'border-destructive' : ''}`}
            />
            <Lock className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={18} />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
            >
              {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          </div>
          {errors.password && <p className="text-destructive text-sm">{errors.password}</p>}
        </div>

        {isLogin && (
          <div className="text-right">
            <a href="#" className="text-sm text-club hover:underline">
              Forgot password?
            </a>
          </div>
        )}

        <Button 
          type="submit" 
          className="w-full bg-club hover:bg-club-dark text-white py-6"
          disabled={isLoading}
        >
          {isLoading 
            ? 'Processing...' 
            : isLogin ? 'Sign In' : 'Create Account'}
        </Button>
      </form>

      <div className="text-center pt-4">
        <p className="text-muted-foreground">
          {isLogin ? "Don't have an account? " : "Already have an account? "}
          <button 
            type="button" 
            onClick={toggleForm} 
            className="text-club hover:underline font-medium"
          >
            {isLogin ? 'Sign Up' : 'Sign In'}
          </button>
        </p>
      </div>
    </div>
  );
};

export default AuthForms;
