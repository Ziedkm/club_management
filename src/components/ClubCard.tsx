
import React from 'react';
import { Link } from 'react-router-dom';
import { Users, Clock, ArrowRight } from 'lucide-react';

type ClubCardProps = {
  id: string;
  name: string;
  description: string;
  memberCount: number;
  createdAt: string;
  compact?: boolean;
};

const ClubCard = ({ id, name, description, memberCount, createdAt, compact = false }: ClubCardProps) => {
  if (compact) {
    return (
      <Link 
        to={`/clubs/${id}`}
        className="block p-4 rounded-xl border border-border bg-card shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-1 hover:border-club/20"
      >
        <div className="flex justify-between items-center">
          <h3 className="font-semibold text-lg">{name}</h3>
          <div className="flex items-center text-xs text-muted-foreground">
            <Users size={14} className="mr-1" />
            <span>{memberCount}</span>
          </div>
        </div>
        <p className="mt-1 text-sm text-muted-foreground line-clamp-2">{description}</p>
      </Link>
    );
  }

  return (
    <div className="flex flex-col h-full bg-card rounded-xl overflow-hidden border border-border shadow-card card-hover">
      <div className="h-36 bg-gradient-to-br from-club/80 to-club-dark/90 relative overflow-hidden">
        <div className="absolute inset-0 bg-noise opacity-10"></div>
        <div className="p-6">
          <span className="inline-block px-2 py-1 bg-white/20 backdrop-blur-sm rounded-md text-white text-xs font-medium">
            Club
          </span>
        </div>
      </div>
      
      <div className="flex-1 p-6">
        <h3 className="text-xl font-semibold mb-2">{name}</h3>
        <p className="text-muted-foreground mb-4 line-clamp-3">{description}</p>
        
        <div className="flex items-center justify-between text-sm text-muted-foreground mb-4">
          <div className="flex items-center">
            <Users size={16} className="mr-1" />
            <span>{memberCount} members</span>
          </div>
          <div className="flex items-center">
            <Clock size={16} className="mr-1" />
            <span>{new Date(createdAt).toLocaleDateString()}</span>
          </div>
        </div>
        
        <Link 
          to={`/clubs/${id}`}
          className="w-full flex items-center justify-center space-x-2 px-4 py-2 rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground font-medium transition-colors"
        >
          <span>View Details</span>
          <ArrowRight size={16} />
        </Link>
      </div>
    </div>
  );
};

export default ClubCard;
