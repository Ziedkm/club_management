
import React, { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { Button } from '@/components/ui/button';
import { Menu, X, User, LogOut } from 'lucide-react';

const Navbar = () => {
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const { user, logout } = useAuth();
  const location = useLocation();

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 10);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  useEffect(() => {
    setIsMobileMenuOpen(false);
  }, [location.pathname]);

  return (
    <nav
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        isScrolled || isMobileMenuOpen
          ? 'bg-white/80 dark:bg-black/80 backdrop-blur-lg shadow-sm'
          : 'bg-transparent'
      }`}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          <div className="flex items-center">
            <Link 
              to="/" 
              className="flex items-center space-x-2 text-xl font-bold text-primary transition-all hover:text-club"
            >
              <span className="text-club">Club</span>
              <span>Nest</span>
            </Link>
          </div>

          {/* Desktop navigation */}
          <div className="hidden md:block">
            <div className="flex items-center space-x-8">
              <Link
                to="/"
                className={`transition-all duration-300 ${
                  location.pathname === '/' 
                    ? 'text-club font-medium' 
                    : 'text-foreground/80 hover:text-club'
                }`}
              >
                Home
              </Link>
              <Link
                to="/clubs"
                className={`transition-all duration-300 ${
                  location.pathname === '/clubs' 
                    ? 'text-club font-medium' 
                    : 'text-foreground/80 hover:text-club'
                }`}
              >
                Clubs
              </Link>
              
              {user ? (
                <>
                  <Link
                    to="/dashboard"
                    className={`transition-all duration-300 ${
                      location.pathname === '/dashboard' 
                        ? 'text-club font-medium' 
                        : 'text-foreground/80 hover:text-club'
                    }`}
                  >
                    Dashboard
                  </Link>
                  <div className="relative group">
                    <Button
                      variant="ghost"
                      className="flex items-center space-x-2 text-foreground/80 hover:text-club"
                    >
                      <User size={18} />
                      <span>{user.username}</span>
                    </Button>
                    <div className="absolute right-0 mt-2 w-48 bg-card rounded-lg shadow-lg overflow-hidden opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 scale-95 group-hover:scale-100 origin-top-right z-50">
                      <Link
                        to="/profile"
                        className="block px-4 py-3 text-sm text-foreground hover:bg-secondary transition-colors"
                      >
                        Profile
                      </Link>
                      <button
                        onClick={logout}
                        className="w-full text-left px-4 py-3 text-sm text-destructive hover:bg-secondary flex items-center space-x-2 transition-colors"
                      >
                        <LogOut size={16} />
                        <span>Sign out</span>
                      </button>
                    </div>
                  </div>
                </>
              ) : (
                <>
                  <Link
                    to="/auth"
                    className="btn-primary"
                  >
                    Sign In
                  </Link>
                </>
              )}
            </div>
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden">
            <button
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="p-2 rounded-md text-foreground hover:bg-secondary transition-colors"
            >
              {isMobileMenuOpen ? <X size={24} /> : <Menu size={24} />}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile menu */}
      {isMobileMenuOpen && (
        <div className="md:hidden bg-card shadow-md animate-fade-in">
          <div className="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <Link
              to="/"
              className={`block px-3 py-2 rounded-md ${
                location.pathname === '/' 
                  ? 'bg-club text-club-foreground' 
                  : 'hover:bg-secondary'
              }`}
            >
              Home
            </Link>
            <Link
              to="/clubs"
              className={`block px-3 py-2 rounded-md ${
                location.pathname === '/clubs' 
                  ? 'bg-club text-club-foreground' 
                  : 'hover:bg-secondary'
              }`}
            >
              Clubs
            </Link>
            
            {user ? (
              <>
                <Link
                  to="/dashboard"
                  className={`block px-3 py-2 rounded-md ${
                    location.pathname === '/dashboard' 
                      ? 'bg-club text-club-foreground' 
                      : 'hover:bg-secondary'
                  }`}
                >
                  Dashboard
                </Link>
                <Link
                  to="/profile"
                  className="block px-3 py-2 rounded-md hover:bg-secondary"
                >
                  Profile
                </Link>
                <button
                  onClick={logout}
                  className="w-full text-left flex items-center space-x-2 px-3 py-2 rounded-md text-destructive hover:bg-secondary"
                >
                  <LogOut size={16} />
                  <span>Sign out</span>
                </button>
              </>
            ) : (
              <Link
                to="/auth"
                className="block px-3 py-2 bg-club text-club-foreground rounded-md"
              >
                Sign In
              </Link>
            )}
          </div>
        </div>
      )}
    </nav>
  );
};

export default Navbar;
