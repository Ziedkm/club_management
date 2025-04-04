
import React, { useState } from 'react';
import Navbar from '@/components/Navbar';
import ClubCard from '@/components/ClubCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { SearchIcon, Filter, PlusCircle } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';

// Mock data for clubs
const ALL_CLUBS = [
  {
    id: '1',
    name: 'Photography Club',
    description: 'For students passionate about photography and visual arts. Weekly meetups, photo walks, and workshops.',
    memberCount: 32,
    createdAt: '2023-05-15',
    category: 'Arts'
  },
  {
    id: '2',
    name: 'Debate Society',
    description: 'Improve your public speaking and critical thinking skills in our regular debate competitions.',
    memberCount: 45,
    createdAt: '2022-09-10',
    category: 'Academic'
  },
  {
    id: '3',
    name: 'Robotics Club',
    description: 'Design, build and program robots for competitions and exhibitions. All experience levels welcome.',
    memberCount: 28,
    createdAt: '2023-01-22',
    category: 'Technology'
  },
  {
    id: '4',
    name: 'Chess Club',
    description: 'Weekly chess tournaments and training sessions for all skill levels.',
    memberCount: 19,
    createdAt: '2023-03-05',
    category: 'Games'
  },
  {
    id: '5',
    name: 'Film Society',
    description: 'Watch and discuss classic and contemporary films from around the world.',
    memberCount: 37,
    createdAt: '2022-11-18',
    category: 'Arts'
  },
  {
    id: '6',
    name: 'AI Research Group',
    description: 'Collaborative research on artificial intelligence and machine learning applications.',
    memberCount: 23,
    createdAt: '2023-02-14',
    category: 'Technology'
  }
];

// Categories for filtering
const CATEGORIES = [
  'All Categories',
  'Arts',
  'Academic',
  'Technology',
  'Games',
  'Sports',
  'Social'
];

const Clubs = () => {
  const { user } = useAuth();
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('All Categories');
  
  // Filter clubs based on search query and category
  const filteredClubs = ALL_CLUBS.filter(club => {
    const matchesSearch = 
      club.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      club.description.toLowerCase().includes(searchQuery.toLowerCase());
    
    const matchesCategory = 
      selectedCategory === 'All Categories' || club.category === selectedCategory;
    
    return matchesSearch && matchesCategory;
  });

  return (
    <div className="min-h-screen bg-muted/30">
      <Navbar />
      
      <div className="pt-24 pb-16">
        <div className="container px-4 md:px-6 max-w-7xl mx-auto">
          {/* Header */}
          <div className="mb-12 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
              <h1 className="text-4xl font-bold mb-2">University Clubs</h1>
              <p className="text-muted-foreground">
                Discover and join clubs that match your interests
              </p>
            </div>
            
            {user && (
              <Link to="/dashboard" className="btn-primary">
                <PlusCircle className="mr-2 h-4 w-4" />
                Request New Club
              </Link>
            )}
          </div>
          
          {/* Search and Filter */}
          <div className="mb-8 grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4">
            <div className="relative">
              <SearchIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={18} />
              <Input
                type="text"
                placeholder="Search for clubs..."
                className="pl-10"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            
            <div className="flex items-center space-x-2">
              <Filter className="text-muted-foreground" size={18} />
              <select 
                className="input-field max-w-xs"
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
              >
                {CATEGORIES.map(category => (
                  <option key={category} value={category}>{category}</option>
                ))}
              </select>
            </div>
          </div>
          
          {/* Club Listings */}
          {filteredClubs.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {filteredClubs.map((club, index) => (
                <div 
                  key={club.id}
                  className="fade-up"
                  style={{ animationDelay: `${index * 100}ms` }}
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
          ) : (
            <div className="text-center py-16 border border-dashed border-border rounded-lg">
              <h3 className="text-lg font-medium mb-2">No clubs found</h3>
              <p className="text-muted-foreground mb-4">
                {searchQuery 
                  ? `No clubs match "${searchQuery}" in ${selectedCategory === 'All Categories' ? 'any category' : selectedCategory}`
                  : 'No clubs available in this category'}
              </p>
              <Button onClick={() => {
                setSearchQuery('');
                setSelectedCategory('All Categories');
              }}>
                Reset Filters
              </Button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Clubs;
