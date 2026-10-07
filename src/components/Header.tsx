import React from 'react';
import { AppScreen } from '../types';

interface HeaderProps {
  currentScreen: AppScreen;
  onNavigate: (screen: AppScreen) => void;
  cartCount: number;
  onOpenCart: () => void;
  onChangeAddress?: () => void;
  searchQuery: string;
  onSearchChange: (query: string) => void;
}

export const Header: React.FC<HeaderProps> = ({
  currentScreen,
  onNavigate,
  cartCount,
  onOpenCart,
  onChangeAddress,
  searchQuery,
  onSearchChange,
}) => {
  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-[#ffffff]/95 backdrop-blur-md shadow-[0_4px_16px_-2px_rgba(30,41,59,0.06)] border-b border-[#e7eeff]">
      <div className="h-20 max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10 flex items-center justify-between gap-4 sm:gap-6">
        {/* Brand & Address */}
        <div className="flex items-center gap-4 shrink-0">
          <button
            onClick={() => onNavigate('explorar')}
            className="flex items-center gap-2 cursor-pointer focus:outline-none group text-left"
          >
            <div className="w-9 h-9 rounded-lg bg-[#dc2626] flex items-center justify-center text-white font-black text-xl shadow-md group-hover:scale-105 transition-transform">
              <span className="material-symbols-outlined text-2xl">local_fire_department</span>
            </div>
            <span className="font-headline-sm text-lg sm:text-xl text-[#b70011] tracking-tight font-extrabold">
              ANTOJO-VIRTUAL
            </span>
          </button>

          {/* Delivery Location pill button */}
          <button
            onClick={onChangeAddress}
            type="button"
            className="hidden xl:flex items-center gap-2 bg-[#f0f3ff] hover:bg-[#e7eeff] px-4 py-1.5 rounded-full transition-all text-left border border-[#d8e3fb] cursor-pointer"
          >
            <span className="material-symbols-outlined text-[#b70011] text-xl">location_on</span>
            <div className="flex flex-col">
              <span className="font-label-sm text-[11px] text-[#5c403c] uppercase tracking-wider font-bold">
                Entrega en Neiva
              </span>
              <span className="font-body-sm text-xs text-[#111c2d] font-semibold truncate max-w-[170px]">
                Cra. 5 #18-42, Centro
              </span>
            </div>
            <span className="material-symbols-outlined text-[#5c403c] text-base">expand_more</span>
          </button>
        </div>

        {/* Search Bar */}
        <div className="hidden md:flex flex-1 max-w-[400px] items-center bg-[#f0f3ff] rounded-full px-4 py-2 shadow-inner border border-[#d8e3fb]/60 focus-within:border-[#dc2626] transition-colors">
          <span className="material-symbols-outlined text-[#5c403c] mr-2 text-xl">search</span>
          <input
            className="w-full bg-transparent border-0 focus:outline-none font-body-sm text-sm text-[#111c2d] placeholder:text-[#5c403c]/70"
            placeholder="¿Qué se te antoja hoy? (Burgers, Asados, Mazorcadas...)"
            type="text"
            value={searchQuery}
            onChange={(e) => onSearchChange(e.target.value)}
          />
          {searchQuery && (
            <button
              onClick={() => onSearchChange('')}
              className="text-[#5c403c] hover:text-[#b70011] p-1"
              title="Borrar búsqueda"
            >
              <span className="material-symbols-outlined text-base">close</span>
            </button>
          )}
          <button
            className="shrink-0 ml-1 p-1 text-[#5c403c] hover:text-[#b70011] transition-colors"
            type="button"
            title="Filtros de búsqueda"
          >
            <span className="material-symbols-outlined text-lg">tune</span>
          </button>
        </div>

        {/* Navigation links */}
        <nav className="hidden lg:flex items-center gap-1">
          <button
            onClick={() => onNavigate('explorar')}
            className={`px-4 py-2 rounded-full font-label-md text-sm transition-all cursor-pointer ${
              currentScreen === 'explorar'
                ? 'bg-[#dc2626] text-white font-bold shadow-sm'
                : 'text-[#5c403c] hover:text-[#111c2d] hover:bg-[#e7eeff]'
            }`}
          >
            Explorar Restaurantes
          </button>
          <button
            onClick={() => onNavigate('explorar')}
            className="px-4 py-2 rounded-full font-label-md text-sm text-[#5c403c] hover:text-[#111c2d] hover:bg-[#e7eeff] transition-all cursor-pointer"
          >
            Ofertas Huila
          </button>
          <button
            onClick={() => onNavigate('explorar')}
            className="px-4 py-2 rounded-full font-label-md text-sm text-[#5c403c] hover:text-[#111c2d] hover:bg-[#e7eeff] transition-all cursor-pointer"
          >
            Favoritos
          </button>
          <button
            onClick={() => onNavigate('rastreo')}
            className={`px-4 py-2 rounded-full font-label-md text-sm transition-all cursor-pointer ${
              currentScreen === 'rastreo'
                ? 'bg-[#dc2626] text-white font-bold shadow-sm'
                : 'text-[#5c403c] hover:text-[#111c2d] hover:bg-[#e7eeff]'
            }`}
          >
            Rastreo en Vivo
          </button>
        </nav>

        {/* Cart & User Profile */}
        <div className="flex items-center gap-3 shrink-0">
          <div className="hidden 2xl:flex items-center gap-1 px-3 py-1 bg-[#f0f3ff] rounded-full border border-[#d8e3fb]">
            <span className="material-symbols-outlined text-[#855300] text-base">flag</span>
            <span className="font-label-sm text-xs text-[#111c2d] font-bold">NEV (COL)</span>
          </div>

          <button
            onClick={onOpenCart}
            type="button"
            className="relative flex items-center justify-center bg-[#dc2626] text-white p-2.5 rounded-full hover:scale-105 active:scale-95 transition-all shadow-[0_4px_16px_-2px_rgba(220,38,38,0.3)] cursor-pointer"
            title="Ver carrito de compras"
          >
            <span className="material-symbols-outlined text-xl">shopping_bag</span>
            {cartCount > 0 && (
              <span className="absolute -top-1 -right-1 bg-[#fea619] text-[#684000] font-label-sm text-[11px] w-5 h-5 rounded-full flex items-center justify-center font-black shadow-sm">
                {cartCount}
              </span>
            )}
          </button>

          <div className="flex items-center gap-2 pl-1">
            <img
              alt="Perfil de Mateo Rodríguez"
              className="w-9 h-9 rounded-full object-cover ring-2 ring-[#d8e3fb] shadow-sm"
              src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80"
              onError={(e) => {
                // Fallback avatar if external image fails
                (e.target as HTMLElement).style.display = 'none';
              }}
            />
            <div className="hidden xl:flex flex-col text-left">
              <span className="font-label-md text-xs text-[#111c2d] font-bold">Mateo R.</span>
              <span className="text-[10px] text-[#00682e] font-semibold flex items-center gap-0.5">
                <span className="w-1.5 h-1.5 rounded-full bg-[#00682e]"></span> Huila VIP
              </span>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
};
