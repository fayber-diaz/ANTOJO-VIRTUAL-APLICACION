import React, { useState } from 'react';
import { CartItem } from '../types';

interface CustomizerModalProps {
  isOpen: boolean;
  onClose: () => void;
  onAddToCart: (item: CartItem) => void;
  onGoToCheckout?: () => void;
}

export const CustomizerModal: React.FC<CustomizerModalProps> = ({
  isOpen,
  onClose,
  onAddToCart,
  onGoToCheckout,
}) => {
  const basePrice = 24900;
  const [quantity, setQuantity] = useState(1);
  const [doneness, setDoneness] = useState('tres_cuartos');
  const [bread, setBread] = useState<{ id: string; name: string; price: number }>({
    id: 'brioche',
    name: 'Pan Brioche Dorado Tradicional',
    price: 0,
  });

  // Toppings state
  const [tocinetaActive, setTocinetaActive] = useState(true);
  const [tocinetaCount, setTocinetaCount] = useState(1);
  const [quesoActive, setQuesoActive] = useState(true);
  const [huevoActive, setHuevoActive] = useState(true);
  const [platanoActive, setPlatanoActive] = useState(false);
  const [pinaActive, setPinaActive] = useState(false);
  const [chicharronActive, setChicharronActive] = useState(false);

  // Sauces state
  const [sauces, setSauces] = useState<string[]>(['tartara', 'bbq_guayaba', 'mayo_ajo']);
  const [notes, setNotes] = useState('');

  if (!isOpen) return null;

  // Calculate Toppings Cost
  let toppingsCost = bread.price;
  if (tocinetaActive) toppingsCost += 3500 * tocinetaCount;
  if (quesoActive) toppingsCost += 3000;
  if (huevoActive) toppingsCost += 2000;
  if (platanoActive) toppingsCost += 2500;
  if (pinaActive) toppingsCost += 2000;
  if (chicharronActive) toppingsCost += 4500;

  const itemTotal = (basePrice + toppingsCost) * quantity;

  const handleToggleSauce = (sauceId: string) => {
    if (sauces.includes(sauceId)) {
      setSauces(sauces.filter((s) => s !== sauceId));
    } else {
      if (sauces.length >= 3) {
        alert('Puedes elegir un máximo de 3 salsas gratuitas de la casa.');
        return;
      }
      setSauces([...sauces, sauceId]);
    }
  };

  const handleAddToCart = (directCheckout = false) => {
    const selectedToppings = [];
    if (tocinetaActive) {
      selectedToppings.push({
        name: `Tocineta Ahumada Crunch ${tocinetaCount > 1 ? `(${tocinetaCount}x)` : ''}`,
        price: 3500 * tocinetaCount,
        count: tocinetaCount,
      });
    }
    if (quesoActive) {
      selectedToppings.push({ name: 'Queso Campesino Asado Extra', price: 3000 });
    }
    if (huevoActive) {
      selectedToppings.push({ name: 'Huevo Frito Campesino de Finca', price: 2000 });
    }
    if (platanoActive) {
      selectedToppings.push({ name: 'Plátano Maduro Caramelizado', price: 2500 });
    }
    if (pinaActive) {
      selectedToppings.push({ name: 'Piña Calada Artesanal', price: 2000 });
    }
    if (chicharronActive) {
      selectedToppings.push({ name: 'Tiras de Chicharrón Crocante', price: 4500 });
    }

    const cartItem: CartItem = {
      id: `burger-${Date.now()}`,
      name: 'Burger Monumental 200g',
      restaurant: 'La Estación Burger',
      basePrice,
      quantity,
      doneness:
        doneness === 'tres_cuartos'
          ? 'Tres Cuartos (3/4)'
          : doneness === 'medio'
          ? 'Término Medio'
          : 'Bien Asada',
      bread: bread.name,
      toppings: selectedToppings,
      sauces,
      notes,
      image:
        'https://lh3.googleusercontent.com/aida-public/AB6AXuAvpNe-2sxMHJB6ZfCpBDmFunrgNT9edS0cAy3G2AUKknHSJ1ij4r1T46hg1vFCWCA-ReaJsjYUzLDKYP7q78TShe6FLl5JQ7ehHaIUG28S2qzWXwjqpFp6wjH2HPKNknRhhdnSj4MV0-iFFGgs6_ypaU0W-TxZJz-h4uRA1LAbk1PLFdS3koJNHWZrRMT0ZhtjB7yddtpHRy_nf6J47Ok9TejT0dbuBsQThJ65Qh6yxm02bbSsYZ9p',
    };

    onAddToCart(cartItem);
    if (directCheckout && onGoToCheckout) {
      onGoToCheckout();
    } else {
      onClose();
    }
  };

  return (
    <div className="fixed inset-0 z-50 bg-[#263143]/70 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 overflow-y-auto animate-in fade-in duration-200">
      {/* Modal Dialog Card */}
      <div className="relative w-full max-w-[1020px] max-h-[92vh] bg-[#ffffff] rounded-2xl shadow-[0_20px_50px_rgba(17,28,45,0.3)] flex flex-col overflow-hidden my-auto border border-[#d8e3fb]">
        {/* Close Button */}
        <button
          onClick={onClose}
          type="button"
          className="absolute top-4 right-4 z-30 w-10 h-10 rounded-full bg-[#ffffff]/90 hover:bg-[#ffffff] text-[#111c2d] flex items-center justify-center shadow-lg transition-transform hover:scale-105 active:scale-95 cursor-pointer"
          title="Cerrar modal"
        >
          <span className="material-symbols-outlined text-2xl">close</span>
        </button>

        {/* Header Hero Section */}
        <div className="relative bg-[#f0f3ff] flex flex-col md:flex-row items-stretch shrink-0 border-b border-[#e7eeff]">
          {/* Imagery */}
          <div className="relative md:w-5/12 min-h-[220px] md:min-h-[260px] overflow-hidden bg-[#e7eeff]">
            <img
              alt="Hamburguesa Monumental Huilense 200g en parrilla"
              className="w-full h-full object-cover scale-105 hover:scale-100 transition-transform duration-700"
              src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&auto=format&fit=crop&q=80"
              onError={(e) => {
                // Fallback styling
                (e.target as HTMLImageElement).src =
                  'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800&auto=format&fit=crop&q=80';
              }}
            />
            <div className="absolute inset-0 bg-gradient-to-t md:bg-gradient-to-r from-transparent via-[#263143]/15 to-transparent"></div>
            <span className="absolute bottom-3 left-3 bg-[#fea619] text-[#684000] px-3 py-1 rounded-full font-label-sm text-xs font-bold shadow-md flex items-center gap-1">
              <span className="material-symbols-outlined text-sm">local_fire_department</span>
              Fuego Directo al Carbón
            </span>
          </div>

          {/* Details */}
          <div className="md:w-7/12 p-5 sm:p-6 lg:p-8 flex flex-col justify-center gap-2">
            <div className="flex items-center gap-2 flex-wrap">
              <span className="bg-[#dc2626] text-white px-3 py-1 rounded-full font-label-sm text-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-sm">
                <span className="material-symbols-outlined text-sm">verified</span>
                Especialidad de la Casa
              </span>
              <span className="bg-[#e7eeff] text-[#5c403c] px-3 py-1 rounded-full font-label-sm text-xs font-bold flex items-center gap-1">
                <span className="material-symbols-outlined text-sm">store</span>
                La Estación Burger • Neiva
              </span>
            </div>

            <h2 className="font-headline-lg text-2xl sm:text-3xl text-[#111c2d] tracking-tight font-extrabold mt-1">
              Hamburguesa Monumental Huilense 200g
            </h2>

            <div className="flex items-baseline gap-2">
              <span className="font-headline-md text-2xl text-[#b70011] font-black">
                $24.900 COP
              </span>
              <span className="font-label-sm text-xs text-[#5c403c]">Precio Base Sugerido</span>
            </div>

            <p className="font-body-sm text-xs sm:text-sm text-[#5c403c] leading-relaxed">
              Carne 100% de res seleccionada a la brasa, queso campesino asado de Algeciras (Huila), cebolla caramelizada en panela orgánica, lechuga cogollo fresca, tomate milano y salsa tártara artesanal de la casa.
            </p>
          </div>
        </div>

        {/* Scrollable Customization Options */}
        <div className="flex-1 overflow-y-auto p-5 sm:p-6 lg:p-8 bg-[#f9f9ff]">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* LEFT COLUMN: Término & Pan & Notas */}
            <div className="flex flex-col gap-6">
              {/* SECTION 1: Término de la Carne */}
              <div className="bg-[#ffffff] p-4 sm:p-5 rounded-2xl shadow-sm border border-[#e7eeff] flex flex-col gap-3">
                <div className="flex items-center justify-between pb-1 border-b border-[#f0f3ff]">
                  <div className="flex items-center gap-2">
                    <span className="w-6 h-6 rounded-full bg-[#dc2626] text-white font-label-sm text-xs flex items-center justify-center font-bold">
                      1
                    </span>
                    <span className="font-headline-sm text-base sm:text-lg text-[#111c2d] font-bold">
                      Término de la Carne
                    </span>
                  </div>
                  <span className="bg-[#ffdad6] text-[#93000a] px-2.5 py-0.5 rounded-full font-label-sm text-[11px] font-bold uppercase">
                    Obligatorio
                  </span>
                </div>
                <p className="font-body-sm text-xs text-[#5c403c]">
                  Elige el punto exacto de jugosidad para tu medallón de 200g.
                </p>

                <div className="flex flex-col gap-2 mt-1">
                  <label
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer ${
                      doneness === 'medio'
                        ? 'bg-[#d8e3fb]/40 border-[#dc2626]'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="radio"
                        name="cooking_doneness"
                        value="medio"
                        checked={doneness === 'medio'}
                        onChange={() => setDoneness('medio')}
                        className="w-4 h-4 accent-[#dc2626] cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Término Medio
                        </span>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Centro rojo caliente y máxima jugosidad
                        </span>
                      </div>
                    </div>
                    <span className="font-label-sm text-xs text-[#00682e] font-bold">Sin costo</span>
                  </label>

                  <label
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer ${
                      doneness === 'tres_cuartos'
                        ? 'bg-[#d8e3fb]/50 border-[#dc2626] shadow-sm'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="radio"
                        name="cooking_doneness"
                        value="tres_cuartos"
                        checked={doneness === 'tres_cuartos'}
                        onChange={() => setDoneness('tres_cuartos')}
                        className="w-4 h-4 accent-[#dc2626] cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <div className="flex items-center gap-1.5">
                          <span className="font-label-md text-sm text-[#111c2d] font-bold">
                            Tres Cuartos (3/4)
                          </span>
                          <span className="bg-[#fea619] text-[#684000] px-1.5 py-0.2 rounded font-label-sm text-[10px] font-black">
                            Recomendado
                          </span>
                        </div>
                        <span className="font-body-sm text-xs text-[#111c2d]">
                          Punto ideal: centro rosado y sellado perfecto
                        </span>
                      </div>
                    </div>
                    <span className="font-label-sm text-xs text-[#00682e] font-bold">Sin costo</span>
                  </label>

                  <label
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer ${
                      doneness === 'bien_asada'
                        ? 'bg-[#d8e3fb]/40 border-[#dc2626]'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="radio"
                        name="cooking_doneness"
                        value="bien_asada"
                        checked={doneness === 'bien_asada'}
                        onChange={() => setDoneness('bien_asada')}
                        className="w-4 h-4 accent-[#dc2626] cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Bien Asada
                        </span>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Cocción completa uniforme sin tonos rosas
                        </span>
                      </div>
                    </div>
                    <span className="font-label-sm text-xs text-[#00682e] font-bold">Sin costo</span>
                  </label>
                </div>
              </div>

              {/* SECTION 2: Tipo de Pan */}
              <div className="bg-[#ffffff] p-4 sm:p-5 rounded-2xl shadow-sm border border-[#e7eeff] flex flex-col gap-3">
                <div className="flex items-center justify-between pb-1 border-b border-[#f0f3ff]">
                  <div className="flex items-center gap-2">
                    <span className="w-6 h-6 rounded-full bg-[#dc2626] text-white font-label-sm text-xs flex items-center justify-center font-bold">
                      2
                    </span>
                    <span className="font-headline-sm text-base sm:text-lg text-[#111c2d] font-bold">
                      Tipo de Pan
                    </span>
                  </div>
                  <span className="bg-[#ffdad6] text-[#93000a] px-2.5 py-0.5 rounded-full font-label-sm text-[11px] font-bold uppercase">
                    Obligatorio
                  </span>
                </div>
                <p className="font-body-sm text-xs text-[#5c403c]">
                  Bases horneadas diariamente por panaderos locales.
                </p>

                <div className="flex flex-col gap-2 mt-1">
                  <label
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer ${
                      bread.id === 'brioche'
                        ? 'bg-[#d8e3fb]/50 border-[#dc2626] shadow-sm'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="radio"
                        name="bread_type"
                        checked={bread.id === 'brioche'}
                        onChange={() =>
                          setBread({ id: 'brioche', name: 'Pan Brioche Dorado Tradicional', price: 0 })
                        }
                        className="w-4 h-4 accent-[#dc2626] cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Pan Brioche Dorado Tradicional
                        </span>
                        <span className="font-body-sm text-xs text-[#111c2d]">
                          Toque de mantequilla y ajonjolí negro tostado
                        </span>
                      </div>
                    </div>
                    <span className="font-label-sm text-xs text-[#00682e] font-bold">Incluido</span>
                  </label>

                  <label
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer ${
                      bread.id === 'papa'
                        ? 'bg-[#d8e3fb]/50 border-[#dc2626] shadow-sm'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="radio"
                        name="bread_type"
                        checked={bread.id === 'papa'}
                        onChange={() =>
                          setBread({ id: 'papa', name: 'Pan de Papa Artesanal', price: 1500 })
                        }
                        className="w-4 h-4 accent-[#dc2626] cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Pan de Papa Artesanal
                        </span>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Textura ultra-esponjosa de absorción lenta
                        </span>
                      </div>
                    </div>
                    <span className="font-label-sm text-xs text-[#855300] font-bold">
                      + $1.500 COP
                    </span>
                  </label>

                  <label
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer ${
                      bread.id === 'fit'
                        ? 'bg-[#d8e3fb]/50 border-[#dc2626] shadow-sm'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="radio"
                        name="bread_type"
                        checked={bread.id === 'fit'}
                        onChange={() =>
                          setBread({ id: 'fit', name: 'Opción Fit (Envoltura Verde)', price: 0 })
                        }
                        className="w-4 h-4 accent-[#dc2626] cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <div className="flex items-center gap-1.5">
                          <span className="font-label-md text-sm text-[#111c2d] font-bold">
                            Opción Fit (Envoltura Verde)
                          </span>
                          <span className="bg-[#95f8a7] text-[#005323] px-1.5 py-0.2 rounded font-label-sm text-[10px] font-bold">
                            Sin Gluten
                          </span>
                        </div>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Envuelta en crujientes hojas de lechuga cogollo
                        </span>
                      </div>
                    </div>
                    <span className="font-label-sm text-xs text-[#00682e] font-bold">Sin costo extra</span>
                  </label>
                </div>
              </div>

              {/* SECTION 5: Instrucciones Especiales */}
              <div className="bg-[#ffffff] p-4 sm:p-5 rounded-2xl shadow-sm border border-[#e7eeff] flex flex-col gap-2">
                <div className="flex items-center gap-2 pb-1">
                  <span className="material-symbols-outlined text-[#dc2626] text-xl">
                    soup_kitchen
                  </span>
                  <span className="font-headline-sm text-base text-[#111c2d] font-bold">
                    Instrucciones Especiales para Cocina
                  </span>
                </div>
                <p className="font-body-sm text-xs text-[#5c403c]">
                  ¿Alguna preferencia de corte, empaque o alergia alimentaria?
                </p>
                <textarea
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  className="w-full bg-[#f0f3ff] focus:bg-[#ffffff] border border-[#d8e3fb] focus:border-[#dc2626] text-[#111c2d] font-body-sm text-sm p-3 rounded-xl focus:outline-none transition-all placeholder:text-[#5c403c]/60 resize-none"
                  placeholder="Ej: Por favor salsas empacadas por aparte, sin cebolla, cortar la burger por la mitad..."
                  rows={2}
                />
              </div>
            </div>

            {/* RIGHT COLUMN: Toppings & Salsas */}
            <div className="flex flex-col gap-6">
              {/* SECTION 3: TOPPINGS & ADICIONES EXTRA */}
              <div className="bg-[#ffffff] p-4 sm:p-5 rounded-2xl shadow-sm border border-[#e7eeff] flex flex-col gap-3">
                <div className="flex items-center justify-between pb-1 border-b border-[#f0f3ff]">
                  <div className="flex items-center gap-2">
                    <span className="w-6 h-6 rounded-full bg-[#855300] text-white font-label-sm text-xs flex items-center justify-center font-bold">
                      3
                    </span>
                    <span className="font-headline-sm text-base sm:text-lg text-[#111c2d] font-bold">
                      Toppings & Adiciones Extra
                    </span>
                  </div>
                  <span className="bg-[#e7eeff] text-[#5c403c] px-2.5 py-0.5 rounded-full font-label-sm text-[11px] font-bold">
                    Opcional
                  </span>
                </div>
                <p className="font-body-sm text-xs text-[#5c403c]">
                  Personaliza con ingredientes frescos y crujientes preparados al instante.
                </p>

                <div className="flex flex-col gap-2 mt-1">
                  {/* Tocineta Crunch */}
                  <div
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all ${
                      tocinetaActive
                        ? 'bg-[#dee8ff]/50 border-[#dc2626]/40'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <label className="flex items-center gap-3 cursor-pointer select-none flex-1">
                      <input
                        type="checkbox"
                        checked={tocinetaActive}
                        onChange={(e) => setTocinetaActive(e.target.checked)}
                        className="w-4 h-4 accent-[#dc2626] rounded cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Tocineta Ahumada Crunch
                        </span>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Tiras extra crujientes caramelizadas
                        </span>
                      </div>
                    </label>

                    <div className="flex items-center gap-3">
                      <span className="font-label-md text-sm text-[#b70011] font-bold">
                        + $3.500
                      </span>
                      {tocinetaActive && (
                        <div className="flex items-center bg-[#ffffff] rounded-full px-1.5 py-0.5 shadow-sm border border-[#d8e3fb]">
                          <button
                            type="button"
                            onClick={() => setTocinetaCount(Math.max(1, tocinetaCount - 1))}
                            className="w-5 h-5 flex items-center justify-center text-[#5c403c] hover:text-[#dc2626] cursor-pointer"
                          >
                            <span className="material-symbols-outlined text-sm">remove</span>
                          </button>
                          <span className="font-label-sm text-xs px-2 font-bold text-[#111c2d]">
                            {tocinetaCount}
                          </span>
                          <button
                            type="button"
                            onClick={() => setTocinetaCount(tocinetaCount + 1)}
                            className="w-5 h-5 flex items-center justify-center text-[#5c403c] hover:text-[#dc2626] cursor-pointer"
                          >
                            <span className="material-symbols-outlined text-sm">add</span>
                          </button>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Queso Campesino Asado Extra */}
                  <div
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all ${
                      quesoActive
                        ? 'bg-[#dee8ff]/50 border-[#dc2626]/40'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <label className="flex items-center gap-3 cursor-pointer select-none flex-1">
                      <input
                        type="checkbox"
                        checked={quesoActive}
                        onChange={(e) => setQuesoActive(e.target.checked)}
                        className="w-4 h-4 accent-[#dc2626] rounded cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Queso Campesino Asado Extra
                        </span>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Tajada gruesa dorada en parrilla de Algeciras
                        </span>
                      </div>
                    </label>
                    <span className="font-label-md text-sm text-[#b70011] font-bold">
                      + $3.000
                    </span>
                  </div>

                  {/* Huevo Frito */}
                  <div
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all ${
                      huevoActive
                        ? 'bg-[#dee8ff]/50 border-[#dc2626]/40'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <label className="flex items-center gap-3 cursor-pointer select-none flex-1">
                      <input
                        type="checkbox"
                        checked={huevoActive}
                        onChange={(e) => setHuevoActive(e.target.checked)}
                        className="w-4 h-4 accent-[#dc2626] rounded cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Huevo Frito Campesino de Finca
                        </span>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Yema tierna con borde tostado
                        </span>
                      </div>
                    </label>
                    <span className="font-label-md text-sm text-[#b70011] font-bold">
                      + $2.000
                    </span>
                  </div>

                  {/* Plátano Maduro */}
                  <div
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all ${
                      platanoActive
                        ? 'bg-[#dee8ff]/50 border-[#dc2626]/40'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <label className="flex items-center gap-3 cursor-pointer select-none flex-1">
                      <input
                        type="checkbox"
                        checked={platanoActive}
                        onChange={(e) => setPlatanoActive(e.target.checked)}
                        className="w-4 h-4 accent-[#dc2626] rounded cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Plátano Maduro Caramelizado
                        </span>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Trozos dulces fritos estilo huilense
                        </span>
                      </div>
                    </label>
                    <span className="font-label-md text-sm text-[#111c2d] font-semibold">
                      + $2.500
                    </span>
                  </div>

                  {/* Piña Calada */}
                  <div
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all ${
                      pinaActive
                        ? 'bg-[#dee8ff]/50 border-[#dc2626]/40'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <label className="flex items-center gap-3 cursor-pointer select-none flex-1">
                      <input
                        type="checkbox"
                        checked={pinaActive}
                        onChange={(e) => setPinaActive(e.target.checked)}
                        className="w-4 h-4 accent-[#dc2626] rounded cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Piña Calada Artesanal
                        </span>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Melao tradicional con especias dulces
                        </span>
                      </div>
                    </label>
                    <span className="font-label-md text-sm text-[#111c2d] font-semibold">
                      + $2.000
                    </span>
                  </div>

                  {/* Chicharrón Crocante */}
                  <div
                    className={`flex items-center justify-between p-3 rounded-xl border transition-all ${
                      chicharronActive
                        ? 'bg-[#dee8ff]/50 border-[#dc2626]/40'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <label className="flex items-center gap-3 cursor-pointer select-none flex-1">
                      <input
                        type="checkbox"
                        checked={chicharronActive}
                        onChange={(e) => setChicharronActive(e.target.checked)}
                        className="w-4 h-4 accent-[#dc2626] rounded cursor-pointer"
                      />
                      <div className="flex flex-col">
                        <span className="font-label-md text-sm text-[#111c2d] font-bold">
                          Tiras de Chicharrón Crocante
                        </span>
                        <span className="font-body-sm text-xs text-[#5c403c]">
                          Corte crujiente con toque de sal marina
                        </span>
                      </div>
                    </label>
                    <span className="font-label-md text-sm text-[#111c2d] font-semibold">
                      + $4.500
                    </span>
                  </div>
                </div>
              </div>

              {/* SECTION 4: SALSAS DE LA CASA */}
              <div className="bg-[#ffffff] p-4 sm:p-5 rounded-2xl shadow-sm border border-[#e7eeff] flex flex-col gap-3">
                <div className="flex items-center justify-between pb-1 border-b border-[#f0f3ff]">
                  <div className="flex items-center gap-2">
                    <span className="w-6 h-6 rounded-full bg-[#fea619] text-[#684000] font-label-sm text-xs flex items-center justify-center font-bold">
                      4
                    </span>
                    <span className="font-headline-sm text-base sm:text-lg text-[#111c2d] font-bold">
                      Salsas de la Casa
                    </span>
                  </div>
                  <span className="bg-[#95f8a7] text-[#005323] px-2.5 py-0.5 rounded-full font-label-sm text-[11px] font-bold">
                    Hasta 3 Gratis ({sauces.length}/3)
                  </span>
                </div>
                <p className="font-body-sm text-xs text-[#5c403c]">
                  Recetas exclusivas preparadas a diario por La Estación.
                </p>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-1">
                  {[
                    { id: 'tartara', label: 'Tártara Huilense' },
                    { id: 'bbq_guayaba', label: 'BBQ Guayaba & Panela' },
                    { id: 'mayo_ajo', label: 'Ajo Rostizado' },
                    { id: 'aji_pajarito', label: 'Ají Pajarito Opita (Picante)' },
                    { id: 'mostaza_dulce', label: 'Mostaza Dulce a la Antigua' },
                  ].map((s) => {
                    const isChecked = sauces.includes(s.id);
                    return (
                      <label
                        key={s.id}
                        className={`flex items-center gap-2.5 p-2.5 rounded-xl border transition-all cursor-pointer ${
                          isChecked
                            ? 'bg-[#dee8ff]/50 border-[#dc2626]/40 font-bold'
                            : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                        }`}
                      >
                        <input
                          type="checkbox"
                          checked={isChecked}
                          onChange={() => handleToggleSauce(s.id)}
                          className="w-4 h-4 accent-[#dc2626] rounded cursor-pointer"
                        />
                        <span className="font-body-sm text-xs text-[#111c2d]">{s.label}</span>
                      </label>
                    );
                  })}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Floating Summary Dock Footer */}
        <div className="bg-[#ffffff] p-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4 shadow-[0_-8px_25px_rgba(30,41,59,0.08)] shrink-0 border-t border-[#e7eeff]">
          {/* Quantity Stepper & Price Calculation */}
          <div className="flex items-center gap-4 sm:gap-6 w-full md:w-auto justify-between md:justify-start">
            <div className="flex items-center bg-[#f0f3ff] rounded-full p-1 border border-[#d8e3fb]">
              <button
                type="button"
                onClick={() => setQuantity(Math.max(1, quantity - 1))}
                className="w-9 h-9 rounded-full bg-[#ffffff] hover:bg-[#f9f9ff] text-[#111c2d] flex items-center justify-center transition-all shadow-sm active:scale-90 cursor-pointer"
                title="Disminuir cantidad"
              >
                <span className="material-symbols-outlined text-base">remove</span>
              </button>
              <span className="font-headline-sm text-lg text-[#111c2d] px-4 font-black">
                {quantity}
              </span>
              <button
                type="button"
                onClick={() => setQuantity(quantity + 1)}
                className="w-9 h-9 rounded-full bg-[#ffffff] hover:bg-[#f9f9ff] text-[#111c2d] flex items-center justify-center transition-all shadow-sm active:scale-90 cursor-pointer"
                title="Aumentar cantidad"
              >
                <span className="material-symbols-outlined text-base">add</span>
              </button>
            </div>

            <div className="flex flex-col text-left">
              <div className="flex items-center gap-1.5 font-label-sm text-xs text-[#5c403c]">
                <span>Base $24.900</span>
                <span>+</span>
                <span>Toppings ${toppingsCost.toLocaleString('es-CO')}</span>
              </div>
              <div className="flex items-baseline gap-1">
                <span className="font-label-sm text-xs uppercase tracking-wider text-[#5c403c] font-bold">
                  Total:
                </span>
                <span className="font-headline-md text-xl sm:text-2xl text-[#dc2626] font-black">
                  ${itemTotal.toLocaleString('es-CO')} COP
                </span>
              </div>
            </div>
          </div>

          {/* Action Button */}
          <div className="flex flex-col sm:flex-row md:flex-col items-end gap-1.5 w-full md:w-auto">
            <div className="flex gap-2 w-full md:w-auto">
              <button
                type="button"
                onClick={() => handleAddToCart(false)}
                className="flex-1 md:flex-none px-6 py-3 rounded-full bg-[#dc2626] hover:bg-[#b70011] text-white font-label-lg text-sm sm:text-base shadow-lg shadow-[#dc2626]/20 hover:shadow-xl hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer font-bold"
              >
                <span className="material-symbols-outlined text-xl">shopping_cart_checkout</span>
                <span>Agregar a la Orden — ${itemTotal.toLocaleString('es-CO')} COP</span>
              </button>
            </div>
            <div className="flex items-center gap-1 font-label-sm text-[11px] text-[#00682e] font-semibold">
              <span className="material-symbols-outlined text-sm">verified</span>
              <span>Preparación en vivo al carbón • Despacho prioritario en Neiva</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
