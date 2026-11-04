package com.antojovirtual.handlers;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.config.JwtUtil;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

/**
 * OrdersHandler - API de Creacion y Consulta de Pedidos con Factura DIAN (100% Java)
 * Soporta: POST /api/orders | GET /api/orders?my_orders=1 | ?code=#NV-... | ?id=...
 */
public class OrdersHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (HttpUtil.handleCorsPreflight(ex)) return;

        String method = ex.getRequestMethod().toUpperCase();
        try {
            if ("POST".equals(method)) {
                handleCreateOrder(ex);
            } else {
                handleGetOrders(ex);
            }
        } catch (Exception e) {
            e.printStackTrace();
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "Error en el servidor Java: " + e.getMessage());
            HttpUtil.sendJson(ex, 500, err);
        }
    }

    private void handleCreateOrder(HttpExchange ex) throws Exception {
        String bodyRaw = HttpUtil.readBody(ex);
        Map<String, Object> input = JsonUtil.parseObject(bodyRaw);
        Map<String, Object> authUser = JwtUtil.getAuthUser(ex);

        Integer userId = authUser != null ? JsonUtil.getInt(authUser, "id", 0) : null;
        if (userId != null && userId <= 0) userId = null;

        String customerName = JsonUtil.getString(input, "customer_name", authUser != null ? JsonUtil.getString(authUser, "name", "Cliente Neiva") : "Cliente Neiva").trim();
        String customerPhone = JsonUtil.getString(input, "customer_phone", authUser != null ? JsonUtil.getString(authUser, "phone", "+57 300 000 0000") : "+57 300 000 0000").trim();
        String customerEmail = JsonUtil.getString(input, "customer_email", authUser != null ? JsonUtil.getString(authUser, "email", "cliente@antojo.com") : "cliente@antojo.com").trim();
        String address = JsonUtil.getString(input, "delivery_address", authUser != null ? JsonUtil.getString(authUser, "address", "Cra 5 #10-00, Neiva") : "Cra 5 #10-00, Neiva").trim();
        String comuna = JsonUtil.getString(input, "comuna", authUser != null ? JsonUtil.getString(authUser, "comuna", "Comuna 4 - Centro") : "Comuna 4 - Centro").trim();
        String timing = JsonUtil.getString(input, "delivery_timing", "immediate");
        if (!"scheduled".equals(timing)) timing = "immediate";
        String instructions = JsonUtil.getString(input, "instructions", "").trim();
        String paymentMethod = JsonUtil.getString(input, "payment_method", "nequi").trim();
        String cashChangeFor = JsonUtil.getString(input, "cash_change_for", "").trim();
        String couponCode = JsonUtil.getString(input, "coupon_code", "").trim().toUpperCase();
        List<Object> items = JsonUtil.getList(input, "items");

        if (items.isEmpty()) {
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "El carrito está vacío. Agrega productos para ordenar.");
            HttpUtil.sendJson(ex, 400, err);
            return;
        }

        int subtotal = 0;
        for (Object itemObj : items) {
            if (itemObj instanceof Map) {
                @SuppressWarnings("unchecked")
                Map<String, Object> item = (Map<String, Object>) itemObj;
                int base = JsonUtil.getInt(item, "basePrice", JsonUtil.getInt(item, "price", 0));
                int qty = Math.max(1, JsonUtil.getInt(item, "quantity", JsonUtil.getInt(item, "qty", 1)));
                int toppingsCost = 0;
                List<Object> toppings = JsonUtil.getList(item, "toppings");
                for (Object topObj : toppings) {
                    if (topObj instanceof Map) {
                        @SuppressWarnings("unchecked")
                        Map<String, Object> top = (Map<String, Object>) topObj;
                        int topPrice = JsonUtil.getInt(top, "price", 0);
                        int topCount = Math.max(1, JsonUtil.getInt(top, "count", 1));
                        toppingsCost += topPrice * topCount;
                    }
                }
                subtotal += (base + toppingsCost) * qty;
            }
        }

        int discount = 0;
        if (!couponCode.isEmpty()) {
            Map<String, Object> coupon = DatabaseManager.queryOne(
                    "SELECT * FROM `coupons` WHERE `code` = ? AND `is_active` = 1 LIMIT 1",
                    Collections.singletonList(couponCode)
            );
            if (coupon != null && subtotal >= JsonUtil.getInt(coupon, "min_purchase", 0)) {
                discount = JsonUtil.getInt(coupon, "discount_amount", 0);
                DatabaseManager.executeUpdate(
                        "UPDATE `coupons` SET `used_count` = `used_count` + 1 WHERE `id` = ?",
                        Collections.singletonList(JsonUtil.getInt(coupon, "id", 0))
                );
            }
        }

        int deliveryFee = 3500;
        int tip = 1000;
        int total = Math.max(0, subtotal - discount + deliveryFee + tip);

        Random rnd = new Random();
        String orderCode = "#NV-" + (1000 + rnd.nextInt(9000));

        Map<String, Object> riderRow = DatabaseManager.queryOne("SELECT id FROM `riders` WHERE `is_active` = 1 ORDER BY RAND() LIMIT 1");
        Integer riderId = riderRow != null ? JsonUtil.getInt(riderRow, "id", 1) : 1;

        Map<String, Object> paymentData = JsonUtil.getMap(input, "payment_data");
        boolean isCash = "cash_efectivo".equals(paymentMethod) || "cash_datafono".equals(paymentMethod);
        String paymentStatus = isCash ? "pending" : "paid";
        int taxImpoconsumo = (int) Math.round((subtotal - discount) * 0.08);

        long orderId = DatabaseManager.insertAndGetId(
                "INSERT INTO `orders` (`user_id`, `order_code`, `customer_name`, `customer_phone`, `customer_email`, " +
                "`delivery_address`, `comuna`, `delivery_timing`, `instructions`, `payment_method`, `payment_status`, " +
                "`cash_change_for`, `subtotal`, `discount`, `delivery_fee`, `tip`, `tax_impoconsumo`, `total`, `status`, `rider_id`) " +
                "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'recibido', ?)",
                Arrays.asList(
                        userId, orderCode, customerName, customerPhone, customerEmail,
                        address, comuna, timing, instructions, paymentMethod, paymentStatus,
                        cashChangeFor, subtotal, discount, deliveryFee, tip, taxImpoconsumo, total, riderId
                )
        );

        String mainRestaurant = "La Estación Burger";
        for (Object itemObj : items) {
            if (itemObj instanceof Map) {
                @SuppressWarnings("unchecked")
                Map<String, Object> item = (Map<String, Object>) itemObj;
                int base = JsonUtil.getInt(item, "basePrice", JsonUtil.getInt(item, "price", 0));
                int qty = Math.max(1, JsonUtil.getInt(item, "quantity", JsonUtil.getInt(item, "qty", 1)));
                List<Object> toppings = JsonUtil.getList(item, "toppings");
                List<Object> sauces = JsonUtil.getList(item, "sauces");

                int toppingsCost = 0;
                for (Object topObj : toppings) {
                    if (topObj instanceof Map) {
                        @SuppressWarnings("unchecked")
                        Map<String, Object> top = (Map<String, Object>) topObj;
                        toppingsCost += JsonUtil.getInt(top, "price", 0) * Math.max(1, JsonUtil.getInt(top, "count", 1));
                    }
                }
                int itemSubtotal = (base + toppingsCost) * qty;
                String restName = JsonUtil.getString(item, "restaurant", "La Estación Burger");
                if (!restName.isEmpty()) mainRestaurant = restName;

                Integer menuItemId = JsonUtil.getInt(item, "id", 0);
                if (menuItemId <= 0) menuItemId = null;

                long orderItemId = DatabaseManager.insertAndGetId(
                        "INSERT INTO `order_items` (`order_id`, `menu_item_id`, `item_name`, `restaurant_name`, `base_price`, `quantity`, `doneness`, `bread`, `toppings_json`, `sauces_json`, `notes`, `subtotal`) " +
                        "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        Arrays.asList(
                                orderId, menuItemId, JsonUtil.getString(item, "name", "Producto Antojo"), restName,
                                base, qty, JsonUtil.getString(item, "doneness", null), JsonUtil.getString(item, "bread", null),
                                JsonUtil.toJson(toppings), JsonUtil.toJson(sauces), JsonUtil.getString(item, "notes", ""), itemSubtotal
                        )
                );

                // Registrar cada topping en la tabla relacional order_item_toppings (Liquibase v3.0)
                if (orderItemId > 0 && !toppings.isEmpty()) {
                    for (Object topObj : toppings) {
                        if (topObj instanceof Map) {
                            @SuppressWarnings("unchecked")
                            Map<String, Object> top = (Map<String, Object>) topObj;
                            String topName = JsonUtil.getString(top, "name", "Topping Extra");
                            int topPrice = JsonUtil.getInt(top, "price", 0);
                            int topCount = Math.max(1, JsonUtil.getInt(top, "count", 1));
                            int topSub = topPrice * topCount;
                            try {
                                DatabaseManager.executeUpdate(
                                        "INSERT INTO `order_item_toppings` (`order_item_id`, `topping_name`, `topping_group`, `unit_price`, `quantity`, `subtotal`) VALUES (?, ?, 'extra', ?, ?, ?)",
                                        Arrays.asList(orderItemId, topName, topPrice, topCount, topSub)
                                );
                            } catch (Exception ignored) {}
                        }
                    }
                }
            }
        }

        String dateBasic = LocalDate.now().format(DateTimeFormatter.BASIC_ISO_DATE);
        String txRef = "PAY-NV-" + dateBasic + "-" + (1000 + rnd.nextInt(9000));
        String authCode = "AUT-" + (100000 + rnd.nextInt(900000));
        String bankName = JsonUtil.getString(paymentData, "bank_name", "");
        Integer bankId = JsonUtil.getInt(paymentData, "bank_id", 0);
        if (bankId <= 0) bankId = null;
        String cardBrand = JsonUtil.getString(paymentData, "card_brand", null);
        String cardLastFour = JsonUtil.getString(paymentData, "card_last_four", null);
        String cardholderName = JsonUtil.getString(paymentData, "cardholder_name", null);
        int installments = Math.max(1, JsonUtil.getInt(paymentData, "installments", 1));
        String payerDocType = JsonUtil.getString(paymentData, "payer_doc_type", "CC");
        String payerDocNumber = JsonUtil.getString(paymentData, "payer_doc_number", "1075304128");

        if ("pse".equals(paymentMethod) && bankName.isEmpty()) bankName = "Bancolombia S.A.";
        else if ("nequi".equals(paymentMethod)) bankName = "Nequi (Bancolombia)";
        else if ("daviplata".equals(paymentMethod)) bankName = "Daviplata (Davivienda)";
        else if ("cash_efectivo".equals(paymentMethod)) bankName = "Efectivo contra Entrega";
        else if ("cash_datafono".equals(paymentMethod)) bankName = "Datáfono contra Entrega";

        String payStatus = isCash ? "pending" : "approved";
        String paidAt = isCash ? null : LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));

        Map<String, Object> gwRespMap = new LinkedHashMap<>();
        gwRespMap.put("status", payStatus.toUpperCase());
        gwRespMap.put("ref", txRef);
        gwRespMap.put("auth", authCode);
        gwRespMap.put("method", paymentMethod);
        gwRespMap.put("bank", bankName);
        gwRespMap.put("engine", "100% Java");

        long paymentId = DatabaseManager.insertAndGetId(
                "INSERT INTO `payments` (`order_id`, `payment_method`, `transaction_reference`, `gateway`, `amount`, `currency`, " +
                "`status`, `authorization_code`, `bank_id`, `bank_name`, `payer_doc_type`, `payer_doc_number`, `payer_person_type`, " +
                "`payer_email`, `payer_phone`, `card_brand`, `card_last_four`, `cardholder_name`, `installments`, `gateway_response`, `ip_address`, `paid_at`) " +
                "VALUES (?, ?, ?, 'antojo_pay_gateway_java', ?, 'COP', ?, ?, ?, ?, ?, ?, 'natural', ?, ?, ?, ?, ?, ?, ?, '127.0.0.1', ?)",
                Arrays.asList(
                        orderId, paymentMethod, txRef, total, payStatus, isCash ? null : authCode,
                        bankId, bankName.isEmpty() ? null : bankName, payerDocType, payerDocNumber,
                        customerEmail, customerPhone, cardBrand, cardLastFour, cardholderName,
                        installments, JsonUtil.toJson(gwRespMap), paidAt
                )
        );

        String invoiceNumber = "FE-ANTOJO-" + LocalDate.now().getYear() + "-" + String.format("%05d", orderId);
        String cufeCode = JwtUtil.sha384Hex(invoiceNumber + System.currentTimeMillis() + total + customerName);
        String methodLabel = !bankName.isEmpty() ? bankName : ((cardBrand != null ? cardBrand.toUpperCase() : "TARJETA") + " •••• " + (cardLastFour != null ? cardLastFour : "4242"));
        String qrString = "NumFac=" + invoiceNumber + "&FecFac=" + LocalDate.now() + "&NitFac=901458921-4&DocAdq=" + payerDocNumber + "&ValFac=" + total + "&CUFE=" + cufeCode.substring(0, 16);

        DatabaseManager.executeUpdate(
                "INSERT INTO `invoices` (`order_id`, `payment_id`, `invoice_number`, `cufe_code`, `customer_name`, `customer_document`, " +
                "`customer_email`, `customer_phone`, `customer_address`, `subtotal`, `discount`, `tax_impoconsumo`, `delivery_fee`, `tip`, `total`, " +
                "`payment_method_label`, `transaction_reference`, `qr_string`, `status`) " +
                "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'issued')",
                Arrays.asList(
                        orderId, paymentId, invoiceNumber, cufeCode, customerName, payerDocType + " " + payerDocNumber,
                        customerEmail, customerPhone, address + " (" + comuna + ")",
                        subtotal, discount, taxImpoconsumo, deliveryFee, tip, total,
                        methodLabel, txRef, qrString
                )
        );

        String paymentTrackingText = isCash
                ? "Método de pago seleccionado: " + methodLabel + ". Cobro contra entrega en Neiva."
                : "Pago verificado y aprobado vía " + methodLabel + ". Ref: " + txRef + ". Aprobación: " + authCode + ".";

        DatabaseManager.executeUpdate(
                "INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`) VALUES (?, 'recibido', 'Orden y Pago Registrados (Java)', ?)",
                Arrays.asList(orderId, paymentTrackingText + " " + mainRestaurant + " está alistando los ingredientes frescos.")
        );

        Map<String, Object> payObj = new LinkedHashMap<>();
        payObj.put("id", paymentId);
        payObj.put("transaction_reference", txRef);
        payObj.put("authorization_code", authCode);
        payObj.put("payment_method", paymentMethod);
        payObj.put("method_label", methodLabel);
        payObj.put("status", payStatus);

        Map<String, Object> invObj = new LinkedHashMap<>();
        invObj.put("invoice_number", invoiceNumber);
        invObj.put("cufe_code", cufeCode);

        Map<String, Object> resp = new LinkedHashMap<>();
        resp.put("success", true);
        resp.put("status", "recibido");
        resp.put("engine", "100% Java");
        resp.put("message", "¡Pedido creado y confirmado exitosamente en Neiva!");
        resp.put("order_id", orderId);
        resp.put("order_code", orderCode);
        resp.put("user_id", userId);
        resp.put("total", total);
        resp.put("total_formatted", HttpUtil.formatCop(total));
        resp.put("payment_status", paymentStatus);
        resp.put("payment", payObj);
        resp.put("invoice", invObj);

        HttpUtil.sendJson(ex, 200, resp);
    }

    private void handleGetOrders(HttpExchange ex) throws Exception {
        Map<String, String> query = HttpUtil.parseQueryParams(ex);
        Map<String, Object> authUser = JwtUtil.getAuthUser(ex);

        if (query.containsKey("my_orders")) {
            List<Map<String, Object>> orders;
            if (authUser != null) {
                int uid = JsonUtil.getInt(authUser, "id", 0);
                String phone = JsonUtil.getString(authUser, "phone", "");
                orders = DatabaseManager.queryList(
                        "SELECT o.*, COUNT(i.id) AS total_items FROM `orders` o " +
                        "LEFT JOIN `order_items` i ON o.id = i.order_id " +
                        "WHERE o.user_id = ? OR o.customer_phone = ? " +
                        "GROUP BY o.id ORDER BY o.created_at DESC LIMIT 20",
                        Arrays.asList(uid, phone)
                );
            } else {
                orders = DatabaseManager.queryList(
                        "SELECT o.*, COUNT(i.id) AS total_items FROM `orders` o " +
                        "LEFT JOIN `order_items` i ON o.id = i.order_id " +
                        "GROUP BY o.id ORDER BY o.created_at DESC LIMIT 10"
                );
            }

            for (Map<String, Object> ord : orders) {
                ord.put("total_formatted", HttpUtil.formatCop(JsonUtil.getDouble(ord, "total", 0)));
            }

            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("success", true);
            resp.put("engine", "100% Java");
            resp.put("orders", orders);
            HttpUtil.sendJson(ex, 200, resp);
            return;
        }

        String code = query.getOrDefault("code", "").trim();
        String idStr = query.getOrDefault("id", "").trim();

        if (!code.isEmpty() || !idStr.isEmpty()) {
            String param = !code.isEmpty() ? code : idStr;
            String cleanCode = param.startsWith("#") ? param.substring(1) : param;

            Map<String, Object> order = DatabaseManager.queryOne(
                    "SELECT o.*, r.name AS rider_name, r.phone AS rider_phone, r.vehicle AS rider_vehicle, " +
                    "r.plate AS rider_plate, r.rating AS rider_rating, r.photo AS rider_photo, r.current_street AS rider_street " +
                    "FROM `orders` o LEFT JOIN `riders` r ON o.rider_id = r.id " +
                    "WHERE o.order_code = ? OR o.order_code = ? OR CAST(o.id AS CHAR) = ? LIMIT 1",
                    Arrays.asList(param, "#" + cleanCode, param)
            );

            if (order == null) {
                Map<String, Object> err = new LinkedHashMap<>();
                err.put("success", false);
                err.put("message", "Orden no encontrada.");
                HttpUtil.sendJson(ex, 404, err);
                return;
            }

            int oid = JsonUtil.getInt(order, "id", 0);
            List<Map<String, Object>> items = DatabaseManager.queryList(
                    "SELECT * FROM `order_items` WHERE `order_id` = ?",
                    Collections.singletonList(oid)
            );
            for (Map<String, Object> it : items) {
                it.put("toppings", JsonUtil.parseArray(JsonUtil.getString(it, "toppings_json", "[]")));
                it.put("sauces", JsonUtil.parseArray(JsonUtil.getString(it, "sauces_json", "[]")));
                it.put("base_price_formatted", HttpUtil.formatCop(JsonUtil.getDouble(it, "base_price", 0)));
                it.put("subtotal_formatted", HttpUtil.formatCop(JsonUtil.getDouble(it, "subtotal", 0)));
            }

            List<Map<String, Object>> events = DatabaseManager.queryList(
                    "SELECT * FROM `order_tracking_events` WHERE `order_id` = ? ORDER BY `created_at` ASC",
                    Collections.singletonList(oid)
            );

            order.put("items", items);
            order.put("events", events);
            order.put("subtotal_formatted", HttpUtil.formatCop(JsonUtil.getDouble(order, "subtotal", 0)));
            order.put("discount_formatted", HttpUtil.formatCop(JsonUtil.getDouble(order, "discount", 0)));
            order.put("delivery_fee_formatted", HttpUtil.formatCop(JsonUtil.getDouble(order, "delivery_fee", 0)));
            order.put("total_formatted", HttpUtil.formatCop(JsonUtil.getDouble(order, "total", 0)));

            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("success", true);
            resp.put("engine", "100% Java");
            resp.put("order", order);
            HttpUtil.sendJson(ex, 200, resp);
            return;
        }

        List<Map<String, Object>> orders = DatabaseManager.queryList(
                "SELECT o.*, COUNT(i.id) AS total_items, r.name AS rider_name " +
                "FROM `orders` o LEFT JOIN `order_items` i ON o.id = i.order_id " +
                "LEFT JOIN `riders` r ON o.rider_id = r.id " +
                "GROUP BY o.id ORDER BY o.created_at DESC LIMIT 50"
        );
        for (Map<String, Object> ord : orders) {
            ord.put("total_formatted", HttpUtil.formatCop(JsonUtil.getDouble(ord, "total", 0)));
        }

        Map<String, Object> resp = new LinkedHashMap<>();
        resp.put("success", true);
        resp.put("engine", "100% Java");
        resp.put("count", orders.size());
        resp.put("orders", orders);
        HttpUtil.sendJson(ex, 200, resp);
    }
}
