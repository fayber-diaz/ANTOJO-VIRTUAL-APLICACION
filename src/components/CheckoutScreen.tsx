import React, { useState } from 'react';
import { AppScreen, CartItem } from '../types';

interface CheckoutScreenProps {
  onNavigate: (screen: AppScreen) => void;
  onConfirmOrder: () => void;
  cartItems: CartItem[];
  appliedCoupon: boolean;
  onRemoveCoupon: () => void;
  onApplyCoupon: (code: string) => void;
}

export const CheckoutScreen: React.FC<CheckoutScreenProps> = ({
  onNavigate,
  onConfirmOrder,
  cartItems,
  appliedCoupon,
  onRemoveCoupon,
  onApplyCoupon,
}) => {
  const [deliveryTiming, setDeliveryTiming] = useState<'immediate' | 'scheduled'>('immediate');
  const [instructions, setInstructions] = useState('Dejar en recepción con portero don Hernando');
  const [paymentTab, setPaymentTab] = useState<'online' | 'cash'>('online');
  const [onlineMethod, setOnlineMethod] = useState<'nequi' | 'daviplata' | 'pse' | 'card'>('nequi');
  const [cashSubMethod, setCashSubMethod] = useState<'efectivo' | 'datafono'>('efectivo');
  const [cashBillAmount, setCashBillAmount] = useState<string>('exacto');
  const [receiverName, setReceiverName] = useState('Mateo R.');
  const [receiverPhone, setReceiverPhone] = useState('+57 314 289 9012');
  const [whatsappNotify, setWhatsappNotify] = useState(true);
  const [showAddressModal, setShowAddressModal] = useState(false);
  const [currentAddress, setCurrentAddress] = useState('Cra. 5 #18-42, Centro');
  const [currentComuna, setCurrentComuna] = useState('Comuna 4');
  const [couponInput, setCouponInput] = useState('');
  const [showCouponInput, setShowCouponInput] = useState(false);

  // Card form state
  const [cardNumber, setCardNumber] = useState('4520 0000 0000 0000');
  const [cardExpiry, setCardExpiry] = useState('11/28');
  const [cardCvv, setCardCvv] = useState('123');
  const [cardName, setCardName] = useState('Mateo Rodríguez');

  // Math totals calculation
  const subtotalProducts = 40900;
  const toppingsAdiciones = 8500;
  const discountAmount = appliedCoupon ? 5000 : 0;
  const deliveryFee = 3500;
  const tasaApoyo = 1000;
  const totalToPay = subtotalProducts + toppingsAdiciones - discountAmount + deliveryFee + tasaApoyo;

  return (
    <div className="flex flex-col w-full">
      {/* PROGRESS STEPPER HEADER */}
      <section className="w-full bg-[#ffffff] shadow-sm py-4 border-b border-[#e7eeff]">
        <div className="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-10">
          <div className="flex items-center justify-between">
            {/* Step 1 */}
            <button
              onClick={() => onNavigate('explorar')}
              className="flex items-center gap-2 text-[#00682e] hover:opacity-80 transition-opacity cursor-pointer text-left"
            >
              <div className="w-8 h-8 rounded-full bg-[#00682e]/10 flex items-center justify-center font-label-md text-[#00682e]">
                <span className="material-symbols-outlined text-lg">check_circle</span>
              </div>
              <div className="flex flex-col">
                <span className="font-label-sm text-[11px] uppercase tracking-wider text-[#00682e] font-bold">
                  Paso 1
                </span>
                <span className="font-label-md text-xs sm:text-sm font-bold text-[#111c2d]">
                  Carrito & Toppings
                </span>
              </div>
            </button>

            <div className="flex-1 h-0.5 mx-4 bg-[#00682e]/30 rounded-full"></div>

            {/* Step 2 (ACTIVE) */}
            <div className="flex items-center gap-2 text-[#dc2626]">
              <div className="w-8 h-8 rounded-full bg-[#dc2626] text-white flex items-center justify-center font-label-md font-black shadow-sm">
                2
              </div>
              <div className="flex flex-col">
                <span className="font-label-sm text-[11px] uppercase tracking-wider text-[#dc2626] font-bold">
                  Paso 2
                </span>
                <span className="font-label-md text-xs sm:text-sm font-bold text-[#111c2d]">
                  Entrega & Pago
                </span>
              </div>
            </div>

            <div className="flex-1 h-0.5 mx-4 bg-[#d8e3fb] rounded-full"></div>

            {/* Step 3 */}
            <div className="flex items-center gap-2 opacity-50">
              <div className="w-8 h-8 rounded-full bg-[#d8e3fb] flex items-center justify-center font-label-md font-bold text-[#5c403c]">
                3
              </div>
              <div className="flex flex-col">
                <span className="font-label-sm text-[11px] uppercase tracking-wider text-[#5c403c]">
                  Paso 3
                </span>
                <span className="font-label-md text-xs sm:text-sm text-[#5c403c]">Confirmación</span>
              </div>
            </div>

            <div className="flex-1 h-0.5 mx-4 bg-[#d8e3fb] rounded-full hidden sm:block"></div>

            {/* Step 4 */}
            <button
              onClick={() => onNavigate('rastreo')}
              className="flex items-center gap-2 opacity-50 hover:opacity-100 transition-opacity cursor-pointer hidden sm:flex text-left"
            >
              <div className="w-8 h-8 rounded-full bg-[#d8e3fb] flex items-center justify-center font-label-md font-bold text-[#5c403c]">
                4
              </div>
              <div className="flex flex-col">
                <span className="font-label-sm text-[11px] uppercase tracking-wider text-[#5c403c]">
                  Paso 4
                </span>
                <span className="font-label-md text-xs sm:text-sm text-[#5c403c]">Rastreo en Vivo</span>
              </div>
            </button>
          </div>
        </div>
      </section>

      {/* CHECKOUT GRID CONTAINER */}
      <div className="max-w-[1280px] w-full mx-auto px-4 sm:px-6 lg:px-10 py-8 lg:py-10">
        <div className="flex flex-col lg:flex-row gap-8 items-start">
          {/* LEFT COLUMN: Transaction Details (65%) */}
          <div className="w-full lg:w-[65%] flex flex-col gap-6">
            {/* SECTION A: Delivery Location */}
            <div className="bg-[#ffffff] rounded-2xl p-5 sm:p-6 shadow-sm border border-[#e7eeff]">
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-2">
                  <span className="material-symbols-outlined text-[#dc2626] text-2xl">pin_drop</span>
                  <h2 className="font-headline-sm text-lg sm:text-xl text-[#111c2d] font-bold">
                    Dirección de Entrega en Neiva
                  </h2>
                </div>
                <button
                  type="button"
                  onClick={() => setShowAddressModal(true)}
                  className="font-label-md text-xs sm:text-sm text-[#dc2626] hover:text-[#b70011] font-bold transition-colors cursor-pointer"
                >
                  Cambiar Dirección
                </button>
              </div>

              {/* Active Address Card */}
              <div className="bg-[#f0f3ff] rounded-xl p-4 flex items-start justify-between gap-4 mb-4 border border-[#d8e3fb]">
                <div className="flex items-start gap-3">
                  <div className="p-2 bg-[#dc2626]/10 rounded-full text-[#dc2626] mt-0.5">
                    <span className="material-symbols-outlined text-xl">home</span>
                  </div>
                  <div className="flex flex-col">
                    <div className="flex items-center gap-2">
                      <span className="font-label-lg text-sm sm:text-base text-[#111c2d] font-bold">
                        {currentAddress}
                      </span>
                      <span className="px-2.5 py-0.5 bg-[#ffddb8] text-[#2a1700] font-label-sm text-[11px] rounded-full font-bold">
                        {currentComuna}
                      </span>
                    </div>
                    <p className="font-body-md text-xs sm:text-sm text-[#5c403c] mt-0.5">
                      Edificio Los Comuneros, Apto 402 - Timbre 402
                    </p>
                    <div className="flex items-center gap-1.5 text-[#00682e] font-label-sm text-xs mt-1.5 font-bold">
                      <span className="material-symbols-outlined text-base">check_circle</span>
                      <span>Área de cobertura rápida Neiva Centro</span>
                    </div>
                  </div>
                </div>
                <button
                  onClick={() => setShowAddressModal(true)}
                  className="material-symbols-outlined text-[#5c403c] cursor-pointer hover:text-[#111c2d] p-1"
                  title="Editar dirección"
                >
                  edit
                </button>
              </div>

              {/* Dispatch Time Selector */}
              <div className="mb-4">
                <label className="font-label-md text-xs sm:text-sm text-[#111c2d] block mb-2 font-bold">
                  Tiempo Estimado de Entrega
                </label>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {/* Immediate */}
                  <label
                    onClick={() => setDeliveryTiming('immediate')}
                    className={`flex items-center gap-3 p-3 rounded-xl cursor-pointer border transition-all ${
                      deliveryTiming === 'immediate'
                        ? 'bg-[#dc2626]/5 border-[#dc2626] shadow-xs'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <input
                      checked={deliveryTiming === 'immediate'}
                      onChange={() => setDeliveryTiming('immediate')}
                      className="accent-[#dc2626] w-4 h-4 cursor-pointer"
                      name="delivery_timing"
                      type="radio"
                    />
                    <div className="flex flex-col">
                      <span className="font-label-md text-xs sm:text-sm text-[#dc2626] font-bold flex items-center gap-1">
                        <span className="material-symbols-outlined text-base">bolt</span> Entrega Inmediata
                      </span>
                      <span className="font-body-sm text-xs text-[#5c403c]">Llega en 25 - 35 minutos</span>
                    </div>
                  </label>

                  {/* Scheduled */}
                  <label
                    onClick={() => setDeliveryTiming('scheduled')}
                    className={`flex items-center gap-3 p-3 rounded-xl cursor-pointer border transition-all ${
                      deliveryTiming === 'scheduled'
                        ? 'bg-[#dc2626]/5 border-[#dc2626] shadow-xs'
                        : 'bg-[#f0f3ff] border-transparent hover:bg-[#e7eeff]'
                    }`}
                  >
                    <input
                      checked={deliveryTiming === 'scheduled'}
                      onChange={() => setDeliveryTiming('scheduled')}
                      className="accent-[#dc2626] w-4 h-4 cursor-pointer"
                      name="delivery_timing"
                      type="radio"
                    />
                    <div className="flex flex-col">
                      <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-semibold flex items-center gap-1">
                        <span className="material-symbols-outlined text-base">schedule</span> Programar para hoy
                      </span>
                      <span className="font-body-sm text-xs text-[#5c403c]">Elegir franja de la tarde</span>
                    </div>
                  </label>
                </div>
              </div>

              {/* Instructions Field */}
              <div>
                <label className="font-label-md text-xs sm:text-sm text-[#111c2d] block mb-1.5 font-bold" htmlFor="instructions">
                  Instrucciones para el domiciliario o portería
                </label>
                <div className="relative">
                  <input
                    className="w-full bg-[#f0f3ff] rounded-xl px-4 py-2.5 font-body-md text-xs sm:text-sm text-[#111c2d] placeholder:text-[#5c403c] focus:outline-none focus:bg-[#ffffff] border border-[#d8e3fb] focus:border-[#dc2626] transition-colors"
                    id="instructions"
                    type="text"
                    value={instructions}
                    onChange={(e) => setInstructions(e.target.value)}
                  />
                  <span className="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[#5c403c]">
                    badge
                  </span>
                </div>
              </div>
            </div>

            {/* SECTION B: Payment Method */}
            <div className="bg-[#ffffff] rounded-2xl p-5 sm:p-6 shadow-sm border border-[#e7eeff]">
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-2">
                  <span className="material-symbols-outlined text-[#dc2626] text-2xl">
                    account_balance_wallet
                  </span>
                  <h2 className="font-headline-sm text-lg sm:text-xl text-[#111c2d] font-bold">
                    Método de Pago
                  </h2>
                </div>
                <span className="font-label-sm text-xs text-[#00682e] flex items-center gap-1 font-bold">
                  <span className="material-symbols-outlined text-sm">lock</span> Transacciones Protegidas
                </span>
              </div>

              {/* Payment Switcher Tabs */}
              <div className="flex rounded-full bg-[#f0f3ff] p-1 mb-5 border border-[#d8e3fb]">
                <button
                  type="button"
                  onClick={() => setPaymentTab('online')}
                  className={`flex-1 py-2 px-3 rounded-full font-label-md text-xs sm:text-sm font-bold transition-all text-center cursor-pointer ${
                    paymentTab === 'online'
                      ? 'bg-[#dc2626] text-white shadow-sm'
                      : 'text-[#5c403c] hover:text-[#111c2d]'
                  }`}
                >
                  Pago Digital en Línea
                </button>
                <button
                  type="button"
                  onClick={() => setPaymentTab('cash')}
                  className={`flex-1 py-2 px-3 rounded-full font-label-md text-xs sm:text-sm font-bold transition-all text-center cursor-pointer ${
                    paymentTab === 'cash'
                      ? 'bg-[#dc2626] text-white shadow-sm'
                      : 'text-[#5c403c] hover:text-[#111c2d]'
                  }`}
                >
                  Pago Contra Entrega (Neiva)
                </button>
              </div>

              {/* OPTION 1: Pago Digital en Línea */}
              {paymentTab === 'online' && (
                <div className="flex flex-col gap-4">
                  {/* Billeteras Móviles */}
                  <div className="p-4 bg-[#f0f3ff] rounded-xl border border-[#d8e3fb]">
                    <span className="font-label-sm text-xs text-[#5c403c] uppercase tracking-wider block mb-3 font-bold">
                      Transferencia Rápida / Billeteras Móviles
                    </span>
                    <div className="grid grid-cols-3 gap-3">
                      {/* Nequi */}
                      <label
                        onClick={() => setOnlineMethod('nequi')}
                        className={`flex flex-col items-center justify-center p-3 rounded-xl cursor-pointer transition-all border shadow-xs ${
                          onlineMethod === 'nequi'
                            ? 'bg-white border-[#3c004d] ring-2 ring-[#3c004d]/20 scale-102'
                            : 'bg-white border-transparent hover:border-[#3c004d]/40'
                        }`}
                      >
                        <input
                          checked={onlineMethod === 'nequi'}
                          onChange={() => setOnlineMethod('nequi')}
                          className="sr-only"
                          name="online_option"
                          type="radio"
                        />
                        <div className="w-10 h-10 rounded-full bg-[#3c004d] text-white flex items-center justify-center font-bold text-sm mb-1 shadow-sm">
                          N
                        </div>
                        <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">Nequi</span>
                        <span className="font-label-sm text-[11px] text-[#5c403c]">Al instante</span>
                      </label>

                      {/* DaviPlata */}
                      <label
                        onClick={() => setOnlineMethod('daviplata')}
                        className={`flex flex-col items-center justify-center p-3 rounded-xl cursor-pointer transition-all border shadow-xs ${
                          onlineMethod === 'daviplata'
                            ? 'bg-white border-[#e31837] ring-2 ring-[#e31837]/20 scale-102'
                            : 'bg-white border-transparent hover:border-[#e31837]/40'
                        }`}
                      >
                        <input
                          checked={onlineMethod === 'daviplata'}
                          onChange={() => setOnlineMethod('daviplata')}
                          className="sr-only"
                          name="online_option"
                          type="radio"
                        />
                        <div className="w-10 h-10 rounded-full bg-[#e31837] text-white flex items-center justify-center font-bold text-sm mb-1 shadow-sm">
                          D
                        </div>
                        <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">DaviPlata</span>
                        <span className="font-label-sm text-[11px] text-[#5c403c]">Sin comisiones</span>
                      </label>

                      {/* PSE */}
                      <label
                        onClick={() => setOnlineMethod('pse')}
                        className={`flex flex-col items-center justify-center p-3 rounded-xl cursor-pointer transition-all border shadow-xs ${
                          onlineMethod === 'pse'
                            ? 'bg-white border-[#004b87] ring-2 ring-[#004b87]/20 scale-102'
                            : 'bg-white border-transparent hover:border-[#004b87]/40'
                        }`}
                      >
                        <input
                          checked={onlineMethod === 'pse'}
                          onChange={() => setOnlineMethod('pse')}
                          className="sr-only"
                          name="online_option"
                          type="radio"
                        />
                        <div className="w-10 h-10 rounded-full bg-[#004b87] text-white flex items-center justify-center font-bold text-xs mb-1 shadow-sm">
                          PSE
                        </div>
                        <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">Bancolombia</span>
                        <span className="font-label-sm text-[11px] text-[#5c403c]">Débito cuenta</span>
                      </label>
                    </div>
                  </div>

                  {/* Credit / Debit Card Fields */}
                  <div className="p-4 bg-[#f0f3ff] rounded-xl flex flex-col gap-3 border border-[#d8e3fb]">
                    <div className="flex items-center justify-between">
                      <span className="font-label-sm text-xs text-[#5c403c] uppercase tracking-wider font-bold">
                        O paga con Tarjeta de Crédito o Débito
                      </span>
                      <div className="flex items-center gap-1.5">
                        <span className="font-label-sm text-xs bg-white px-2 py-0.5 rounded font-bold text-[#111c2d] border border-[#d8e3fb]">
                          Visa
                        </span>
                        <span className="font-label-sm text-xs bg-white px-2 py-0.5 rounded font-bold text-[#111c2d] border border-[#d8e3fb]">
                          Mastercard
                        </span>
                      </div>
                    </div>

                    <div>
                      <label className="font-label-sm text-xs text-[#111c2d] font-semibold mb-1 block">
                        Número de la tarjeta
                      </label>
                      <div className="relative">
                        <input
                          className="w-full bg-white rounded-xl px-4 py-2 font-body-md text-xs sm:text-sm text-[#111c2d] placeholder:text-[#5c403c] focus:outline-none border border-[#d8e3fb] focus:border-[#dc2626]"
                          placeholder="4520 0000 0000 0000"
                          type="text"
                          value={cardNumber}
                          onChange={(e) => setCardNumber(e.target.value)}
                        />
                        <span className="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[#5c403c]">
                          credit_card
                        </span>
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="font-label-sm text-xs text-[#111c2d] font-semibold mb-1 block">
                          Fecha Vencimiento
                        </label>
                        <input
                          className="w-full bg-white rounded-xl px-4 py-2 font-body-md text-xs sm:text-sm text-[#111c2d] placeholder:text-[#5c403c] focus:outline-none border border-[#d8e3fb] focus:border-[#dc2626]"
                          placeholder="MM / AA"
                          type="text"
                          value={cardExpiry}
                          onChange={(e) => setCardExpiry(e.target.value)}
                        />
                      </div>
                      <div>
                        <label className="font-label-sm text-xs text-[#111c2d] font-semibold mb-1 block">
                          Código CVV
                        </label>
                        <div className="relative">
                          <input
                            className="w-full bg-white rounded-xl px-4 py-2 font-body-md text-xs sm:text-sm text-[#111c2d] placeholder:text-[#5c403c] focus:outline-none border border-[#d8e3fb] focus:border-[#dc2626]"
                            maxLength={4}
                            placeholder="123"
                            type="password"
                            value={cardCvv}
                            onChange={(e) => setCardCvv(e.target.value)}
                          />
                          <span className="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[#5c403c] text-lg">
                            help
                          </span>
                        </div>
                      </div>
                    </div>

                    <div>
                      <label className="font-label-sm text-xs text-[#111c2d] font-semibold mb-1 block">
                        Nombre impreso en la tarjeta
                      </label>
                      <input
                        className="w-full bg-white rounded-xl px-4 py-2 font-body-md text-xs sm:text-sm text-[#111c2d] placeholder:text-[#5c403c] focus:outline-none border border-[#d8e3fb] focus:border-[#dc2626]"
                        placeholder="Mateo Rodríguez"
                        type="text"
                        value={cardName}
                        onChange={(e) => setCardName(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              )}

              {/* OPTION 2: Pago Contra Entrega */}
              {paymentTab === 'cash' && (
                <div className="flex flex-col gap-4">
                  <div className="p-4 bg-[#ffddb8]/30 rounded-xl border border-[#ffddb8]">
                    <span className="font-label-md text-xs sm:text-sm text-[#653e00] font-bold block mb-1">
                      Modalidades de Pago al Recibir en Neiva
                    </span>
                    <p className="font-body-sm text-xs text-[#5c403c] mb-3">
                      Paga en el momento en que nuestro repartidor te entregue tu pedido caliente en puerta.
                    </p>

                    {/* Option A: Cash */}
                    <div
                      onClick={() => setCashSubMethod('efectivo')}
                      className={`bg-white rounded-xl p-4 mb-2.5 shadow-xs border cursor-pointer transition-all ${
                        cashSubMethod === 'efectivo'
                          ? 'border-[#dc2626] ring-2 ring-[#dc2626]/10'
                          : 'border-[#d8e3fb]'
                      }`}
                    >
                      <label className="flex items-start gap-3 cursor-pointer">
                        <input
                          checked={cashSubMethod === 'efectivo'}
                          onChange={() => setCashSubMethod('efectivo')}
                          className="accent-[#dc2626] mt-1 cursor-pointer"
                          name="contra_entrega_sub"
                          type="radio"
                        />
                        <div className="flex-1">
                          <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold block">
                            Efectivo contra entrega
                          </span>
                          <span className="font-body-sm text-xs text-[#5c403c] block mb-2">
                            Indícanos con cuánto billete pagarás para llevarte el cambio exacto sin retrasos.
                          </span>
                          <div className="flex flex-wrap items-center gap-2">
                            <button
                              type="button"
                              onClick={(e) => {
                                e.preventDefault();
                                setCashBillAmount('50k');
                              }}
                              className={`px-3 py-1 rounded-full font-label-sm text-xs transition-colors cursor-pointer ${
                                cashBillAmount === '50k'
                                  ? 'bg-[#dc2626] text-white font-bold'
                                  : 'bg-[#f0f3ff] text-[#111c2d] font-semibold hover:bg-[#e7eeff]'
                              }`}
                            >
                              $50.000 COP
                            </button>
                            <button
                              type="button"
                              onClick={(e) => {
                                e.preventDefault();
                                setCashBillAmount('100k');
                              }}
                              className={`px-3 py-1 rounded-full font-label-sm text-xs transition-colors cursor-pointer ${
                                cashBillAmount === '100k'
                                  ? 'bg-[#dc2626] text-white font-bold'
                                  : 'bg-[#f0f3ff] text-[#111c2d] font-semibold hover:bg-[#e7eeff]'
                              }`}
                            >
                              $100.000 COP
                            </button>
                            <button
                              type="button"
                              onClick={(e) => {
                                e.preventDefault();
                                setCashBillAmount('exacto');
                              }}
                              className={`px-3 py-1 rounded-full font-label-sm text-xs transition-colors cursor-pointer ${
                                cashBillAmount === 'exacto'
                                  ? 'bg-[#dc2626] text-white font-bold'
                                  : 'bg-[#f0f3ff] text-[#111c2d] font-semibold hover:bg-[#e7eeff]'
                              }`}
                            >
                              Monto exacto ($40.400)
                            </button>
                          </div>
                        </div>
                      </label>
                    </div>

                    {/* Option B: Dataphone */}
                    <div
                      onClick={() => setCashSubMethod('datafono')}
                      className={`bg-white rounded-xl p-4 shadow-xs border cursor-pointer transition-all ${
                        cashSubMethod === 'datafono'
                          ? 'border-[#dc2626] ring-2 ring-[#dc2626]/10'
                          : 'border-[#d8e3fb]'
                      }`}
                    >
                      <label className="flex items-start gap-3 cursor-pointer">
                        <input
                          checked={cashSubMethod === 'datafono'}
                          onChange={() => setCashSubMethod('datafono')}
                          className="accent-[#dc2626] mt-1 cursor-pointer"
                          name="contra_entrega_sub"
                          type="radio"
                        />
                        <div className="flex-1">
                          <div className="flex items-center gap-1.5">
                            <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">
                              Datáfono inalámbrico en sitio
                            </span>
                            <span className="material-symbols-outlined text-[#00682e] text-lg">
                              point_of_sale
                            </span>
                          </div>
                          <span className="font-body-sm text-xs text-[#5c403c] block mt-0.5">
                            El domiciliario llevará terminal móvil Redeban para tarjeta física débito o crédito.
                          </span>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>
              )}
            </div>

            {/* SECTION C: Contact Info & WhatsApp Notifications */}
            <div className="bg-[#ffffff] rounded-2xl p-5 sm:p-6 shadow-sm border border-[#e7eeff]">
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-2">
                  <span className="material-symbols-outlined text-[#dc2626] text-2xl">person_pin</span>
                  <h2 className="font-headline-sm text-lg sm:text-xl text-[#111c2d] font-bold">
                    Datos de Notificación
                  </h2>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="font-label-sm text-xs text-[#5c403c] font-semibold mb-1 block">
                    Nombre receptor
                  </label>
                  <input
                    className="w-full bg-[#f0f3ff] rounded-xl px-4 py-2.5 font-body-md text-xs sm:text-sm text-[#111c2d] font-semibold focus:outline-none border border-[#d8e3fb]"
                    type="text"
                    value={receiverName}
                    onChange={(e) => setReceiverName(e.target.value)}
                  />
                </div>
                <div>
                  <label className="font-label-sm text-xs text-[#5c403c] font-semibold mb-1 block">
                    Celular de contacto Huila
                  </label>
                  <input
                    className="w-full bg-[#f0f3ff] rounded-xl px-4 py-2.5 font-body-md text-xs sm:text-sm text-[#111c2d] font-semibold focus:outline-none border border-[#d8e3fb]"
                    type="tel"
                    value={receiverPhone}
                    onChange={(e) => setReceiverPhone(e.target.value)}
                  />
                </div>
              </div>

              {/* WhatsApp Status Switch */}
              <div className="flex items-center justify-between bg-[#00682e]/10 p-3 sm:p-4 rounded-xl border border-[#00682e]/20">
                <div className="flex items-center gap-3">
                  <span className="material-symbols-outlined text-[#00682e] text-2xl">chat</span>
                  <div>
                    <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold block">
                      Recibir actualizaciones por WhatsApp
                    </span>
                    <span className="font-body-sm text-xs text-[#5c403c]">
                      Alertas cuando salga de parrilla y cuando el domiciliario llegue
                    </span>
                  </div>
                </div>
                <label className="relative inline-flex items-center cursor-pointer">
                  <input
                    checked={whatsappNotify}
                    onChange={(e) => setWhatsappNotify(e.target.checked)}
                    className="sr-only peer"
                    type="checkbox"
                  />
                  <div className="w-11 h-6 bg-[#d8e3fb] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00682e]"></div>
                </label>
              </div>
            </div>
          </div>

          {/* RIGHT COLUMN: Sticky Order Summary (35%) */}
          <div className="w-full lg:w-[35%] flex flex-col gap-4 sticky top-24">
            {/* Summary Container Card */}
            <div className="bg-[#ffffff] rounded-2xl p-5 sm:p-6 shadow-sm border border-[#e7eeff]">
              {/* Restaurant Banner */}
              <div className="flex items-center gap-3 pb-4 border-b border-[#f0f3ff]">
                <img
                  className="w-14 h-14 rounded-xl object-cover shadow-xs border border-[#d8e3fb]"
                  alt="La Estación Burger cocina"
                  src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=200&auto=format&fit=crop&q=80"
                />
                <div className="flex flex-col">
                  <div className="flex items-center gap-1">
                    <h3 className="font-headline-sm text-base text-[#111c2d] font-bold">
                      La Estación Burger
                    </h3>
                    <span className="material-symbols-outlined text-[#00682e] text-base" title="Verificado Huila">
                      verified
                    </span>
                  </div>
                  <span className="font-body-sm text-xs text-[#5c403c]">Cra 12 #10-34, Centro Neiva</span>
                  <span className="font-label-sm text-[11px] text-[#855300] font-bold flex items-center gap-1 mt-0.5">
                    <span className="material-symbols-outlined text-xs">store</span> Local Huilense Autorizado
                  </span>
                </div>
              </div>

              {/* Product Line Items */}
              <div className="flex flex-col gap-3 py-3 bg-[#f0f3ff]/60 rounded-xl p-3.5 my-3 border border-[#d8e3fb]">
                {/* Item 1: Burger Monumental */}
                <div className="flex flex-col gap-1">
                  <div className="flex items-start justify-between">
                    <div className="flex items-start gap-2">
                      <span className="font-label-md text-xs sm:text-sm text-[#dc2626] font-bold">1x</span>
                      <div>
                        <span className="font-label-lg text-xs sm:text-sm text-[#111c2d] font-bold">
                          Burger Monumental 200g
                        </span>
                        <span className="block font-body-sm text-[11px] text-[#5c403c]">
                          Término: Tres Cuartos (3/4) · Brioche
                        </span>
                      </div>
                    </div>
                    <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">$24.900</span>
                  </div>

                  {/* Selected Toppings */}
                  <div className="ml-5 pl-1 flex flex-col gap-0.5 border-l-2 border-[#d8e3fb]">
                    <div className="flex justify-between font-body-sm text-[11px] text-[#5c403c]">
                      <span>+ Tocineta Ahumada Crunch</span>
                      <span>+$3.500</span>
                    </div>
                    <div className="flex justify-between font-body-sm text-[11px] text-[#5c403c]">
                      <span>+ Huevo Frito Campesino</span>
                      <span>+$2.000</span>
                    </div>
                    <div className="flex justify-between font-body-sm text-[11px] text-[#5c403c]">
                      <span>+ Queso Campesino Asado</span>
                      <span>+$3.000</span>
                    </div>
                    <div className="flex justify-between font-label-sm text-[11px] text-[#dc2626] font-bold mt-1">
                      <span>Subtotal Ítem</span>
                      <span>$33.400 COP</span>
                    </div>
                  </div>
                </div>

                <div className="h-px bg-[#d8e3fb]"></div>

                {/* Item 2: Papas Rústicas */}
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-2">
                    <span className="font-label-md text-xs sm:text-sm text-[#dc2626] font-bold">1x</span>
                    <div>
                      <span className="font-label-lg text-xs sm:text-sm text-[#111c2d] font-bold">
                        Papas Rústicas Romero
                      </span>
                      <span className="block font-body-sm text-[11px] text-[#5c403c]">
                        Con salsa tártara artesanal
                      </span>
                    </div>
                  </div>
                  <span className="font-label-md text-xs sm:text-sm text-[#111c2d] font-bold">
                    $7.500 COP
                  </span>
                </div>
              </div>

              {/* Promo Code Card */}
              {appliedCoupon ? (
                <div className="bg-[#f0f3ff] p-3 rounded-xl mb-3 flex items-center justify-between border border-[#d8e3fb]">
                  <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-[#00682e] text-xl">loyalty</span>
                    <div>
                      <span className="font-label-sm text-[11px] font-bold text-[#00682e] block">
                        CUPÓN APLICADO
                      </span>
                      <span className="font-label-md text-xs sm:text-sm font-mono text-[#111c2d] font-bold">
                        ANTOJONEIVA5K
                      </span>
                    </div>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <span className="font-label-md text-xs sm:text-sm text-[#00682e] font-bold">
                      -$5.000 COP
                    </span>
                    <button
                      type="button"
                      onClick={onRemoveCoupon}
                      className="text-[#5c403c] hover:text-[#ba1a1a] transition-colors p-1 cursor-pointer"
                      title="Eliminar cupón"
                    >
                      <span className="material-symbols-outlined text-base">close</span>
                    </button>
                  </div>
                </div>
              ) : (
                <div className="bg-[#f0f3ff] p-3 rounded-xl mb-3 border border-[#d8e3fb]">
                  {showCouponInput ? (
                    <div className="flex gap-2">
                      <input
                        type="text"
                        placeholder="Ej: ANTOJONEIVA5K"
                        value={couponInput}
                        onChange={(e) => setCouponInput(e.target.value.toUpperCase())}
                        className="flex-1 bg-white px-3 py-1.5 rounded-lg text-xs font-mono font-bold uppercase border border-[#d8e3fb] focus:outline-none focus:border-[#dc2626]"
                      />
                      <button
                        type="button"
                        onClick={() => {
                          if (couponInput) onApplyCoupon(couponInput);
                        }}
                        className="bg-[#dc2626] text-white px-3 py-1.5 rounded-lg text-xs font-bold"
                      >
                        Aplicar
                      </button>
                    </div>
                  ) : (
                    <button
                      type="button"
                      onClick={() => setShowCouponInput(true)}
                      className="text-xs text-[#dc2626] font-bold flex items-center gap-1 hover:underline cursor-pointer"
                    >
                      <span className="material-symbols-outlined text-base">add</span>
                      ¿Tienes un cupón de descuento?
                    </button>
                  )}
                </div>
              )}

              {/* Math Breakdown */}
              <div className="flex flex-col gap-1 py-1 mb-3">
                <div className="flex justify-between font-body-md text-xs sm:text-sm text-[#5c403c]">
                  <span>Subtotal Productos</span>
                  <span>${subtotalProducts.toLocaleString('es-CO')} COP</span>
                </div>
                <div className="flex justify-between font-body-md text-xs sm:text-sm text-[#5c403c]">
                  <span>Toppings & Adiciones</span>
                  <span className="text-[#111c2d] font-semibold">
                    +${toppingsAdiciones.toLocaleString('es-CO')} COP
                  </span>
                </div>
                {appliedCoupon && (
                  <div className="flex justify-between font-body-md text-xs sm:text-sm text-[#00682e] font-medium">
                    <span>Descuento Promocional Neiva</span>
                    <span>-$5.000 COP</span>
                  </div>
                )}
                <div className="flex justify-between font-body-md text-xs sm:text-sm text-[#5c403c]">
                  <span>Tarifa Domicilio (Comuna 4 Centro)</span>
                  <span>${deliveryFee.toLocaleString('es-CO')} COP</span>
                </div>
                <div className="flex justify-between font-body-sm text-[11px] text-[#5c403c]">
                  <span className="flex items-center gap-1">
                    Tasa Apoyo Gastronómico Huila
                    <span className="material-symbols-outlined text-xs" title="Apoyo a la red de repartidores locales">
                      info
                    </span>
                  </span>
                  <span>${tasaApoyo.toLocaleString('es-CO')} COP</span>
                </div>

                <div className="h-0.5 bg-[#d8e3fb] my-2"></div>

                {/* Total Price Row */}
                <div className="flex justify-between items-baseline pt-1">
                  <div>
                    <span className="font-headline-sm text-base sm:text-lg text-[#111c2d] font-extrabold block">
                      Total a Pagar
                    </span>
                    <span className="font-label-sm text-[11px] text-[#5c403c]">
                      Impuestos y entrega incluidos
                    </span>
                  </div>
                  <span className="font-display-hero-mobile text-2xl sm:text-3xl font-extrabold text-[#dc2626] tracking-tight">
                    ${totalToPay.toLocaleString('es-CO')}{' '}
                    <span className="text-xs font-bold text-[#5c403c]">COP</span>
                  </span>
                </div>
              </div>

              {/* Primary CTA Button */}
              <button
                type="button"
                onClick={onConfirmOrder}
                className="w-full bg-[#dc2626] hover:bg-[#b70011] text-white font-label-lg text-sm sm:text-base py-3.5 rounded-full shadow-md hover:shadow-xl hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 uppercase tracking-wide font-black cursor-pointer"
              >
                <span className="material-symbols-outlined text-xl">lock</span>
                Confirmar Pedido y Pagar ${totalToPay.toLocaleString('es-CO')} COP
              </button>

              {/* Trust Badges */}
              <div className="mt-4 pt-2 flex flex-col gap-1.5 text-center">
                <div className="flex items-center justify-center gap-1.5 text-[#5c403c] font-label-sm text-[11px]">
                  <span className="material-symbols-outlined text-[#00682e] text-base">verified_user</span>
                  <span>Pago 100% protegido con cifrado SSL bancario</span>
                </div>
                <div className="flex items-center justify-center gap-1.5 text-[#5c403c] font-label-sm text-[11px]">
                  <span className="material-symbols-outlined text-[#855300] text-base">timer</span>
                  <span>Cancelación gratuita antes de entrar a parrilla</span>
                </div>
                <div className="flex items-center justify-center gap-1.5 text-[#5c403c] font-label-sm text-[11px]">
                  <span className="material-symbols-outlined text-[#dc2626] text-base">support_agent</span>
                  <span>Soporte directo Neiva vía WhatsApp</span>
                </div>
              </div>
            </div>

            {/* AntojoPuntos banner */}
            <div className="bg-gradient-to-r from-[#fea619] to-[#ffddb8] p-4 rounded-2xl shadow-sm flex items-center gap-3 border border-[#fea619]/40">
              <div className="w-10 h-10 rounded-full bg-white/80 flex items-center justify-center text-[#855300] shrink-0 shadow-sm">
                <span className="material-symbols-outlined text-2xl">celebration</span>
              </div>
              <div className="flex flex-col text-left">
                <span className="font-label-md text-xs sm:text-sm font-black text-[#684000]">
                  ¡Tu pedido acumula 40 AntojoPuntos!
                </span>
                <span className="font-body-sm text-xs text-[#684000]/90">
                  Canjeables por bebidas o empanadas en tu próxima orden
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Address Switcher Modal */}
      {showAddressModal && (
        <div className="fixed inset-0 z-50 bg-[#263143]/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-[#d8e3fb]">
            <h3 className="font-headline-sm text-lg font-bold text-[#111c2d] mb-4">
              Seleccionar Dirección en Neiva
            </h3>
            <div className="flex flex-col gap-2.5 mb-4">
              {[
                { addr: 'Cra. 5 #18-42, Centro', comuna: 'Comuna 4', desc: 'Edif. Los Comuneros, Apto 402' },
                { addr: 'Calle 21 #6-15, Buganviles', comuna: 'Comuna 5', desc: 'Casa familiar 2 pisos' },
                { addr: 'Cra 15 #34-10, Las Granjas', comuna: 'Comuna 2', desc: 'Conjunto Portal del Norte' },
              ].map((item, idx) => (
                <button
                  key={idx}
                  type="button"
                  onClick={() => {
                    setCurrentAddress(item.addr);
                    setCurrentComuna(item.comuna);
                    setShowAddressModal(false);
                  }}
                  className={`p-3 rounded-xl text-left border flex flex-col gap-0.5 transition-all cursor-pointer ${
                    currentAddress === item.addr
                      ? 'border-[#dc2626] bg-[#dc2626]/5'
                      : 'border-[#d8e3fb] hover:bg-[#f0f3ff]'
                  }`}
                >
                  <div className="flex justify-between items-center">
                    <span className="font-bold text-sm text-[#111c2d]">{item.addr}</span>
                    <span className="text-[10px] bg-[#ffddb8] px-2 py-0.5 rounded-full font-bold text-[#2a1700]">
                      {item.comuna}
                    </span>
                  </div>
                  <span className="text-xs text-[#5c403c]">{item.desc}</span>
                </button>
              ))}
            </div>
            <button
              type="button"
              onClick={() => setShowAddressModal(false)}
              className="w-full py-2.5 bg-[#f0f3ff] hover:bg-[#e7eeff] text-[#111c2d] font-bold rounded-xl text-xs transition-colors"
            >
              Cerrar
            </button>
          </div>
        </div>
      )}
    </div>
  );
};
