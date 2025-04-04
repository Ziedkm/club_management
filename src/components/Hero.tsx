
import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, Users, Calendar, Bell } from 'lucide-react';

const Hero = () => {
  return (
    <div className="relative overflow-hidden pb-16 pt-32 md:pt-40">
      {/* Background elements */}
      <div className="absolute inset-0 -z-10 bg-[radial-gradient(45%_40%_at_50%_30%,hsl(var(--club-primary)/0.1),transparent)] dark:bg-[radial-gradient(45%_40%_at_50%_30%,hsl(var(--club-primary)/0.2),transparent)]" />
      <div className="absolute inset-0 -z-10 bg-noise opacity-30" />
      
      <div className="container px-4 md:px-6">
        <div className="grid gap-6 lg:grid-cols-[1fr_400px] lg:gap-12 xl:grid-cols-[1fr_600px]">
          <div className="flex flex-col justify-center space-y-4">
            <div className="space-y-2">
              <h1 className="text-4xl font-bold tracking-tighter sm:text-5xl md:text-6xl lg:text-7xl/none fade-up" style={{ animationDelay: '100ms' }}>
                Connecting campus communities
              </h1>
              <p className="max-w-[600px] text-gray-500 md:text-xl dark:text-gray-400 fade-up" style={{ animationDelay: '300ms' }}>
                Discover, join, and manage university clubs all in one place — your gateway to campus engagement.
              </p>
            </div>
            <div className="flex flex-col gap-3 min-[400px]:flex-row fade-up" style={{ animationDelay: '500ms' }}>
              <Link to="/clubs" className="btn-primary inline-flex items-center gap-2">
                Explore Clubs
                <ArrowRight className="h-4 w-4" />
              </Link>
              <Link to="/auth" className="btn-secondary">
                Create Account
              </Link>
            </div>
            
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3 fade-up" style={{ animationDelay: '700ms' }}>
              <div className="flex items-center gap-2 rounded-lg border border-border bg-background p-4 shadow-sm">
                <Users className="h-5 w-5 text-club" />
                <div>
                  <h3 className="font-semibold">Club Management</h3>
                  <p className="text-sm text-muted-foreground">Create and manage clubs</p>
                </div>
              </div>
              <div className="flex items-center gap-2 rounded-lg border border-border bg-background p-4 shadow-sm">
                <Calendar className="h-5 w-5 text-club" />
                <div>
                  <h3 className="font-semibold">Member Tracking</h3>
                  <p className="text-sm text-muted-foreground">Track club members</p>
                </div>
              </div>
              <div className="flex items-center gap-2 rounded-lg border border-border bg-background p-4 shadow-sm">
                <Bell className="h-5 w-5 text-club" />
                <div>
                  <h3 className="font-semibold">Notifications</h3>
                  <p className="text-sm text-muted-foreground">Send notifications</p>
                </div>
              </div>
            </div>
          </div>
          
          <div className="mx-auto flex items-center justify-center lg:justify-end fade-up" style={{ animationDelay: '900ms' }}>
            <div className="relative h-[350px] w-[350px] sm:h-[400px] sm:w-[400px] md:h-[450px] md:w-[450px] lg:h-[500px] lg:w-[500px]">
              <div className="absolute left-1/2 top-1/2 h-[300px] w-[300px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-br from-club-light to-club opacity-20 blur-3xl" />
              
              <div className="absolute left-[40%] top-[35%] -translate-x-1/2 -translate-y-1/2 animate-pulse-subtle">
                <div className="glass-panel rounded-2xl border-club/20 p-4 shadow-lg">
                  <div className="flex items-center gap-3">
                    <div className="h-10 w-10 rounded-full bg-club/20 flex items-center justify-center text-club">
                      <Users size={20} />
                    </div>
                    <div>
                      <h3 className="font-medium">Photography Club</h3>
                      <p className="text-xs text-muted-foreground">32 members</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="absolute right-[30%] top-[60%] -translate-x-1/2 -translate-y-1/2 animate-pulse-subtle" style={{ animationDelay: '1s' }}>
                <div className="glass-panel rounded-2xl border-club/20 p-4 shadow-lg">
                  <div className="flex items-center gap-3">
                    <div className="h-10 w-10 rounded-full bg-club/20 flex items-center justify-center text-club">
                      <Bell size={20} />
                    </div>
                    <div>
                      <h3 className="font-medium">New Notification</h3>
                      <p className="text-xs text-muted-foreground">Chess club meeting</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="absolute left-[35%] bottom-[15%] -translate-x-1/2 -translate-y-1/2 animate-pulse-subtle" style={{ animationDelay: '1.5s' }}>
                <div className="glass-panel rounded-2xl border-club/20 p-4 shadow-lg">
                  <div className="flex items-center gap-3">
                    <div className="h-10 w-10 rounded-full bg-club/20 flex items-center justify-center text-club">
                      <Calendar size={20} />
                    </div>
                    <div>
                      <h3 className="font-medium">Upcoming Events</h3>
                      <p className="text-xs text-muted-foreground">3 events this week</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Hero;
