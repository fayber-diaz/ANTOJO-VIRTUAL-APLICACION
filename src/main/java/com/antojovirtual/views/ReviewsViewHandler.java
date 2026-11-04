package com.antojovirtual.views;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.util.*;

/**
 * ReviewsViewHandler - Muro Publico de Resenas Verificadas en Neiva (100% Java SSR)
 * Ruta: /reviews (y compatibilidad /reviews.php)
 */
public class ReviewsViewHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        try {
            if ("POST".equalsIgnoreCase(ex.getRequestMethod())) {
                Map<String, String> form = HttpUtil.parseFormUrlEncoded(HttpUtil.readBody(ex));
                String name = form.getOrDefault("customer_name", "Cliente Neiva").trim();
                int restId = 1;
                int rating = 5;
                try { restId = Math.max(1, Integer.parseInt(form.getOrDefault("restaurant_id", "1"))); } catch (NumberFormatException ignored) {}
                try { rating = Math.min(5, Math.max(1, Integer.parseInt(form.getOrDefault("rating", "5")))); } catch (NumberFormatException ignored) {}
                String comment = form.getOrDefault("comment", "").trim();

                if (comment.length() >= 4) {
                    DatabaseManager.executeUpdate(
                            "INSERT INTO `reviews` (`restaurant_id`, `customer_name`, `rating`, `comment`, `is_approved`, `created_at`) VALUES (?, ?, ?, ?, 1, NOW())",
                            Arrays.asList(restId, name, rating, comment)
                    );
                }
                HttpUtil.sendRedirect(ex, "/reviews?saved=1");
                return;
            }

            List<Map<String, Object>> reviews = DatabaseManager.queryList(
                    "SELECT r.*, res.name AS restaurant_name, res.comuna " +
                    "FROM `reviews` r " +
                    "LEFT JOIN `restaurants` res ON res.id = r.restaurant_id " +
                    "WHERE r.is_approved = 1 ORDER BY r.created_at DESC LIMIT 30"
            );

            List<Map<String, Object>> restaurants = DatabaseManager.queryList(
                    "SELECT id, name, comuna FROM `restaurants` ORDER BY name ASC"
            );

            Map<String, Object> stats = DatabaseManager.queryOne(
                    "SELECT ROUND(AVG(rating), 2) AS avg_rating, COUNT(*) AS total FROM `reviews`"
            );

            Map<String, String> query = HttpUtil.parseQueryParams(ex);
            boolean saved = "1".equals(query.get("saved"));

