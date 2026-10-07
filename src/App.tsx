import { useState } from 'react';
import { AppScreen, CartItem } from './types';
import { Header } from './components/Header';
import { Footer } from './components/Footer';
import { HomeScreen } from './components/HomeScreen';
import { CheckoutScreen } from './components/CheckoutScreen';
import { LiveTrackingScreen } from './components/LiveTrackingScreen';
import { CustomizerModal } from './components/CustomizerModal';
import { ScreenNavRibbon } from './components/ScreenNavRibbon';

export default function App() {
  const [currentScreen, setCurrentScreen] = useState<AppScreen>('explorar');
  const [isCustomizerOpen, setIsCustomizerOpen] = useState(false);
  const [appliedCoupon, setAppliedCoupon] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  // Initial cart items matching the provided mockups
  const [cartItems, setCartItems] = useState<CartItem[]>([
    {
      id: 'burger-init',
      name: 'Burger Monumental 200g',
      restaurant: 'La Estación Burger',
      basePrice: 24900,
      quantity: 1,
      doneness: 'Tres Cuartos (3/4)',
      bread: 'Pan Brioche Dorado Tradicional',
      toppings: [
        { name: 'Tocineta Ahumada Crunch', price: 3500, count: 1 },
        { name: 'Huevo Frito Campesino', price: 2000 },
        { name: 'Queso Campesino Asado', price: 3000 },
      ],
      sauces: ['tartara', 'bbq_guayaba'],
    },
    {
      id: 'papas-init',
      name: 'Papas Rústicas Romero',
      restaurant: 'La Estación Burger',
      basePrice: 7500,
      quantity: 1,
      toppings: [],
    },
  ]);

  const showToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => {
      setToastMessage(null);
    }, 3500);
  };

  const handleAddToCart = (newItem: CartItem) => {
    setCartItems((prev) => [newItem, ...prev]);
    showToast(`¡${newItem.name} agregada a tu orden!`);
  };

  const handleApplyCoupon = (code: string) => {
    if (code.trim().toUpperCase() === 'ANTOJONEIVA5K') {
      setAppliedCoupon(true);
      showToast('¡Cupón ANTOJONEIVA5K aplicado con éxito! (-$5.000 COP)');
    } else {
      showToast('Código no válido en Neiva. Prueba con ANTOJONEIVA5K');
    }
  };

  const handleRemoveCoupon = () => {
    setAppliedCoupon(false);
    showToast('Cupón promocional removido');
  };

  const handleConfirmOrder = () => {
    showToast('¡Pedido confirmado con éxito! Conectando con repartidor en Neiva...');
    setTimeout(() => {
      setCurrentScreen('rastreo');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 600);
  };

  const totalCartUnits = cartItems.reduce((acc, curr) => acc + curr.quantity, 0);

  return (
    <div className="min-h-screen flex flex-col bg-[#f9f9ff] text-[#111c2d] selection:bg-[#dc2626]/20 selection:text-[#b70011]">
      {/* Primary Top Header */}
      <Header
        currentScreen={currentScreen}
        onNavigate={(screen) => {
          setCurrentScreen(screen);
          setIsCustomizerOpen(false);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }}
        cartCount={totalCartUnits}
        onOpenCart={() => {
          setCurrentScreen('checkout');
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }}
        searchQuery={searchQuery}
        onSearchChange={setSearchQuery}
      />

      {/* Screen Navigation Ribbon for instantaneous previewing */}
      <ScreenNavRibbon
        currentScreen={currentScreen}
        onNavigate={(screen) => {
          setCurrentScreen(screen);
          setIsCustomizerOpen(false);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }}
        onOpenCustomizer={() => setIsCustomizerOpen(true)}
        isCustomizerOpen={isCustomizerOpen}
      />

      {/* Toast Notification Bar */}
      {toastMessage && (
        <div className="fixed bottom-6 right-6 z-50 bg-[#111c2d] text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-[#fea619] animate-in fade-in slide-in-from-bottom-4 duration-300">
          <span className="material-symbols-outlined text-[#fea619] text-xl">check_circle</span>
          <span className="font-label-md text-sm font-semibold">{toastMessage}</span>
          <button
            onClick={() => setToastMessage(null)}
            className="text-white/70 hover:text-white ml-2 text-sm cursor-pointer"
          >
            ✕
          </button>
        </div>
      )}

      {/* Main Screen Views */}
      <main className="flex-1 w-full">
        {currentScreen === 'explorar' && (
          <HomeScreen
            onOpenCustomizer={() => setIsCustomizerOpen(true)}
            onNavigate={(screen) => {
              setCurrentScreen(screen);
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }}
            cartItems={cartItems}
            appliedCoupon={appliedCoupon}
            onApplyCoupon={handleApplyCoupon}
            searchQuery={searchQuery}
          />
        )}

        {currentScreen === 'checkout' && (
          <CheckoutScreen
            onNavigate={(screen) => {
              setCurrentScreen(screen);
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }}
            onConfirmOrder={handleConfirmOrder}
            cartItems={cartItems}
            appliedCoupon={appliedCoupon}
            onRemoveCoupon={handleRemoveCoupon}
            onApplyCoupon={handleApplyCoupon}
          />
        )}

        {currentScreen === 'rastreo' && (
          <LiveTrackingScreen
            onNavigate={(screen) => {
              setCurrentScreen(screen);
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }}
          />
        )}
      </main>

      {/* Interactive Customizer Modal (Screen 3) */}
      <CustomizerModal
        isOpen={isCustomizerOpen}
        onClose={() => setIsCustomizerOpen(false)}
        onAddToCart={handleAddToCart}
        onGoToCheckout={() => {
          setIsCustomizerOpen(false);
          setCurrentScreen('checkout');
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }}
      />

      {/* Site Footer */}
      <Footer
        onNavigate={(screen) => {
          setCurrentScreen(screen);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }}
      />
    </div>
  );
}
