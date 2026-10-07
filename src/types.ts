export type AppScreen = 'explorar' | 'checkout' | 'rastreo';

export interface CartItem {
  id: string;
  name: string;
  restaurant: string;
  basePrice: number;
  quantity: number;
  doneness?: string;
  bread?: string;
  toppings: {
    name: string;
    price: number;
    count?: number;
  }[];
  sauces?: string[];
  notes?: string;
  image?: string;
}

export interface Restaurant {
  id: string;
  name: string;
  address: string;
  rating: number;
  reviewsCount: number;
  deliveryTime: string;
  deliveryFee: number;
  badge?: string;
  badgeType?: 'verified' | 'bestseller' | 'speed';
  image: string;
  description: string;
  starDish: string;
  starDishPrice: number;
  tag: string;
  category: string;
}

export interface MenuItem {
  id: string;
  restaurant: string;
  name: string;
  price: number;
  description: string;
  image: string;
  tags: string[];
  category: string;
}
