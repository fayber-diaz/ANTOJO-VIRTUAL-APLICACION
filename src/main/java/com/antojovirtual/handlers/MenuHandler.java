package com.antojovirtual.handlers;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.util.*;

/**
 * MenuHandler - API JSON + Catalogo Visual Interactivo de 79 Platos y Postres en Neiva (100% Java)
 * Soporta:
 *   - GET /api/menu?options=1 (Grupos de modificadores desde `item_options`)
 *   - GET /api/menu?id=5 (Detalle de plato desde `menu_items`)
 *   - GET /api/menu?format=json (Lista filtrada de platos en JSON)
 *   - GET /menu o /api/menu en navegador (Catalogo visual Neumorfico al 100% zoom)
 */
public class MenuHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (HttpUtil.handleCorsPreflight(ex)) return;

        try {
            Map<String, String> query = HttpUtil.parseQueryParams(ex);

            // 1. Opciones de personalizacion (?options=1)
            if (query.containsKey("options")) {
                List<Map<String, Object>> allOptions = DatabaseManager.queryList(
                        "SELECT * FROM `item_options` ORDER BY `category`, `price` ASC"
                );

                Map<String, List<Map<String, Object>>> grouped = new LinkedHashMap<>();
                grouped.put("terminos", new ArrayList<>());
                grouped.put("panes", new ArrayList<>());
                grouped.put("toppings", new ArrayList<>());
                grouped.put("salsas", new ArrayList<>());
                grouped.put("sabores", new ArrayList<>());
                grouped.put("bases_dulces", new ArrayList<>());
                grouped.put("siropes", new ArrayList<>());
                grouped.put("toppings_dulces", new ArrayList<>());
                grouped.put("dulzura", new ArrayList<>());

                for (Map<String, Object> opt : allOptions) {
                    String cat = JsonUtil.getString(opt, "category", "topping");
                    String key;
                    switch (cat) {
                        case "termino": key = "terminos"; break;
                        case "pan": key = "panes"; break;
                        case "topping": key = "toppings"; break;
                        case "salsa": key = "salsas"; break;
                        case "sabor_helado": key = "sabores"; break;
                        case "base_dulce": key = "bases_dulces"; break;
                        case "sirope_dulce": key = "siropes"; break;
                        case "topping_dulce": key = "toppings_dulces"; break;
                        case "dulzura": key = "dulzura"; break;
                        default: key = "toppings"; break;
                    }

                    int price = JsonUtil.getInt(opt, "price", 0);
                    opt.put("id", JsonUtil.getInt(opt, "id", 0));
                    opt.put("price", price);
                    opt.put("is_default", JsonUtil.getInt(opt, "is_default", 0) == 1);
                    opt.put("price_formatted", price > 0 ? "+" + HttpUtil.formatCop(price) : "Gratis");

                    grouped.get(key).add(opt);
                }

                Map<String, Object> resp = new LinkedHashMap<>();
                resp.put("success", true);
                resp.put("status", "ok");
                resp.put("engine", "100% Java");
                resp.put("options", grouped);
                HttpUtil.sendJson(ex, 200, resp);
                return;
            }

            // 2. Detalle de un plato especifico (?id=...)
            if (query.containsKey("id")) {
                int dishId = 0;
                try { dishId = Integer.parseInt(query.get("id")); } catch (NumberFormatException ignored) {}

                Map<String, Object> dish = DatabaseManager.queryOne(
                        "SELECT m.*, r.name AS restaurant_name, r.delivery_time, r.delivery_fee, r.site_type " +
                        "FROM `menu_items` m JOIN `restaurants` r ON m.restaurant_id = r.id " +
                        "WHERE m.id = ? AND m.is_available = 1",
                        Collections.singletonList(dishId)
                );

                if (dish == null) {
                    Map<String, Object> err = new LinkedHashMap<>();
                    err.put("success", false);
                    err.put("error", "Plato no encontrado");
                    HttpUtil.sendJson(ex, 404, err);
                    return;
                }

                normalizeMenuItem(dish);
                Map<String, Object> resp = new LinkedHashMap<>();
                resp.put("success", true);
                resp.put("status", "ok");
                resp.put("engine", "100% Java");
                resp.put("data", dish);
                HttpUtil.sendJson(ex, 200, resp);
                return;
            }

            // 3. Listado de platos con filtros
            String category = query.getOrDefault("category", "all").trim();
            String search = query.getOrDefault("search", query.getOrDefault("q", "")).trim();
            int restaurantId = 0;
            try { restaurantId = Integer.parseInt(query.getOrDefault("restaurant_id", "0")); } catch (NumberFormatException ignored) {}

            StringBuilder sql = new StringBuilder(
                    "SELECT m.*, r.name AS restaurant_name, r.badge AS restaurant_badge, r.delivery_time, r.site_type, r.comuna AS restaurant_comuna " +
                    "FROM `menu_items` m JOIN `restaurants` r ON m.restaurant_id = r.id WHERE m.is_available = 1"
            );
            List<Object> params = new ArrayList<>();

            if ("comidas_rapidas".equalsIgnoreCase(category) || "salados".equalsIgnoreCase(category)) {
                sql.append(" AND m.dish_type = 'salado'");
            } else if ("dulces".equalsIgnoreCase(category) || "postres_all".equalsIgnoreCase(category)) {
                sql.append(" AND m.dish_type = 'dulce'");
            } else if (!"all".equalsIgnoreCase(category) && !"todos".equalsIgnoreCase(category) && !category.isEmpty()) {
                sql.append(" AND m.category = ?");
                params.add(category);
            }

            if (restaurantId > 0) {
                sql.append(" AND m.restaurant_id = ?");
                params.add(restaurantId);
            }

            if (!search.isEmpty()) {
                sql.append(" AND (m.name LIKE ? OR m.description LIKE ? OR m.tags LIKE ? OR r.name LIKE ?)");
                String like = "%" + search + "%";
                params.add(like);
                params.add(like);
                params.add(like);
                params.add(like);
            }

            sql.append(" ORDER BY m.is_popular DESC, m.id ASC");

            List<Map<String, Object>> dishes = DatabaseManager.queryList(sql.toString(), params);
            for (Map<String, Object> d : dishes) {
                normalizeMenuItem(d);
            }

            boolean forceHtml = ex.getRequestURI().getPath().toLowerCase().contains("/menu")
                    && !ex.getRequestURI().getPath().toLowerCase().contains("/api/")
                    && !"json".equalsIgnoreCase(query.get("format"));

            if (!forceHtml && !HttpUtil.prefersHtml(ex, query)) {
                Map<String, Object> resp = new LinkedHashMap<>();
                resp.put("success", true);
                resp.put("status", "ok");
                resp.put("engine", "100% Java");
                resp.put("count", dishes.size());
                resp.put("data", dishes);
                HttpUtil.sendJson(ex, 200, resp);
                return;
            }

            renderHtmlCatalog(ex, dishes);
        } catch (Exception e) {
            e.printStackTrace();
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("error", e.getMessage());
            HttpUtil.sendJson(ex, 500, err);
        }
    }

    private void normalizeMenuItem(Map<String, Object> d) {
        int id = JsonUtil.getInt(d, "id", 0);
        int restId = JsonUtil.getInt(d, "restaurant_id", 1);
        int price = JsonUtil.getInt(d, "price", 0);
        d.put("id", id);
        d.put("restaurant_id", restId);
        d.put("price", price);
        d.put("price_formatted", HttpUtil.formatCop(price));
        d.put("calories", JsonUtil.getInt(d, "calories", 550));
        d.put("is_popular", JsonUtil.getInt(d, "is_popular", 0) == 1);

        String tagsStr = JsonUtil.getString(d, "tags", "");
        List<String> tagsArr = new ArrayList<>();
        if (!tagsStr.isEmpty() && !"null".equalsIgnoreCase(tagsStr)) {
            for (String part : tagsStr.split(",")) {
                if (!part.trim().isEmpty()) tagsArr.add(part.trim());
            }
        }
        d.put("tags_array", tagsArr);

        String dishType = JsonUtil.getString(d, "dish_type", "");
        String cat = JsonUtil.getString(d, "category", "");
        if (dishType.isEmpty() || "null".equalsIgnoreCase(dishType)) {
            dishType = Arrays.asList("helados", "postres", "waffles", "malteadas").contains(cat) ? "dulce" : "salado";
            d.put("dish_type", dishType);
        }
    }

    private void renderHtmlCatalog(HttpExchange ex, List<Map<String, Object>> dishes) throws IOException {
        int totalSweet = 0;
        for (Map<String, Object> d : dishes) {
            if ("dulce".equalsIgnoreCase(JsonUtil.getString(d, "dish_type", ""))) {
                totalSweet++;
            }
        }
        int totalSavory = dishes.size() - totalSweet;

        StringBuilder sb = new StringBuilder(48000);
        sb.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\">");
        sb.append("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">");
        sb.append("<title>Catálogo Gastronómico (").append(dishes.size()).append(" Platos y Postres) — Antojo Virtual Java</title>");
        sb.append("<link href=\"https://fonts.googleapis.com/css2?family=Epilogue:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap\" rel=\"stylesheet\">");
        sb.append("<link rel=\"stylesheet\" href=\"/assets/css/style.css\">");
        sb.append("<style>");
        sb.append("html { font-size: 13.5px; }");
        sb.append(".cat-wrap { max-width: 1240px; margin: 22px auto; padding: 0 18px; }");
        sb.append(".cat-top { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px; }");
        sb.append(".cat-pills { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; padding:12px; border-radius:16px; background:var(--neu-bg); box-shadow: var(--neu-extruded); border:1px solid var(--color-border-subtle); }");
        sb.append(".pill-btn { padding:7px 13px; border-radius:999px; border:none; font-weight:800; font-size:0.8rem; cursor:pointer; background:var(--neu-bg); color:var(--color-on-surface-variant); box-shadow: var(--neu-extruded-sm); transition:all 0.2s; }");
        sb.append(".pill-btn.active { background:linear-gradient(135deg,#dc2626,#ea580c); color:#fff; }");
        sb.append(".menu-grid-visual { display:grid; grid-template-columns:repeat(auto-fill,minmax(255px,1fr)); gap:18px; }");
        sb.append(".dish-v-card { border-radius:18px; background:var(--neu-bg); box-shadow: var(--neu-extruded); overflow:hidden; display:flex; flex-direction:column; transition:transform 0.25s ease; border:1px solid var(--color-border-subtle); }");
        sb.append(".dish-v-card:hover { transform:translateY(-4px); }");
        sb.append(".dish-v-img { position:relative; height:155px; overflow:hidden; }");
        sb.append(".dish-v-img img { width:100%; height:100%; object-fit:cover; }");
        sb.append(".dish-v-badge { position:absolute; top:12px; left:12px; padding:5px 12px; border-radius:999px; font-size:0.74rem; font-weight:800; color:#fff; background:linear-gradient(135deg,#dc2626,#e11d48); }");
        sb.append(".dish-v-body { padding:15px; display:flex; flex-direction:column; gap:7px; flex:1; }");
        sb.append(".dish-v-rest { font-size:0.75rem; font-weight:800; color:#dc2626; text-transform:uppercase; letter-spacing:0.4px; }");
        sb.append(".dish-v-title { font-family:'Epilogue',sans-serif; font-size:1.02rem; font-weight:800; color:var(--color-on-surface); }");
        sb.append(".dish-v-desc { font-size:0.82rem; color:var(--color-on-surface-variant); line-height:1.42; }");
        sb.append(".dish-v-footer { display:flex; justify-content:space-between; align-items:center; padding-top:12px; margin-top:auto; border-top:1px solid var(--color-border-subtle); }");
        sb.append(".dish-v-price { font-family:'Epilogue',sans-serif; font-size:1.15rem; font-weight:900; color:#b70011; }");
        sb.append("</style></head><body><div class=\"cat-wrap\">");

        sb.append("<div class=\"cat-top\"><div>");
        sb.append("<div style=\"display:inline-block;padding:4px 12px;border-radius:999px;background:#fef2f2;color:#dc2626;font-weight:800;font-size:0.78rem;margin-bottom:6px;\">☕ 100% JAVA BACKEND • ").append(totalSavory).append(" SALADOS • ").append(totalSweet).append(" DULCES</div>");
        sb.append("<h1 style=\"font-family:'Epilogue';font-size:1.85rem;font-weight:900;\">Catálogo Completo: ").append(dishes.size()).append(" Platos, Asados y Postres</h1>");
        sb.append("<p style=\"color:var(--color-on-surface-variant);\">Hamburguesas artesanales, pizzas a la leña, carnes al barril, alitas, helados, waffles y repostería.</p>");
        sb.append("</div><div style=\"display:flex;gap:10px;flex-wrap:wrap;\">");
        sb.append("<a href=\"/\" class=\"btn-customize\" style=\"padding:10px 18px;text-decoration:none;border-radius:14px;font-weight:800;background:var(--color-primary-neu);color:#fff;\">🛒 Pedir en la App Principal</a>");
        sb.append("<a href=\"/restaurantes\" class=\"btn-admin-link\" style=\"padding:10px 16px;text-decoration:none;border-radius:14px;font-weight:700;\">🏪 Ver 40 Restaurantes</a>");
        sb.append("<a href=\"/api/menu?format=json\" class=\"btn-admin-link\" style=\"padding:10px 14px;text-decoration:none;border-radius:14px;font-size:0.82rem;\">{ } Ver JSON Puro</a>");
        sb.append("</div></div>");

        String[][] cats = {
                {"todos", "🍽️ Todos (" + dishes.size() + ")"},
                {"burgers", "🍔 Hamburguesas"},
                {"pizzas", "🍕 Pizzas & Calzones"},
                {"asados", "🥩 Asados"},
                {"salchipapas", "🍟 Salchipapas"},
                {"pollo", "🍗 Pollo Crispy"},
                {"mexicana", "🌮 Tacos & Tex-Mex"},
                {"perros", "🌭 Perros"},
                {"arepas", "🫓 Arepas"},
                {"desgranados", "🌽 Desgranados"},
                {"helados", "🍨 Helados"},
                {"postres", "🍰 Postres"},
                {"waffles", "🧇 Waffles"},
                {"malteadas", "🥤 Malteadas"}
        };

        sb.append("<div class=\"cat-pills\">");
        for (int i = 0; i < cats.length; i++) {
            sb.append("<button class=\"pill-btn").append(i == 0 ? " active" : "").append("\" onclick=\"filterCat('").append(cats[i][0]).append("', this)\">").append(cats[i][1]).append("</button>");
        }
        sb.append("<input type=\"search\" id=\"dishSearchInput\" placeholder=\"🔍 Buscar hamburguesa, pizza, asado, waffle, helado...\" oninput=\"applyDishFilter()\" class=\"form-input\" style=\"margin-left:auto;max-width:280px;\">");
        sb.append("</div>");

        sb.append("<div class=\"menu-grid-visual\">");
        for (Map<String, Object> d : dishes) {
            String catSlug = JsonUtil.getString(d, "category", "");
            String name = JsonUtil.getString(d, "name", "");
            String desc = JsonUtil.getString(d, "description", "");
            String restName = JsonUtil.getString(d, "restaurant_name", "Antojo Virtual Neiva");
            String restComuna = JsonUtil.getString(d, "restaurant_comuna", "Neiva");
            String badge = JsonUtil.getString(d, "badge", "");
            String imgUrl = JsonUtil.getString(d, "image", "");
            String priceFmt = JsonUtil.getString(d, "price_formatted", "$20.000");
            String prepTime = JsonUtil.getString(d, "prep_time", "15-20 min");
            int calories = JsonUtil.getInt(d, "calories", 500);
            boolean isSweet = "dulce".equalsIgnoreCase(JsonUtil.getString(d, "dish_type", ""));

            String searchStr = (name + " " + desc + " " + restName + " " + catSlug).toLowerCase();

            sb.append("<article class=\"dish-v-card\" data-cat=\"").append(HttpUtil.escapeHtml(catSlug))
              .append("\" data-search=\"").append(HttpUtil.escapeHtml(searchStr)).append("\">");
            sb.append("<div class=\"dish-v-img\">");
            sb.append("<img src=\"").append(HttpUtil.escapeHtml(imgUrl)).append("\" alt=\"").append(HttpUtil.escapeHtml(name)).append("\" loading=\"lazy\">");
            if (!badge.isEmpty() && !"null".equalsIgnoreCase(badge)) {
                sb.append("<span class=\"dish-v-badge\">").append(HttpUtil.escapeHtml(badge)).append("</span>");
            }
            sb.append("</div>");
            sb.append("<div class=\"dish-v-body\">");
            sb.append("<div class=\"dish-v-rest\">🏪 ").append(HttpUtil.escapeHtml(restName)).append(" • ").append(HttpUtil.escapeHtml(restComuna)).append("</div>");
            sb.append("<div class=\"dish-v-title\">").append(HttpUtil.escapeHtml(name)).append("</div>");
            sb.append("<div class=\"dish-v-desc\">").append(HttpUtil.escapeHtml(desc)).append("</div>");
            sb.append("<div style=\"font-size:0.76rem;color:var(--color-on-surface-variant);margin-top:4px;\">");
            sb.append("⏱️ ").append(HttpUtil.escapeHtml(prepTime)).append(" • 🔥 ").append(calories).append(" kcal • ");
            sb.append(isSweet ? "🍓 Personalización Dulce" : "🥓 Personalización Salada");
            sb.append("</div>");
            sb.append("<div class=\"dish-v-footer\">");
            sb.append("<div class=\"dish-v-price\">").append(HttpUtil.escapeHtml(priceFmt)).append("</div>");
            sb.append("<a href=\"/\" class=\"btn-customize\" style=\"padding:6px 14px;border-radius:999px;font-size:0.8rem;text-decoration:none;font-weight:800;background:var(--color-primary-neu);color:#fff;\">+ Ordenar en App</a>");
            sb.append("</div></div></article>");
        }
        sb.append("</div></div>");

        sb.append("<script>");
        sb.append("let activeCat = 'todos';");
        sb.append("function filterCat(slug, btn) { activeCat = slug; document.querySelectorAll('.pill-btn').forEach(b => b.classList.remove('active')); btn.classList.add('active'); applyDishFilter(); }");
        sb.append("function applyDishFilter() { const q = (document.getElementById('dishSearchInput').value || '').toLowerCase().trim(); document.querySelectorAll('.dish-v-card').forEach(card => { const okCat = activeCat === 'todos' || card.dataset.cat === activeCat; const okSearch = !q || card.dataset.search.includes(q); card.style.display = (okCat && okSearch) ? 'flex' : 'none'; }); }");
        sb.append("</script></body></html>");

        HttpUtil.sendHtml(ex, 200, sb.toString());
    }
}
