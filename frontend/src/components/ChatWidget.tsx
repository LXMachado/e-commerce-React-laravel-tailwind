import { useState } from 'react';
import { ChatBubbleLeftRightIcon, XMarkIcon } from '@heroicons/react/24/outline';

const ChatWidget = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState([
    { text: 'Hi! How can I help you today?', sender: 'bot' },
  ]);
  const [input, setInput] = useState('');

  const toggleChat = () => setIsOpen(!isOpen);

  const sendMessage = () => {
    if (input.trim()) {
      setMessages([...messages, { text: input, sender: 'user' }]);
      setInput('');
      // Simulate bot response
      setTimeout(() => {
        setMessages(prev => [...prev, { text: 'Thanks for your message! Our team will get back to you soon.', sender: 'bot' }]);
      }, 1000);
    }
  };

  return (
    <>
      <button
        onClick={toggleChat}
        className="fixed bottom-4 right-4 bg-emerald-500 p-3 rounded-full shadow-lg hover:bg-emerald-600 transition"
        aria-label="Open chat support"
      >
        <ChatBubbleLeftRightIcon className="w-6 h-6 text-white" />
      </button>
      {isOpen && (
        <div className="fixed bottom-4 right-4 w-80 h-96 bg-white/10 backdrop-blur-xl rounded-lg shadow-lg border border-white/20">
          <div className="flex justify-between items-center p-4 border-b border-white/20">
            <h3 className="text-emerald-100 font-semibold">Chat Support</h3>
            <button onClick={toggleChat}>
              <XMarkIcon className="w-5 h-5 text-emerald-100" />
            </button>
          </div>
          <div className="p-4 h-64 overflow-y-auto">
            {messages.map((msg, index) => (
              <div key={index} className={`mb-2 ${msg.sender === 'user' ? 'text-right' : 'text-left'}`}>
                <span className={`inline-block p-2 rounded-lg ${msg.sender === 'user' ? 'bg-emerald-500 text-white' : 'bg-white/20 text-emerald-100'}`}>
                  {msg.text}
                </span>
              </div>
            ))}
          </div>
          <div className="p-4 border-t border-white/20">
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder="Type your message..."
              className="w-full p-2 rounded-lg bg-white/20 text-emerald-100 placeholder:text-emerald-100/50"
              onKeyPress={(e) => e.key === 'Enter' && sendMessage()}
            />
            <button
              onClick={sendMessage}
              className="mt-2 w-full bg-emerald-500 text-white p-2 rounded-lg hover:bg-emerald-600 transition"
            >
              Send
            </button>
          </div>
        </div>
      )}
    </>
  );
};

export default ChatWidget;