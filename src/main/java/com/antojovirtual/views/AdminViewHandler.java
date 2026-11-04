package com.antojovirtual.views;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.util.*;

/**
 * AdminViewHandler - Panel Administrativo y de Cocina en Tiempo Real (100% Java SSR)
 * Ruta: /admin (y compatibilidad /admin.php)
 */
public class AdminViewHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        try {
            if ("POST".equalsIgnoreCase(ex.getRequestMethod())) {
                Map<String, String> form = HttpUtil.parseFormUrlEncoded(HttpUtil.readBody(ex));
                if ("change_status".equals(form.get("action"))) {
                    int oid = 0;
                    try { oid = Integer.parseInt(form.getOrDefault("order_id", "0")); } catch (NumberFormatException ignored) {}
                    String st = form.getOrDefault("status", "").trim();
                    if (oid > 0 && !st.isEmpty()) {
                        DatabaseManager.executeUpdate(
                                "UPDATE `orders` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?",
                                Arrays.asList(st, oid)
                        );
                        DatabaseManager.executeUpdate(
                                "INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`) VALUES (?, ?, ?, 'Actualizado desde el Panel de Cocina (Servidor Java).')",
                                Arrays.asList(oid, st, "Estado: " + st.toUpperCase())
                        );
                    }
                    HttpUtil.sendRedirect(ex, "/admin?updated=1");
                    return;
                }
            }

            Map<String, Object> metrics = DatabaseManager.queryOne(
                    "SELECT COUNT(*) AS total_orders, COALESCE(SUM(total), 0) AS revenue, " +
                    "SUM(CASE WHEN `status` IN ('recibido','preparando','en_camino') THEN 1 ELSE 0 END) AS active_orders, " +
                    "SUM(CASE WHEN `status` = 'entregado' THEN 1 ELSE 0 END) AS delivered_orders " +
                    "FROM `orders`"
            );

            List<Map<String, Object>> orders = DatabaseManager.queryList(
                    "SELECT o.*, i.invoice_number, i.cufe_code, r.name AS rider_name " +
                    "FROM `orders` o " +
                    "LEFT JOIN `invoices` i ON i.order_id = o.id " +
                    "LEFT JOIN `riders` r ON r.id = o.rider_id " +
                    "ORDER BY o.created_at DESC LIMIT 30"
            );

            Map<String, String> query = HttpUtil.parseQueryParams(ex);
            boolean updated = "1".equals(query.get("updated"));

