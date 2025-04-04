
import React from 'react';
import Navbar from '@/components/Navbar';
import Hero from '@/components/Hero';
import { ArrowRight, Users, Bell, Calendar, Search } from 'lucide-react';
import ClubCard from '@/components/ClubCard';
import { Link } from 'react-router-dom';

// Mock data for featured clubs
const FEATURED_CLUBS = [
  {
    id: '1',
    name: 'Photography Club',
    description: 'For students passionate about photography and visual arts. Weekly meetups, photo walks, and workshops.',
    memberCount: 32,
    createdAt: '2023-05-15',
  },
  {
    id: '2',
    name: 'Debate Society',
    description: 'Improve your public speaking and critical thinking skills in our regular debate competitions.',
    memberCount: 45,
    createdAt: '2022-09-10',
  },
  {
    id: '3',
    name: 'Robotics Club',
    description: 'Design, build and program robots for competitions and exhibitions. All experience levels welcome.',
    memberCount: 28,
    createdAt: '2023-01-22',
  }
];

const Index = () => {
  return (
    <div className="min-h-screen">
      <Navbar />
      
      {/* Hero Section */}
      <main>
        <Hero />
        
        {/* Features Section */}
        <section className="py-24 bg-muted/30">
          <div className="container px-4 md:px-6">
            <div className="text-center max-w-3xl mx-auto mb-12 space-y-4">
              <h2 className="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl fade-up">
                Everything you need to manage university clubs
              </h2>
              <p className="text-muted-foreground md:text-lg fade-up" style={{ animationDelay: '200ms' }}>
                ClubNest provides a comprehensive platform for students, club leaders, and administrators.
              </p>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 fade-up" style={{ animationDelay: '400ms' }}>
              <div className="bg-card border border-border rounded-xl p-6 text-center shadow-sm hover:shadow-md transition-all duration-300">
                <div className="w-12 h-12 bg-club-light rounded-full flex items-center justify-center mx-auto mb-4">
                  <Users className="h-6 w-6 text-club" />
                </div>
                <h3 className="text-xl font-semibold mb-2">Club Management</h3>
                <p className="text-muted-foreground mb-4">
                  Create, join, and manage university clubs with ease. Assign roles and track membership.
                </p>
                <Link to="/clubs" className="text-club hover:text-club-dark font-medium inline-flex items-center">
                  Explore Clubs
                  <ArrowRight className="ml-1 h-4 w-4" />
                </Link>
              </div>
              
              <div className="bg-card border border-border rounded-xl p-6 text-center shadow-sm hover:shadow-md transition-all duration-300">
                <div className="w-12 h-12 bg-club-light rounded-full flex items-center justify-center mx-auto mb-4">
                  <Bell className="h-6 w-6 text-club" />
                </div>
                <h3 className="text-xl font-semibold mb-2">Notifications</h3>
                <p className="text-muted-foreground mb-4">
                  Send email notifications to club members about upcoming events, meetings, and important updates.
                </p>
                <Link to="/dashboard" className="text-club hover:text-club-dark font-medium inline-flex items-center">
                  View Dashboard
                  <ArrowRight className="ml-1 h-4 w-4" />
                </Link>
              </div>
              
              <div className="bg-card border border-border rounded-xl p-6 text-center shadow-sm hover:shadow-md transition-all duration-300">
                <div className="w-12 h-12 bg-club-light rounded-full flex items-center justify-center mx-auto mb-4">
                  <Calendar className="h-6 w-6 text-club" />
                </div>
                <h3 className="text-xl font-semibold mb-2">Admin Controls</h3>
                <p className="text-muted-foreground mb-4">
                  Administrators can approve club requests, manage accounts, and oversee all university clubs.
                </p>
                <Link to="/auth" className="text-club hover:text-club-dark font-medium inline-flex items-center">
                  Admin Login
                  <ArrowRight className="ml-1 h-4 w-4" />
                </Link>
              </div>
            </div>
          </div>
        </section>
        
        {/* Featured Clubs Section */}
        <section className="py-24">
          <div className="container px-4 md:px-6">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-12">
              <div className="mb-6 md:mb-0">
                <h2 className="text-3xl font-bold tracking-tight mb-2">Featured Clubs</h2>
                <p className="text-muted-foreground">
                  Discover popular clubs from across the university
                </p>
              </div>
              <Link 
                to="/clubs" 
                className="btn-primary inline-flex items-center"
              >
                View All Clubs
                <ArrowRight className="ml-1 h-4 w-4" />
              </Link>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {FEATURED_CLUBS.map((club, index) => (
                <div 
                  key={club.id}
                  className="fade-up"
                  style={{ animationDelay: `${index * 200}ms` }}
                >
                  <ClubCard
                    id={club.id}
                    name={club.name}
                    description={club.description}
                    memberCount={club.memberCount}
                    createdAt={club.createdAt}
                  />
                </div>
              ))}
            </div>
          </div>
        </section>
        
        {/* CTA Section */}
        <section className="py-24 bg-gradient-to-br from-club/90 to-club-dark relative overflow-hidden">
          <div className="absolute inset-0 bg-noise opacity-10"></div>
          <div className="container px-4 md:px-6 relative">
            <div className="max-w-3xl mx-auto text-center text-white space-y-6">
              <h2 className="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl fade-up">
                Ready to enhance your university experience?
              </h2>
              <p className="text-white/80 md:text-lg fade-up" style={{ animationDelay: '200ms' }}>
                Join ClubNest today and connect with like-minded students through university clubs.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center fade-up" style={{ animationDelay: '400ms' }}>
                <Link 
                  to="/auth" 
                  className="bg-white text-club hover:bg-opacity-90 font-medium px-6 py-3 rounded-lg transition-colors duration-300"
                >
                  Create Account
                </Link>
                <Link 
                  to="/clubs" 
                  className="bg-white/10 backdrop-blur-sm hover:bg-white/20 text-white font-medium px-6 py-3 rounded-lg transition-colors duration-300"
                >
                  Explore Clubs
                </Link>
              </div>
            </div>
          </div>
        </section>
      </main>
      
      {/* Footer */}
      <footer className="bg-card border-t border-border py-12">
        <div className="container px-4 md:px-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div className="md:col-span-2">
              <Link to="/" className="flex items-center space-x-2 text-xl font-bold mb-4">
                <span className="text-club">Club</span>
                <span>Nest</span>
              </Link>
              <p className="text-muted-foreground max-w-md">
                The comprehensive platform for managing university clubs, connecting students, and enhancing campus life.
              </p>
            </div>
            
            <div>
              <h3 className="font-semibold mb-4">Quick Links</h3>
              <ul className="space-y-2">
                <li>
                  <Link to="/" className="text-muted-foreground hover:text-foreground transition-colors">
                    Home
                  </Link>
                </li>
                <li>
                  <Link to="/clubs" className="text-muted-foreground hover:text-foreground transition-colors">
                    Clubs
                  </Link>
                </li>
                <li>
                  <Link to="/dashboard" className="text-muted-foreground hover:text-foreground transition-colors">
                    Dashboard
                  </Link>
                </li>
                <li>
                  <Link to="/auth" className="text-muted-foreground hover:text-foreground transition-colors">
                    Sign In
                  </Link>
                </li>
              </ul>
            </div>
            
            <div>
              <h3 className="font-semibold mb-4">Contact</h3>
              <ul className="space-y-2 text-muted-foreground">
                <li>support@clubnest.edu</li>
                <li>University Campus, Building A</li>
                <li>(555) 123-4567</li>
              </ul>
            </div>
          </div>
          
          <div className="border-t border-border mt-12 pt-6 flex flex-col md:flex-row justify-between items-center">
            <p className="text-sm text-muted-foreground">
              &copy; {new Date().getFullYear()} ClubNest. All rights reserved.
            </p>
            <div className="flex space-x-4 mt-4 md:mt-0">
              <a href="#" className="text-muted-foreground hover:text-foreground transition-colors">
                Terms
              </a>
              <a href="#" className="text-muted-foreground hover:text-foreground transition-colors">
                Privacy
              </a>
              <a href="#" className="text-muted-foreground hover:text-foreground transition-colors">
                Help
              </a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default Index;
