package com.antojovirtual.handlers;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.util.*;

/**
 * CouponsHandler - API de Validacion de Cupones en MySQL (100% Java)
 * Soporta: POST o GET /api/coupons?code=ANTOJONEIVA5K&subtotal=40000
 */
public class CouponsHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (HttpUtil.handleCorsPreflight(ex)) return;

        try {
            Map<String, String> query = HttpUtil.parseQueryParams(ex);
            String bodyRaw = HttpUtil.readBody(ex);
            Map<String, Object> body = bodyRaw.trim().startsWith("{")
                    ? JsonUtil.parseObject(bodyRaw)
                    : Collections.emptyMap();

            String code = JsonUtil.getString(body, "code", query.getOrDefault("code", "")).trim().toUpperCase();
            int subtotal = JsonUtil.getInt(body, "subtotal", 0);
            if (subtotal == 0 && query.containsKey("subtotal")) {
                try { subtotal = (int) Double.parseDouble(query.get("subtotal")); } catch (NumberFormatException ignored) {}
            }

            if (code.isEmpty()) {
                Map<String, Object> err = new LinkedHashMap<>();
                err.put("success", false);
                err.put("message", "Por favor ingresa un código de cupón.");
                HttpUtil.sendJson(ex, 400, err);
                return;
            }

            Map<String, Object> coupon = DatabaseManager.queryOne(
                    "SELECT * FROM coupons WHERE UPPER(code) = ? LIMIT 1",
                    Collections.singletonList(code)
            );

            if (coupon == null) {
                Map<String, Object> err = new LinkedHashMap<>();
                err.put("success", false);
                err.put("message", "Código no válido en Neiva. Prueba con ANTOJONEIVA5K");
                HttpUtil.sendJson(ex, 404, err);
                return;
            }

            if (JsonUtil.getInt(coupon, "is_active", 0) == 0) {
                Map<String, Object> err = new LinkedHashMap<>();
                err.put("success", false);
                err.put("message", "Este cupón se encuentra inactivo actualmente.");
                HttpUtil.sendJson(ex, 400, err);
                return;
            }

            int usedCount = JsonUtil.getInt(coupon, "used_count", 0);
            int maxUses = JsonUtil.getInt(coupon, "max_uses", 9999);
            if (usedCount >= maxUses) {
                Map<String, Object> err = new LinkedHashMap<>();
                err.put("success", false);
                err.put("message", "Este cupón ha alcanzado el límite máximo de usos.");
                HttpUtil.sendJson(ex, 400, err);
                return;
            }

            int minPurchase = JsonUtil.getInt(coupon, "min_purchase", 0);
            if (subtotal > 0 && subtotal < minPurchase) {
                Map<String, Object> err = new LinkedHashMap<>();
                err.put("success", false);
                err.put("message", "El pedido mínimo para aplicar este cupón es de " + HttpUtil.formatCop(minPurchase) + " COP.");
                HttpUtil.sendJson(ex, 400, err);
                return;
            }

            int discountAmount = JsonUtil.getInt(coupon, "discount_amount", 5000);
            String discFmt = HttpUtil.formatCop(discountAmount);

            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("success", true);
            resp.put("status", "ok");
            resp.put("engine", "100% Java");
            resp.put("code", JsonUtil.getString(coupon, "code", code));
            resp.put("discount_amount", discountAmount);
            resp.put("discount_formatted", discFmt);
            resp.put("description", JsonUtil.getString(coupon, "description", ""));
            resp.put("message", "¡Cupón " + code + " aplicado con éxito! (-" + discFmt + " COP)");
            HttpUtil.sendJson(ex, 200, resp);

        } catch (Exception e) {
            e.printStackTrace();
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "Error al validar cupón en Java: " + e.getMessage());
            HttpUtil.sendJson(ex, 500, err);
        }
    }
}
