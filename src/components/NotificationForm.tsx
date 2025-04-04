
import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { AtSign, Send, Loader2 } from 'lucide-react';

const NotificationForm = () => {
  const [formData, setFormData] = useState({
    recipient: '',
    subject: '',
    message: '',
  });
  
  const [errors, setErrors] = useState({
    recipient: '',
    subject: '',
    message: '',
  });
  
  const [isLoading, setIsLoading] = useState(false);
  
  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
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
    
    // Validate recipient
    if (!formData.recipient.trim()) {
      newErrors.recipient = 'Recipient email is required';
      valid = false;
    } else if (!/\S+@\S+\.\S+/.test(formData.recipient)) {
      newErrors.recipient = 'Invalid email format';
      valid = false;
    }
    
    // Validate subject
    if (!formData.subject.trim()) {
      newErrors.subject = 'Subject is required';
      valid = false;
    }
    
    // Validate message
    if (!formData.message.trim()) {
      newErrors.message = 'Message is required';
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
      // Simulate sending notification
      await new Promise(resolve => setTimeout(resolve, 1500));
      
      toast.success('Notification sent successfully');
      
      // Reset form after successful submission
      setFormData({
        recipient: '',
        subject: '',
        message: '',
      });
    } catch (error) {
      console.error('Notification error:', error);
      toast.error('Failed to send notification. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="space-y-2">
        <label htmlFor="recipient" className="block text-sm font-medium">
          Recipient Email
        </label>
        <div className="relative">
          <Input
            id="recipient"
            name="recipient"
            type="email"
            placeholder="email@university.edu"
            value={formData.recipient}
            onChange={handleChange}
            className={`pl-10 ${errors.recipient ? 'border-destructive' : ''}`}
          />
          <AtSign className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={18} />
        </div>
        {errors.recipient && <p className="text-destructive text-sm">{errors.recipient}</p>}
      </div>
      
      <div className="space-y-2">
        <label htmlFor="subject" className="block text-sm font-medium">
          Subject
        </label>
        <Input
          id="subject"
          name="subject"
          type="text"
          placeholder="Notification subject"
          value={formData.subject}
          onChange={handleChange}
          className={errors.subject ? 'border-destructive' : ''}
        />
        {errors.subject && <p className="text-destructive text-sm">{errors.subject}</p>}
      </div>
      
      <div className="space-y-2">
        <label htmlFor="message" className="block text-sm font-medium">
          Message
        </label>
        <Textarea
          id="message"
          name="message"
          placeholder="Enter your message here..."
          rows={6}
          value={formData.message}
          onChange={handleChange}
          className={errors.message ? 'border-destructive' : ''}
        />
        {errors.message && <p className="text-destructive text-sm">{errors.message}</p>}
      </div>
      
      <div className="flex justify-end">
        <Button 
          type="submit" 
          className="flex items-center space-x-2 btn-primary"
          disabled={isLoading}
        >
          {isLoading ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              <span>Sending...</span>
            </>
          ) : (
            <>
              <Send className="h-4 w-4" />
              <span>Send Notification</span>
            </>
          )}
        </Button>
      </div>
    </form>
  );
};

export default NotificationForm;
