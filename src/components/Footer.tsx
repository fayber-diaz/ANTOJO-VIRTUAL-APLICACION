import React from 'react';
import { AppScreen } from '../types';

interface FooterProps {
  onNavigate: (screen: AppScreen) => void;
}

export const Footer: React.FC<FooterProps> = ({ onNavigate }) => {
  return (
    <footer className="w-full bg-[#ffffff] shadow-[0_-8px_30px_rgba(30,41,59,0.06)] mt-12 border-t border-[#e7eeff]">
      <div className="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10 py-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        {/* Col 1: Brand Info */}
        <div className="flex flex-col gap-3">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-[#dc2626] flex items-center justify-center text-white font-black shadow">
              <span className="material-symbols-outlined text-xl">local_fire_department</span>
            </div>
            <span className="font-headline-sm text-lg text-[#b70011] tracking-tight font-extrabold">
              ANTOJO-VIRTUAL
            </span>
          </div>
          <p className="font-body-sm text-xs sm:text-sm text-[#5c403c] leading-relaxed">
            El epicentro digital de comida rápida, sabor callejero y parrilladas auténticas para Neiva, Huila. Entregas calientes y al instante en tu puerta.
          </p>
          <div className="flex items-center gap-2 mt-1">
            <a
              className="inline-flex items-center gap-1.5 bg-[#00682e] text-white font-label-sm text-xs px-4 py-2 rounded-full hover:bg-[#1a8340] transition-all shadow-sm"
              href="https://wa.me/573142899012"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span className="material-symbols-outlined text-base">chat</span>
              WhatsApp Soporte Huila
            </a>
          </div>
        </div>

        {/* Col 2: Restaurantes y Menús */}
        <div className="flex flex-col gap-2">
          <span className="font-headline-sm text-base text-[#111c2d] mb-1 font-bold">
            Restaurantes y Menús
          </span>
          <button
            onClick={() => onNavigate('explorar')}
            className="text-left font-body-sm text-sm text-[#5c403c] hover:text-[#b70011] transition-colors"
          >
            Hamburguesas Artesanales
          </button>
          <button
            onClick={() => onNavigate('explorar')}
            className="text-left font-body-sm text-sm text-[#5c403c] hover:text-[#b70011] transition-colors"
          >
            Parrilla & Asados del Huila
          </button>
          <button
            onClick={() => onNavigate('explorar')}
            className="text-left font-body-sm text-sm text-[#5c403c] hover:text-[#b70011] transition-colors"
          >
            Salchipapas & Mazorcadas
          </button>
          <button
            onClick={() => onNavigate('explorar')}
            className="text-left font-body-sm text-sm text-[#5c403c] hover:text-[#b70011] transition-colors"
          >
            Pizzas a la Leña Neiva
          </button>
          <button
            onClick={() => onNavigate('explorar')}
            className="text-left font-body-sm text-sm text-[#5c403c] hover:text-[#b70011] transition-colors"
          >
            Bebidas Típicas & Cervezas
          </button>
        </div>

        {/* Col 3: Cobertura Neiva */}
        <div className="flex flex-col gap-2">
          <span className="font-headline-sm text-base text-[#111c2d] mb-1 font-bold">
            Cobertura Neiva
          </span>
          <div className="grid grid-cols-2 gap-x-2 gap-y-1.5 font-body-sm text-xs sm:text-sm text-[#5c403c]">
            <span>Comuna 1 (Norte)</span>
            <span>Comuna 2 (Noroccidente)</span>
            <span>Comuna 3 (Entre Ríos)</span>
            <span>Comuna 4 (Central)</span>
            <span>Comuna 5 (Oriental)</span>
            <span>Comuna 6 (Sur)</span>
            <span>Comuna 7 (Ipanema)</span>
            <span>Comuna 10 (Las Palmas)</span>
          </div>
          <span className="font-label-sm text-xs text-[#855300] font-semibold mt-2 flex items-center gap-1">
            <span className="material-symbols-outlined text-sm">schedule</span>
            Envíos en menos de 35 min garantizados
          </span>
        </div>

        {/* Col 4: Soporte & Legal */}
        <div className="flex flex-col gap-2">
          <span className="font-headline-sm text-base text-[#111c2d] mb-1 font-bold">
            Soporte & Legal
          </span>
          <button
            onClick={() => onNavigate('rastreo')}
            className="text-left font-body-sm text-sm text-[#5c403c] hover:text-[#b70011] transition-colors"
          >
            Rastrear mi Pedido
          </button>
          <a
            href="#afiliar"
            className="font-body-sm text-sm text-[#5c403c] hover:text-[#b70011] transition-colors"
          >
            Afiliar mi Restaurante
          </a>
          <a
            href="#terminos"
            className="font-body-sm text-sm text-[#5c403c] hover:text-[#b70011] transition-colors"
          >
            Términos y Condiciones del Servicio
          </a>
          <a
            href="#privacidad"
            className="font-body-sm text-sm text-[#5c403c] hover:text-[#b70011] transition-colors"
          >
            Política de Privacidad y Datos
          </a>
          <div className="mt-2 p-3 bg-[#f0f3ff] rounded-xl border border-[#d8e3fb]">
            <span className="font-label-sm text-xs text-[#111c2d] block font-bold">
              Central de Operaciones:
            </span>
            <span className="font-body-sm text-xs text-[#5c403c]">
              Centro Empresarial Neiva, Cra 5 # 10-38
            </span>
          </div>
        </div>
      </div>

      <div className="w-full bg-[#f0f3ff] py-4 border-t border-[#d8e3fb]/60">
        <div className="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10 flex flex-col sm:flex-row items-center justify-between text-[#5c403c] font-label-sm text-xs gap-3">
          <span>© 2025 ANTOJO-VIRTUAL Neiva. Todos los derechos reservados. Hecho con orgullo huilense.</span>
          <div className="flex items-center gap-4">
            <span className="flex items-center gap-1">
              <span className="material-symbols-outlined text-[#00682e] text-sm">verified</span>
              Plataforma Segura
            </span>
            <span>Horario: 11:30 AM - 11:45 PM</span>
          </div>
        </div>
      </div>
    </footer>
  );
};