            StringBuilder sb = new StringBuilder(32768);
            sb.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\">");
            sb.append("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">");
            sb.append("<title>Panel de Cocina y Despacho — Antojo Virtual Neiva (100% Java)</title>");
            sb.append("<link href=\"https://fonts.googleapis.com/css2?family=Epilogue:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap\" rel=\"stylesheet\">");
            sb.append("<link rel=\"stylesheet\" href=\"/assets/css/style.css\">");
            sb.append("<style>");
            sb.append("html { font-size: 13.5px; }");
            sb.append(".admin-wrap { max-width: 1240px; margin: 22px auto; padding: 0 18px; }");
            sb.append(".admin-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px; }");
            sb.append(".kpi-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 28px; }");
            sb.append(".kpi-card { padding: 22px; border-radius: 22px; background: var(--neu-bg); box-shadow: var(--neu-extruded); border:1px solid var(--color-border-subtle); }");
            sb.append(".kpi-val { font-family:'Epilogue', sans-serif; font-size: 2rem; font-weight: 900; color: #dc2626; margin-top: 6px; }");
            sb.append(".orders-table-wrap { border-radius: 24px; padding: 22px; background: var(--neu-bg); box-shadow: var(--neu-extruded-lg); border:1px solid var(--color-border-subtle); overflow-x: auto; }");
            sb.append(".order-row { padding: 18px; border-radius: 18px; margin-bottom: 14px; background: var(--neu-bg); box-shadow: var(--neu-inset-sm); border:1px solid var(--color-border-subtle); display:grid; grid-template-columns: 1.3fr 1.6fr 1fr 1.2fr; gap: 16px; align-items: center; }");
            sb.append("@media (max-width: 900px) { .order-row { grid-template-columns: 1fr; } }");
            sb.append(".st-badge { display:inline-block; padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; }");
            sb.append(".st-recibido { background: rgba(59,130,246,0.15); color: #2563EB; }");
            sb.append(".st-preparando { background: rgba(245,158,11,0.18); color: #D97706; }");
            sb.append(".st-en_camino { background: rgba(255,90,31,0.18); color: #EA580C; }");
            sb.append(".st-entregado { background: rgba(16,185,129,0.18); color: #059669; }");
            sb.append(".st-cancelado { background: rgba(239,68,68,0.18); color: #DC2626; }");
            sb.append("</style></head><body><div class=\"admin-wrap\">");

            sb.append("<div class=\"admin-header\"><div>");
            sb.append("<div style=\"display:inline-block;padding:4px 12px;border-radius:999px;background:#fef2f2;color:#dc2626;font-weight:800;font-size:0.78rem;margin-bottom:6px;\">☕ 100% JAVA • CENTRO DE OPERACIONES NEIVA</div>");
            sb.append("<h1 style=\"font-family:'Epilogue';font-size:2rem;font-weight:900;\">Panel de Cocina, Facturación DIAN y Despacho</h1>");
            sb.append("</div><div style=\"display:flex;gap:10px;flex-wrap:wrap;\">");
            sb.append("<a href=\"/\" class=\"btn-admin-link\" style=\"padding:10px 18px;text-decoration:none;border-radius:14px;font-weight:700;\">← Volver a la Tienda</a>");
            sb.append("<a href=\"/rider\" class=\"btn-admin-link\" style=\"padding:10px 18px;text-decoration:none;border-radius:14px;font-weight:700;color:#dc2626;\">🛵 App Repartidor</a>");
            sb.append("<a href=\"/install\" class=\"btn-admin-link\" style=\"padding:10px 18px;text-decoration:none;border-radius:14px;font-weight:700;\">🗄️ Estado BD Java</a>");
            sb.append("</div></div>");

            if (updated) {
                sb.append("<div style=\"padding:14px 20px;border-radius:16px;background:rgba(16,185,129,0.15);color:#065F46;font-weight:700;margin-bottom:20px;\">");
                sb.append("✅ Estado del pedido actualizado en tiempo real en MySQL desde el Servidor Java. El cliente ya puede verlo en su mapa GPS.");
                sb.append("</div>");
            }

            int totalOrders = metrics != null ? JsonUtil.getInt(metrics, "total_orders", 0) : 0;
            double revenue = metrics != null ? JsonUtil.getDouble(metrics, "revenue", 0) : 0;
            int activeOrders = metrics != null ? JsonUtil.getInt(metrics, "active_orders", 0) : 0;
            int deliveredOrders = metrics != null ? JsonUtil.getInt(metrics, "delivered_orders", 0) : 0;

            sb.append("<div class=\"kpi-grid\">");
            sb.append("<div class=\"kpi-card\"><div style=\"color:var(--color-on-surface-variant);font-size:0.85rem;font-weight:700;\">TOTAL PEDIDOS</div><div class=\"kpi-val\">").append(totalOrders).append("</div></div>");
            sb.append("<div class=\"kpi-card\"><div style=\"color:var(--color-on-surface-variant);font-size:0.85rem;font-weight:700;\">VENTAS ACUMULADAS (COP)</div><div class=\"kpi-val\" style=\"color:#10B981;\">").append(HttpUtil.formatCop(revenue)).append("</div></div>");
            sb.append("<div class=\"kpi-card\"><div style=\"color:var(--color-on-surface-variant);font-size:0.85rem;font-weight:700;\">EN COCINA / RUTA</div><div class=\"kpi-val\" style=\"color:#F59E0B;\">").append(activeOrders).append("</div></div>");
            sb.append("<div class=\"kpi-card\"><div style=\"color:var(--color-on-surface-variant);font-size:0.85rem;font-weight:700;\">ENTREGADOS CON ÉXITO</div><div class=\"kpi-val\" style=\"color:#3B82F6;\">").append(deliveredOrders).append("</div></div>");
            sb.append("</div>");

            sb.append("<div class=\"orders-table-wrap\">");
            sb.append("<h2 style=\"font-family:'Epilogue';font-size:1.35rem;margin-bottom:18px;\">📋 Comandas Activas e Historial Reciente</h2>");

            for (Map<String, Object> o : orders) {
                int oid = JsonUtil.getInt(o, "id", 0);
                List<Map<String, Object>> items = DatabaseManager.queryList(
                        "SELECT item_name, restaurant_name, quantity, doneness, bread, notes FROM `order_items` WHERE `order_id` = ?",
                        Collections.singletonList(oid)
                );

                String orderCode = JsonUtil.getString(o, "order_code", "");
                String createdAt = JsonUtil.getString(o, "created_at", "");
                String invNum = JsonUtil.getString(o, "invoice_number", "");
                if (invNum.isEmpty() || "null".equalsIgnoreCase(invNum)) invNum = "FE-ANTOJO-" + oid;
                String custName = JsonUtil.getString(o, "customer_name", "");
                String custPhone = JsonUtil.getString(o, "customer_phone", "");
                String delAddr = JsonUtil.getString(o, "delivery_address", "");
                String comuna = JsonUtil.getString(o, "comuna", "");
                double total = JsonUtil.getDouble(o, "total", 0);
                String payMethod = JsonUtil.getString(o, "payment_method", "nequi").toUpperCase();
                String payStatus = JsonUtil.getString(o, "payment_status", "paid");
                String status = JsonUtil.getString(o, "status", "recibido");

                sb.append("<div class=\"order-row\">");
                sb.append("<div>");
                sb.append("<div style=\"font-weight:900;font-size:1.05rem;color:#dc2626;\">").append(HttpUtil.escapeHtml(orderCode)).append("</div>");
                sb.append("<div style=\"font-size:0.82rem;color:var(--color-on-surface-variant);margin-top:2px;\">🕒 ").append(HttpUtil.escapeHtml(createdAt)).append("</div>");
                sb.append("<div style=\"font-size:0.82rem;font-weight:700;margin-top:4px;\">🧾 DIAN: ").append(HttpUtil.escapeHtml(invNum)).append("</div>");
                sb.append("</div>");

                sb.append("<div>");
                sb.append("<div style=\"font-weight:700;\">👤 ").append(HttpUtil.escapeHtml(custName)).append(" • 📞 ").append(HttpUtil.escapeHtml(custPhone)).append("</div>");
                sb.append("<div style=\"font-size:0.84rem;color:var(--color-on-surface-variant);\">📍 ").append(HttpUtil.escapeHtml(delAddr)).append(" (").append(HttpUtil.escapeHtml(comuna)).append(")</div>");
                sb.append("<div style=\"margin-top:6px;font-size:0.82rem;background:rgba(255,255,255,0.45);padding:6px 10px;border-radius:10px;\">");
                for (Map<String, Object> it : items) {
                    sb.append("<div>• <strong>").append(JsonUtil.getInt(it, "quantity", 1)).append("x ").append(HttpUtil.escapeHtml(JsonUtil.getString(it, "item_name", ""))).append("</strong>");
                    String rName = JsonUtil.getString(it, "restaurant_name", "");
                    if (!rName.isEmpty() && !"null".equalsIgnoreCase(rName)) {
                        sb.append(" <span style=\"color:var(--color-on-surface-variant);\">(").append(HttpUtil.escapeHtml(rName)).append(")</span>");
                    }
                    sb.append("</div>");
                }
                sb.append("</div></div>");

                sb.append("<div>");
                sb.append("<div style=\"font-weight:900;font-size:1.15rem;\">").append(HttpUtil.formatCop(total)).append("</div>");
                sb.append("<div style=\"font-size:0.8rem;text-transform:uppercase;font-weight:700;color:var(--color-on-surface-variant);\">💳 ").append(HttpUtil.escapeHtml(payMethod)).append(" (").append(HttpUtil.escapeHtml(payStatus)).append(")</div>");
                sb.append("<div style=\"margin-top:6px;\"><span class=\"st-badge st-").append(HttpUtil.escapeHtml(status)).append("\">").append(HttpUtil.escapeHtml(status.replace("_", " "))).append("</span></div>");
                sb.append("</div>");

                sb.append("<div>");
                sb.append("<form method=\"POST\" action=\"/admin\" style=\"display:flex;gap:8px;align-items:center;\">");
                sb.append("<input type=\"hidden\" name=\"action\" value=\"change_status\">");
                sb.append("<input type=\"hidden\" name=\"order_id\" value=\"").append(oid).append("\">");
                sb.append("<select name=\"status\" class=\"form-select\" style=\"padding:8px 10px;font-weight:700;font-size:0.82rem;\">");
                String[][] opts = {
                        {"recibido", "Recibido"},
                        {"preparando", "En Cocina"},
                        {"en_camino", "En Camino (Rider)"},
                        {"entregado", "Entregado"},
                        {"cancelado", "Cancelado"}
                };
                for (String[] opt : opts) {
                    sb.append("<option value=\"").append(opt[0]).append("\"").append(opt[0].equals(status) ? " selected" : "").append(">").append(opt[1]).append("</option>");
                }
                sb.append("</select>");
                sb.append("<button type=\"submit\" class=\"btn-customize\" style=\"padding:8px 14px;border-radius:12px;font-size:0.82rem;background:var(--color-primary-neu);color:#fff;\">Actualizar</button>");
                sb.append("</form></div></div>");
            }

            sb.append("</div></div></body></html>");
            HttpUtil.sendHtml(ex, 200, sb.toString());

        } catch (Exception e) {
            e.printStackTrace();
            HttpUtil.sendError(ex, 500, "Error en AdminViewHandler Java: " + e.getMessage());
        }
    }
}
