package com.antojovirtual.handlers;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.config.JwtUtil;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.util.JsonUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.IOException;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

/**
 * AuthHandler - API de Autenticacion JWT Real con Refresh Tokens (100% Java)
 * Soporta: POST/GET /api/auth?action=login|register|refresh|logout|me|verify
 */
public class AuthHandler implements HttpHandler {

    private static final DateTimeFormatter SQL_FMT = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (HttpUtil.handleCorsPreflight(ex)) return;

        try {
            Map<String, String> query = HttpUtil.parseQueryParams(ex);
            String bodyRaw = HttpUtil.readBody(ex);
            Map<String, Object> body = bodyRaw.trim().startsWith("{")
                    ? JsonUtil.parseObject(bodyRaw)
                    : new LinkedHashMap<>(HttpUtil.parseFormUrlEncoded(bodyRaw));

            String action = query.getOrDefault("action", JsonUtil.getString(body, "action", "me")).toLowerCase().trim();

            switch (action) {
                case "register":
                    handleRegister(ex, body);
                    break;
                case "login":
                    handleLogin(ex, body);
                    break;
                case "refresh":
                    handleRefresh(ex, body);
                    break;
                case "logout":
                    handleLogout(ex, body);
                    break;
                case "me":
                case "verify":
                default:
                    handleMe(ex);
                    break;
            }
        } catch (Exception e) {
            e.printStackTrace();
            Map<String, Object> err = new LinkedHashMap<>();
            err.put("success", false);
            err.put("message", "Error interno en autenticacion Java: " + e.getMessage());
            HttpUtil.sendJson(ex, 500, err);
        }
    }

    private void handleRegister(HttpExchange ex, Map<String, Object> body) throws Exception {
        String name = JsonUtil.getString(body, "name", "").trim();
        String email = JsonUtil.getString(body, "email", "").trim().toLowerCase();
        String phone = JsonUtil.getString(body, "phone", "").trim();
        String password = JsonUtil.getString(body, "password", "");
        String address = JsonUtil.getString(body, "address", "Neiva, Huila").trim();
        String comuna = JsonUtil.getString(body, "comuna", "Comuna 4 - Centro").trim();

        if (name.isEmpty() || email.isEmpty() || password.isEmpty()) {
            sendAuthError(ex, 400, "Nombre, correo y contraseña son obligatorios.");
            return;
        }
        if (!email.contains("@") || !email.contains(".")) {
            sendAuthError(ex, 400, "El correo electrónico no es válido.");
            return;
        }
        if (password.length() < 6) {
            sendAuthError(ex, 400, "La contraseña debe tener al menos 6 caracteres.");
            return;
        }

        Map<String, Object> existing = DatabaseManager.queryOne(
                "SELECT id FROM users WHERE email = ? LIMIT 1",
                Collections.singletonList(email)
        );
        if (existing != null) {
            sendAuthError(ex, 409, "Ya existe una cuenta con este correo.");
            return;
        }

        String hash = JwtUtil.hashPassword(password);
        long userId = DatabaseManager.insertAndGetId(
                "INSERT INTO users (name, email, password, phone, address, comuna, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')",
                Arrays.asList(name, email, hash, phone.isEmpty() ? null : phone, address, comuna)
        );

        Map<String, Object> newUser = new LinkedHashMap<>();
        newUser.put("id", (int) userId);
        newUser.put("name", name);
        newUser.put("email", email);
        newUser.put("phone", phone);
        newUser.put("address", address);
        newUser.put("comuna", comuna);
        newUser.put("role", "customer");

        Map<String, Object> tokens = JwtUtil.issueTokenPair(newUser, ex);

        Map<String, Object> resp = new LinkedHashMap<>();
        resp.put("success", true);
        resp.put("status", "ok");
        resp.put("engine", "100% Java");
        resp.put("message", "¡Cuenta creada exitosamente en Antojo Virtual!");
        resp.putAll(tokens);
        resp.put("user", newUser);

        HttpUtil.sendJson(ex, 201, resp);
    }

    private void handleLogin(HttpExchange ex, Map<String, Object> body) throws Exception {
        String email = JsonUtil.getString(body, "email", "").trim().toLowerCase();
        String password = JsonUtil.getString(body, "password", "");

        if (email.isEmpty() || password.isEmpty()) {
            sendAuthError(ex, 400, "Por favor ingresa tu correo y contraseña.");
            return;
        }

        Map<String, Object> user = DatabaseManager.queryOne(
                "SELECT * FROM users WHERE email = ? LIMIT 1",
                Collections.singletonList(email)
        );

        if (user == null) {
            sendAuthError(ex, 401, "Credenciales inválidas. Revisa tu correo y contraseña.");
            return;
        }

        String storedHash = JsonUtil.getString(user, "password", "");
        if (!JwtUtil.verifyPassword(password, storedHash)) {
            sendAuthError(ex, 401, "Credenciales inválidas. Revisa tu correo y contraseña.");
            return;
        }

        user.remove("password");
        user.put("id", JsonUtil.getInt(user, "id", 0));

        Map<String, Object> tokens = JwtUtil.issueTokenPair(user, ex);

        Map<String, Object> resp = new LinkedHashMap<>();
        resp.put("success", true);
        resp.put("status", "ok");
        resp.put("engine", "100% Java");
        resp.put("message", "¡Bienvenido de nuevo, " + JsonUtil.getString(user, "name", "") + "!");
        resp.putAll(tokens);
        resp.put("user", user);

        HttpUtil.sendJson(ex, 200, resp);
    }

    private void handleRefresh(HttpExchange ex, Map<String, Object> body) throws Exception {
        Map<String, String> cookies = HttpUtil.parseCookies(ex);
        String refreshToken = JsonUtil.getString(body, "refresh_token",
                cookies.getOrDefault("antojo_refresh_token", cookies.getOrDefault("antojo_refresh", "")));

        if (refreshToken.isEmpty()) {
            sendAuthError(ex, 400, "Refresh token no proporcionado.");
            return;
        }

        Map<String, Object> user = JwtUtil.validateRefreshToken(refreshToken);
        if (user == null) {
            HttpUtil.clearCookie(ex, "antojo_access_token");
            HttpUtil.clearCookie(ex, "antojo_refresh_token");
            sendAuthError(ex, 401, "Refresh token inválido o expirado. Inicia sesión nuevamente.");
            return;
        }

        JwtUtil.revokeRefreshToken(refreshToken);
        Map<String, Object> tokens = JwtUtil.issueTokenPair(user, ex);

        Map<String, Object> resp = new LinkedHashMap<>();
        resp.put("success", true);
        resp.put("status", "ok");
        resp.put("engine", "100% Java");
        resp.put("message", "Token renovado exitosamente.");
        resp.putAll(tokens);
        resp.put("user", user);

        HttpUtil.sendJson(ex, 200, resp);
    }

    private void handleLogout(HttpExchange ex, Map<String, Object> body) throws Exception {
        String token = JwtUtil.extractBearerToken(ex);
        if (token != null) {
            Map<String, Object> claims = JwtUtil.decode(token);
            if (claims != null && claims.get("jti") != null) {
                String expAt = LocalDateTime.now().plusHours(2).format(SQL_FMT);
                JwtUtil.blacklist(String.valueOf(claims.get("jti")), expAt);
            }
        }

        Map<String, String> cookies = HttpUtil.parseCookies(ex);
        String refresh = JsonUtil.getString(body, "refresh_token",
                cookies.getOrDefault("antojo_refresh_token", cookies.getOrDefault("antojo_refresh", "")));
        if (!refresh.isEmpty()) {
            JwtUtil.revokeRefreshToken(refresh);
        }

        HttpUtil.clearCookie(ex, "antojo_access_token");
        HttpUtil.clearCookie(ex, "antojo_refresh_token");

        Map<String, Object> resp = new LinkedHashMap<>();
        resp.put("success", true);
        resp.put("status", "ok");
        resp.put("engine", "100% Java");
        resp.put("message", "Sesión cerrada correctamente.");
        HttpUtil.sendJson(ex, 200, resp);
    }

    private void handleMe(HttpExchange ex) throws Exception {
        Map<String, Object> auth = JwtUtil.getAuthUser(ex);
        if (auth == null) {
            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("success", false);
            resp.put("authenticated", false);
            resp.put("message", "No autenticado.");
            HttpUtil.sendJson(ex, 200, resp);
            return;
        }

        int userId = JsonUtil.getInt(auth, "id", 0);
        Map<String, Object> user = DatabaseManager.queryOne(
                "SELECT id, name, email, phone, address, comuna, role, created_at FROM users WHERE id = ? LIMIT 1",
                Collections.singletonList(userId)
        );

        if (user == null) {
            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("success", false);
            resp.put("authenticated", false);
            HttpUtil.sendJson(ex, 200, resp);
            return;
        }

        user.put("id", JsonUtil.getInt(user, "id", 0));

        Map<String, Object> resp = new LinkedHashMap<>();
        resp.put("success", true);
        resp.put("status", "ok");
        resp.put("engine", "100% Java");
        resp.put("authenticated", true);
        resp.put("user", user);
        HttpUtil.sendJson(ex, 200, resp);
    }

    private void sendAuthError(HttpExchange ex, int status, String msg) throws IOException {
        Map<String, Object> err = new LinkedHashMap<>();
        err.put("success", false);
        err.put("status", "error");
        err.put("message", msg);
        HttpUtil.sendJson(ex, status, err);
    }
}
