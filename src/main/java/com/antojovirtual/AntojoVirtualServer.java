package com.antojovirtual;

import com.antojovirtual.config.DatabaseManager;
import com.antojovirtual.handlers.*;
import com.antojovirtual.util.HttpUtil;
import com.antojovirtual.views.*;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;
import com.sun.net.httpserver.HttpServer;

import java.io.File;
import java.io.IOException;
import java.net.InetSocketAddress;
import java.util.concurrent.Executors;

/**
 * AntojoVirtualServer - Servidor Web y API REST 100% Java (JDK HttpServer + MySQL)
 * Plataforma Gastronomica de Comidas Rapidas, Asados, Heladerias y Postres en Neiva, Huila.
 */
public class AntojoVirtualServer {

    public static void main(String[] args) throws Exception {
        int primaryPort = 8080;
        if (args.length > 0) {
            try {
                primaryPort = Integer.parseInt(args[0]);
            } catch (NumberFormatException ignored) {}
        }

        File projectRoot = new File(System.getProperty("user.dir"));
        System.out.println("==================================================================");
        System.out.println("  ANTOJO VIRTUAL NEIVA - SERVIDOR WEB 100% JAVA (v2.0.0)");
        System.out.println("  JDK Version : " + System.getProperty("java.version"));
        System.out.println("  Directorio  : " + projectRoot.getAbsolutePath());
        System.out.println("==================================================================");

        // Inicializar y verificar base de datos MySQL
        DatabaseManager.ensureInitialized();
        System.out.println("[DB] Estado Motor MySQL: " + DatabaseManager.getEngineStatus());

        HttpHandler masterRouter = buildMasterRouter(projectRoot);

        // Iniciar servidor en puerto primario (8080)
        HttpServer server8080 = HttpServer.create(new InetSocketAddress("0.0.0.0", primaryPort), 0);
        server8080.createContext("/", masterRouter);
        server8080.setExecutor(Executors.newFixedThreadPool(16));
        server8080.start();
        System.out.println("[OK] Servidor Java escuchando en: http://localhost:" + primaryPort + "/");

        // Tambien intentar iniciar en puerto 80 para compatibilidad con URLs sin puerto (http://localhost/antojo-virtual/)
        if (primaryPort != 80) {
            try {
                HttpServer server80 = HttpServer.create(new InetSocketAddress("0.0.0.0", 80), 0);
                server80.createContext("/", masterRouter);
                server80.setExecutor(Executors.newFixedThreadPool(16));
                server80.start();
                System.out.println("[OK] Servidor Java espejo activo en puerto 80: http://localhost/antojo-virtual/");
            } catch (Exception e) {
                System.out.println("[INFO] Puerto 80 ocupado por otro servicio; usando puerto " + primaryPort + ".");
            }
        }
    }

    private static HttpHandler buildMasterRouter(File projectRoot) {
        final HomeViewHandler homeView = new HomeViewHandler(projectRoot);
        final AdminViewHandler adminView = new AdminViewHandler();
        final RiderViewHandler riderView = new RiderViewHandler();
        final ReviewsViewHandler reviewsView = new ReviewsViewHandler();
        final InstallViewHandler installView = new InstallViewHandler();

        final AuthHandler authHandler = new AuthHandler();
        final CouponsHandler couponsHandler = new CouponsHandler();
        final RestaurantsHandler restaurantsHandler = new RestaurantsHandler();
        final MenuHandler menuHandler = new MenuHandler();
        final OrdersHandler ordersHandler = new OrdersHandler();
        final PaymentsHandler paymentsHandler = new PaymentsHandler();
        final TrackingHandler trackingHandler = new TrackingHandler();
        final ReviewsHandler reviewsHandler = new ReviewsHandler();
        final AdminApiHandler adminApiHandler = new AdminApiHandler();
        final StaticFileHandler staticFileHandler = new StaticFileHandler(projectRoot);

        return new HttpHandler() {
            @Override
            public void handle(HttpExchange ex) throws IOException {
                String rawPath = ex.getRequestURI().getPath();
                if (rawPath == null || rawPath.isEmpty()) rawPath = "/";

                // Soportar tanto raiz "/" como prefijos "/antojo-virtual/..." y "/ANTOJO-VIRTUAL-APLICACION/..."
                String path = rawPath;
                String lowerPath = path.toLowerCase();
                if (lowerPath.equals("/antojo-virtual") || lowerPath.equals("/antojo-virtual/")) {
                    path = "/";
                } else if (lowerPath.startsWith("/antojo-virtual/")) {
                    path = path.substring("/antojo-virtual".length());
                } else if (lowerPath.equals("/antojo-virtual-aplicacion") || lowerPath.equals("/antojo-virtual-aplicacion/")) {
                    path = "/";
                } else if (lowerPath.startsWith("/antojo-virtual-aplicacion/")) {
                    path = path.substring("/antojo-virtual-aplicacion".length());
                }

                // Quitar barra final excepto en "/"
                if (path.length() > 1 && path.endsWith("/")) {
                    path = path.substring(0, path.length() - 1);
                }

                // Archivos estaticos
                if (path.startsWith("/assets/") || path.equals("/manifest.json") || path.equals("/sw.js") || path.equals("/favicon.ico")) {
                    staticFileHandler.handle(ex);
                    return;
                }

                // Enrutamiento de Vistas y APIs en Java
                switch (path.toLowerCase()) {
                    case "/":
                    case "/index":
                    case "/index.html":
                    case "/index.php":
                        homeView.handle(ex);
                        break;

                    case "/restaurantes":
                    case "/restaurantes.php":
                    case "/api/restaurants":
                    case "/api/restaurants.php":
                        restaurantsHandler.handle(ex);
                        break;

                    case "/menu":
                    case "/menu.php":
                    case "/api/menu":
                    case "/api/menu.php":
                        menuHandler.handle(ex);
                        break;

                    case "/rider":
                    case "/rider.php":
                        riderView.handle(ex);
                        break;

                    case "/admin":
                    case "/admin.php":
                        adminView.handle(ex);
                        break;

                    case "/reviews":
                    case "/reviews.php":
                        reviewsView.handle(ex);
                        break;

                    case "/install":
                    case "/install.php":
                    case "/liquibase":
                    case "/liquibase.php":
                    case "/api/liquibase":
                        installView.handle(ex);
                        break;

                    case "/api/auth":
                    case "/api/auth.php":
                        authHandler.handle(ex);
                        break;

                    case "/api/coupons":
                    case "/api/coupons.php":
                        couponsHandler.handle(ex);
                        break;

                    case "/api/orders":
                    case "/api/orders.php":
                        ordersHandler.handle(ex);
                        break;

                    case "/api/payments":
                    case "/api/payments.php":
                        paymentsHandler.handle(ex);
                        break;

                    case "/api/tracking":
                    case "/api/tracking.php":
                        trackingHandler.handle(ex);
                        break;

                    case "/api/reviews":
                    case "/api/reviews.php":
                        reviewsHandler.handle(ex);
                        break;

                    case "/api/admin":
                    case "/api/admin.php":
                        adminApiHandler.handle(ex);
                        break;

                    default:
                        HttpUtil.sendError(ex, 404, "Ruta no encontrada en el Servidor Java: " + path);
                        break;
                }
            }
        };
    }
}

