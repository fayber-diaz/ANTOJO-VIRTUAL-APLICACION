package com.antojovirtual.util;

import com.sun.net.httpserver.Headers;
import com.sun.net.httpserver.HttpExchange;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.URLDecoder;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.text.DecimalFormat;
import java.text.DecimalFormatSymbols;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

/**
 * Utilidades HTTP, Formateo de Moneda COP y Manejo de Peticiones en Java.
 * Antojo Virtual - Neiva, Huila (100% Java Edition)
 */
public class HttpUtil {

    public static void addCorsHeaders(HttpExchange exchange) {
        Headers h = exchange.getResponseHeaders();
        h.set("Access-Control-Allow-Origin", "*");
        h.set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
        h.set("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Requested-With");
        h.set("Access-Control-Expose-Headers", "X-Token-Expires-In");
    }

    public static boolean handleCorsPreflight(HttpExchange exchange) throws IOException {
        addCorsHeaders(exchange);
        if ("OPTIONS".equalsIgnoreCase(exchange.getRequestMethod())) {
            exchange.sendResponseHeaders(204, -1);
            exchange.close();
            return true;
        }
        return false;
    }

    public static void sendJson(HttpExchange exchange, int statusCode, Object data) throws IOException {
        addCorsHeaders(exchange);
        String json = JsonUtil.toJson(data);
        byte[] bytes = json.getBytes(StandardCharsets.UTF_8);
        exchange.getResponseHeaders().set("Content-Type", "application/json; charset=utf-8");
        exchange.sendResponseHeaders(statusCode, bytes.length);
        try (OutputStream os = exchange.getResponseBody()) {
            os.write(bytes);
        }
    }

    public static void sendError(HttpExchange exchange, int statusCode, String message) throws IOException {
        Map<String, Object> err = new LinkedHashMap<>();
        err.put("success", false);
        err.put("status", "error");
        err.put("message", message);
        sendJson(exchange, statusCode, err);
    }

    public static void sendHtml(HttpExchange exchange, int statusCode, String html) throws IOException {
        addCorsHeaders(exchange);
        byte[] bytes = html.getBytes(StandardCharsets.UTF_8);
        exchange.getResponseHeaders().set("Content-Type", "text/html; charset=utf-8");
        exchange.sendResponseHeaders(statusCode, bytes.length);
        try (OutputStream os = exchange.getResponseBody()) {
            os.write(bytes);
        }
    }

    public static void redirect(HttpExchange exchange, String location) throws IOException {
        exchange.getResponseHeaders().set("Location", location);
        exchange.sendResponseHeaders(302, -1);
        exchange.close();
    }

    public static void sendRedirect(HttpExchange exchange, String location) throws IOException {
        redirect(exchange, location);
    }

    public static String readRequestBody(HttpExchange exchange) throws IOException {
        try (InputStream is = exchange.getRequestBody()) {
            byte[] bytes = is.readAllBytes();
            return new String(bytes, StandardCharsets.UTF_8);
        }
    }

    public static String readBody(HttpExchange exchange) throws IOException {
        return readRequestBody(exchange);
    }

    public static Map<String, Object> readJsonBody(HttpExchange exchange) throws IOException {
        String raw = readRequestBody(exchange);
        if (raw == null || raw.isBlank()) {
            return new LinkedHashMap<>();
        }
        return JsonUtil.parseObject(raw);
    }

    public static Map<String, String> parseQueryParams(HttpExchange exchange) {
        String rawQuery = exchange.getRequestURI().getRawQuery();
        return parseUrlEncoded(rawQuery);
    }

    public static Map<String, String> parseFormBody(HttpExchange exchange) throws IOException {
        String body = readRequestBody(exchange);
        return parseUrlEncoded(body);
    }

    public static Map<String, String> parseFormUrlEncoded(String raw) {
        return parseUrlEncoded(raw);
    }

