package com.antojovirtual.views;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.config.LiquibaseManager;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.util.*;

/**
 * InstallViewHandler - Panel de Diagnóstico MySQL (XAMPP) y Control de Migraciones Liquibase (100% Java SSR)
 * Rutas: /install | /liquibase | /api/liquibase
 */
public class InstallViewHandler implements HttpHandler {

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (HttpUtil.handleCorsPreflight(ex)) return;

        try {
            Map<String, String> query = HttpUtil.parseQueryParams(ex);
            String path = ex.getRequestURI().getPath().toLowerCase();

            String message = "";
            if ("POST".equalsIgnoreCase(ex.getRequestMethod())) {
                Map<String, String> form = HttpUtil.parseFormUrlEncoded(HttpUtil.readBody(ex));
                String action = form.getOrDefault("action", "");
                if ("reset_db".equals(action)) {
                    boolean ok = DatabaseManager.resetAndSeedDatabase();
                    message = ok
                            ? "✅ Base de datos MySQL 'antojo_virtual' reinstalada y sincronizada con Liquibase v" + LiquibaseManager.LIQUIBASE_VERSION + " exitosamente."
                            : "⚠️ No se encontró database/antojo_virtual.sql.";
                } else if ("run_liquibase".equals(action)) {
                    Map<String, Object> migRes = LiquibaseManager.runMigrations(true);
                    int applied = JsonUtil.getInt(migRes, "applied_count", 0);
                    message = "✅ Liquibase Update completado en MySQL (XAMPP): " + applied + " changesets ejecutados y verificados en DATABASECHANGELOG.";
                }
            }

            List<Map<String, Object>> changelogHistory = LiquibaseManager.getChangelogHistory();
            Map<String, Object> lockStatus = LiquibaseManager.getLockStatus();

            // Si es petición API JSON (/api/liquibase o ?format=json)
            if (path.contains("/api/liquibase") || "json".equalsIgnoreCase(query.get("format"))) {
                Map<String, Object> resp = new LinkedHashMap<>();
                resp.put("success", true);
                resp.put("engine", DatabaseManager.getEngineStatus());
                resp.put("liquibase_version", LiquibaseManager.LIQUIBASE_VERSION);
                resp.put("master_changelog", LiquibaseManager.MASTER_CHANGELOG);
                resp.put("lock_status", lockStatus);
                resp.put("changesets_executed", changelogHistory.size());
                resp.put("databasechangelog", changelogHistory);
                HttpUtil.sendJson(ex, 200, resp);
                return;
            }

            List<Map<String, Object>> tables = DatabaseManager.queryList(
                    "SELECT TABLE_NAME AS table_name, TABLE_ROWS AS table_rows " +
                    "FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'antojo_virtual' ORDER BY TABLE_NAME ASC"
            );

            Map<String, Object> counts = DatabaseManager.queryOne(
                    "SELECT " +
                    "(SELECT COUNT(*) FROM `restaurants`) AS rest_count, " +
                    "(SELECT COUNT(*) FROM `menu_items`) AS dish_count, " +
                    "(SELECT COUNT(*) FROM `menu_items` WHERE `dish_type` = 'salado') AS savory_count, " +
                    "(SELECT COUNT(*) FROM `menu_items` WHERE `dish_type` = 'dulce') AS sweet_count, " +
                    "(SELECT COUNT(*) FROM `item_options`) AS options_count, " +
                    "(SELECT COUNT(*) FROM `orders`) AS order_count"
            );

            int restCount = counts != null ? JsonUtil.getInt(counts, "rest_count", 0) : 0;
            int dishCount = counts != null ? JsonUtil.getInt(counts, "dish_count", 0) : 0;
            int savoryCount = counts != null ? JsonUtil.getInt(counts, "savory_count", 0) : 0;
            int sweetCount = counts != null ? JsonUtil.getInt(counts, "sweet_count", 0) : 0;
            int optionsCount = counts != null ? JsonUtil.getInt(counts, "options_count", 0) : 0;
            int orderCount = counts != null ? JsonUtil.getInt(counts, "order_count", 0) : 0;

            StringBuilder sb = new StringBuilder(32768);
            sb.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\">");
            sb.append("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">");
            sb.append("<title>Liquibase + MySQL XAMPP — Antojo Virtual Java</title>");
            sb.append("<link href=\"https://fonts.googleapis.com/css2?family=Epilogue:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap\" rel=\"stylesheet\">");
            sb.append("<link rel=\"stylesheet\" href=\"/assets/css/style.css\">");
            sb.append("<style>");
            sb.append("html { font-size: 13.5px; }");
            sb.append(".inst-wrap { max-width: 1180px; margin: 24px auto; padding: 0 18px; }");
            sb.append(".inst-card { padding: 22px; border-radius: 22px; background: var(--neu-bg); box-shadow: var(--neu-extruded); border:1px solid var(--color-border-subtle); margin-bottom: 20px; }");
            sb.append(".tbl-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-top: 14px; }");
            sb.append(".tbl-pill { padding: 12px 14px; border-radius: 14px; background: var(--neu-bg); box-shadow: var(--neu-inset-sm); border:1px solid var(--color-border-subtle); display:flex; justify-content:space-between; align-items:center; font-size:0.84rem; font-weight:700; }");
            sb.append(".lb-table { width:100%; border-collapse:collapse; margin-top:14px; font-size:0.82rem; }");
            sb.append(".lb-table th, .lb-table td { padding:10px 12px; text-align:left; border-bottom:1px solid var(--color-border-subtle); }");
            sb.append(".lb-table th { font-weight:800; color:var(--color-on-surface-variant); text-transform:uppercase; font-size:0.74rem; }");
            sb.append(".badge-exec { display:inline-block; padding:3px 9px; border-radius:999px; background:#dcfce7; color:#166534; font-weight:800; font-size:0.72rem; }");
            sb.append("</style></head><body><div class=\"inst-wrap\">");

            sb.append("<div class=\"inst-card\" style=\"display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;\"><div>");
            sb.append("<div style=\"display:inline-block;padding:4px 12px;border-radius:999px;background:#fef2f2;color:#dc2626;font-weight:800;font-size:0.78rem;margin-bottom:6px;\">☕ 100% JAVA + MYSQL (XAMPP) + LIQUIBASE v").append(LiquibaseManager.LIQUIBASE_VERSION).append("</div>");
            sb.append("<h1 style=\"font-family:'Epilogue';font-size:1.85rem;font-weight:900;margin-top:6px;\">Control de Base de Datos MySQL & Migraciones Liquibase</h1>");
            sb.append("<p style=\"color:var(--color-on-surface-variant);\">Motor: <strong>").append(HttpUtil.escapeHtml(DatabaseManager.getEngineStatus())).append("</strong> • Master Changelog: <code>").append(LiquibaseManager.MASTER_CHANGELOG).append("</code></p>");
            sb.append("</div><div style=\"display:flex;gap:10px;flex-wrap:wrap;\">");
            sb.append("<a href=\"/\" class=\"btn-customize\" style=\"padding:10px 18px;text-decoration:none;border-radius:14px;font-weight:800;background:var(--color-primary-neu);color:#fff;\">🍔 Ir a la Aplicación</a>");
            sb.append("<a href=\"/api/liquibase?format=json\" target=\"_blank\" class=\"btn-admin-link\" style=\"padding:10px 14px;text-decoration:none;border-radius:14px;font-weight:700;\">{ } JSON Liquibase</a>");
            sb.append("<a href=\"/admin\" class=\"btn-admin-link\" style=\"padding:10px 16px;text-decoration:none;border-radius:14px;font-weight:700;\">👨‍🍳 Panel Admin</a>");
            sb.append("</div></div>");

            if (!message.isEmpty()) {
                sb.append("<div class=\"inst-card\" style=\"background:rgba(16,185,129,0.16);color:#065F46;font-weight:800;\">")
                  .append(HttpUtil.escapeHtml(message)).append("</div>");
            }

            sb.append("<div style=\"display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:14px;margin-bottom:20px;\">");
            sb.append("<div class=\"inst-card\" style=\"text-align:center;margin:0;\"><div style=\"font-size:1.8rem;font-weight:900;color:#dc2626;\">").append(restCount).append("</div><div style=\"font-size:0.8rem;font-weight:700;color:var(--color-on-surface-variant);\">Sitios en Neiva</div></div>");
            sb.append("<div class=\"inst-card\" style=\"text-align:center;margin:0;\"><div style=\"font-size:1.8rem;font-weight:900;color:#ea580c;\">").append(savoryCount).append("</div><div style=\"font-size:0.8rem;font-weight:700;color:var(--color-on-surface-variant);\">Comidas Rápidas</div></div>");
            sb.append("<div class=\"inst-card\" style=\"text-align:center;margin:0;\"><div style=\"font-size:1.8rem;font-weight:900;color:#e11d48;\">").append(sweetCount).append("</div><div style=\"font-size:0.8rem;font-weight:700;color:var(--color-on-surface-variant);\">Dulces & Postres</div></div>");
            sb.append("<div class=\"inst-card\" style=\"text-align:center;margin:0;\"><div style=\"font-size:1.8rem;font-weight:900;color:#855300;\">").append(optionsCount).append("</div><div style=\"font-size:0.8rem;font-weight:700;color:var(--color-on-surface-variant);\">Toppings & Salsas</div></div>");
            sb.append("<div class=\"inst-card\" style=\"text-align:center;margin:0;\"><div style=\"font-size:1.8rem;font-weight:900;color:#10b981;\">").append(changelogHistory.size()).append("</div><div style=\"font-size:0.8rem;font-weight:700;color:var(--color-on-surface-variant);\">Changesets Liquibase</div></div>");
            sb.append("<div class=\"inst-card\" style=\"text-align:center;margin:0;\"><div style=\"font-size:1.8rem;font-weight:900;color:#0284c7;\">").append(orderCount).append("</div><div style=\"font-size:0.8rem;font-weight:700;color:var(--color-on-surface-variant);\">Pedidos MySQL</div></div>");
            sb.append("</div>");

            // Tabla Liquibase DATABASECHANGELOG
            sb.append("<div class=\"inst-card\">");
            sb.append("<div style=\"display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;\">");
            sb.append("<div><h2 style=\"font-family:'Epilogue';font-size:1.25rem;margin:0;\">💧 Historial Oficial Liquibase (`DATABASECHANGELOG`)</h2>");
            sb.append("<span style=\"font-size:0.8rem;color:var(--color-on-surface-variant);\">Control de versiones de esquema, semillas de productos, catálogo de toppings y rutas GPS de Neiva.</span></div>");
            sb.append("<div style=\"display:flex;gap:10px;\">");
            sb.append("<form method=\"POST\" action=\"/install\" style=\"margin:0;\"><input type=\"hidden\" name=\"action\" value=\"run_liquibase\">");
            sb.append("<button type=\"submit\" class=\"btn-customize\" style=\"padding:9px 16px;border-radius:12px;font-weight:800;background:#16a34a;color:#fff;border:none;cursor:pointer;\">⚡ Ejecutar Liquibase Update</button></form>");
            sb.append("<form method=\"POST\" action=\"/install\" style=\"margin:0;\" onsubmit=\"return confirm('¿Reinstalar semilla completa y aplicar todos los changesets de Liquibase?');\"><input type=\"hidden\" name=\"action\" value=\"reset_db\">");
            sb.append("<button type=\"submit\" class=\"btn-admin-link\" style=\"padding:9px 16px;border-radius:12px;font-weight:800;color:#dc2626;cursor:pointer;\">🔄 Reinstalar BD + Liquibase</button></form>");
            sb.append("</div></div>");

            sb.append("<div style=\"overflow-x:auto;\"><table class=\"lb-table\">");
            sb.append("<thead><tr><th>Orden</th><th>ID Changeset</th><th>Autor</th><th>Archivo Changelog</th><th>Estado</th><th>MD5 Checksum</th><th>Fecha Ejecución</th><th>Comentario</th></tr></thead><tbody>");
            for (Map<String, Object> row : changelogHistory) {
                sb.append("<tr>");
                sb.append("<td><strong>#").append(HttpUtil.escapeHtml(JsonUtil.getString(row, "ORDEREXECUTED", "1"))).append("</strong></td>");
                sb.append("<td style=\"font-weight:800;color:#dc2626;\">").append(HttpUtil.escapeHtml(JsonUtil.getString(row, "ID", ""))).append("</td>");
                sb.append("<td>").append(HttpUtil.escapeHtml(JsonUtil.getString(row, "AUTHOR", ""))).append("</td>");
                sb.append("<td><code>").append(HttpUtil.escapeHtml(JsonUtil.getString(row, "FILENAME", ""))).append("</code></td>");
                sb.append("<td><span class=\"badge-exec\">").append(HttpUtil.escapeHtml(JsonUtil.getString(row, "EXECTYPE", "EXECUTED"))).append("</span></td>");
                sb.append("<td><code style=\"font-size:0.74rem;\">").append(HttpUtil.escapeHtml(JsonUtil.getString(row, "MD5SUM", ""))).append("</code></td>");
                sb.append("<td>").append(HttpUtil.escapeHtml(JsonUtil.getString(row, "DATEEXECUTED", ""))).append("</td>");
                sb.append("<td>").append(HttpUtil.escapeHtml(JsonUtil.getString(row, "COMMENTS", ""))).append("</td>");
                sb.append("</tr>");
            }
            sb.append("</tbody></table></div></div>");

            sb.append("<div class=\"inst-card\">");
            sb.append("<h2 style=\"font-family:'Epilogue';font-size:1.25rem;margin:0;\">🗄️ Tablas y Vistas Activas en XAMPP MySQL `antojo_virtual` (").append(tables.size()).append(")</h2>");
            sb.append("<div class=\"tbl-grid\">");
            for (Map<String, Object> t : tables) {
                String tName = JsonUtil.getString(t, "table_name", "");
                boolean isLb = tName.equalsIgnoreCase("databasechangelog") || tName.equalsIgnoreCase("databasechangeloglock");
                sb.append("<div class=\"tbl-pill\" style=\"").append(isLb ? "border-color:#16a34a;background:#f0fdf4;" : "").append("\">");
                sb.append("<span>").append(isLb ? "💧 " : "📄 ").append(HttpUtil.escapeHtml(tName)).append("</span>");
                sb.append("<span style=\"color:#10B981;\">✔ Activa</span></div>");
            }
            sb.append("</div></div></div></body></html>");

            HttpUtil.sendHtml(ex, 200, sb.toString());
        } catch (Exception e) {
            e.printStackTrace();
            HttpUtil.sendError(ex, 500, "Error en InstallViewHandler Java: " + e.getMessage());
        }
    }
}
