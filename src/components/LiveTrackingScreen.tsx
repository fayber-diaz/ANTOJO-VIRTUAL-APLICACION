import React, { useState, useEffect } from 'react';
import { AppScreen } from '../types';

interface LiveTrackingScreenProps {
  onNavigate: (screen: AppScreen) => void;
}

export const LiveTrackingScreen: React.FC<LiveTrackingScreenProps> = ({ onNavigate }) => {
  const [zoomLevel, setZoomLevel] = useState(1);
  const [riderOffset, setRiderOffset] = useState({ x: 0, y: 0 });
  const [speed, setSpeed] = useState(34);
  const [etaMinutes, setEtaMinutes] = useState(19);

  // Subtle real-time simulation
  useEffect(() => {
    const interval = setInterval(() => {
      setSpeed(Math.floor(32 + Math.random() * 6));
      setRiderOffset((prev) => ({
        x: Math.min(20, prev.x + (Math.random() * 2 - 1)),
        y: Math.min(25, prev.y + (Math.random() * 2 - 0.5)),
      }));
    }, 4000);
    return () => clearInterval(interval);
  }, []);

  const handleZoom = (delta: number) => {
    setZoomLevel((prev) => Math.max(0.8, Math.min(1.4, prev + delta)));
  };

  const handleRecenter = () => {
    setZoomLevel(1);
    setRiderOffset({ x: 0, y: 0 });
  };

  return (
    <div className="flex flex-col w-full">
      <div className="max-w-[1280px] w-full mx-auto px-4 sm:px-6 lg:px-10 py-6 lg:py-8 flex flex-col gap-6">
        {/* TOP STEPPER */}
        <section className="w-full bg-[#ffffff] p-4 rounded-2xl shadow-sm border border-[#e7eeff]">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 items-center">
            {/* Step 1 */}
            <button
              onClick={() => onNavigate('explorar')}
              className="flex items-center gap-3 text-left hover:opacity-80 transition-opacity cursor-pointer"
            >
              <div className="w-9 h-9 rounded-full bg-[#95f8a7] text-[#00210a] flex items-center justify-center shrink-0">
                <span className="material-symbols-outlined text-lg">check_circle</span>
              </div>
              <div className="flex flex-col min-w-0">
                <span className="font-label-sm text-[11px] text-[#00682e] uppercase tracking-wider font-bold">
                  Paso 1
                </span>
                <span className="font-label-md text-xs sm:text-sm text-[#111c2d] truncate font-semibold">
                  Carrito & Toppings
                </span>
              </div>
            </button>

            {/* Step 2 */}
            <button
              onClick={() => onNavigate('checkout')}
              className="flex items-center gap-3 text-left hover:opacity-80 transition-opacity cursor-pointer"
            >
              <div className="w-9 h-9 rounded-full bg-[#95f8a7] text-[#00210a] flex items-center justify-center shrink-0">
                <span className="material-symbols-outlined text-lg">check_circle</span>
              </div>
              <div className="flex flex-col min-w-0">
                <span className="font-label-sm text-[11px] text-[#00682e] uppercase tracking-wider font-bold">
                  Paso 2
                </span>
                <span className="font-label-md text-xs sm:text-sm text-[#111c2d] truncate font-semibold">
                  Entrega & Pago
                </span>
              </div>
            </button>

            {/* Step 3 */}
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-full bg-[#95f8a7] text-[#00210a] flex items-center justify-center shrink-0">
                <span className="material-symbols-outlined text-lg">check_circle</span>
              </div>
              <div className="flex flex-col min-w-0">
                <span className="font-label-sm text-[11px] text-[#00682e] uppercase tracking-wider font-bold">
                  Paso 3
                </span>
                <span className="font-label-md text-xs sm:text-sm text-[#111c2d] truncate font-semibold">
                  Confirmación Pago
                </span>
              </div>
            </div>

            {/* Step 4 (LIVE) */}
            <div className="flex items-center gap-2.5 bg-[#ffdad6]/60 px-3 py-1.5 rounded-full border border-[#ffdad6]">
              <div className="relative w-9 h-9 rounded-full bg-[#dc2626] text-white flex items-center justify-center shrink-0 shadow-sm">
                <span className="material-symbols-outlined text-lg animate-pulse">near_me</span>
                <span className="absolute -top-0.5 -right-0.5 w-3 h-3 bg-[#fea619] rounded-full ring-2 ring-white animate-ping"></span>
              </div>
              <div className="flex flex-col min-w-0">
                <span className="font-label-sm text-[11px] text-[#b70011] font-black uppercase tracking-wider flex items-center gap-1">
                  En Vivo <span className="w-2 h-2 rounded-full bg-[#dc2626] animate-pulse"></span>
                </span>
                <span className="font-label-md text-xs sm:text-sm text-[#b70011] font-extrabold truncate">
                  Rastreo GPS Neiva
                </span>
              </div>
            </div>
          </div>
        </section>

        {/* ORDER CONFIRMATION BANNER */}
        <section className="w-full bg-[#ffffff] rounded-2xl p-5 sm:p-6 shadow-sm border border-[#e7eeff] relative overflow-hidden">
          <div className="absolute right-0 top-0 bottom-0 w-80 bg-gradient-to-l from-[#ffddb8]/30 to-transparent pointer-events-none hidden md:block"></div>
          <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 relative z-10">
            <div className="flex items-start gap-4">
              <div className="w-14 h-14 rounded-full bg-[#00682e]/10 text-[#00682e] flex items-center justify-center shrink-0 shadow-inner">
                <span className="material-symbols-outlined text-3xl">task_alt</span>
              </div>
              <div className="flex flex-col text-left">
                <div className="flex items-center gap-2 flex-wrap">
                  <span className="font-headline-md text-lg sm:text-xl text-[#111c2d] font-extrabold tracking-tight">
                    ¡Tu pedido está confirmado y en ruta!
                  </span>
                  <span className="bg-[#00682e] text-white px-2.5 py-0.5 rounded-full font-label-sm text-xs font-bold">
                    En Tiempo Real
                  </span>
                </div>
                <p className="font-body-md text-xs sm:text-sm text-[#5c403c] mt-0.5">
                  Orden <strong className="text-[#111c2d]">#AV-98412</strong> • Restaurante:{' '}
                  <span className="font-semibold text-[#dc2626]">La Estación Burger (Centro Neiva)</span>
                </p>
              </div>
            </div>

            <div className="flex flex-wrap sm:flex-nowrap items-center gap-4 w-full lg:w-auto bg-[#f0f3ff] p-3 rounded-2xl border border-[#d8e3fb]">
              <div className="flex items-center gap-2 px-2">
                <span className="material-symbols-outlined text-[#dc2626] text-2xl">schedule</span>
                <div className="flex flex-col text-left">
                  <span className="font-label-sm text-[11px] text-[#5c403c] uppercase font-bold">
                    Tiempo Estimado
                  </span>
                  <span className="font-headline-sm text-sm sm:text-base text-[#111c2d] font-extrabold">
                    {etaMinutes - 1} - {etaMinutes + 3} min
                  </span>
                </div>
              </div>

              <div className="flex items-center gap-2 px-2 border-l border-[#d8e3fb]">
                <span className="material-symbols-outlined text-[#00682e] text-2xl">verified_user</span>
                <div className="flex flex-col text-left">
                  <span className="font-label-sm text-[11px] text-[#5c403c] uppercase font-bold">
                    Nequi Aprobado
                  </span>
                  <span className="font-label-md text-xs sm:text-sm text-[#00682e] font-extrabold">
                    #NQ-849201
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* MAIN 2-COLUMN LAYOUT (7 COLS / 5 COLS) */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
          {/* LEFT COLUMN: MAPA Y TIMELINE */}
          <section className="lg:col-span-7 flex flex-col gap-6 w-full">
            {/* SATELLITE MAP CONTAINER */}
            <div className="w-full bg-[#ffffff] rounded-2xl p-4 sm:p-5 shadow-sm border border-[#e7eeff] flex flex-col gap-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className="material-symbols-outlined text-[#dc2626] text-xl">satellite_alt</span>
                  <span className="font-headline-sm text-sm sm:text-base text-[#111c2d] font-bold">
                    Mapa Satelital Comuna 4 • Centro Neiva
                  </span>
                </div>
                <div className="flex items-center gap-1.5">
                  <span className="inline-flex items-center gap-1 bg-[#00682e]/10 text-[#00682e] px-3 py-1 rounded-full font-label-sm text-xs font-bold">
                    <span className="w-2 h-2 rounded-full bg-[#00682e] animate-ping"></span>
                    Tráfico Fluido (Cra 5ta)
                  </span>
                </div>
              </div>

              {/* Realistic Simulated Map of Neiva Comuna 4 */}
              <div className="relative w-full h-[440px] rounded-2xl overflow-hidden bg-[#e8ecef] select-none border border-[#d8e3fb]">
                <div
                  className="w-full h-full relative transition-transform duration-300 origin-center"
                  style={{ transform: `scale(${zoomLevel})` }}
                >
                  {/* SVG Street Grid */}
                  <svg className="absolute inset-0 w-full h-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                      <pattern id="neivaGrid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#d5dbe0" strokeWidth="1" />
                      </pattern>
                      <linearGradient id="gpsRoute" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stopColor="#DC2626" />
                        <stop offset="100%" stopColor="#FEA619" />
                      </linearGradient>
                    </defs>

                    <rect width="100%" height="100%" fill="#f4f6f8" />
                    <rect width="100%" height="100%" fill="url(#neivaGrid)" />

                    {/* Rio Las Ceibas */}
                    <path
                      d="M -20,60 Q 120,90 280,40 T 600,80 T 900,30"
                      fill="none"
                      stroke="#bfdbfe"
                      strokeWidth="26"
                      strokeLinecap="round"
                      opacity="0.7"
                    />

                    {/* Major Avenues in Neiva Centro */}
                    <line x1="0" y1="180" x2="900" y2="180" stroke="#cbd5e1" strokeWidth="18" />
                    <line x1="0" y1="310" x2="900" y2="310" stroke="#cbd5e1" strokeWidth="14" />
                    <line x1="220" y1="0" x2="220" y2="440" stroke="#cbd5e1" strokeWidth="16" />
                    <line x1="460" y1="0" x2="460" y2="440" stroke="#cbd5e1" strokeWidth="22" />
                    <line x1="680" y1="0" x2="680" y2="440" stroke="#cbd5e1" strokeWidth="14" />

                    {/* Street Labels */}
                    <text x="230" y="30" fill="#94a3b8" fontFamily="Plus Jakarta Sans" fontSize="10" fontWeight="700">
                      CRA. 12 (LA TOMA)
                    </text>
                    <text x="470" y="30" fill="#94a3b8" fontFamily="Plus Jakarta Sans" fontSize="10" fontWeight="700">
                      CRA. 5TA (EJE COMERCIAL)
                    </text>
                    <text x="20" y="172" fill="#94a3b8" fontFamily="Plus Jakarta Sans" fontSize="10" fontWeight="700">
                      CALLE 10 (PARQUE SANTANDER)
                    </text>
                    <text x="20" y="302" fill="#94a3b8" fontFamily="Plus Jakarta Sans" fontSize="10" fontWeight="700">
                      CALLE 18 (LOS COMUNEROS)
                    </text>

                    {/* City Blocks */}
                    <rect x="235" y="80" width="80" height="85" rx="6" fill="#e2e8f0" opacity="0.75" />
                    <rect x="330" y="80" width="115" height="85" rx="6" fill="#e2e8f0" opacity="0.75" />
                    <rect x="235" y="195" width="80" height="100" rx="6" fill="#e2e8f0" opacity="0.75" />
                    <rect x="330" y="195" width="115" height="100" rx="6" fill="#e2e8f0" opacity="0.75" />
                    <rect x="485" y="195" width="180" height="100" rx="6" fill="#e2e8f0" opacity="0.75" />

                    {/* GPS Delivery Route */}
                    <path
                      d="M 220 120 L 460 180 L 460 310"
                      fill="none"
                      stroke="url(#gpsRoute)"
                      strokeWidth="6"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    />
                    <path
                      d="M 220 120 L 460 180 L 460 250"
                      fill="none"
                      stroke="#FFFFFF"
                      strokeWidth="2"
                      strokeDasharray="6,6"
                      strokeLinecap="round"
                    />
                  </svg>

                  {/* RESTAURANT PIN */}
                  <div className="absolute top-[100px] left-[175px] flex flex-col items-center">
                    <div className="bg-[#ffffff] text-[#dc2626] px-2.5 py-1 rounded-full shadow-md font-label-sm text-xs font-bold flex items-center gap-1 border border-[#d8e3fb]">
                      <span className="material-symbols-outlined text-sm">storefront</span>
                      La Estación Burger
                    </div>
                    <div className="w-8 h-8 rounded-full bg-[#dc2626] text-white flex items-center justify-center shadow-lg -mt-1 ring-4 ring-[#dc2626]/20">
                      <span className="material-symbols-outlined text-base">restaurant</span>
                    </div>
                  </div>

                  {/* RIDER PIN (CARLOS M.) */}
                  <div
                    className="absolute top-[220px] left-[420px] flex flex-col items-center z-20 transition-all duration-700"
                    style={{
                      transform: `translate(${riderOffset.x}px, ${riderOffset.y}px)`,
                    }}
                  >
                    {/* Floating Tooltip */}
                    <div className="bg-[#263143] text-white px-3 py-1.5 rounded-xl shadow-xl flex items-center gap-2 text-left mb-1.5 animate-bounce">
                      <img
                        alt="Carlos Mauricio"
                        className="w-6 h-6 rounded-full object-cover ring-1 ring-white"
                        src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-sm text-[11px] font-bold text-[#ffddb8]">
                          Carlos M. (A 1.2 km)
                        </span>
                        <span className="font-body-sm text-[10px] text-white">
                          En ruta Cra 5ta • 8 min
                        </span>
                      </div>
                    </div>

                    {/* Motorbike radar icon */}
                    <div className="relative w-12 h-12 rounded-full bg-[#fea619] text-[#684000] flex items-center justify-center shadow-2xl ring-4 ring-[#fea619]/40 cursor-pointer">
                      <span className="material-symbols-outlined text-2xl font-bold">two_wheeler</span>
                      <span className="absolute -inset-2 rounded-full bg-[#fea619]/30 animate-ping"></span>
                    </div>
                  </div>

                  {/* DESTINATION PIN */}
                  <div className="absolute top-[285px] left-[445px] flex flex-col items-center z-10">
                    <div className="w-8 h-8 rounded-full bg-[#00682e] text-white flex items-center justify-center shadow-lg ring-4 ring-[#00682e]/20">
                      <span className="material-symbols-outlined text-base">home</span>
                    </div>
                    <div className="bg-[#ffffff] text-[#111c2d] px-2.5 py-1 rounded-full shadow-md font-label-sm text-xs font-bold mt-1 border border-[#d8e3fb]">
                      Tu Entrega: Edif. Los Comuneros
                    </div>
                  </div>
                </div>

                {/* Floating Map Controls */}
                <div className="absolute top-3 right-3 flex flex-col gap-1 z-30">
                  <button
                    onClick={() => handleZoom(0.15)}
                    className="w-9 h-9 rounded-lg bg-[#ffffff] text-[#111c2d] shadow-md flex items-center justify-center hover:bg-[#f0f3ff] transition-all cursor-pointer border border-[#d8e3fb]"
                    title="Acercar mapa"
                    type="button"
                  >
                    <span className="material-symbols-outlined text-lg">add</span>
                  </button>
                  <button
                    onClick={() => handleZoom(-0.15)}
                    className="w-9 h-9 rounded-lg bg-[#ffffff] text-[#111c2d] shadow-md flex items-center justify-center hover:bg-[#f0f3ff] transition-all cursor-pointer border border-[#d8e3fb]"
                    title="Alejar mapa"
                    type="button"
                  >
                    <span className="material-symbols-outlined text-lg">remove</span>
                  </button>
                  <button
                    onClick={handleRecenter}
                    className="w-9 h-9 rounded-lg bg-[#ffffff] text-[#dc2626] shadow-md flex items-center justify-center hover:bg-[#dc2626] hover:text-white transition-all cursor-pointer border border-[#d8e3fb] mt-1"
                    title="Centrar en repartidor"
                    type="button"
                  >
                    <span className="material-symbols-outlined text-lg">my_location</span>
                  </button>
                </div>

                {/* Bottom Telemetry Bar */}
                <div className="absolute bottom-3 left-3 right-3 bg-[#ffffff]/95 backdrop-blur-md px-4 py-2 rounded-xl shadow-md flex items-center justify-between z-30 text-[#111c2d] border border-[#d8e3fb]">
                  <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-[#dc2626] animate-pulse">navigation</span>
                    <span className="font-body-sm text-xs font-medium">
                      Velocidad actual: <strong className="text-[#111c2d]">{speed} km/h</strong> • Cra 5ta sentido Sur-Norte
                    </span>
                  </div>
                  <span className="font-label-sm text-[11px] bg-[#95f8a7] text-[#00210a] px-2.5 py-0.5 rounded-full font-bold">
                    GPS Activo
                  </span>
                </div>
              </div>

              {/* Thermal Guarantee */}
              <div className="flex items-center gap-3 bg-[#f0f3ff] p-3.5 rounded-xl border border-[#d8e3fb]">
                <span className="material-symbols-outlined text-[#855300] text-2xl">verified</span>
                <p className="font-body-sm text-xs text-[#5c403c]">
                  Garantía ANTOJO-VIRTUAL: Tu comida viaja en mochila térmica con aislamiento de calor certificado. Si llega frío, lo reponemos sin costo adicional.
                </p>
              </div>
            </div>

            {/* LIVE TIMELINE STEP-BY-STEP */}
            <div className="w-full bg-[#ffffff] rounded-2xl p-5 sm:p-6 shadow-sm border border-[#e7eeff] flex flex-col gap-4">
              <div className="flex items-center justify-between">
                <span className="font-headline-sm text-base sm:text-lg text-[#111c2d] font-bold">
                  Estado del Pedido paso a paso
                </span>
                <span className="font-label-sm text-xs text-[#dc2626] font-bold">
                  Actualizado hace 30 seg
                </span>
              </div>

              <div className="relative pl-6 flex flex-col gap-6 before:content-[''] before:absolute before:left-3 before:top-2 before:bottom-3 before:w-0.5 before:bg-[#d8e3fb]">
                {/* 1. Recibido */}
                <div className="relative flex items-start gap-4">
                  <div className="absolute -left-6 w-6 h-6 rounded-full bg-[#00682e] text-white flex items-center justify-center ring-4 ring-white">
                    <span className="material-symbols-outlined text-xs">check</span>
                  </div>
                  <div className="flex-1 flex items-center justify-between">
                    <div>
                      <h4 className="font-label-lg text-xs sm:text-sm text-[#111c2d] font-bold">
                        1. Pedido Recibido y Transmisión a Cocina
                      </h4>
                      <p className="font-body-sm text-xs text-[#5c403c]">
                        El restaurante aceptó la comanda #AV-98412
                      </p>
                    </div>
                    <span className="font-label-sm text-xs text-[#5c403c] font-mono">12:15 PM</span>
                  </div>
                </div>

                {/* 2. Cocina */}
                <div className="relative flex items-start gap-4">
                  <div className="absolute -left-6 w-6 h-6 rounded-full bg-[#00682e] text-white flex items-center justify-center ring-4 ring-white">
                    <span className="material-symbols-outlined text-xs">check</span>
                  </div>
                  <div className="flex-1 flex items-center justify-between">
                    <div>
                      <h4 className="font-label-lg text-xs sm:text-sm text-[#111c2d] font-bold">
                        2. En Parrilla y Cocina Artesanal
                      </h4>
                      <p className="font-body-sm text-xs text-[#5c403c]">
                        Carne 200g al carbón en término 3/4 y papas crujientes
                      </p>
                    </div>
                    <span className="font-label-sm text-xs text-[#5c403c] font-mono">12:20 PM</span>
                  </div>
                </div>

                {/* 3. Empacado */}
                <div className="relative flex items-start gap-4">
                  <div className="absolute -left-6 w-6 h-6 rounded-full bg-[#00682e] text-white flex items-center justify-center ring-4 ring-white">
                    <span className="material-symbols-outlined text-xs">check</span>
                  </div>
                  <div className="flex-1 flex items-center justify-between">
                    <div>
                      <h4 className="font-label-lg text-xs sm:text-sm text-[#111c2d] font-bold">
                        3. Empacado con Sello Térmico de Seguridad
                      </h4>
                      <p className="font-body-sm text-xs text-[#5c403c]">
                        Sello de bioseguridad colocado intacto en empaque biodegradable
                      </p>
                    </div>
                    <span className="font-label-sm text-xs text-[#5c403c] font-mono">12:32 PM</span>
                  </div>
                </div>

                {/* 4. En Camino (Active) */}
                <div className="relative flex items-start gap-4">
                  <div className="absolute -left-6 w-6 h-6 rounded-full bg-[#dc2626] text-white flex items-center justify-center ring-4 ring-[#ffdad6] animate-pulse">
                    <span className="material-symbols-outlined text-xs">two_wheeler</span>
                  </div>
                  <div className="flex-1 flex items-center justify-between bg-[#ffdad6]/30 p-3 rounded-xl border border-[#ffdad6]">
                    <div>
                      <div className="flex items-center gap-2">
                        <h4 className="font-label-lg text-xs sm:text-sm text-[#dc2626] font-bold">
                          4. En Camino con el Domiciliario
                        </h4>
                        <span className="bg-[#dc2626] text-white px-2 py-0.5 rounded-full font-label-sm text-[10px] font-black">
                          En curso
                        </span>
                      </div>
                      <p className="font-body-sm text-xs text-[#5c403c]">
                        Carlos M. conduce por la Cra 5ta acercándose a Los Comuneros
                      </p>
                    </div>
                    <span className="font-label-sm text-xs text-[#dc2626] font-bold font-mono">
                      12:38 PM
                    </span>
                  </div>
                </div>

                {/* 5. Entrega */}
                <div className="relative flex items-start gap-4 opacity-60">
                  <div className="absolute -left-6 w-6 h-6 rounded-full bg-[#d8e3fb] text-[#5c403c] flex items-center justify-center ring-4 ring-white">
                    <span className="material-symbols-outlined text-xs">pin_drop</span>
                  </div>
                  <div className="flex-1 flex items-center justify-between">
                    <div>
                      <h4 className="font-label-lg text-xs sm:text-sm text-[#111c2d] font-bold">
                        5. Entrega en Portería / Destino
                      </h4>
                      <p className="font-body-sm text-xs text-[#5c403c]">
                        Presenta tu código PIN de 4 dígitos a Carlos al recibir
                      </p>
                    </div>
                    <span className="font-label-sm text-xs text-[#5c403c] font-mono">~12:45 PM</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          {/* RIGHT COLUMN: DOMICILIARIO, PIN, RESUMEN */}
          <section className="lg:col-span-5 flex flex-col gap-6 w-full">
            {/* DOMICILIARIO ASIGNADO CARD */}
            <div className="w-full bg-[#ffffff] rounded-2xl p-5 sm:p-6 shadow-sm border border-[#e7eeff] flex flex-col gap-4">
              <div className="flex items-center justify-between">
                <span className="font-headline-sm text-base text-[#111c2d] font-bold">
                  Tu Domiciliario Asignado
                </span>
                <span className="inline-flex items-center gap-1 font-label-sm text-[11px] text-[#00682e] bg-[#00682e]/10 px-2.5 py-0.5 rounded-full font-bold">
                  <span className="material-symbols-outlined text-xs">shield</span> Conductor Verificado
                </span>
              </div>

              <div className="flex items-center gap-4">
                <div className="relative">
                  <img
                    alt="Carlos Mauricio Pérez"
                    className="w-16 h-16 rounded-full object-cover shadow-md ring-2 ring-[#d8e3fb]"
                    src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&auto=format&fit=crop&q=80"
                  />
                  <div className="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-[#00682e] text-white flex items-center justify-center text-xs shadow-md">
                    <span className="material-symbols-outlined text-sm">electric_moped</span>
                  </div>
                </div>

                <div className="flex flex-col text-left">
                  <h3 className="font-headline-sm text-base sm:text-lg text-[#111c2d] font-bold">
                    Carlos Mauricio Pérez
                  </h3>
                  <div className="flex items-center gap-2 mt-0.5">
                    <span className="flex items-center text-[#855300] font-label-md text-xs font-bold">
                      <span className="material-symbols-outlined text-sm mr-0.5">star</span>
                      4.9
                    </span>
                    <span className="text-[#5c403c] font-body-sm text-xs">• 148 entregas en Neiva</span>
                  </div>
                  <span className="font-body-sm text-xs text-[#5c403c] mt-1 font-medium">
                    Moto Bajaj Pulsar 180 • <strong className="text-[#111c2d]">Placa WQ-44F</strong>
                  </span>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
                <a
                  className="flex items-center justify-center gap-2 bg-[#00682e] hover:bg-[#1a8340] text-white py-2.5 px-4 rounded-full font-label-md text-xs sm:text-sm font-bold transition-all shadow-sm active:scale-95 text-center cursor-pointer"
                  href="https://wa.me/573142899012"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <span className="material-symbols-outlined text-lg">chat</span>
                  WhatsApp con Carlos
                </a>
                <a
                  className="flex items-center justify-center gap-2 bg-[#f0f3ff] hover:bg-[#e7eeff] text-[#111c2d] py-2.5 px-4 rounded-full font-label-md text-xs sm:text-sm font-bold transition-all active:scale-95 text-center cursor-pointer border border-[#d8e3fb]"
                  href="tel:+573142899012"
                >
                  <span className="material-symbols-outlined text-lg text-[#dc2626]">call</span>
                  Llamar (314 289 9012)
                </a>
              </div>
            </div>

            {/* SECURITY PIN CODE */}
            <div className="w-full bg-[#ffddb8]/30 rounded-2xl p-4 sm:p-5 shadow-sm border border-[#ffddb8] flex flex-col gap-2">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-1.5">
                  <span className="material-symbols-outlined text-[#855300] text-xl">key</span>
                  <span className="font-label-lg text-xs sm:text-sm text-[#111c2d] font-bold">
                    PIN de Seguridad de Entrega
                  </span>
                </div>
                <span className="font-label-sm text-[11px] text-[#5c403c] font-semibold">
                  Díctalo a Carlos
                </span>
              </div>
              <p className="font-body-sm text-xs text-[#5c403c]">
                Para garantizar que nadie más reciba tu orden en portería, entrégale este código al momento del despacho:
              </p>
              <div className="flex items-center justify-center gap-3 py-2">
                {['4', '8', '2', '9'].map((digit, idx) => (
                  <div
                    key={idx}
                    className="w-12 h-14 bg-white rounded-xl flex items-center justify-center font-display-hero text-2xl sm:text-3xl text-[#dc2626] shadow-sm font-black border border-[#d8e3fb]"
                  >
                    {digit}
                  </div>
                ))}
              </div>
            </div>

            {/* DIRECCIÓN E INSTRUCCIONES */}
            <div className="w-full bg-[#ffffff] rounded-2xl p-4 sm:p-5 shadow-sm border border-[#e7eeff] flex flex-col gap-2">
              <div className="flex items-center gap-2 text-[#111c2d]">
                <span className="material-symbols-outlined text-[#dc2626] text-xl">home_pin</span>
                <span className="font-label-lg text-xs sm:text-sm font-bold">Dirección e Instrucciones</span>
              </div>
              <div className="bg-[#f0f3ff] p-3 rounded-xl border border-[#d8e3fb] mt-1">
                <span className="font-label-md text-xs sm:text-sm text-[#111c2d] block font-bold">
                  Cra. 5 #18-42, Centro Neiva
                </span>
                <span className="font-body-sm text-xs text-[#5c403c] block mt-0.5">
                  Edificio Los Comuneros • Apto 402
                </span>
                <div className="flex items-start gap-2 mt-2 bg-white p-2.5 rounded-lg border border-[#d8e3fb]/60">
                  <span className="material-symbols-outlined text-[#855300] text-sm shrink-0 mt-0.5">
                    info
                  </span>
                  <p className="font-body-sm text-xs text-[#5c403c]">
                    "Dejar en recepción con el señor portero Don Hernando si no contesto citófono."
                  </p>
                </div>
              </div>
            </div>

            {/* RESUMEN DE COMIDA */}
            <div className="w-full bg-[#ffffff] rounded-2xl p-5 sm:p-6 shadow-sm border border-[#e7eeff] flex flex-col gap-3">
              <div className="flex items-center justify-between pb-1 border-b border-[#f0f3ff]">
                <span className="font-headline-sm text-sm sm:text-base text-[#111c2d] font-bold">
                  Resumen de Comida
                </span>
                <span className="font-label-sm text-xs text-[#5c403c]">2 Artículos</span>
              </div>

              {/* Items */}
              <div className="flex flex-col gap-2.5">
                <div className="flex items-start justify-between gap-3">
                  <div className="flex flex-col text-left">
                    <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">
                      1x Burger Monumental 200g
                    </span>
                    <span className="font-body-sm text-[11px] text-[#5c403c]">
                      Término 3/4 • Tocineta Crunch • Huevo Campesino • Queso Asado
                    </span>
                  </div>
                  <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold shrink-0">
                    $31.900
                  </span>
                </div>

                <div className="flex items-start justify-between gap-3">
                  <div className="flex flex-col text-left">
                    <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">
                      1x Papas Rústicas Romero
                    </span>
                    <span className="font-body-sm text-[11px] text-[#5c403c]">
                      Acompañamiento • Salsa tártara casera
                    </span>
                  </div>
                  <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold shrink-0">
                    $8.500
                  </span>
                </div>
              </div>

              {/* Totals Breakdown */}
              <div className="pt-2 mt-1 flex flex-col gap-1 border-t border-[#f0f3ff]">
                <div className="flex items-center justify-between text-[#5c403c] font-body-sm text-xs">
                  <span>Subtotal Alimentos</span>
                  <span>$40.400 COP</span>
                </div>
                <div className="flex items-center justify-between text-[#5c403c] font-body-sm text-xs">
                  <span>Costo de Envío (Centro Neiva)</span>
                  <span className="text-[#00682e] font-bold">GRATIS ($0)</span>
                </div>

                <div className="flex items-center justify-between text-[#111c2d] font-headline-sm text-base sm:text-lg font-black pt-2 mt-1">
                  <span>Total Pagado</span>
                  <span className="text-[#dc2626]">$40.400 COP</span>
                </div>

                <div className="flex items-center gap-1.5 mt-1 text-[#5c403c] font-label-sm text-[11px]">
                  <span className="material-symbols-outlined text-xs text-[#00682e]">check_circle</span>
                  <span>
                    Pagado electrónicamente vía <strong className="text-[#3c004d]">Nequi</strong>
                  </span>
                </div>
              </div>
            </div>

            {/* SUPPORT CONTACT */}
            <div className="w-full bg-[#f0f3ff] rounded-2xl p-4 shadow-sm border border-[#d8e3fb] flex items-center justify-between gap-3">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-[#dc2626]/10 text-[#dc2626] flex items-center justify-center shrink-0">
                  <span className="material-symbols-outlined text-xl">support_agent</span>
                </div>
                <div className="flex flex-col text-left">
                  <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">
                    ¿Tienes dudas con tu entrega?
                  </span>
                  <span className="font-body-sm text-xs text-[#5c403c]">Línea de soporte local Neiva 24/7</span>
                </div>
              </div>
              <a
                className="px-4 py-2 bg-white hover:bg-[#e7eeff] text-[#dc2626] font-label-sm text-xs font-bold rounded-full shadow-sm transition-all shrink-0 border border-[#d8e3fb] cursor-pointer"
                href="https://wa.me/573142899012"
                target="_blank"
                rel="noopener noreferrer"
              >
                Contactar
              </a>
            </div>
          </section>
        </div>
      </div>
    </div>
  );
};
