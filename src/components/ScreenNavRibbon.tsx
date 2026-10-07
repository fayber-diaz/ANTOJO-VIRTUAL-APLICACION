import React from 'react';
import { AppScreen } from '../types';

interface ScreenNavRibbonProps {
  currentScreen: AppScreen;
  onNavigate: (screen: AppScreen) => void;
  onOpenCustomizer: () => void;
  isCustomizerOpen: boolean;
}

export const ScreenNavRibbon: React.FC<ScreenNavRibbonProps> = ({
  currentScreen,
  onNavigate,
  onOpenCustomizer,
  isCustomizerOpen,
}) => {
  return (
    <div className="bg-[#111c2d] text-white py-2 px-4 shadow-md sticky top-20 z-45 border-b border-[#263143]">
      <div className="max-w-[1280px] mx-auto flex flex-wrap items-center justify-between gap-2 text-xs">
        <div className="flex items-center gap-2">
          <span className="w-2 h-2 rounded-full bg-[#fea619] animate-pulse"></span>
          <span className="font-bold text-[#fea619] uppercase tracking-wider text-[11px]">
            Pantallas del Prototipo:
          </span>
        </div>

        <div className="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5">
          {/* Screen 1: Explorar */}
          <button
            type="button"
            onClick={() => onNavigate('explorar')}
            className={`px-3 py-1 rounded-full font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap ${
              currentScreen === 'explorar' && !isCustomizerOpen
                ? 'bg-[#dc2626] text-white shadow-sm'
                : 'bg-white/10 text-white/80 hover:bg-white/20 hover:text-white'
            }`}
          >
            <span>1. Explorar Restaurantes</span>
          </button>

          {/* Screen 2: Modal */}
          <button
            type="button"
            onClick={onOpenCustomizer}
            className={`px-3 py-1 rounded-full font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap ${
              isCustomizerOpen
                ? 'bg-[#fea619] text-[#2a1700] shadow-sm'
                : 'bg-white/10 text-white/80 hover:bg-white/20 hover:text-white'
            }`}
          >
            <span className="material-symbols-outlined text-sm">tune</span>
            <span>2. Personalizador (Modal)</span>
          </button>

          {/* Screen 3: Checkout */}
          <button
            type="button"
            onClick={() => onNavigate('checkout')}
            className={`px-3 py-1 rounded-full font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap ${
              currentScreen === 'checkout' && !isCustomizerOpen
                ? 'bg-[#dc2626] text-white shadow-sm'
                : 'bg-white/10 text-white/80 hover:bg-white/20 hover:text-white'
            }`}
          >
            <span>3. Entrega & Pago</span>
          </button>

          {/* Screen 4: Rastreo */}
          <button
            type="button"
            onClick={() => onNavigate('rastreo')}
            className={`px-3 py-1 rounded-full font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap ${
              currentScreen === 'rastreo' && !isCustomizerOpen
                ? 'bg-[#00682e] text-white shadow-sm'
                : 'bg-white/10 text-white/80 hover:bg-white/20 hover:text-white'
            }`}
          >
            <span className="material-symbols-outlined text-sm">near_me</span>
            <span>4. Rastreo GPS Neiva</span>
          </button>
        </div>
      </div>
    </div>
  );
};