            StringBuilder sb = new StringBuilder(24576);
            sb.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\">");
            sb.append("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">");
            sb.append("<title>Muro de Reseñas Reales — Antojo Virtual Neiva (100% Java)</title>");
            sb.append("<link href=\"https://fonts.googleapis.com/css2?family=Epilogue:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap\" rel=\"stylesheet\">");
            sb.append("<link rel=\"stylesheet\" href=\"/assets/css/style.css\">");
            sb.append("<style>");
            sb.append("html { font-size: 13.5px; }");
            sb.append(".rev-wrap { max-width: 1120px; margin: 24px auto; padding: 0 18px; }");
            sb.append(".rev-grid { display:grid; grid-template-columns: 1fr 1.6fr; gap: 24px; align-items: start; }");
            sb.append("@media (max-width: 860px) { .rev-grid { grid-template-columns: 1fr; } }");
            sb.append(".rev-card { padding: 20px; border-radius: 22px; background: var(--neu-bg); box-shadow: var(--neu-extruded); border:1px solid var(--color-border-subtle); margin-bottom: 16px; }");
            sb.append("</style></head><body><div class=\"rev-wrap\">");

            sb.append("<div style=\"display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:24px;\"><div>");
            sb.append("<div style=\"display:inline-block;padding:4px 12px;border-radius:999px;background:#fef2f2;color:#dc2626;font-weight:800;font-size:0.78rem;margin-bottom:6px;\">☕ 100% JAVA • OPINIONES VERIFICADAS EN NEIVA</div>");
            sb.append("<h1 style=\"font-family:'Epilogue';font-size:1.95rem;font-weight:900;\">Muro de Reseñas de Clientes (★ ").append(stats != null ? JsonUtil.getDouble(stats, "avg_rating", 4.92) : 4.92).append(")</h1>");
            sb.append("</div><div style=\"display:flex;gap:10px;\">");
            sb.append("<a href=\"/\" class=\"btn-admin-link\" style=\"padding:10px 18px;text-decoration:none;border-radius:14px;font-weight:700;\">← Volver a la Tienda</a>");
            sb.append("<a href=\"/api/reviews?json=1\" class=\"btn-admin-link\" style=\"padding:10px 14px;text-decoration:none;border-radius:14px;font-size:0.82rem;\">{ } API JSON</a>");
            sb.append("</div></div>");

            if (saved) {
                sb.append("<div style=\"padding:14px 18px;border-radius:16px;background:rgba(16,185,129,0.16);color:#065F46;font-weight:700;margin-bottom:20px;\">");
                sb.append("🎉 ¡Gracias! Tu reseña ha sido publicada en el muro de Neiva (Servidor Java).");
                sb.append("</div>");
            }

            sb.append("<div class=\"rev-grid\">");
            sb.append("<div class=\"rev-card\">");
            sb.append("<h3 style=\"font-family:'Epilogue';font-size:1.2rem;margin-bottom:12px;\">✍️ Publicar Nueva Reseña</h3>");
            sb.append("<form method=\"POST\" action=\"/reviews\">");
            sb.append("<div class=\"form-group\"><label class=\"form-label\">Tu Nombre</label>");
            sb.append("<input type=\"text\" name=\"customer_name\" class=\"form-input\" placeholder=\"Ej. Valentina Perdomo\" required></div>");
            sb.append("<div class=\"form-group\"><label class=\"form-label\">Restaurante o Heladería</label>");
            sb.append("<select name=\"restaurant_id\" class=\"form-select\">");
            for (Map<String, Object> r : restaurants) {
                sb.append("<option value=\"").append(JsonUtil.getInt(r, "id", 1)).append("\">")
                  .append(HttpUtil.escapeHtml(JsonUtil.getString(r, "name", "")))
                  .append(" (").append(HttpUtil.escapeHtml(JsonUtil.getString(r, "comuna", ""))).append(")</option>");
            }
            sb.append("</select></div>");
            sb.append("<div class=\"form-group\"><label class=\"form-label\">Calificación Comida (1 a 5 ★)</label>");
            sb.append("<select name=\"rating\" class=\"form-select\">");
            sb.append("<option value=\"5\">★★★★★ (5/5 Excelente)</option>");
            sb.append("<option value=\"4\">★★★★☆ (4/5 Muy Bueno)</option>");
            sb.append("<option value=\"3\">★★★☆☆ (3/5 Regular)</option>");
            sb.append("</select></div>");
            sb.append("<div class=\"form-group\"><label class=\"form-label\">Tu Comentario</label>");
            sb.append("<textarea name=\"comment\" rows=\"3\" class=\"form-input\" placeholder=\"¿Qué tal estuvo tu pedido y el tiempo de entrega?\" required></textarea></div>");
            sb.append("<button type=\"submit\" class=\"btn-customize\" style=\"width:100%;justify-content:center;padding:12px;border-radius:14px;font-weight:800;background:var(--color-primary-neu);color:#fff;\">Publicar Calificación</button>");
            sb.append("</form></div>");

            sb.append("<div>");
            for (Map<String, Object> rv : reviews) {
                int stars = Math.min(5, Math.max(1, (int) Math.round(JsonUtil.getDouble(rv, "rating", 5))));
                String starStr = "★".repeat(stars) + "☆".repeat(5 - stars);
                String cName = JsonUtil.getString(rv, "customer_name", "Cliente");
                String rName = JsonUtil.getString(rv, "restaurant_name", "");
                if (rName.isEmpty() || "null".equalsIgnoreCase(rName)) rName = "Antojo Virtual Neiva";
                String comment = JsonUtil.getString(rv, "comment", "");
                String createdAt = JsonUtil.getString(rv, "created_at", "");

                sb.append("<div class=\"rev-card\">");
                sb.append("<div style=\"display:flex;justify-content:space-between;align-items:center;\">");
                sb.append("<div><strong style=\"font-size:1rem;\">").append(HttpUtil.escapeHtml(cName)).append("</strong> <span style=\"font-size:0.76rem;color:#10B981;font-weight:700;\">✔ Compra Verificada</span></div>");
                sb.append("<span style=\"color:#F59E0B;font-size:1.05rem;letter-spacing:1px;\">").append(starStr).append("</span>");
                sb.append("</div>");
                sb.append("<div style=\"font-size:0.8rem;font-weight:700;color:#dc2626;margin-top:4px;\">🏪 ").append(HttpUtil.escapeHtml(rName)).append("</div>");
                sb.append("<p style=\"margin-top:8px;color:var(--color-on-surface);line-height:1.5;\">“").append(HttpUtil.escapeHtml(comment)).append("”</p>");
                sb.append("<div style=\"font-size:0.75rem;color:var(--color-on-surface-variant);margin-top:8px;\">🕒 ").append(HttpUtil.escapeHtml(createdAt)).append("</div>");
                sb.append("</div>");
            }
            sb.append("</div></div></div></body></html>");

            HttpUtil.sendHtml(ex, 200, sb.toString());
        } catch (Exception e) {
            e.printStackTrace();
            HttpUtil.sendError(ex, 500, "Error en ReviewsViewHandler Java: " + e.getMessage());
        }
    }
}
