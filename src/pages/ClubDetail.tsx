
import React, { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import Navbar from '@/components/Navbar';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/context/AuthContext';
import { toast } from 'sonner';
import { Users, Calendar, ArrowLeft, Mail, UserPlus, UserCheck, Shield } from 'lucide-react';

// Mock data for club details (simplified)
const CLUB_DETAILS = {
  '1': {
    id: '1',
    name: 'Photography Club',
    description: 'For students passionate about photography and visual arts. Weekly meetups, photo walks, and workshops.',
    longDescription: 'The Photography Club brings together students with a shared interest in photography and visual storytelling. Our club offers a supportive environment for photographers of all skill levels, from beginners to advanced.',
    memberCount: 32,
    createdAt: '2023-05-15',
    leaderId: '2',
    leaderName: 'David Wilson',
    category: 'Arts',
    meetingSchedule: 'Thursdays at 6 PM',
    location: 'Arts Building, Room 105',
    members: [
      { id: '2', username: 'davidwilson', role: 'leader' },
      { id: '1', username: 'emilyjohnson', role: 'member' },
      { id: '3', username: 'sarahmiller', role: 'member' }
    ]
  },
  '2': {
    id: '2',
    name: 'Debate Society',
    description: 'Improve your public speaking and critical thinking skills in our regular debate competitions.',
    longDescription: 'The Debate Society is dedicated to fostering public speaking skills, critical thinking, and persuasive argumentation. Our club hosts weekly practice debates on a variety of topics.',
    memberCount: 45,
    createdAt: '2022-09-10',
    leaderId: '3',
    leaderName: 'Sarah Miller',
    category: 'Academic',
    meetingSchedule: 'Tuesdays at 5 PM',
    location: 'Liberal Arts Building, Room 220',
    members: [
      { id: '3', username: 'sarahmiller', role: 'leader' },
      { id: '1', username: 'emilyjohnson', role: 'member' }
    ]
  }
};

// Main ClubDetail component
const ClubDetail = () => {
  const { id } = useParams<{ id: string }>();
  const { user } = useAuth();
  const navigate = useNavigate();
  const [club, setClub] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isMember, setIsMember] = useState(false);
  const [isLeader, setIsLeader] = useState(false);
  
  // Fetch club details
  useEffect(() => {
    const fetchClub = async () => {
      setIsLoading(true);
      try {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500));
        
        if (id && CLUB_DETAILS[id as keyof typeof CLUB_DETAILS]) {
          const clubData = CLUB_DETAILS[id as keyof typeof CLUB_DETAILS];
          setClub(clubData);
          
          // Check if user is a member or leader
          if (user) {
            const memberInfo = clubData.members.find((m: any) => m.username === user.username);
            setIsMember(!!memberInfo);
            setIsLeader(memberInfo?.role === 'leader');
          }
        } else {
          // Club not found
          toast.error('Club not found');
          navigate('/clubs');
        }
      } catch (error) {
        console.error('Error fetching club:', error);
        toast.error('Failed to load club details');
      } finally {
        setIsLoading(false);
      }
    };
    
    fetchClub();
  }, [id, user, navigate]);
  
  // Handle joining a club
  const handleJoinClub = async () => {
    if (!user) {
      toast.error('Please sign in to join clubs');
      navigate('/auth');
      return;
    }
    
    try {
      // Simulate joining club
      await new Promise(resolve => setTimeout(resolve, 800));
      toast.success(`Successfully joined ${club.name}`);
      setIsMember(true);
    } catch (error) {
      console.error('Error joining club:', error);
      toast.error('Failed to join club');
    }
  };
  
  // Handle leaving a club
  const handleLeaveClub = async () => {
    try {
      // Simulate leaving club
      await new Promise(resolve => setTimeout(resolve, 800));
      toast.success(`Successfully left ${club.name}`);
      setIsMember(false);
    } catch (error) {
      console.error('Error leaving club:', error);
      toast.error('Failed to leave club');
    }
  };

  // Show loading skeleton
  if (isLoading) {
    return (
      <div className="min-h-screen bg-muted/30">
        <Navbar />
        <div className="pt-24 pb-16">
          <div className="container px-4 md:px-6 max-w-7xl mx-auto">
            <div className="flex items-center justify-center min-h-[50vh]">
              <div className="animate-pulse space-y-8 w-full">
                <div className="h-8 bg-muted rounded w-1/3"></div>
                <div className="h-4 bg-muted rounded w-2/3"></div>
                <div className="h-64 bg-muted rounded"></div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div className="h-32 bg-muted rounded"></div>
                  <div className="h-32 bg-muted rounded"></div>
                  <div className="h-32 bg-muted rounded"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
  
  // Return null if club not found
  if (!club) {
    return null;
  }

  // Main club detail view
  return (
    <div className="min-h-screen bg-muted/30">
      <Navbar />
      
      <div className="pt-24 pb-16">
        <div className="container px-4 md:px-6 max-w-7xl mx-auto">
          {/* Back button */}
          <Link 
            to="/clubs" 
            className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-6 transition-colors"
          >
            <ArrowLeft className="mr-1 h-4 w-4" />
            Back to Clubs
          </Link>
          
          {/* Club Header */}
          <div className="mb-10 fade-up">
            <div className="flex flex-col md:flex-row justify-between md:items-center gap-4">
              <div>
                <h1 className="text-4xl font-bold mb-2">{club.name}</h1>
                <p className="text-muted-foreground mb-4">{club.description}</p>
                <div className="flex flex-wrap gap-4">
                  <span className="inline-flex items-center px-3 py-1 rounded-full bg-club/10 text-club text-sm">
                    <Users className="mr-1 h-4 w-4" />
                    {club.memberCount} members
                  </span>
                  <span className="inline-flex items-center px-3 py-1 rounded-full bg-secondary text-secondary-foreground text-sm">
                    {club.category}
                  </span>
                  <span className="inline-flex items-center px-3 py-1 rounded-full bg-secondary text-secondary-foreground text-sm">
                    <Calendar className="mr-1 h-4 w-4" />
                    {club.meetingSchedule}
                  </span>
                </div>
              </div>
              
              {/* Action buttons */}
              <div className="flex gap-3">
                {!user ? (
                  <Link to="/auth" className="btn-primary">
                    Sign In to Join
                  </Link>
                ) : isLeader ? (
                  <Link to="/dashboard" className="btn-primary">
                    Manage Club
                  </Link>
                ) : isMember ? (
                  <Button 
                    className="btn-secondary"
                    onClick={handleLeaveClub}
                  >
                    Leave Club
                  </Button>
                ) : (
                  <Button 
                    className="btn-primary flex items-center"
                    onClick={handleJoinClub}
                  >
                    <UserPlus className="mr-2 h-4 w-4" />
                    Join Club
                  </Button>
                )}
                
                {(isMember || isLeader) && (
                  <Link to="/dashboard" className="btn-secondary">
                    <Mail className="mr-2 h-4 w-4" />
                    Contact
                  </Link>
                )}
              </div>
            </div>
          </div>
          
          {/* Main Content */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Club Details */}
            <div className="lg:col-span-2 space-y-8 fade-up" style={{ animationDelay: '100ms' }}>
              {/* About */}
              <div className="bg-card border border-border rounded-xl p-6 shadow-sm">
                <h2 className="text-xl font-semibold mb-4">About</h2>
                <p className="text-muted-foreground whitespace-pre-line">
                  {club.longDescription}
                </p>
              </div>
              
              {/* Meeting Information */}
              <div className="bg-card border border-border rounded-xl p-6 shadow-sm">
                <h2 className="text-xl font-semibold mb-4">Meeting Information</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <h3 className="font-medium mb-1">Schedule</h3>
                    <p className="text-muted-foreground">{club.meetingSchedule}</p>
                  </div>
                  <div>
                    <h3 className="font-medium mb-1">Location</h3>
                    <p className="text-muted-foreground">{club.location}</p>
                  </div>
                </div>
              </div>
              
              {/* Members list - visible to members and leaders */}
              {(isMember || isLeader) && (
                <div className="bg-card border border-border rounded-xl p-6 shadow-sm">
                  <h2 className="text-xl font-semibold mb-4">Members</h2>
                  <div className="space-y-4">
                    {club.members.map((member: any) => (
                      <div key={member.id} className="flex items-center justify-between">
                        <div className="flex items-center">
                          <div className="h-10 w-10 rounded-full bg-club-light text-club flex items-center justify-center">
                            {member.username[0].toUpperCase()}
                          </div>
                          <div className="ml-3">
                            <p className="font-medium">{member.username}</p>
                            <p className="text-xs text-muted-foreground">
                              {member.role === 'leader' ? 'Club Leader' : 'Member'}
                            </p>
                          </div>
                        </div>
                        {member.role === 'leader' && (
                          <Shield className="h-4 w-4 text-club" />
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
            
            {/* Sidebar */}
            <div className="space-y-6 fade-up" style={{ animationDelay: '200ms' }}>
              {/* Leadership */}
              <div className="bg-card border border-border rounded-xl p-6 shadow-sm">
                <h2 className="text-xl font-semibold mb-4">Leadership</h2>
                <div className="flex items-center">
                  <div className="h-12 w-12 rounded-full bg-club-light text-club flex items-center justify-center text-xl font-bold">
                    {club.leaderName.charAt(0)}
                  </div>
                  <div className="ml-4">
                    <p className="font-semibold">{club.leaderName}</p>
                    <p className="text-sm text-muted-foreground">Club Leader</p>
                  </div>
                </div>
              </div>
              
              {/* Club Stats */}
              <div className="bg-card border border-border rounded-xl p-6 shadow-sm">
                <h2 className="text-xl font-semibold mb-4">Club Stats</h2>
                <div className="space-y-3">
                  <div>
                    <p className="text-sm text-muted-foreground">Members</p>
                    <p className="font-semibold">{club.memberCount}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Established</p>
                    <p className="font-semibold">{new Date(club.createdAt).toLocaleDateString()}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Category</p>
                    <p className="font-semibold">{club.category}</p>
                  </div>
                </div>
              </div>
              
              {/* Join Info */}
              {!user || (!isMember && !isLeader) ? (
                <div className="bg-gradient-to-br from-club to-club-dark text-white rounded-xl p-6 shadow-md">
                  <h2 className="text-xl font-semibold mb-2">Join {club.name}</h2>
                  <p className="text-white/80 mb-4">
                    Connect with like-minded students and participate in club activities
                  </p>
                  {!user ? (
                    <Link to="/auth" className="block w-full py-2 bg-white text-club text-center rounded-lg font-medium hover:bg-opacity-90 transition-colors">
                      Sign In to Join
                    </Link>
                  ) : (
                    <Button 
                      className="w-full bg-white text-club hover:bg-opacity-90"
                      onClick={handleJoinClub}
                    >
                      <UserPlus className="mr-2 h-4 w-4" />
                      Join Now
                    </Button>
                  )}
                </div>
              ) : isLeader && (
                <div className="bg-gradient-to-br from-club to-club-dark text-white rounded-xl p-6 shadow-md">
                  <h2 className="text-xl font-semibold mb-2">Manage Your Club</h2>
                  <p className="text-white/80 mb-4">
                    Send notifications, manage members, and update club information
                  </p>
                  <Link to="/dashboard" className="block w-full py-2 bg-white text-club text-center rounded-lg font-medium hover:bg-opacity-90 transition-colors">
                    Go to Dashboard
                  </Link>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ClubDetail;
