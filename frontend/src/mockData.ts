import { assetPath } from './utils/assetPath';

export interface Product {
  id: number;
  name: string;
  tagline: string;
  price: number;
  description: string;
  imageUrl: string;
  category: string;
  badges: string[];
}

export interface Feature {
  id: number;
  title: string;
  description: string;
  icon: string;
}

export const products: Product[] = [
  {
    id: 1,
    name: 'Aurora Solar Backpack',
    tagline: 'Charge as you roam with modular solar armor.',
    price: 199.99,
    description: 'Lightweight 32L pack crafted from recycled ripstop with removable solar tiles, USB-C PD hub, and a magnetic tech cradle for safe camp setups.',
    imageUrl: assetPath('images/aurora-solar-backpack.jpg'),
    category: 'Gear',
    badges: ['Solar Harvest', 'Modular', 'Recycled Nylon'],
  },
  {
    id: 2,
    name: 'Sequoia Lantern Hub',
    tagline: 'Ambient light meets dual USB quick charge.',
    price: 89.99,
    description: 'Bamboo-based lantern with 360° diffused glow, Qi wireless top plate, and dusk sensors so your basecamp stays lit without lifting a finger.',
    imageUrl: assetPath('images/sequoia-lantern.jpg'),
    category: 'Lighting',
    badges: ['USB-C 60W', 'Auto-Dusk', 'Bamboo Core'],
  },
  {
    id: 3,
    name: 'Alta Ridge Shelter',
    tagline: 'Featherlight dome with integrated solar skin.',
    price: 329.0,
    description: 'Two-person alpine tent featuring solar-coated fly, cross-vent thermal regulation, and color-coded quick-deploy poles for 90-second setups.',
    imageUrl: assetPath('images/alta-ridge-tent.jpg'),
    category: 'Shelter',
    badges: ['2.9 kg', 'Solar Roof', 'Storm Rated'],
  },
  {
    id: 4,
    name: 'ForgeTrail Multi-Tool',
    tagline: 'One tool, 18 precision-forged functions.',
    price: 74.5,
    description: 'Titanium-coated chassis, FSC bamboo grip, and field-ready implements with auto-lock channels for no-slip modular swaps.',
    imageUrl: assetPath('images/forgetrail-multitool.jpg'),
    category: 'Tools',
    badges: ['Bamboo Grip', 'Titanium Edge', '18-in-1'],
  },
  {
    id: 5,
    name: 'Summit Flow Bottle',
    tagline: 'Camp-hardened hydration with thermal memory.',
    price: 38.75,
    description: 'Ceramic-lined steel bottle with removable hemp sleeve, magnetic sip cap, and 18-hour thermal retention in backcountry temps.',
    imageUrl: assetPath('images/summit-flow-bottle.jpg'),
    category: 'Hydration',
    badges: ['Ceramic Core', 'Thermal Memory', 'Hemp Wrap'],
  },
  {
    id: 6,
    name: 'Horizon Pulse Generator',
    tagline: 'Adventure-grade power station with AI load balance.',
    price: 259.99,
    description: 'Ruggedized 40,000 mAh power core with GaN fast charge, satellite-ready output, and adaptive AI that shifts modes for climate-positive output.',
    imageUrl: assetPath('images/horizon-pulse-generator.jpg'),
    category: 'Power',
    badges: ['GaN Inside', 'AI Load Balance', 'IP68'],
  },
];

export const features: Feature[] = [
  {
    id: 1,
    title: 'Solar-First Engineering',
    description: 'Modular photovoltaics, GaN power cores, and AI load balancing turn every trailhead into a resilient micro-grid.',
    icon: 'leaf',
  },
  {
    id: 2,
    title: 'Energy Symbiosis',
    description: 'Each product is calibrated to work as a system—sharing power, data, and insights to stretch every lumen and watt.',
    icon: 'bolt',
  },
  {
    id: 3,
    title: 'Lifetime Durability',
    description: 'Bamboo composites, recycled ripstop, and titanium hardware promise gear that evolves instead of wears out.',
    icon: 'shield',
  },
];

export const heroText = {
  eyebrow: 'Eco • Tech • Outdoors',
  title: 'Rewild Your Weekend',
  highlight: 'Solar-Native Gear',
  subtitle: 'Designed for micro-expeditions, our regenerative tech keeps your basecamp powered, organized, and climate positive from trailhead to summit.',
  button1: 'Shop the Drop',
  button2: 'Explore Collection',
};

export const missionText = {
  title: 'Every Weekend Should Give Back to the Wild',
  description: 'Weekender funnels 2% of every purchase into reforestation while our circular build program keeps gear in play and plastics out of landfills.',
  button: 'See Our Impact Map',
};

export const newsletterText = {
  title: 'Join the Basecamp Broadcast',
  description: 'Monthly drops, field notes, and trail-tested tips from our eco-tech lab delivered with zero inbox waste.',
  placeholder: 'Enter your email to stay trail-ready',
  button: 'Get Updates',
};

export const footerText = {
  companyName: 'Weekender',
  tagline: 'Pioneering sustainable technology solutions for the environmentally conscious consumer.',
  quickLinks: ['Home', 'Products', 'Dashboard', 'Cart', 'Login', 'Register', 'About', 'Contact'],
  contact: {
    address: '123 Eco Street, Green City',
    email: 'info@weekender.com',
    phone: '+1 (555) 123-4567',
  },
  copyright: '© 2025 Weekender. All rights reserved. Pioneering sustainable technology.',
};

export const metrics = [
  { label: 'Products Sold', value: 25000, suffix: '+' },
  { label: 'Customer Satisfaction', value: 98, suffix: '%' },
  { label: 'Carbon Offset', value: 50000, suffix: ' kg' },
  { label: 'Trees Planted', value: 10000, suffix: '+' },
];

export const testimonials = [
  {
    id: 1,
    name: 'Alex Johnson',
    role: 'Outdoor Enthusiast',
    content: 'Weekender gear has transformed my camping trips. The solar backpack kept my devices charged all weekend!',
    rating: 5,
    image: assetPath('images/testimonial1.jpg'),
  },
  {
    id: 2,
    name: 'Maria Garcia',
    role: 'Hiker',
    content: 'Love the lantern! It\'s durable and eco-friendly. Perfect for my night hikes.',
    rating: 5,
    image: assetPath('images/testimonial2.jpg'),
  },
  {
    id: 3,
    name: 'John Doe',
    role: 'Camper',
    content: 'The multi-tool is a game-changer. So many functions in one compact design.',
    rating: 5,
    image: assetPath('images/testimonial3.jpg'),
  },
];

export const faqs = [
  {
    question: 'How do I charge the solar backpack?',
    answer: 'Place the backpack in direct sunlight for 4-6 hours to fully charge the integrated solar panels.',
  },
  {
    question: 'Are the products waterproof?',
    answer: 'Most products are water-resistant with IP ratings. Check individual product specs for details.',
  },
  {
    question: 'What is the warranty period?',
    answer: 'All products come with a 2-year warranty against manufacturing defects.',
  },
  {
    question: 'How do I return a product?',
    answer: 'You can initiate a return within 30 days via our website or contact customer support.',
  },
];
