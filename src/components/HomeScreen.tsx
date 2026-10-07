import React, { useState } from 'react';
import { AppScreen, CartItem } from '../types';

interface HomeScreenProps {
  onOpenCustomizer: () => void;
  onNavigate: (screen: AppScreen) => void;
  cartItems: CartItem[];
  appliedCoupon: boolean;
  onApplyCoupon: (code: string) => void;
  searchQuery: string;
}

export const HomeScreen: React.FC<HomeScreenProps> = ({
  onOpenCustomizer,
  onNavigate,
  cartItems,
  appliedCoupon,
  onApplyCoupon,
  searchQuery,
}) => {
  const [selectedComuna, setSelectedComuna] = useState('c4');
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [copiedCoupon, setCopiedCoupon] = useState(false);
  const [selectedPaymentMode, setSelectedPaymentMode] = useState<'efectivo' | 'datafono'>('efectivo');

  const handleCopyCoupon = () => {
    navigator.clipboard?.writeText('ANTOJONEIVA5K');
    onApplyCoupon('ANTOJONEIVA5K');
    setCopiedCoupon(true);
    setTimeout(() => setCopiedCoupon(false), 3000);
  };

  const restaurants = [
    {
      id: 'rest-1',
      name: 'La Estación Burger',
      address: 'Cra 12 #10-34, Centro',
      description: 'Especialistas en cocción a la parrilla de carbón, salsas artesanales de la casa y pan brioche horneado diario en Neiva.',
      image: 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&auto=format&fit=crop&q=80',
      badge: 'Verificado Huila',
      badgeType: 'verified' as const,
      rating: 4.9,
      reviews: '120+',
      time: '25-35 min',
      fee: '$3.500',
      starDish: 'Hamburguesa Monumental Huilense',
      starPrice: '$24.900',
      starTag: 'Listo en 20 min',
      category: 'burgers',
    },
    {
      id: 'rest-2',
      name: 'Salchipapería La 8va',
      address: 'Calle 8 #14-20',
      description: 'Porciones gigantescas con papas crujientes criollas y americanas, bañadas en tártara especial y queso costeño rallado.',
      image: 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80',
      badge: 'Más Vendido',
      badgeType: 'bestseller' as const,
      rating: 4.8,
      reviews: '85',
      time: '20-30 min',
      fee: '$3.000',
      starDish: 'Super Salchipapa Costeña-Opita',
      starPrice: '$22.000',
      starTag: 'Para compartir',
      category: 'salchipapas',
    },
    {
      id: 'rest-3',
      name: 'El Patacón Callejero',
      address: 'Av. Circunvalar',
      description: 'Patacones crocantes pisados al instante con plátano verde del Huila, carnes desmechadas y salsas artesanales frescas.',
      image: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80',
      badge: 'Verificado Huila',
      badgeType: 'verified' as const,
      rating: 4.9,
      reviews: '94',
      time: '25-35 min',
      fee: '$3.500',
      starDish: 'Patacón Monumental con Todo',
      starPrice: '$19.500',
      starTag: '100% Crocante',
      category: 'arepas',
    },
    {
      id: 'rest-4',
      name: 'Perros & Grill Opita',
      address: 'Las Granjas, Neiva',
      description: 'El auténtico perro caliente colombiano: salchicha suiza a la plancha, ripio crujiente, tocineta y huevos de codorniz.',
      image: 'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?w=600&auto=format&fit=crop&q=80',
      badge: 'Súper Rápido',
      badgeType: 'speed' as const,
      rating: 4.7,
      reviews: '62',
      time: '15-25 min',
      fee: '$2.800',
      starDish: 'Perro Caliente Especial Opita',
      starPrice: '$16.000',
      starTag: 'Combo disponible',
      category: 'perros',
    },
  ];

  const dishes = [
    {
      id: 'dish-1',
      restaurant: 'La Estación Burger',
      name: 'Hamburguesa Monumental Huilense 200g',
      price: '$24.900',
      description: 'Carne de res seleccionada 200g a la parrilla, queso campesino asado de Algeciras, cebolla caramelizada en panela, tocineta ahumada y salsa tártara de la casa.',
      image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80',
      tags: ['Res 200g', 'Queso Algeciras', 'Pan Brioche'],
      category: 'burgers',
    },
    {
      id: 'dish-2',
      restaurant: 'Mazorcadas del Magdalena',
      name: 'Desgranado Mixto Supremo al Carbón',
      price: '$21.500',
      description: 'Maíz tierno desgranado a la mantequilla, pollo desmechado, lomo de res salteado, cubierta de queso mozzarella fundido y crujientes fosforitos de papa criolla.',
      image: 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80',
      tags: ['Maíz Tierno', 'Pollo & Lomo', 'Queso Fundido'],
      category: 'desgranados',
    },
    {
      id: 'dish-3',
      restaurant: 'Arepas Don Huila',
      name: 'Arepa Rellena Trifásica Campesina',
      price: '$17.800',
      description: 'Masa artesanal asada de maíz blanco rellena de pechuga mechada, carne deshebrada, chicharrón crocante y guacamole suave casero.',
      image: 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80',
      tags: ['Maíz 100%', 'Chicharrón Crocante', 'Guacamole'],
      category: 'arepas',
    },
  ];

  // Filter items if searching
  const filteredDishes = dishes.filter((d) => {
    const matchesSearch =
      !searchQuery ||
      d.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      d.restaurant.toLowerCase().includes(searchQuery.toLowerCase()) ||
      d.description.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCat = selectedCategory === 'all' || d.category === selectedCategory;
    return matchesSearch && matchesCat;
  });

  const filteredRestaurants = restaurants.filter((r) => {
    const matchesSearch =
      !searchQuery ||
      r.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      r.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
      r.starDish.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCat = selectedCategory === 'all' || r.category === selectedCategory;
    return matchesSearch && matchesCat;
  });

  return (
    <div className="flex flex-col w-full">
      {/* HERO BANNER SECTION */}
      <section className="relative w-full overflow-hidden bg-gradient-to-r from-[#dc2626] via-[#b70011] to-[#8c000d] text-white shadow-xl">
        {/* Scrim & Lighting Accents */}
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_30%,rgba(254,166,25,0.22),transparent_60%)] pointer-events-none"></div>
        <div className="absolute -right-24 -bottom-24 w-96 h-96 bg-[#fea619]/20 rounded-full blur-3xl pointer-events-none"></div>

        <div className="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10 py-10 lg:py-14 relative z-10">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            {/* Left Content */}
            <div className="lg:col-span-7 flex flex-col gap-4">
              <div className="inline-flex items-center gap-1.5 bg-[#fea619] text-[#684000] px-3.5 py-1 rounded-full font-label-sm text-xs uppercase tracking-wider w-fit shadow-md font-black">
                <span className="material-symbols-outlined text-base">local_fire_department</span>
                Plataforma 100% Neivana • Envío Caliente Garantizado
              </div>

              <h1 className="font-display-hero text-3xl sm:text-4xl lg:text-5xl text-white leading-tight font-extrabold tracking-tight">
                ¡El auténtico sabor huilense directo a tu puerta!
              </h1>

              <p className="font-body-lg text-sm sm:text-base text-[#ffdad6] leading-relaxed max-w-[620px]">
                Descubre los mejores emprendimientos gastronómicos de Neiva: hamburguesas artesanales de carne magra, salchipapas monumentales, perros calientes asados a la leña y desgranados tradicionales.
              </p>

              {/* Comunas Selector & Time Pill */}
              <div className="flex flex-wrap items-center gap-3 mt-1">
                <div className="flex items-center bg-white/15 backdrop-blur-md rounded-full px-4 py-1.5 text-white border border-white/20">
                  <span className="material-symbols-outlined text-[#fea619] mr-2 text-xl">near_me</span>
                  <span className="font-label-sm text-xs font-semibold uppercase mr-2 text-[#d8e3fb]">
                    Sector:
                  </span>
                  <select
                    className="bg-transparent font-label-md text-xs sm:text-sm text-white font-bold focus:outline-none cursor-pointer"
                    value={selectedComuna}
                    onChange={(e) => setSelectedComuna(e.target.value)}
                  >
                    <option className="text-[#111c2d] bg-white" value="c1">
                      Comuna 1 (Norte / Las Mercedes)
                    </option>
                    <option className="text-[#111c2d] bg-white" value="c2">
                      Comuna 2 (Prado Alto / Aeropuerto)
                    </option>
                    <option className="text-[#111c2d] bg-white" value="c3">
                      Comuna 3 (Entre Ríos / Centro Empresarial)
                    </option>
                    <option className="text-[#111c2d] bg-white" value="c4">
                      Comuna 4 (Centro Neiva / Carrera 5ta)
                    </option>
                    <option className="text-[#111c2d] bg-white" value="c5">
                      Comuna 5 (Ipanema / Buganviles)
                    </option>
                    <option className="text-[#111c2d] bg-white" value="c6">
                      Comuna 6 (Sur / Canaima)
                    </option>
                  </select>
                </div>

                <div className="inline-flex items-center gap-1.5 bg-[#1a8340] text-white px-4 py-1.5 rounded-full font-label-md text-xs sm:text-sm shadow-sm font-bold">
                  <span className="material-symbols-outlined text-sm">timer</span>
                  <span>25 – 35 min promedio</span>
                </div>
              </div>
            </div>

            {/* Right Coupon Card */}
            <div className="lg:col-span-5 flex flex-col justify-center">
              <div className="relative bg-[#ffffff] text-[#111c2d] rounded-2xl p-6 shadow-2xl flex flex-col gap-4 overflow-hidden border border-[#d8e3fb]">
                {/* Accent Ribbon */}
                <div className="absolute -top-6 -right-6 w-24 h-24 bg-[#fea619] rotate-45 flex items-end justify-center pb-1 shadow-md">
                  <span className="font-label-sm text-[10px] text-[#684000] font-black uppercase tracking-tighter">
                    Huilense
                  </span>
                </div>

                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 rounded-xl bg-[#ffdad6] flex items-center justify-center text-[#dc2626] shrink-0">
                    <span className="material-symbols-outlined text-2xl">redeem</span>
                  </div>
                  <div className="flex flex-col">
                    <span className="font-label-sm text-xs text-[#5c403c] uppercase font-bold tracking-wide">
                      Bono de Bienvenida Huila
                    </span>
                    <span className="font-headline-sm text-base sm:text-lg text-[#111c2d] font-extrabold">
                      Ahorra en tu primer antojo
                    </span>
                  </div>
                </div>

                {/* Code Container */}
                <div className="bg-[#f0f3ff] rounded-xl p-4 flex items-center justify-between border border-[#d8e3fb]">
                  <div className="flex flex-col">
                    <span className="font-label-sm text-xs text-[#5c403c]">Código Promocional:</span>
                    <span className="font-headline-md text-xl sm:text-2xl text-[#b70011] font-black tracking-widest font-mono">
                      ANTOJONEIVA5K
                    </span>
                  </div>
                  <div className="flex flex-col items-end">
                    <span className="font-label-sm text-xs text-[#00682e] font-black bg-[#95f8a7] px-2.5 py-0.5 rounded-full">
                      -$5.000 COP
                    </span>
                    <span className="font-body-sm text-xs text-[#5c403c] mt-0.5 font-medium">Válido hoy</span>
                  </div>
                </div>

                <div className="flex items-center justify-between text-xs text-[#5c403c]">
                  <span className="flex items-center gap-1 font-semibold">
                    <span className="material-symbols-outlined text-[#00682e] text-base">check_circle</span>
                    Aplica a todos los restaurantes
                  </span>
                  <button
                    onClick={handleCopyCoupon}
                    type="button"
                    className="font-label-md text-xs sm:text-sm text-[#b70011] hover:text-[#dc2626] font-bold underline cursor-pointer"
                  >
                    {copiedCoupon ? '¡Copiado & Aplicado!' : 'Copiar'}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* FILTER CATEGORIES BAR */}
      <section className="w-full bg-[#ffffff] shadow-sm sticky top-20 z-40 border-b border-[#e7eeff]">
        <div className="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10 py-3 flex items-center gap-2 overflow-x-auto no-scrollbar">
          <span className="font-label-sm text-xs text-[#5c403c] uppercase tracking-wider shrink-0 mr-1 font-black">
            Antojos Populares:
          </span>

          <button
            onClick={() => setSelectedCategory('all')}
            type="button"
            className={`group flex items-center gap-1.5 px-4 py-1.5 rounded-full font-label-md text-xs sm:text-sm transition-all active:scale-95 shrink-0 cursor-pointer ${
              selectedCategory === 'all'
                ? 'bg-[#dc2626] text-white shadow-md font-bold'
                : 'bg-[#f0f3ff] hover:bg-[#e7eeff] text-[#111c2d]'
            }`}
          >
            <span>Todos</span>
          </button>

          <button
            onClick={() => setSelectedCategory('burgers')}
            type="button"
            className={`group flex items-center gap-1.5 px-4 py-1.5 rounded-full font-label-md text-xs sm:text-sm transition-all active:scale-95 shrink-0 cursor-pointer ${
              selectedCategory === 'burgers'
                ? 'bg-[#dc2626] text-white shadow-md font-bold'
                : 'bg-[#f0f3ff] hover:bg-[#e7eeff] text-[#111c2d]'
            }`}
          >
            <span className="material-symbols-outlined text-base">lunch_dining</span>
            <span>Burgers Artesanales</span>
            <span
              className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ml-1 ${
                selectedCategory === 'burgers' ? 'bg-white/20 text-white' : 'bg-[#e7eeff] text-[#5c403c]'
              }`}
            >
              18
            </span>
          </button>

          <button
            onClick={() => setSelectedCategory('salchipapas')}
            type="button"
            className={`group flex items-center gap-1.5 px-4 py-1.5 rounded-full font-label-md text-xs sm:text-sm transition-all active:scale-95 shrink-0 cursor-pointer ${
              selectedCategory === 'salchipapas'
                ? 'bg-[#dc2626] text-white shadow-md font-bold'
                : 'bg-[#f0f3ff] hover:bg-[#e7eeff] text-[#111c2d]'
            }`}
          >
            <span className="material-symbols-outlined text-[#fea619] text-base">fastfood</span>
            <span>Super Salchipapas</span>
            <span
              className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ml-1 ${
                selectedCategory === 'salchipapas' ? 'bg-white/20 text-white' : 'bg-[#e7eeff] text-[#5c403c]'
              }`}
            >
              14
            </span>
          </button>

          <button
            onClick={() => setSelectedCategory('perros')}
            type="button"
            className={`group flex items-center gap-1.5 px-4 py-1.5 rounded-full font-label-md text-xs sm:text-sm transition-all active:scale-95 shrink-0 cursor-pointer ${
              selectedCategory === 'perros'
                ? 'bg-[#dc2626] text-white shadow-md font-bold'
                : 'bg-[#f0f3ff] hover:bg-[#e7eeff] text-[#111c2d]'
            }`}
          >
            <span className="material-symbols-outlined text-[#dc2626] text-base">hot_tub</span>
            <span>Perros Calientes</span>
            <span
              className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ml-1 ${
                selectedCategory === 'perros' ? 'bg-white/20 text-white' : 'bg-[#e7eeff] text-[#5c403c]'
              }`}
            >
              9
            </span>
          </button>

          <button
            onClick={() => setSelectedCategory('arepas')}
            type="button"
            className={`group flex items-center gap-1.5 px-4 py-1.5 rounded-full font-label-md text-xs sm:text-sm transition-all active:scale-95 shrink-0 cursor-pointer ${
              selectedCategory === 'arepas'
                ? 'bg-[#dc2626] text-white shadow-md font-bold'
                : 'bg-[#f0f3ff] hover:bg-[#e7eeff] text-[#111c2d]'
            }`}
          >
            <span className="material-symbols-outlined text-[#855300] text-base">bakery_dining</span>
            <span>Arepas Rellenas</span>
            <span
              className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ml-1 ${
                selectedCategory === 'arepas' ? 'bg-white/20 text-white' : 'bg-[#e7eeff] text-[#5c403c]'
              }`}
            >
              12
            </span>
          </button>

          <button
            onClick={() => setSelectedCategory('desgranados')}
            type="button"
            className={`group flex items-center gap-1.5 px-4 py-1.5 rounded-full font-label-md text-xs sm:text-sm transition-all active:scale-95 shrink-0 cursor-pointer ${
              selectedCategory === 'desgranados'
                ? 'bg-[#dc2626] text-white shadow-md font-bold'
                : 'bg-[#f0f3ff] hover:bg-[#e7eeff] text-[#111c2d]'
            }`}
          >
            <span className="material-symbols-outlined text-[#fea619] text-base">grain</span>
            <span>Desgranados Huilenses</span>
            <span
              className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ml-1 ${
                selectedCategory === 'desgranados' ? 'bg-white/20 text-white' : 'bg-[#e7eeff] text-[#5c403c]'
              }`}
            >
              8
            </span>
          </button>
        </div>
      </section>

      {/* MAIN TWO-COLUMN CONTAINER */}
      <main className="max-w-[1280px] w-full mx-auto px-4 sm:px-6 lg:px-10 py-8 lg:py-10">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          {/* LEFT COLUMN (70% -> 8 Cols) */}
          <div className="lg:col-span-8 flex flex-col gap-10">
            {/* SECCIÓN 1: Emprendimientos Destacados en Neiva */}
            <section className="flex flex-col gap-5">
              <div className="flex items-end justify-between">
                <div className="flex flex-col">
                  <div className="flex items-center gap-1.5 text-[#dc2626] font-label-sm text-xs uppercase font-extrabold tracking-widest">
                    <span className="material-symbols-outlined text-base">workspace_premium</span>
                    Favoritos de la Ciudad
                  </div>
                  <h2 className="font-headline-lg text-2xl sm:text-3xl text-[#111c2d] tracking-tight font-extrabold">
                    Emprendimientos Destacados en Neiva
                  </h2>
                </div>
                <a
                  href="#todos"
                  onClick={(e) => {
                    e.preventDefault();
                    setSelectedCategory('all');
                  }}
                  className="font-label-md text-xs sm:text-sm text-[#b70011] hover:text-[#dc2626] font-bold flex items-center gap-1 transition-colors"
                >
                  Ver los 56 locales
                  <span className="material-symbols-outlined text-base">arrow_forward</span>
                </a>
              </div>

              {/* 2-Column Restaurant Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {filteredRestaurants.map((r) => (
                  <article
                    key={r.id}
                    onClick={onOpenCustomizer}
                    className="bg-[#ffffff] rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 flex flex-col group cursor-pointer border border-[#e7eeff]"
                  >
                    <div className="relative h-48 w-full overflow-hidden bg-[#e7eeff]">
                      <img
                        alt={r.name}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        src={r.image}
                      />
                      {r.badgeType === 'verified' && (
                        <div className="absolute top-3 left-3 bg-[#00682e] text-white px-2.5 py-0.5 rounded-full font-label-sm text-xs flex items-center gap-1 shadow-md font-bold">
                          <span className="material-symbols-outlined text-sm">verified</span>
                          {r.badge}
                        </div>
                      )}
                      {r.badgeType === 'bestseller' && (
                        <div className="absolute top-3 left-3 bg-[#fea619] text-[#684000] px-2.5 py-0.5 rounded-full font-label-sm text-xs font-black flex items-center gap-1 shadow-md">
                          <span className="material-symbols-outlined text-sm">local_fire_department</span>
                          {r.badge}
                        </div>
                      )}
                      {r.badgeType === 'speed' && (
                        <div className="absolute top-3 left-3 bg-white/95 text-[#b70011] px-2.5 py-0.5 rounded-full font-label-sm text-xs font-black flex items-center gap-1 shadow-md">
                          <span className="material-symbols-outlined text-sm">bolt</span>
                          {r.badge}
                        </div>
                      )}

                      <div className="absolute top-3 right-3 bg-white/90 backdrop-blur-sm text-[#111c2d] px-2.5 py-0.5 rounded-full font-label-sm text-xs font-bold flex items-center gap-1 shadow-sm">
                        <span className="material-symbols-outlined text-[#fea619] text-sm">star</span>
                        {r.rating} ({r.reviews})
                      </div>

                      <div className="absolute bottom-3 left-3 bg-[#263143]/85 backdrop-blur-sm text-white px-2.5 py-0.5 rounded-full font-label-sm text-[11px] flex items-center gap-1 font-semibold">
                        <span className="material-symbols-outlined text-xs">schedule</span>
                        {r.time} • Envío {r.fee}
                      </div>
                    </div>

                    <div className="p-4 sm:p-5 flex flex-col justify-between flex-1 gap-3">
                      <div className="flex flex-col">
                        <div className="flex items-center justify-between">
                          <h3 className="font-headline-sm text-lg text-[#111c2d] group-hover:text-[#b70011] transition-colors font-bold">
                            {r.name}
                          </h3>
                          <span className="font-label-sm text-xs text-[#5c403c]">{r.address}</span>
                        </div>
                        <p className="font-body-sm text-xs text-[#5c403c] line-clamp-2 mt-1">
                          {r.description}
                        </p>
                      </div>

                      <div className="bg-[#f0f3ff] p-3 rounded-xl flex items-center justify-between border border-[#d8e3fb]">
                        <div className="flex flex-col">
                          <span className="font-label-sm text-[11px] text-[#5c403c]">Plato estrella:</span>
                          <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">
                            {r.starDish}
                          </span>
                        </div>
                        <div className="text-right">
                          <span className="font-headline-sm text-base sm:text-lg text-[#dc2626] font-black">
                            {r.starPrice}
                          </span>
                          <span className="block font-label-sm text-[11px] text-[#00682e] font-bold">
                            {r.starTag}
                          </span>
                        </div>
                      </div>
                    </div>
                  </article>
                ))}
              </div>
            </section>

            {/* SECCIÓN 2: Los Más Antojados de Neiva */}
            <section className="flex flex-col gap-5">
              <div className="flex items-center justify-between">
                <div className="flex flex-col">
                  <div className="flex items-center gap-1.5 text-[#fea619] font-label-sm text-xs uppercase font-extrabold tracking-widest">
                    <span className="material-symbols-outlined text-base">whatshot</span>
                    Tendencias de la Noche
                  </div>
                  <h2 className="font-headline-lg text-2xl sm:text-3xl text-[#111c2d] tracking-tight font-extrabold">
                    Los Más Antojados de Neiva
                  </h2>
                </div>
                <span className="font-body-sm text-xs text-[#5c403c] hidden sm:inline">
                  Personaliza salsas, términos y adiciones en un solo clic
                </span>
              </div>

              {/* Platos List */}
              <div className="grid grid-cols-1 gap-4">
                {filteredDishes.map((dish) => (
                  <div
                    key={dish.id}
                    className="bg-[#ffffff] rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row items-center gap-4 sm:gap-6 border border-[#e7eeff]"
                  >
                    <div className="w-full md:w-44 h-36 rounded-xl overflow-hidden bg-[#e7eeff] shrink-0">
                      <img
                        alt={dish.name}
                        className="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                        src={dish.image}
                      />
                    </div>

                    <div className="flex flex-col flex-1 min-w-0 justify-between h-full gap-2">
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <span className="font-label-sm text-xs text-[#dc2626] font-bold">
                            {dish.restaurant}
                          </span>
                          <h4 className="font-headline-sm text-base sm:text-lg text-[#111c2d] font-bold">
                            {dish.name}
                          </h4>
                        </div>
                        <span className="font-headline-md text-xl text-[#dc2626] font-black shrink-0">
                          {dish.price}
                        </span>
                      </div>

                      <p className="font-body-sm text-xs sm:text-sm text-[#5c403c] line-clamp-2">
                        {dish.description}
                      </p>

                      <div className="flex flex-wrap items-center justify-between gap-3 pt-1">
                        <div className="flex items-center gap-1.5 text-[#5c403c] font-label-sm text-[11px]">
                          {dish.tags.map((t, i) => (
                            <span key={i} className="bg-[#f0f3ff] px-2.5 py-0.5 rounded-full border border-[#d8e3fb]">
                              {t}
                            </span>
                          ))}
                        </div>

                        <button
                          type="button"
                          onClick={onOpenCustomizer}
                          className="inline-flex items-center gap-1.5 bg-[#dc2626] hover:bg-[#b70011] text-white font-label-md text-xs sm:text-sm px-4 py-2 rounded-full shadow transition-all active:scale-95 cursor-pointer font-bold"
                        >
                          <span className="material-symbols-outlined text-base">tune</span>
                          + Personalizar y Pedir
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </section>
          </div>

          {/* RIGHT COLUMN (30% -> 4 Cols Sticky) */}
          <aside className="lg:col-span-4 sticky top-28 flex flex-col gap-5">
            {/* ACTIVE RESTAURANT CART WIDGET */}
            <div className="bg-[#ffffff] rounded-2xl p-5 sm:p-6 shadow-xl flex flex-col gap-4 border border-[#e7eeff]">
              {/* Header */}
              <div className="flex items-center justify-between pb-2 bg-[#f0f3ff] p-3 rounded-xl border border-[#d8e3fb]">
                <div className="flex items-center gap-2">
                  <span className="material-symbols-outlined text-[#dc2626] text-xl">shopping_cart</span>
                  <div>
                    <span className="font-label-sm text-[11px] text-[#5c403c] block uppercase font-bold">
                      Restaurante Activo
                    </span>
                    <span className="font-label-md text-sm text-[#111c2d] font-bold">
                      La Estación Burger
                    </span>
                  </div>
                </div>
                <span className="bg-[#95f8a7] text-[#005323] font-label-sm text-xs px-2.5 py-0.5 rounded-full font-bold">
                  Abierto
                </span>
              </div>

              {/* Items in Cart */}
              <div className="flex flex-col gap-3">
                {/* Item 1 */}
                <div className="flex items-start justify-between gap-3 bg-[#f9f9ff] rounded-xl p-3 border border-[#e7eeff]">
                  <div className="flex flex-col flex-1">
                    <div className="flex items-center gap-1.5">
                      <span className="font-label-sm text-xs text-[#dc2626] font-bold">1x</span>
                      <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">
                        Burger Monumental 200g
                      </span>
                    </div>
                    <ul className="text-[#5c403c] font-body-sm pl-4 list-disc mt-1 space-y-0.5 text-[11px]">
                      <li>Término: Tres Cuartos (3/4)</li>
                      <li>Tocineta Ahumada extra (+$3.500)</li>
                      <li>Huevo frito de campo (+$2.000)</li>
                    </ul>
                  </div>
                  <span className="font-label-md text-sm text-[#111c2d] font-black shrink-0">
                    $30.400
                  </span>
                </div>

                {/* Item 2 */}
                <div className="flex items-start justify-between gap-3 bg-[#f9f9ff] rounded-xl p-3 border border-[#e7eeff]">
                  <div className="flex flex-col flex-1">
                    <div className="flex items-center gap-1.5">
                      <span className="font-label-sm text-xs text-[#dc2626] font-bold">1x</span>
                      <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">
                        Papas Rústicas con Romero
                      </span>
                    </div>
                    <span className="text-[#5c403c] font-body-sm text-[11px] mt-0.5">
                      Salsa tártara incluida
                    </span>
                  </div>
                  <span className="font-label-md text-sm text-[#111c2d] font-black shrink-0">
                    $7.500
                  </span>
                </div>
              </div>

              {/* Breakdown */}
              <div className="flex flex-col gap-1.5 bg-[#f0f3ff] p-4 rounded-xl border border-[#d8e3fb]">
                <div className="flex justify-between font-body-sm text-xs text-[#5c403c]">
                  <span>Subtotal Productos:</span>
                  <span className="font-bold text-[#111c2d]">$37.900 COP</span>
                </div>
                {appliedCoupon && (
                  <div className="flex justify-between font-body-sm text-xs text-[#00682e] font-semibold">
                    <span>Cupón (ANTOJONEIVA5K):</span>
                    <span>-$5.000 COP</span>
                  </div>
                )}
                <div className="flex justify-between font-body-sm text-xs text-[#5c403c]">
                  <span>Costo Domicilio (Centro):</span>
                  <span>$3.500 COP</span>
                </div>

                <div className="flex justify-between font-headline-sm text-base sm:text-lg text-[#111c2d] font-black pt-2 mt-1 bg-[#ffffff] p-3 rounded-lg border border-[#e7eeff]">
                  <span>Total a Pagar:</span>
                  <span className="text-[#dc2626]">
                    {appliedCoupon ? '$36.400 COP' : '$41.400 COP'}
                  </span>
                </div>
              </div>

              {/* Payment selector */}
              <div className="flex flex-col gap-2">
                <span className="font-label-sm text-xs text-[#5c403c] font-bold uppercase">
                  Forma de Pago Contra Entrega:
                </span>
                <div className="grid grid-cols-2 gap-2">
                  <label
                    className={`flex items-center gap-2 p-2.5 rounded-xl cursor-pointer transition-all border ${
                      selectedPaymentMode === 'efectivo'
                        ? 'bg-[#dee8ff]/50 border-[#dc2626] font-bold'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <input
                      type="radio"
                      name="pago_home"
                      checked={selectedPaymentMode === 'efectivo'}
                      onChange={() => setSelectedPaymentMode('efectivo')}
                      className="accent-[#dc2626] focus:ring-0"
                    />
                    <span className="font-label-sm text-xs text-[#111c2d]">Efectivo</span>
                  </label>

                  <label
                    className={`flex items-center gap-2 p-2.5 rounded-xl cursor-pointer transition-all border ${
                      selectedPaymentMode === 'datafono'
                        ? 'bg-[#dee8ff]/50 border-[#dc2626] font-bold'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <input
                      type="radio"
                      name="pago_home"
                      checked={selectedPaymentMode === 'datafono'}
                      onChange={() => setSelectedPaymentMode('datafono')}
                      className="accent-[#dc2626] focus:ring-0"
                    />
                    <span className="font-label-sm text-xs text-[#111c2d]">Datáfono / QR</span>
                  </label>
                </div>
              </div>

              {/* Red CTA Checkout Button */}
              <button
                type="button"
                onClick={() => onNavigate('checkout')}
                className="w-full bg-gradient-to-r from-[#dc2626] to-[#b70011] hover:opacity-95 text-white py-3.5 rounded-full font-label-lg text-sm sm:text-base font-bold shadow-[0_8px_20px_-4px_rgba(220,38,38,0.4)] flex items-center justify-center gap-2 transition-all transform active:scale-98 cursor-pointer"
              >
                <span>Proceder al Pago Seguro</span>
                <span className="material-symbols-outlined text-xl">arrow_forward</span>
              </button>
            </div>

            {/* LIVE TRACKER MINI CARD */}
            <div
              onClick={() => onNavigate('rastreo')}
              className="bg-[#ffffff] rounded-2xl p-4 sm:p-5 shadow-md flex flex-col gap-3 border border-[#e7eeff] hover:shadow-lg transition-all cursor-pointer group"
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className="relative flex h-3 w-3">
                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#fea619] opacity-75"></span>
                    <span className="relative inline-flex rounded-full h-3 w-3 bg-[#fea619]"></span>
                  </span>
                  <span className="font-label-sm text-xs text-[#111c2d] font-bold uppercase tracking-wider">
                    Pedido en Camino #8941
                  </span>
                </div>
                <span className="font-label-sm text-xs text-[#684000] font-bold bg-[#ffddb8] px-2 py-0.5 rounded-full">
                  12 min aprox
                </span>
              </div>

              {/* Rider Info */}
              <div className="flex items-center gap-3 bg-[#f0f3ff] p-3 rounded-xl border border-[#d8e3fb]">
                <div className="w-10 h-10 rounded-full bg-[#ffdad6] flex items-center justify-center text-[#dc2626] font-bold shrink-0">
                  <span className="material-symbols-outlined text-xl">two_wheeler</span>
                </div>
                <div className="flex flex-col flex-1 min-w-0 text-left">
                  <div className="flex items-center justify-between">
                    <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold truncate">
                      Carlos M. (Repartidor)
                    </span>
                    <span className="font-label-sm text-xs text-[#684000] font-black flex items-center">
                      <span className="material-symbols-outlined text-xs mr-0.5">star</span>
                      4.9
                    </span>
                  </div>
                  <span className="font-body-sm text-[11px] text-[#5c403c]">
                    Moto Bajaj Pulsar • Placa WQ-44
                  </span>
                </div>
              </div>

              {/* Progress bar */}
              <div className="flex flex-col gap-1 mt-1">
                <div className="w-full bg-[#d8e3fb] h-2 rounded-full overflow-hidden flex">
                  <div className="bg-[#fea619] w-1/3 h-full"></div>
                  <div className="bg-[#dc2626] w-1/3 h-full animate-pulse"></div>
                  <div className="bg-[#d8e3fb] w-1/3 h-full"></div>
                </div>
                <div className="flex justify-between font-label-sm text-[10px] text-[#5c403c] mt-1 font-semibold">
                  <span className="text-[#855300]">1. Cocina</span>
                  <span className="text-[#dc2626] font-bold">2. En Ruta (Cra 7)</span>
                  <span>3. Entregado</span>
                </div>
              </div>
            </div>

            {/* HUILA SUPPORT CALLOUT */}
            <div className="bg-[#dee8ff] rounded-2xl p-4 flex items-center gap-3 shadow-sm border border-[#d8e3fb]">
              <span className="material-symbols-outlined text-[#dc2626] text-2xl">support_agent</span>
              <div className="flex flex-col text-xs">
                <span className="font-bold text-[#111c2d]">¿Inquietudes con tu dirección en Neiva?</span>
                <span className="text-[#5c403c]">Línea directa de despachos vía WhatsApp al 318-000-NEIVA.</span>
              </div>
            </div>
          </aside>
        </div>
      </main>
    </div>
  );
};
