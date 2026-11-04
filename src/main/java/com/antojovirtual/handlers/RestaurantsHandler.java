package com.antojovirtual.handlers;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.util.*;

/**
 * RestaurantsHandler - API JSON + Directorio Visual de los 40 Sitios de Neiva (100% Java)
 * Soporta:
 *   - GET /api/restaurants?format=json (JSON para el frontend o integraciones)
 *   - GET /restaurantes o /api/restaurants en navegador (Vista HTML Neumorfica al 100% zoom)
 */
public class RestaurantsHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (HttpUtil.handleCorsPreflight(ex)) return;

        try {
            Map<String, String> query = HttpUtil.parseQueryParams(ex);
            String category = query.getOrDefault("category", query.getOrDefault("type", "all")).trim();
            String search = query.getOrDefault("search", query.getOrDefault("q", "")).trim();
            String comuna = query.getOrDefault("comuna", "").trim();

            StringBuilder sql = new StringBuilder("SELECT * FROM `restaurants` WHERE `is_open` = 1");
            List<Object> params = new ArrayList<>();

            if ("comidas_rapidas".equalsIgnoreCase(category) || "salados".equalsIgnoreCase(category) || "fast_food".equalsIgnoreCase(category)) {
                sql.append(" AND `site_type` = 'comidas_rapidas'");
            } else if ("dulces".equalsIgnoreCase(category) || "postres_all".equalsIgnoreCase(category) || "sweet".equalsIgnoreCase(category)) {
                sql.append(" AND `site_type` = 'dulces'");
            } else if (!"all".equalsIgnoreCase(category) && !category.isEmpty()) {
                sql.append(" AND `category` = ?");
                params.add(category);
            }

            if (!comuna.isEmpty() && !"all".equalsIgnoreCase(comuna)) {
                sql.append(" AND `comuna` LIKE ?");
                params.add("%" + comuna + "%");
            }

            if (!search.isEmpty()) {
                sql.append(" AND (`name` LIKE ? OR `description` LIKE ? OR `star_dish` LIKE ? OR `address` LIKE ?)");
                String like = "%" + search + "%";
                params.add(like);
                params.add(like);
                params.add(like);
                params.add(like);
            }

            sql.append(" ORDER BY `rating` DESC, `reviews_count` DESC");

            List<Map<String, Object>> restaurants = DatabaseManager.queryList(sql.toString(), params);
            for (Map<String, Object> r : restaurants) {
                r.put("id", JsonUtil.getInt(r, "id", 0));
                r.put("rating", JsonUtil.getDouble(r, "rating", 4.8));
                r.put("reviews_count", JsonUtil.getInt(r, "reviews_count", 100));
                int delFee = JsonUtil.getInt(r, "delivery_fee", 3500);
                int starPrice = JsonUtil.getInt(r, "star_price", 20000);
                r.put("delivery_fee", delFee);
                r.put("star_price", starPrice);
                r.put("delivery_fee_formatted", HttpUtil.formatCop(delFee));
                r.put("star_price_formatted", HttpUtil.formatCop(starPrice));
            }

            boolean forceHtml = ex.getRequestURI().getPath().toLowerCase().contains("restaurantes") && !"json".equalsIgnoreCase(query.get("format"));
            if (!forceHtml && !HttpUtil.prefersHtml(ex, query)) {
                Map<String, Object> resp = new LinkedHashMap<>();
                resp.put("success", true);
                resp.put("status", "ok");
                resp.put("engine", "100% Java");
                resp.put("count", restaurants.size());
                resp.put("data", restaurants);
                HttpUtil.sendJson(ex, 200, resp);
                return;
            }

            renderHtmlDirectory(ex, restaurants);
        } catch (Exception e) {
            e.printStackTrace();
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("error", e.getMessage());
            HttpUtil.sendJson(ex, 500, err);
        }
    }

    private void renderHtmlDirectory(HttpExchange ex, List<Map<String, Object>> restaurants) throws IOException {
        int totalFast = 0;
        int totalSweet = 0;
        for (Map<String, Object> r : restaurants) {
            if ("dulces".equalsIgnoreCase(JsonUtil.getString(r, "site_type", ""))) {
                totalSweet++;
            } else {
                totalFast++;
            }
        }

        StringBuilder sb = new StringBuilder(32768);
        sb.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\">");
        sb.append("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">");
        sb.append("<title>Directorio de ").append(restaurants.size()).append(" Sitios en Neiva — Antojo Virtual (100% Java)</title>");
        sb.append("<link href=\"https://fonts.googleapis.com/css2?family=Epilogue:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap\" rel=\"stylesheet\">");
        sb.append("<link rel=\"stylesheet\" href=\"/assets/css/style.css\">");
        sb.append("<style>");
        sb.append("html { font-size: 13.5px; }");
        sb.append(".dir-wrap { max-width: 1240px; margin: 22px auto; padding: 0 18px; }");
        sb.append(".dir-top { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; }");
        sb.append(".dir-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-bottom:20px; }");
        sb.append(".stat-box { padding:16px; border-radius:16px; background:var(--neu-bg); box-shadow: var(--neu-extruded); text-align:center; border:1px solid var(--color-border-subtle); }");
        sb.append(".stat-num { font-family:'Epilogue',sans-serif; font-size:1.75rem; font-weight:900; color:#dc2626; }");
        sb.append(".dir-toolbar { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; padding:14px 18px; border-radius:18px; background:var(--neu-bg); box-shadow: var(--neu-extruded); border:1px solid var(--color-border-subtle); }");
        sb.append(".dir-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(265px,1fr)); gap:18px; }");
        sb.append(".site-card { border-radius:18px; background:var(--neu-bg); box-shadow: var(--neu-extruded); overflow:hidden; display:flex; flex-direction:column; transition:transform 0.25s ease; border:1px solid var(--color-border-subtle); }");
        sb.append(".site-card:hover { transform:translateY(-4px); }");
        sb.append(".site-img { position:relative; height:150px; overflow:hidden; }");
        sb.append(".site-img img { width:100%; height:100%; object-fit:cover; }");
        sb.append(".site-badge { position:absolute; top:12px; left:12px; padding:5px 12px; border-radius:999px; font-size:0.75rem; font-weight:800; color:#fff; backdrop-filter:blur(6px); }");
        sb.append(".site-rating { position:absolute; top:12px; right:12px; padding:5px 10px; border-radius:999px; font-size:0.78rem; font-weight:800; background:rgba(255,255,255,0.92); color:#1E242B; }");
        sb.append(".site-body { padding:15px; display:flex; flex-direction:column; gap:7px; flex:1; }");
        sb.append(".site-title { font-family:'Epilogue',sans-serif; font-size:1.05rem; font-weight:800; color:var(--color-on-surface); }");
        sb.append(".site-spec { font-size:0.84rem; font-weight:800; color:#dc2626; }");
        sb.append(".site-meta { font-size:0.8rem; color:var(--color-on-surface-variant); line-height:1.4; }");
        sb.append(".site-footer { display:flex; justify-content:space-between; align-items:center; padding-top:12px; margin-top:auto; border-top:1px solid var(--color-border-subtle); }");
        sb.append(".filter-btn { padding:8px 16px; border-radius:999px; border:none; font-weight:800; cursor:pointer; background:var(--neu-bg); color:var(--color-on-surface-variant); box-shadow: var(--neu-extruded-sm); transition:all 0.2s; }");
        sb.append(".filter-btn.active { background:linear-gradient(135deg,#dc2626,#ea580c); color:#fff; }");
        sb.append("</style></head><body><div class=\"dir-wrap\">");

        sb.append("<div class=\"dir-top\"><div>");
        sb.append("<div style=\"display:inline-block;padding:4px 12px;border-radius:999px;background:#fef2f2;color:#dc2626;font-weight:800;font-size:0.78rem;margin-bottom:6px;\">☕ 100% JAVA BACKEND • NEIVA, HUILA</div>");
        sb.append("<h1 style=\"font-family:'Epilogue';font-size:1.85rem;font-weight:900;\">Directorio Oficial de ").append(restaurants.size()).append(" Sitios en Neiva</h1>");
        sb.append("<p style=\"color:var(--color-on-surface-variant);\">Explora los mejores restaurantes de comidas rápidas, asados, pizzerías, heladerías y reposterías.</p>");
        sb.append("</div><div style=\"display:flex;gap:10px;flex-wrap:wrap;\">");
        sb.append("<a href=\"/\" class=\"btn-customize\" style=\"padding:10px 18px;text-decoration:none;border-radius:14px;font-weight:800;background:var(--color-primary-neu);color:#fff;\">🍔 Ir a la App Principal</a>");
        sb.append("<a href=\"/menu\" class=\"btn-admin-link\" style=\"padding:10px 16px;text-decoration:none;border-radius:14px;font-weight:700;\">📋 Ver Catálogo (79 Platos)</a>");
        sb.append("<a href=\"/api/restaurants?format=json\" class=\"btn-admin-link\" style=\"padding:10px 14px;text-decoration:none;border-radius:14px;font-size:0.82rem;\">{ } Ver JSON Puro</a>");
        sb.append("</div></div>");

        sb.append("<div class=\"dir-stats\">");
        sb.append("<div class=\"stat-box\"><div class=\"stat-num\">").append(restaurants.size()).append("</div><div style=\"font-size:0.84rem;color:var(--color-on-surface-variant);font-weight:700;\">Sitios Aliados en Neiva</div></div>");
        sb.append("<div class=\"stat-box\"><div class=\"stat-num\" style=\"color:#dc2626;\">").append(totalFast).append("</div><div style=\"font-size:0.84rem;color:var(--color-on-surface-variant);font-weight:700;\">🍔 Comidas Rápidas y Asados</div></div>");
        sb.append("<div class=\"stat-box\"><div class=\"stat-num\" style=\"color:#e11d48;\">").append(totalSweet).append("</div><div style=\"font-size:0.84rem;color:var(--color-on-surface-variant);font-weight:700;\">🍦 Heladerías y Dulces</div></div>");
        sb.append("<div class=\"stat-box\"><div class=\"stat-num\" style=\"color:#16a34a;\">4.9 ★</div><div style=\"font-size:0.84rem;color:var(--color-on-surface-variant);font-weight:700;\">Calificación Promedio</div></div>");
        sb.append("</div>");

        sb.append("<div class=\"dir-toolbar\"><div style=\"display:flex;gap:10px;flex-wrap:wrap;\">");
        sb.append("<button class=\"filter-btn active\" onclick=\"filterSites('all', this)\">Todos (").append(restaurants.size()).append(")</button>");
        sb.append("<button class=\"filter-btn\" onclick=\"filterSites('comidas_rapidas', this)\">🍔 Comidas Rápidas (").append(totalFast).append(")</button>");
        sb.append("<button class=\"filter-btn\" onclick=\"filterSites('dulces', this)\">🍦 Dulces y Postres (").append(totalSweet).append(")</button>");
        sb.append("</div>");
        sb.append("<input type=\"search\" id=\"siteSearch\" placeholder=\"🔍 Buscar por nombre, comuna o plato estrella...\" oninput=\"searchSites()\" class=\"form-input\" style=\"max-width:320px;\">");
        sb.append("</div>");

        sb.append("<div class=\"dir-grid\" id=\"sitesGrid\">");
        for (Map<String, Object> r : restaurants) {
            String siteType = JsonUtil.getString(r, "site_type", "comidas_rapidas");
            boolean isSweet = "dulces".equalsIgnoreCase(siteType);
            String badgeBg = isSweet ? "rgba(225, 29, 72, 0.9)" : "rgba(220, 38, 38, 0.9)";
            String badgeText = isSweet ? "🍦 Dulces & Postres" : "🍔 Comida Rápida";
            String name = JsonUtil.getString(r, "name", "");
            String starDish = JsonUtil.getString(r, "star_dish", "");
            String desc = JsonUtil.getString(r, "description", "");
            String comuna = JsonUtil.getString(r, "comuna", "Neiva");
            String address = JsonUtil.getString(r, "address", "");
            String deliveryTime = JsonUtil.getString(r, "delivery_time", "25-35 min");
            String imageUrl = JsonUtil.getString(r, "image", "");
            double rating = JsonUtil.getDouble(r, "rating", 4.8);
            int reviewsCount = JsonUtil.getInt(r, "reviews_count", 100);
            String delFeeFmt = JsonUtil.getString(r, "delivery_fee_formatted", "$3.500");
            String starPriceFmt = JsonUtil.getString(r, "star_price_formatted", "$22.000");
            int id = JsonUtil.getInt(r, "id", 1);

            String searchIndex = (name + " " + starDish + " " + comuna + " " + desc + " " + address).toLowerCase();

            sb.append("<article class=\"site-card\" data-type=\"").append(HttpUtil.escapeHtml(siteType))
              .append("\" data-search=\"").append(HttpUtil.escapeHtml(searchIndex)).append("\">");
            sb.append("<div class=\"site-img\">");
            sb.append("<img src=\"").append(HttpUtil.escapeHtml(imageUrl)).append("\" alt=\"").append(HttpUtil.escapeHtml(name)).append("\" loading=\"lazy\">");
            sb.append("<span class=\"site-badge\" style=\"background:").append(badgeBg).append(";\">").append(badgeText).append("</span>");
            sb.append("<span class=\"site-rating\">★ ").append(String.format(Locale.US, "%.1f", rating)).append(" (").append(reviewsCount).append(")</span>");
            sb.append("</div>");
            sb.append("<div class=\"site-body\">");
            sb.append("<div class=\"site-title\">").append(HttpUtil.escapeHtml(name)).append("</div>");
            sb.append("<div class=\"site-spec\">⭐ ").append(HttpUtil.escapeHtml(starDish)).append(" (").append(HttpUtil.escapeHtml(starPriceFmt)).append(")</div>");
            sb.append("<div class=\"site-meta\">").append(HttpUtil.escapeHtml(desc)).append("<br>📍 <strong>").append(HttpUtil.escapeHtml(comuna)).append("</strong> — ").append(HttpUtil.escapeHtml(address)).append("<br>⏱️ ").append(HttpUtil.escapeHtml(deliveryTime)).append(" • 🛵 Envío ").append(HttpUtil.escapeHtml(delFeeFmt)).append("</div>");
            sb.append("<div class=\"site-footer\">");
            sb.append("<span style=\"font-size:0.78rem;font-weight:800;color:#16a34a;\">● Abierto ahora</span>");
            sb.append("<a href=\"/menu?restaurant_id=").append(id).append("\" class=\"btn-customize\" style=\"padding:6px 14px;border-radius:999px;font-size:0.8rem;text-decoration:none;font-weight:800;background:var(--color-primary-neu);color:#fff;\">Ver Platos →</a>");
            sb.append("</div></div></article>");
        }
        sb.append("</div></div>");

        sb.append("<script>");
        sb.append("let currentType = 'all';");
        sb.append("function filterSites(type, btn) { currentType = type; document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active')); btn.classList.add('active'); applyFilters(); }");
        sb.append("function searchSites() { applyFilters(); }");
        sb.append("function applyFilters() { const q = (document.getElementById('siteSearch').value || '').toLowerCase().trim(); document.querySelectorAll('.site-card').forEach(card => { const matchType = currentType === 'all' || card.dataset.type === currentType; const matchSearch = !q || card.dataset.search.includes(q); card.style.display = (matchType && matchSearch) ? 'flex' : 'none'; }); }");
        sb.append("</script></body></html>");

        HttpUtil.sendHtml(ex, 200, sb.toString());
    }
}
