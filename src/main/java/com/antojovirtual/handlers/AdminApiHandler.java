package com.antojovirtual.handlers;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.config.JwtUtil;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.util.*;

/**
 * AdminApiHandler - API de Gestion de Pedidos, Cocina y Repartidores (100% Java)
 * Soporta: POST /api/admin (auto_advance, update_status) | GET /api/admin
 */
public class AdminApiHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (HttpUtil.handleCorsPreflight(ex)) return;

        String method = ex.getRequestMethod().toUpperCase();
        try {
            if ("POST".equals(method)) {
                handlePost(ex);
            } else {
                handleGet(ex);
            }
        } catch (Exception e) {
            e.printStackTrace();
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "Error administrativo en Java: " + e.getMessage());
            HttpUtil.sendJson(ex, 500, err);
        }
    }

    private void handlePost(HttpExchange ex) throws Exception {
        String bodyRaw = HttpUtil.readBody(ex);
        Map<String, Object> input = bodyRaw.trim().startsWith("{")
                ? JsonUtil.parseObject(bodyRaw)
                : new LinkedHashMap<>(HttpUtil.parseFormUrlEncoded(bodyRaw));

        String action = JsonUtil.getString(input, "action", "update_status").trim();
        int orderId = JsonUtil.getInt(input, "order_id", 0);
        String orderCode = JsonUtil.getString(input, "order_code", "").trim();

        if (orderId <= 0 && !orderCode.isEmpty()) {
            String cleanCode = orderCode.startsWith("#") ? orderCode.substring(1) : orderCode;
            Map<String, Object> found = DatabaseManager.queryOne(
                    "SELECT id FROM `orders` WHERE `order_code` = ? OR `order_code` = ? OR `order_code` = ? LIMIT 1",
                    Arrays.asList(orderCode, "#" + cleanCode, cleanCode)
            );
            if (found != null) {
                orderId = JsonUtil.getInt(found, "id", 0);
            }
        }

        if (orderId <= 0) {
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "Se requiere ID o código de la orden.");
            HttpUtil.sendJson(ex, 400, err);
            return;
        }

        Map<String, Object> currentOrder = DatabaseManager.queryOne(
                "SELECT * FROM `orders` WHERE `id` = ? LIMIT 1",
                Collections.singletonList(orderId)
        );
        if (currentOrder == null) {
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "Orden no encontrada.");
            HttpUtil.sendJson(ex, 404, err);
            return;
        }

        String currStatus = JsonUtil.getString(currentOrder, "status", "recibido");
        String targetStatus;
        if ("auto_advance".equalsIgnoreCase(action)) {
            switch (currStatus) {
                case "recibido": targetStatus = "preparando"; break;
                case "preparando": targetStatus = "en_camino"; break;
                case "en_camino": targetStatus = "entregado"; break;
                case "entregado": targetStatus = "recibido"; break;
                default: targetStatus = "recibido"; break;
            }
        } else {
            targetStatus = JsonUtil.getString(input, "status", "preparando");
        }

        String title;
        String desc;
        switch (targetStatus) {
            case "recibido":
                title = "Orden Recibida y Confirmada";
                desc = "Pedido en cola de espera. Restaurante verificando ingredientes.";
                break;
            case "preparando":
                title = "Carbón al Rojo Vivo & Cocción";
                desc = "El maestro parrillero está asando tu orden con sazón huilense artesanal.";
                break;
            case "en_camino":
                title = "Repartidor en Ruta por Neiva";
                desc = "Tu pedido va en caliente empacado en termo térmico por la Av. Circunvalar.";
                break;
            case "entregado":
                title = "¡Pedido Entregado con Éxito!";
                desc = "Repartidor entregó la orden en puerta. ¡Buen provecho en Neiva!";
                break;
            default:
                title = "Orden Cancelada";
                desc = "El pedido fue cancelado a solicitud del cliente o restaurante.";
                break;
        }

        DatabaseManager.executeUpdate(
                "UPDATE `orders` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?",
                Arrays.asList(targetStatus, orderId)
        );

        DatabaseManager.executeUpdate(
                "INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`) VALUES (?, ?, ?, ?)",
                Arrays.asList(orderId, targetStatus, title, desc)
        );

        Map<String, Object> resp = new LinkedHashMap<>();
        resp.put("success", true);
        resp.put("engine", "100% Java");
        resp.put("message", "Estado actualizado exitosamente a: " + targetStatus);
        resp.put("order_id", orderId);
        resp.put("previous_status", currStatus);
        resp.put("new_status", targetStatus);
        resp.put("new_status_title", title);
        HttpUtil.sendJson(ex, 200, resp);
    }

    private void handleGet(HttpExchange ex) throws Exception {
        Map<String, Object> mRow = DatabaseManager.queryOne(
                "SELECT COUNT(*) AS total_orders, " +
                "SUM(CASE WHEN `status` IN ('recibido','preparando','en_camino') THEN 1 ELSE 0 END) AS active_orders, " +
                "SUM(CASE WHEN `status` = 'entregado' THEN 1 ELSE 0 END) AS delivered_orders, " +
                "COALESCE(SUM(CASE WHEN `status` != 'cancelado' THEN total ELSE 0 END), 0) AS total_revenue " +
                "FROM `orders`"
        );

        Map<String, Object> stats = new LinkedHashMap<>();
        int totalRevenue = mRow != null ? JsonUtil.getInt(mRow, "total_revenue", 0) : 0;
        stats.put("total_orders", mRow != null ? JsonUtil.getInt(mRow, "total_orders", 0) : 0);
        stats.put("active_orders", mRow != null ? JsonUtil.getInt(mRow, "active_orders", 0) : 0);
        stats.put("delivered_orders", mRow != null ? JsonUtil.getInt(mRow, "delivered_orders", 0) : 0);
        stats.put("total_revenue", totalRevenue);
        stats.put("total_revenue_formatted", HttpUtil.formatCop(totalRevenue));

        List<Map<String, Object>> orders = DatabaseManager.queryList(
                "SELECT o.*, COUNT(i.id) AS items_count, r.name AS rider_name, r.vehicle AS rider_vehicle " +
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
        resp.put("metrics", stats);
        resp.put("orders", orders);
        resp.put("admin_user", JwtUtil.getAuthUser(ex));
        HttpUtil.sendJson(ex, 200, resp);
    }
}
