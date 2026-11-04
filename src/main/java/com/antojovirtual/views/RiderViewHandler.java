package com.antojovirtual.views;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.*;

/**
 * RiderViewHandler - Portal Movil de Repartidores en Tiempo Real (100% Java SSR)
 * Ruta: /rider (y compatibilidad /rider.php)
 */
public class RiderViewHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        try {
            if ("POST".equalsIgnoreCase(ex.getRequestMethod())) {
                Map<String, String> form = HttpUtil.parseFormUrlEncoded(HttpUtil.readBody(ex));
                int oid = 0;
                try { oid = Integer.parseInt(form.getOrDefault("order_id", "0")); } catch (NumberFormatException ignored) {}
                String newSt = form.getOrDefault("new_status", "").trim();

                if (oid > 0 && !newSt.isEmpty()) {
                    DatabaseManager.executeUpdate(
                            "UPDATE `orders` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?",
                            Arrays.asList(newSt, oid)
                    );
                    String title = "en_camino".equals(newSt)
                            ? "Repartidor en Camino hacia tu Dirección"
                            : "¡Pedido Entregado en Manos del Cliente!";
                    DatabaseManager.executeUpdate(
                            "INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`) VALUES (?, ?, ?, 'Actualizado desde la App Móvil del Repartidor (Servidor Java).')",
                            Arrays.asList(oid, newSt, title)
                    );
                }
                HttpUtil.sendRedirect(ex, "/rider?ok=1");
                return;
            }

            List<Map<String, Object>> activeOrders = DatabaseManager.queryList(
                    "SELECT o.* FROM `orders` o WHERE o.status IN ('recibido','preparando','en_camino') ORDER BY o.created_at DESC LIMIT 12"
            );

            List<Map<String, Object>> historyOrders = DatabaseManager.queryList(
                    "SELECT * FROM `orders` WHERE `status` = 'entregado' ORDER BY `created_at` DESC LIMIT 6"
            );

            Map<String, String> query = HttpUtil.parseQueryParams(ex);
            boolean ok = "1".equals(query.get("ok"));

