package com.antojovirtual.handlers;

import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.File;
import java.io.IOException;
import java.io.OutputStream;
import java.nio.file.Files;

/**
 * StaticFileHandler - Servidor de archivos estaticos CSS, JS, JSON, SVG, PNG, JPG (100% Java)
 */
public class StaticFileHandler implements HttpHandler {

    private final File rootDir;

    public StaticFileHandler(File rootDir) {
        this.rootDir = rootDir;
    }

    @Override
    public void handle(HttpExchange ex) throws IOException {
        String uriPath = ex.getRequestURI().getPath();
        if (uriPath.startsWith("/antojo-virtual/")) {
            uriPath = uriPath.substring("/antojo-virtual".length());
        }
        if (uriPath.contains("..")) {
            ex.sendResponseHeaders(403, -1);
            return;
        }

        File file = new File(rootDir, uriPath.startsWith("/") ? uriPath.substring(1) : uriPath);
        if (!file.exists() || !file.isFile()) {
            ex.sendResponseHeaders(404, -1);
            return;
        }

        String mime = guessMimeType(file.getName());
        byte[] bytes = Files.readAllBytes(file.toPath());
        ex.getResponseHeaders().set("Content-Type", mime);
        ex.getResponseHeaders().set("Cache-Control", "no-cache");
        ex.sendResponseHeaders(200, bytes.length);
        try (OutputStream os = ex.getResponseBody()) {
            os.write(bytes);
        }
    }

    private String guessMimeType(String name) {
        String lower = name.toLowerCase();
        if (lower.endsWith(".css")) return "text/css; charset=UTF-8";
        if (lower.endsWith(".js")) return "application/javascript; charset=UTF-8";
        if (lower.endsWith(".json")) return "application/manifest+json; charset=UTF-8";
        if (lower.endsWith(".html")) return "text/html; charset=UTF-8";
        if (lower.endsWith(".svg")) return "image/svg+xml";
        if (lower.endsWith(".png")) return "image/png";
        if (lower.endsWith(".jpg") || lower.endsWith(".jpeg")) return "image/jpeg";
        if (lower.endsWith(".webp")) return "image/webp";
        if (lower.endsWith(".ico")) return "image/x-icon";
        return "application/octet-stream";
    }
}

