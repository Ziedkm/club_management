
import React, { createContext, useContext, useState, useEffect } from 'react';
import { toast } from 'sonner';

// Define basic user types
type UserRole = 'student' | 'club_leader' | 'admin';

type User = {
  id: string;
  username: string;
  email: string;
  role: UserRole;
};

// Context type definition
type AuthContextType = {
  user: User | null;
  login: (email: string, password: string) => Promise<void>;
  register: (username: string, email: string, password: string) => Promise<void>;
  logout: () => void;
  updateProfile: (data: Partial<User> & { password?: string }) => Promise<void>;
};

// Create the context
const AuthContext = createContext<AuthContextType | undefined>(undefined);

// Custom hook for easy access to auth context
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

// Sample users for testing
const MOCK_USERS = [
  {
    id: '1',
    username: 'admin',
    email: 'admin@university.edu',
    password: 'admin123',
    role: 'admin' as UserRole
  },
  {
    id: '2',
    username: 'clubleader',
    email: 'leader@university.edu',
    password: 'leader123',
    role: 'club_leader' as UserRole
  },
  {
    id: '3',
    username: 'student',
    email: 'student@university.edu',
    password: 'student123',
    role: 'student' as UserRole
  }
];

// Auth Provider component 
export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  // User state and loading state
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  
  // Load saved user on startup
  useEffect(() => {
    // Check localStorage for saved user
    const savedUser = localStorage.getItem('clubnest_user');
    if (savedUser) {
      try {
        setUser(JSON.parse(savedUser));
      } catch (error) {
        console.error('Failed to parse saved user:', error);
        localStorage.removeItem('clubnest_user');
      }
    }
    setIsLoading(false);
  }, []);
  
  // Login function
  const login = async (email: string, password: string) => {
    // Wait a bit to simulate server delay
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Find the user by email and password
    const foundUser = MOCK_USERS.find(
      u => u.email.toLowerCase() === email.toLowerCase() && u.password === password
    );
    
    if (!foundUser) {
      throw new Error('Invalid email or password');
    }
    
    // Don't include password when storing user in state
    const { password: _, ...userWithoutPassword } = foundUser;
    setUser(userWithoutPassword);
    
    // Save to localStorage for persistence
    localStorage.setItem('clubnest_user', JSON.stringify(userWithoutPassword));
  };
  
  // Register function
  const register = async (username: string, email: string, password: string) => {
    // Wait a bit to simulate server delay
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Check if user already exists
    const existingUser = MOCK_USERS.find(
      u => u.email.toLowerCase() === email.toLowerCase() || u.username.toLowerCase() === username.toLowerCase()
    );
    
    if (existingUser) {
      throw new Error('User with this email or username already exists');
    }
    
    // Create new user
    const newUser = {
      id: `${MOCK_USERS.length + 1}`,
      username,
      email,
      password,
      role: 'student' as UserRole
    };
    
    // Add to mock database
    MOCK_USERS.push(newUser);
    
    // In a real app, we would send this data to a server
  };
  
  // Logout function
  const logout = () => {
    setUser(null);
    localStorage.removeItem('clubnest_user');
    toast.success('Successfully logged out');
  };
  
  // Update profile function
  const updateProfile = async (data: Partial<User> & { password?: string }) => {
    if (!user) {
      throw new Error('User is not logged in');
    }
    
    // Wait a bit to simulate server delay
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Update user data
    const updatedUser = { ...user, ...data };
    
    // Remove password field before storing
    const { password, ...userWithoutPassword } = updatedUser as (User & { password?: string });
    
    setUser(userWithoutPassword);
    localStorage.setItem('clubnest_user', JSON.stringify(userWithoutPassword));
    
    // In a real app, we would send this data to a server
  };
  
  return (
    <AuthContext.Provider value={{ user, login, register, logout, updateProfile }}>
      {!isLoading && children}
    </AuthContext.Provider>
  );
};