            StringBuilder sb = new StringBuilder(24576);
            sb.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\">");
            sb.append("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">");
            sb.append("<title>App Repartidor — Antojo Virtual Neiva (100% Java)</title>");
            sb.append("<link href=\"https://fonts.googleapis.com/css2?family=Epilogue:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap\" rel=\"stylesheet\">");
            sb.append("<link rel=\"stylesheet\" href=\"/assets/css/style.css\">");
            sb.append("<style>");
            sb.append("html { font-size: 13.5px; }");
            sb.append(".rider-shell { max-width: 680px; margin: 20px auto; padding: 0 16px; }");
            sb.append(".rider-card { padding: 20px; border-radius: 22px; background: var(--neu-bg); box-shadow: var(--neu-extruded); border:1px solid var(--color-border-subtle); margin-bottom: 18px; }");
            sb.append(".rider-pill { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:999px; font-size:0.78rem; font-weight:800; background:rgba(16,185,129,0.16); color:#059669; }");
            sb.append(".action-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 14px; }");
            sb.append("</style></head><body><div class=\"rider-shell\">");

            sb.append("<div class=\"rider-card\" style=\"display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;\"><div>");
            sb.append("<span class=\"rider-pill\">● GPS ACTIVO EN NEIVA (100% JAVA)</span>");
            sb.append("<h1 style=\"font-family:'Epilogue';font-size:1.45rem;font-weight:900;margin-top:6px;\">🛵 Carlos Mendoza</h1>");
            sb.append("<p style=\"font-size:0.85rem;color:var(--color-on-surface-variant);\">Bajaj Pulsar 200 NS • Placa HUI-84E • ★ 4.95</p>");
            sb.append("</div><div style=\"display:flex;gap:8px;\">");
            sb.append("<a href=\"/\" class=\"btn-admin-link\" style=\"padding:9px 14px;text-decoration:none;border-radius:12px;font-weight:700;font-size:0.85rem;\">🏠 Tienda</a>");
            sb.append("<a href=\"/admin\" class=\"btn-admin-link\" style=\"padding:9px 14px;text-decoration:none;border-radius:12px;font-weight:700;font-size:0.85rem;\">👨‍🍳 Cocina</a>");
            sb.append("</div></div>");

            if (ok) {
                sb.append("<div style=\"padding:12px 18px;border-radius:14px;background:rgba(16,185,129,0.18);color:#065F46;font-weight:700;margin-bottom:16px;\">");
                sb.append("✅ Estado de ruta actualizado y notificado al cliente en vivo (Servidor Java).");
                sb.append("</div>");
            }

            sb.append("<h2 style=\"font-family:'Epilogue';font-size:1.15rem;margin-bottom:12px;\">📦 Entregas Asignadas en Curso (").append(activeOrders.size()).append(")</h2>");

            if (activeOrders.isEmpty()) {
                sb.append("<div class=\"rider-card\" style=\"text-align:center;color:var(--color-on-surface-variant);\">No hay entregas pendientes en este momento. ¡Haz un pedido en la tienda para asignarlo!</div>");
            } else {
                for (Map<String, Object> o : activeOrders) {
                    int oid = JsonUtil.getInt(o, "id", 0);
                    String orderCode = JsonUtil.getString(o, "order_code", "");
                    String status = JsonUtil.getString(o, "status", "recibido");
                    double total = JsonUtil.getDouble(o, "total", 0);
                    double tip = JsonUtil.getDouble(o, "tip", 1000);
                    String custName = JsonUtil.getString(o, "customer_name", "");
                    String custPhone = JsonUtil.getString(o, "customer_phone", "");
                    String delAddr = JsonUtil.getString(o, "delivery_address", "");
                    String comuna = JsonUtil.getString(o, "comuna", "");

                    String gmapsQuery = URLEncoder.encode(delAddr + ", " + comuna + ", Neiva, Huila, Colombia", StandardCharsets.UTF_8);
                    String phoneDigits = custPhone.replaceAll("\\D", "");

                    sb.append("<div class=\"rider-card\">");
                    sb.append("<div style=\"display:flex;justify-content:space-between;align-items:center;\">");
                    sb.append("<strong style=\"color:#dc2626;font-size:1.05rem;\">").append(HttpUtil.escapeHtml(orderCode)).append("</strong>");
                    sb.append("<span style=\"font-size:0.8rem;font-weight:800;text-transform:uppercase;padding:4px 10px;border-radius:999px;background:rgba(255,90,31,0.15);color:#EA580C;\">").append(HttpUtil.escapeHtml(status.replace("_", " "))).append("</span>");
                    sb.append("</div>");

                    sb.append("<div style=\"margin-top:10px;\">");
                    sb.append("<div style=\"font-weight:700;font-size:1rem;\">👤 ").append(HttpUtil.escapeHtml(custName)).append("</div>");
                    sb.append("<div style=\"font-size:0.9rem;color:var(--color-on-surface-variant);margin-top:3px;\">📍 ").append(HttpUtil.escapeHtml(delAddr)).append(" • ").append(HttpUtil.escapeHtml(comuna)).append("</div>");
                    sb.append("<div style=\"font-size:0.85rem;margin-top:6px;font-weight:700;\">💰 Total: ").append(HttpUtil.formatCop(total)).append(" (Propina Rider: ").append(HttpUtil.formatCop(tip)).append(")</div>");
                    sb.append("</div>");

                    sb.append("<div class=\"action-grid\">");
                    sb.append("<a href=\"https://www.google.com/maps/search/?api=1&query=").append(gmapsQuery).append("\" target=\"_blank\" class=\"btn-admin-link\" style=\"padding:10px;justify-content:center;text-decoration:none;border-radius:12px;font-size:0.82rem;font-weight:700;\">🗺️ Abrir Google Maps</a>");
                    sb.append("<a href=\"https://waze.com/ul?ll=2.9280,-75.2820&navigate=yes\" target=\"_blank\" class=\"btn-admin-link\" style=\"padding:10px;justify-content:center;text-decoration:none;border-radius:12px;font-size:0.82rem;font-weight:700;\">🚗 Abrir en Waze</a>");
                    sb.append("<a href=\"tel:").append(HttpUtil.escapeHtml(custPhone)).append("\" class=\"btn-admin-link\" style=\"padding:10px;justify-content:center;text-decoration:none;border-radius:12px;font-size:0.82rem;font-weight:700;\">📞 Llamar Cliente</a>");
                    sb.append("<a href=\"https://wa.me/57").append(phoneDigits).append("\" target=\"_blank\" class=\"btn-admin-link\" style=\"padding:10px;justify-content:center;text-decoration:none;border-radius:12px;font-size:0.82rem;font-weight:700;color:#059669;\">💬 WhatsApp</a>");
                    sb.append("</div>");

                    sb.append("<form method=\"POST\" action=\"/rider\" style=\"display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px;\">");
                    sb.append("<input type=\"hidden\" name=\"order_id\" value=\"").append(oid).append("\">");
                    sb.append("<button type=\"submit\" name=\"new_status\" value=\"en_camino\" class=\"btn-admin-link\" style=\"padding:11px;justify-content:center;border-radius:12px;font-weight:800;color:#EA580C;cursor:pointer;\">🛵 Iniciar Ruta GPS</button>");
                    sb.append("<button type=\"submit\" name=\"new_status\" value=\"entregado\" class=\"btn-customize\" style=\"padding:11px;justify-content:center;border-radius:12px;font-weight:800;background:var(--color-primary-neu);color:#fff;cursor:pointer;\">✅ Marcar Entregado</button>");
                    sb.append("</form></div>");
                }
            }

            sb.append("<h2 style=\"font-family:'Epilogue';font-size:1.1rem;margin:22px 0 12px;\">✅ Últimas Entregas Completadas</h2>");
            for (Map<String, Object> h : historyOrders) {
                sb.append("<div class=\"rider-card\" style=\"padding:14px 18px;display:flex;justify-content:space-between;align-items:center;\"><div>");
                sb.append("<strong>").append(HttpUtil.escapeHtml(JsonUtil.getString(h, "order_code", ""))).append("</strong> • ").append(HttpUtil.escapeHtml(JsonUtil.getString(h, "customer_name", "")));
                sb.append("<div style=\"font-size:0.8rem;color:var(--color-on-surface-variant);\">").append(HttpUtil.escapeHtml(JsonUtil.getString(h, "delivery_address", ""))).append(" (").append(HttpUtil.escapeHtml(JsonUtil.getString(h, "comuna", ""))).append(")</div>");
                sb.append("</div><span style=\"font-weight:800;color:#10B981;\">").append(HttpUtil.formatCop(JsonUtil.getDouble(h, "total", 0))).append("</span></div>");
            }

            sb.append("</div></body></html>");
            HttpUtil.sendHtml(ex, 200, sb.toString());

        } catch (Exception e) {
            e.printStackTrace();
            HttpUtil.sendError(ex, 500, "Error en RiderViewHandler Java: " + e.getMessage());
        }
    }
}
