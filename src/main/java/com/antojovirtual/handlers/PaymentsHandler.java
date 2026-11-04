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
 * PaymentsHandler - API de Pasarela de Pagos Multibanco Colombiana (100% Java)
 * Soporta: GET /api/payments?banks=1 | ?methods=1 | ?comunas=1 | ?receipt=REF | ?order_id=ID | POST /api/payments
 */
public class PaymentsHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (HttpUtil.handleCorsPreflight(ex)) return;

        String method = ex.getRequestMethod().toUpperCase();
        try {
            if ("GET".equals(method)) {
                handleGet(ex);
            } else if ("POST".equals(method)) {
                handlePost(ex);
            } else {
                HttpUtil.sendError(ex, 405, "Metodo no permitido");
            }
        } catch (Exception e) {
            e.printStackTrace();
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "Error en pasarela de pagos Java: " + e.getMessage());
            HttpUtil.sendJson(ex, 500, err);
        }
    }

    private void handleGet(HttpExchange ex) throws Exception {
        Map<String, String> query = HttpUtil.parseQueryParams(ex);

        if (query.containsKey("banks")) {
            List<Map<String, Object>> banks = DatabaseManager.queryList(
                    "SELECT id, code, name, short_name, type, icon_svg FROM `banks` WHERE is_active = 1 ORDER BY sort_order ASC"
            );
            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("success", true);
            resp.put("engine", "100% Java");
            resp.put("count", banks.size());
            resp.put("banks", banks);
            HttpUtil.sendJson(ex, 200, resp);
            return;
        }

        if (query.containsKey("methods")) {
            List<Map<String, Object>> methods = DatabaseManager.queryList(
                    "SELECT id, slug, name, description, icon, type, fee_percentage FROM `payment_methods` WHERE is_active = 1 ORDER BY sort_order ASC"
            );
            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("success", true);
            resp.put("engine", "100% Java");
            resp.put("methods", methods);
            HttpUtil.sendJson(ex, 200, resp);
            return;
        }

        if (query.containsKey("comunas")) {
            List<Map<String, Object>> comunas = DatabaseManager.queryList(
                    "SELECT id, code, name, description, delivery_fee, estimated_minutes FROM `comunas` WHERE is_active = 1 ORDER BY id ASC"
            );
            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("success", true);
            resp.put("engine", "100% Java");
            resp.put("comunas", comunas);
            HttpUtil.sendJson(ex, 200, resp);
            return;
        }

        String receiptRef = query.getOrDefault("receipt", "").trim();
        String orderIdStr = query.getOrDefault("order_id", "").trim();

        if (!receiptRef.isEmpty() || !orderIdStr.isEmpty()) {
            String param = !receiptRef.isEmpty() ? receiptRef : orderIdStr;
            Map<String, Object> receipt = DatabaseManager.queryOne(
                    "SELECT p.*, o.order_code, o.customer_name, o.customer_phone, o.customer_email, " +
                    "o.delivery_address, o.comuna, o.status AS order_status, " +
                    "o.subtotal, o.discount, o.delivery_fee, o.tip, o.total, " +
                    "inv.invoice_number, inv.cufe_code, inv.issued_at, inv.qr_string " +
                    "FROM `payments` p " +
                    "INNER JOIN `orders` o ON p.order_id = o.id " +
                    "LEFT JOIN `invoices` inv ON o.id = inv.order_id " +
                    "WHERE p.transaction_reference = ? OR CAST(p.order_id AS CHAR) = ? OR o.order_code = ? " +
                    "ORDER BY p.id DESC LIMIT 1",
                    Arrays.asList(param, param, param)
            );

            if (receipt == null) {
                Map<String, Object> err = new LinkedHashMap<>();
                err.put("success", false);
                err.put("message", "Comprobante de transacción no encontrado.");
                HttpUtil.sendJson(ex, 404, err);
                return;
            }

            int oid = JsonUtil.getInt(receipt, "order_id", 0);
            List<Map<String, Object>> items = DatabaseManager.queryList(
                    "SELECT item_name, restaurant_name, base_price, quantity, subtotal, doneness, bread, toppings_json, notes FROM `order_items` WHERE order_id = ?",
                    Collections.singletonList(oid)
            );
            for (Map<String, Object> it : items) {
                it.put("toppings", JsonUtil.parseArray(JsonUtil.getString(it, "toppings_json", "[]")));
            }
            receipt.put("items", items);
            receipt.put("amount_formatted", HttpUtil.formatCop(JsonUtil.getDouble(receipt, "amount", 0)));
            receipt.put("subtotal_formatted", HttpUtil.formatCop(JsonUtil.getDouble(receipt, "subtotal", 0)));
            receipt.put("discount_formatted", HttpUtil.formatCop(JsonUtil.getDouble(receipt, "discount", 0)));
            receipt.put("delivery_fee_formatted", HttpUtil.formatCop(JsonUtil.getDouble(receipt, "delivery_fee", 0)));
            receipt.put("tip_formatted", HttpUtil.formatCop(JsonUtil.getDouble(receipt, "tip", 0)));

            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("success", true);
            resp.put("engine", "100% Java");
            resp.put("receipt", receipt);
            HttpUtil.sendJson(ex, 200, resp);
            return;
        }

        Map<String, Object> info = new LinkedHashMap<>();
        info.put("success", true);
        info.put("engine", "100% Java");
        info.put("gateway", "AntojoPay Gateway v2.0 (Neiva - Colombia - Java)");
        info.put("currency", "COP");
        info.put("support", Arrays.asList("PSE", "Tarjetas Visa/Mastercard/Amex/Diners", "Nequi", "Daviplata", "Efectivo", "Datáfono"));
        HttpUtil.sendJson(ex, 200, info);
    }

    private void handlePost(HttpExchange ex) throws Exception {
        Map<String, Object> input = JsonUtil.parseObject(HttpUtil.readBody(ex));
        int orderId = JsonUtil.getInt(input, "order_id", 0);
        String paymentMethod = JsonUtil.getString(input, "payment_method", "pse").trim();
        int amount = JsonUtil.getInt(input, "amount", 0);

        if (orderId <= 0) {
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "Se requiere un ID de orden válido para procesar el pago.");
            HttpUtil.sendJson(ex, 400, err);
            return;
        }

        Map<String, Object> order = DatabaseManager.queryOne("SELECT * FROM `orders` WHERE id = ? LIMIT 1", Collections.singletonList(orderId));
        if (order == null) {
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "La orden especificada no existe.");
            HttpUtil.sendJson(ex, 404, err);
            return;
        }

        if (amount <= 0) amount = JsonUtil.getInt(order, "total", 0);

        Random rnd = new Random();
        String dateBasic = LocalDate.now().format(DateTimeFormatter.BASIC_ISO_DATE);
        String txRef = "PAY-NV-" + dateBasic + "-" + (1000 + rnd.nextInt(9000));
        String authCode = "AUT-" + (100000 + rnd.nextInt(900000));
        boolean isCash = "cash_efectivo".equals(paymentMethod) || "cash_datafono".equals(paymentMethod);
        String status = isCash ? "pending" : "approved";

        Integer bankId = JsonUtil.getInt(input, "bank_id", 0);
        if (bankId <= 0) bankId = null;
        String bankName = JsonUtil.getString(input, "bank_name", "");
        String payerDocType = JsonUtil.getString(input, "payer_doc_type", "CC");
        String payerDocNumber = JsonUtil.getString(input, "payer_doc_number", "1075304128");
        String payerEmail = JsonUtil.getString(input, "payer_email", JsonUtil.getString(order, "customer_email", "cliente@antojo.com"));
        String payerPhone = JsonUtil.getString(input, "payer_phone", JsonUtil.getString(order, "customer_phone", "+57 314 289 9012"));

        String cardBrand = null;
        String cardLastFour = null;
        String cardholderName = null;
        int installments = Math.max(1, JsonUtil.getInt(input, "installments", 1));

        if ("card".equals(paymentMethod)) {
            String cardNum = JsonUtil.getString(input, "card_number", "").replaceAll("\\D", "");
            cardholderName = JsonUtil.getString(input, "cardholder_name", "MATEO RODRIGUEZ").toUpperCase();
            if (cardNum.startsWith("5")) cardBrand = "mastercard";
            else if (cardNum.startsWith("3")) cardBrand = "amex";
            else cardBrand = "visa";
            cardLastFour = cardNum.length() >= 4 ? cardNum.substring(cardNum.length() - 4) : "4242";
            bankName = cardBrand.toUpperCase() + " •••• " + cardLastFour;
        } else if ("pse".equals(paymentMethod) && bankName.isEmpty()) {
            bankName = "Bancolombia S.A.";
        } else if ("nequi".equals(paymentMethod)) {
            bankName = "Nequi (Bancolombia)";
        } else if ("daviplata".equals(paymentMethod)) {
            bankName = "Daviplata (Davivienda)";
        } else if ("cash_efectivo".equals(paymentMethod)) {
            bankName = "Efectivo contra Entrega";
        } else if ("cash_datafono".equals(paymentMethod)) {
            bankName = "Datáfono contra Entrega";
        }

        String paidAt = isCash ? null : LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));

        long paymentId = DatabaseManager.insertAndGetId(
                "INSERT INTO `payments` (`order_id`, `payment_method`, `transaction_reference`, `gateway`, `amount`, `currency`, " +
                "`status`, `authorization_code`, `bank_id`, `bank_name`, `payer_doc_type`, `payer_doc_number`, `payer_person_type`, " +
                "`payer_email`, `payer_phone`, `card_brand`, `card_last_four`, `cardholder_name`, `installments`, `ip_address`, `paid_at`) " +
                "VALUES (?, ?, ?, 'antojo_pay_gateway_java', ?, 'COP', ?, ?, ?, ?, ?, ?, 'natural', ?, ?, ?, ?, ?, ?, '127.0.0.1', ?)",
                Arrays.asList(
                        orderId, paymentMethod, txRef, amount, status, isCash ? null : authCode,
                        bankId, bankName, payerDocType, payerDocNumber, payerEmail, payerPhone,
                        cardBrand, cardLastFour, cardholderName, installments, paidAt
                )
        );

        DatabaseManager.executeUpdate(
                "UPDATE `orders` SET `payment_status` = ?, `payment_method` = ? WHERE `id` = ?",
                Arrays.asList(isCash ? "pending" : "paid", paymentMethod, orderId)
        );

        String invoiceNumber = "FE-ANTOJO-" + LocalDate.now().getYear() + "-" + String.format("%05d", orderId);
        String cufeCode = JwtUtil.sha384Hex(invoiceNumber + txRef + amount);

        Map<String, Object> resp = new LinkedHashMap<>();
        resp.put("success", true);
        resp.put("engine", "100% Java");
        resp.put("payment_id", paymentId);
        resp.put("transaction_reference", txRef);
        resp.put("authorization_code", authCode);
        resp.put("status", status);
        resp.put("invoice_number", invoiceNumber);
        resp.put("cufe_code", cufeCode);
        HttpUtil.sendJson(ex, 200, resp);
    }
}
