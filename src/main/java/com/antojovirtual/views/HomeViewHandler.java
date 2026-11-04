package com.antojovirtual.views;

import com.antojovirtual.util.HttpUtil;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;

import java.io.File;
import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;

/**
 * HomeViewHandler - Renderiza la vista principal de Antojo Virtual (100% Java)
 */
public class HomeViewHandler implements HttpHandler {

    private final File templateFile;

    public HomeViewHandler(File projectRoot) {
        this.templateFile = new File(projectRoot, "src/main/resources/templates/index.html");
    }

    @Override
    public void handle(HttpExchange ex) throws IOException {
        if (!templateFile.exists()) {
            HttpUtil.sendHtml(ex, 500, "<h1>Error: Plantilla index.html no encontrada en src/main/resources/templates/index.html</h1>");
            return;
        }
        String html = Files.readString(templateFile.toPath(), StandardCharsets.UTF_8);
        HttpUtil.sendHtml(ex, 200, html);
    }
}