    public static Map<String, String> parseUrlEncoded(String raw) {
        Map<String, String> params = new LinkedHashMap<>();
        if (raw == null || raw.isBlank()) return params;
        String[] pairs = raw.split("&");
        for (String pair : pairs) {
            if (pair.isEmpty()) continue;
            int idx = pair.indexOf('=');
            if (idx >= 0) {
                String key = URLDecoder.decode(pair.substring(0, idx), StandardCharsets.UTF_8);
                String val = URLDecoder.decode(pair.substring(idx + 1), StandardCharsets.UTF_8);
                params.put(key, val);
            } else {
                String key = URLDecoder.decode(pair, StandardCharsets.UTF_8);
                params.put(key, "");
            }
        }
        return params;
    }

    public static Map<String, String> parseCookies(HttpExchange exchange) {
        Map<String, String> cookies = new LinkedHashMap<>();
        List<String> cookieHeaders = exchange.getRequestHeaders().get("Cookie");
        if (cookieHeaders == null) return cookies;
        for (String header : cookieHeaders) {
            String[] parts = header.split(";");
            for (String part : parts) {
                String trimmed = part.trim();
                int idx = trimmed.indexOf('=');
                if (idx > 0) {
                    String name = trimmed.substring(0, idx).trim();
                    String val = trimmed.substring(idx + 1).trim();
                    cookies.put(name, val);
                }
            }
        }
        return cookies;
    }

    public static void setCookie(HttpExchange exchange, String name, String value, int maxAgeSeconds, boolean httpOnly) {
        StringBuilder sb = new StringBuilder();
        sb.append(name).append("=").append(value)
          .append("; Path=/; Max-Age=").append(maxAgeSeconds)
          .append("; SameSite=Lax");
        if (httpOnly) sb.append("; HttpOnly");
        exchange.getResponseHeaders().add("Set-Cookie", sb.toString());
    }

    public static void clearCookie(HttpExchange exchange, String name) {
        exchange.getResponseHeaders().add("Set-Cookie", name + "=; Path=/; Max-Age=0; SameSite=Lax");
    }

    public static String getHeader(HttpExchange exchange, String name) {
        return exchange.getRequestHeaders().getFirst(name);
    }

    public static String getClientIp(HttpExchange exchange) {
        String forwarded = getHeader(exchange, "X-Forwarded-For");
        if (forwarded != null && !forwarded.isBlank()) {
            return forwarded.split(",")[0].trim();
        }
        if (exchange.getRemoteAddress() != null && exchange.getRemoteAddress().getAddress() != null) {
            return exchange.getRemoteAddress().getAddress().getHostAddress();
        }
        return "127.0.0.1";
    }

    public static boolean wantsJson(HttpExchange exchange, Map<String, String> queryParams) {
        String format = queryParams.getOrDefault("format", "");
        if ("json".equalsIgnoreCase(format) || queryParams.containsKey("json") || queryParams.containsKey("raw")) {
            return true;
        }
        String xrw = getHeader(exchange, "X-Requested-With");
        if ("xmlhttprequest".equalsIgnoreCase(xrw)) {
            return true;
        }
        String accept = getHeader(exchange, "Accept");
        if (accept != null) {
            return accept.contains("application/json") && !accept.contains("text/html");
        }
        return false;
    }

    public static boolean prefersHtml(HttpExchange exchange, Map<String, String> queryParams) {
        if (wantsJson(exchange, queryParams)) return false;
        String accept = getHeader(exchange, "Accept");
        return accept != null && accept.contains("text/html");
    }

    public static String formatCop(long amount) {
        DecimalFormatSymbols symbols = new DecimalFormatSymbols(Locale.of("es", "CO"));
        symbols.setGroupingSeparator('.');
        symbols.setDecimalSeparator(',');
        DecimalFormat df = new DecimalFormat("#,##0", symbols);
        return "$" + df.format(amount);
    }

    public static String formatCop(double amount) {
        return formatCop(Math.round(amount));
    }

    public static String escapeHtml(String s) {
        if (s == null) return "";
        return s.replace("&", "&amp;")
                .replace("<", "&lt;")
                .replace(">", "&gt;")
                .replace("\"", "&quot;")
                .replace("'", "&#039;");
    }

    public static String urlEncode(String s) {
        if (s == null) return "";
        return URLEncoder.encode(s, StandardCharsets.UTF_8);
    }
}
