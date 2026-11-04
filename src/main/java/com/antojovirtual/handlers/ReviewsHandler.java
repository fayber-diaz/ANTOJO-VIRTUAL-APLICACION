package com.antojovirtual.handlers;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.util.*;

/**
 * ReviewsHandler - API de Calificaciones y Resenas Reales de Clientes (100% Java)
 * Soporta: GET /api/reviews | POST /api/reviews
 */
public class ReviewsHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (HttpUtil.handleCorsPreflight(ex)) return;

        Map<String, String> query = HttpUtil.parseQueryParams(ex);
        String method = ex.getRequestMethod().toUpperCase();

        if ("GET".equals(method) && HttpUtil.prefersHtml(ex, query) && !query.containsKey("json") && !query.containsKey("raw")) {
            HttpUtil.sendRedirect(ex, "/reviews");
            return;
        }

        try {
            if ("GET".equals(method)) {
                int restId = 1;
                try { restId = Integer.parseInt(query.getOrDefault("restaurant_id", "1")); } catch (NumberFormatException ignored) {}

                List<Map<String, Object>> reviews = DatabaseManager.queryList(
                        "SELECT r.*, o.order_code FROM `reviews` r LEFT JOIN `orders` o ON r.order_id = o.id " +
                        "WHERE r.restaurant_id = ? AND r.is_approved = 1 ORDER BY r.id DESC LIMIT 20",
                        Collections.singletonList(restId)
                );

                Map<String, Object> statsRow = DatabaseManager.queryOne(
                        "SELECT COUNT(*) AS count, AVG(rating) AS avg_rating FROM `reviews` WHERE restaurant_id = ? AND is_approved = 1",
                        Collections.singletonList(restId)
                );

                Map<String, Object> stats = new LinkedHashMap<>();
                stats.put("total_reviews", statsRow != null ? JsonUtil.getInt(statsRow, "count", 0) : 0);
                stats.put("average_rating", statsRow != null ? Math.round(JsonUtil.getDouble(statsRow, "avg_rating", 4.9) * 10.0) / 10.0 : 4.9);

                Map<String, Object> resp = new LinkedHashMap<>();
                resp.put("success", true);
                resp.put("engine", "100% Java");
                resp.put("stats", stats);
                resp.put("reviews", reviews);
                HttpUtil.sendJson(ex, 200, resp);
                return;
            }

            if ("POST".equals(method)) {
                Map<String, Object> input = JsonUtil.parseObject(HttpUtil.readBody(ex));
                String orderCode = JsonUtil.getString(input, "order_code", "").trim();
                int restaurantId = Math.max(1, JsonUtil.getInt(input, "restaurant_id", 1));
                String customerName = JsonUtil.getString(input, "customer_name", "Cliente Anónimo").trim();
                double rating = Math.max(1.0, Math.min(5.0, JsonUtil.getDouble(input, "rating", 5.0)));
                String comment = JsonUtil.getString(input, "comment", "").trim();
                List<Object> tags = JsonUtil.getList(input, "tags");
                if (!tags.isEmpty()) {
                    List<String> tagStrs = new ArrayList<>();
                    for (Object t : tags) tagStrs.add(String.valueOf(t));
                    comment = "[Destacados: " + String.join(", ", tagStrs) + "] " + comment;
                }
                if (comment.isEmpty()) {
                    comment = "¡Excelente servicio y comida deliciosa de Neiva!";
                }

                Integer orderId = null;
                if (!orderCode.isEmpty()) {
                    Map<String, Object> foundOrder = DatabaseManager.queryOne(
                            "SELECT id FROM `orders` WHERE order_code = ? LIMIT 1",
                            Collections.singletonList(orderCode)
                    );
                    if (foundOrder != null) {
                        orderId = JsonUtil.getInt(foundOrder, "id", 0);
                    }
                }

                long reviewId = DatabaseManager.insertAndGetId(
                        "INSERT INTO `reviews` (`order_id`, `restaurant_id`, `customer_name`, `rating`, `comment`, `is_approved`, `created_at`) VALUES (?, ?, ?, ?, ?, 1, NOW())",
                        Arrays.asList(orderId, restaurantId, customerName, rating, comment)
                );

                Map<String, Object> resp = new LinkedHashMap<>();
                resp.put("success", true);
                resp.put("engine", "100% Java");
                resp.put("message", "¡Muchas gracias por tu calificación! Tu opinión ayuda a mejorar la gastronomía en Neiva.");
                resp.put("review_id", reviewId);
                HttpUtil.sendJson(ex, 200, resp);
                return;
            }

            HttpUtil.sendError(ex, 405, "Metodo no permitido.");
        } catch (Exception e) {
            e.printStackTrace();
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("error", e.getMessage());
            HttpUtil.sendJson(ex, 500, err);
        }
    }
}
