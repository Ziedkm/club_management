
import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { useAuth } from '@/context/AuthContext';
import NotificationForm from '@/components/NotificationForm';
import { Users, Bell, UserPlus, Settings } from 'lucide-react';

// Main Dashboard component
const DashboardComponent = () => {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('overview');
  
  return (
    <div className="space-y-8">
      {/* Dashboard header */}
      <div>
        <h1 className="text-3xl font-bold mb-2">Dashboard</h1>
        <p className="text-muted-foreground">
          Manage your clubs, notifications, and account settings
        </p>
      </div>
      
      {/* Welcome alert - helps user understand what they can do */}
      <Alert>
        <AlertTitle>Welcome, {user?.username}!</AlertTitle>
        <AlertDescription>
          This is your personal dashboard. Here you can manage club memberships, 
          send notifications, and update your profile information.
        </AlertDescription>
      </Alert>
      
      {/* Main dashboard tabs */}
      <Tabs defaultValue="overview" value={activeTab} onValueChange={setActiveTab} className="space-y-4">
        <TabsList className="grid grid-cols-4 md:w-[600px]">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="clubs">My Clubs</TabsTrigger>
          <TabsTrigger value="notifications">Notifications</TabsTrigger>
          <TabsTrigger value="settings">Settings</TabsTrigger>
        </TabsList>
        
        {/* Overview tab content */}
        <TabsContent value="overview" className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Quick stats */}
            <Card>
              <CardHeader>
                <CardTitle>My Clubs</CardTitle>
                <CardDescription>Clubs you belong to or manage</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold mb-2">2</div>
                <p className="text-muted-foreground text-sm">
                  Member of 1 club, leader of 1 club
                </p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader>
                <CardTitle>Notifications</CardTitle>
                <CardDescription>Recent activity and announcements</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold mb-2">3</div>
                <p className="text-muted-foreground text-sm">
                  2 unread messages, 1 announcement
                </p>
              </CardContent>
            </Card>
          </div>
          
          {/* Quick actions */}
          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
              <CardDescription>Common tasks and activities</CardDescription>
            </CardHeader>
            <CardContent className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <button 
                className="p-4 border border-border rounded-lg flex flex-col items-center text-center hover:bg-muted/50 transition-colors"
                onClick={() => setActiveTab('clubs')}
              >
                <Users className="h-10 w-10 text-club mb-2" />
                <div className="font-medium">Manage Clubs</div>
                <p className="text-xs text-muted-foreground mt-1">View and manage your club memberships</p>
              </button>
              
              <button 
                className="p-4 border border-border rounded-lg flex flex-col items-center text-center hover:bg-muted/50 transition-colors"
                onClick={() => setActiveTab('notifications')}
              >
                <Bell className="h-10 w-10 text-club mb-2" />
                <div className="font-medium">Send Notifications</div>
                <p className="text-xs text-muted-foreground mt-1">Send announcements to club members</p>
              </button>
              
              <button 
                className="p-4 border border-border rounded-lg flex flex-col items-center text-center hover:bg-muted/50 transition-colors"
                onClick={() => setActiveTab('settings')}
              >
                <Settings className="h-10 w-10 text-club mb-2" />
                <div className="font-medium">Account Settings</div>
                <p className="text-xs text-muted-foreground mt-1">Update your profile information</p>
              </button>
            </CardContent>
          </Card>
        </TabsContent>
        
        {/* Clubs tab content */}
        <TabsContent value="clubs" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>My Clubs</CardTitle>
              <CardDescription>Clubs you're a member of or manage</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Club where user is a leader */}
              <div className="border border-border rounded-lg p-4">
                <div className="flex justify-between items-start mb-2">
                  <div>
                    <h3 className="font-semibold text-lg">Photography Club</h3>
                    <p className="text-sm text-muted-foreground">Arts, 32 members</p>
                  </div>
                  <Badge>Club Leader</Badge>
                </div>
                <p className="text-sm mb-4">
                  For students passionate about photography and visual arts. Weekly meetups, photo walks, and workshops.
                </p>
                <div className="flex space-x-2">
                  <button className="text-sm px-3 py-1 bg-muted rounded hover:bg-muted/80 transition-colors">
                    Manage Members
                  </button>
                  <button className="text-sm px-3 py-1 bg-muted rounded hover:bg-muted/80 transition-colors">
                    Send Notification
                  </button>
                </div>
              </div>
              
              {/* Club where user is a member */}
              <div className="border border-border rounded-lg p-4">
                <div className="flex justify-between items-start mb-2">
                  <div>
                    <h3 className="font-semibold text-lg">Debate Society</h3>
                    <p className="text-sm text-muted-foreground">Academic, 45 members</p>
                  </div>
                  <Badge variant="outline">Member</Badge>
                </div>
                <p className="text-sm mb-4">
                  Improve your public speaking and critical thinking skills in our regular debate competitions.
                </p>
                <div className="flex space-x-2">
                  <button className="text-sm px-3 py-1 bg-muted rounded hover:bg-muted/80 transition-colors">
                    View Details
                  </button>
                  <button className="text-sm px-3 py-1 bg-muted rounded hover:bg-muted/80 transition-colors">
                    Leave Club
                  </button>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
        
        {/* Notifications tab - for BF4 requirement */}
        <TabsContent value="notifications" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Send Notifications</CardTitle>
              <CardDescription>
                Send notifications to club members or specific students
              </CardDescription>
            </CardHeader>
            <CardContent>
              <NotificationForm />
            </CardContent>
          </Card>
        </TabsContent>
        
        {/* Settings tab */}
        <TabsContent value="settings" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Account Settings</CardTitle>
              <CardDescription>Manage your profile and account preferences</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-muted-foreground mb-4">
                You can update your profile information from the Profile page.
              </p>
              <a href="/profile" className="btn-primary inline-block">
                Go to Profile
              </a>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default DashboardComponent;
