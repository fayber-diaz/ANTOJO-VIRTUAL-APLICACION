package com.antojovirtual.config;

import java.io.BufferedReader;
import java.io.File;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.Collections;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * Gestor de Conexion, Consultas MySQL y Ejecucion Liquibase 100% Java.
 * Soporta tanto JDBC estandar (com.mysql.cj.jdbc.Driver) como puente nativo de alto rendimiento
 * hacia el motor MySQL de XAMPP (C:\xampp\mysql\bin\mysql.exe).
 */
public class DatabaseManager {

    public static final String DB_HOST = System.getenv("DB_HOST") != null ? System.getenv("DB_HOST") : "localhost";
    public static final String DB_PORT = System.getenv("DB_PORT") != null ? System.getenv("DB_PORT") : "3306";
    public static final String DB_USER = System.getenv("DB_USER") != null ? System.getenv("DB_USER") : "root";
    public static final String DB_PASS = System.getenv("DB_PASS") != null ? System.getenv("DB_PASS") : "";
    public static final String DB_NAME = System.getenv("DB_NAME") != null ? System.getenv("DB_NAME") : "antojo_virtual";

    private static final String JDBC_URL = "jdbc:mysql://" + DB_HOST + ":" + DB_PORT + "/" + DB_NAME
            + "?useUnicode=true&characterEncoding=utf8&serverTimezone=America/Bogota&allowMultiQueries=true&useSSL=false&allowPublicKeyRetrieval=true";

    private static boolean jdbcAvailable = false;
    private static String mysqlBinaryPath = "C:\\xampp\\mysql\\bin\\mysql.exe";
    private static boolean initialized = false;

    static {
        File xamppMysql = new File("C:\\xampp\\mysql\\bin\\mysql.exe");
        if (xamppMysql.exists()) {
            mysqlBinaryPath = xamppMysql.getAbsolutePath();
        } else {
            mysqlBinaryPath = "mysql";
        }
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
            jdbcAvailable = true;
            System.out.println("[DatabaseManager] Driver JDBC MySQL detectado y activo.");
        } catch (ClassNotFoundException e) {
            jdbcAvailable = false;
            System.out.println("[DatabaseManager] Modo puente nativo MySQL XAMPP activo (" + mysqlBinaryPath + ").");
        }
    }

    public static synchronized void ensureXamppMySqlRunning() {
        try (java.net.Socket s = new java.net.Socket()) {
            s.connect(new java.net.InetSocketAddress(DB_HOST, Integer.parseInt(DB_PORT)), 800);
            return;
        } catch (Exception notRunning) {
            File mysqld = new File("C:\\xampp\\mysql\\bin\\mysqld.exe");
            File myIni = new File("C:\\xampp\\mysql\\bin\\my.ini");
            if (mysqld.exists()) {
                try {
                    System.out.println("[XAMPP] Iniciando motor MySQL/MariaDB de XAMPP en puerto " + DB_PORT + "...");
                    new ProcessBuilder(mysqld.getAbsolutePath(), "--defaults-file=" + myIni.getAbsolutePath())
                            .redirectErrorStream(true)
                            .start();
                    for (int i = 0; i < 16; i++) {
                        Thread.sleep(500);
                        try (java.net.Socket s2 = new java.net.Socket()) {
                            s2.connect(new java.net.InetSocketAddress(DB_HOST, Integer.parseInt(DB_PORT)), 500);
                            System.out.println("[XAMPP] Motor MySQL/MariaDB activo en puerto " + DB_PORT + ".");
                            break;
                        } catch (Exception ignored) {}
                    }
                } catch (Exception e) {
                    System.err.println("[XAMPP] No se pudo auto-iniciar mysqld: " + e.getMessage());
                }
            }
        }
    }

    public static synchronized void ensureInitialized() {
        if (initialized) return;
        try {
            ensureXamppMySqlRunning();
            createDatabaseIfNotExists();
            int count = 0;
            try {
                count = queryScalarInt("SELECT COUNT(*) FROM `restaurants`");
            } catch (Exception ignored) {}

            if (count == 0) {
                resetAndSeedDatabase();
            }
            // Asegurar siempre que todas las migraciones Liquibase (001 a 004) estén sincronizadas en MySQL
            LiquibaseManager.runMigrations(false);
            initialized = true;
        } catch (Exception e) {
            System.err.println("[DatabaseManager] Aviso inicializando BD MySQL + Liquibase: " + e.getMessage());
        }
    }

    public static boolean isConnected() {
        try {
            int res = queryScalarInt("SELECT 1");
            return res == 1;
        } catch (Exception e) {
            return false;
        }
    }

    public static String getEngineDescription() {
        return jdbcAvailable
                ? "Java JDBC (MySQL Connector/J 8.4) + Liquibase " + LiquibaseManager.LIQUIBASE_VERSION
                : "Java XAMPP MySQL Bridge (UTF-8) + Liquibase " + LiquibaseManager.LIQUIBASE_VERSION;
    }

    public static String getEngineStatus() {
        return getEngineDescription();
    }

    public static void createDatabaseIfNotExists() throws Exception {
        ensureXamppMySqlRunning();
        String createSql = "CREATE DATABASE IF NOT EXISTS `" + DB_NAME + "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        if (jdbcAvailable) {
            String urlNoDb = "jdbc:mysql://" + DB_HOST + ":" + DB_PORT + "?useUnicode=true&characterEncoding=utf8&useSSL=false&allowPublicKeyRetrieval=true";
            try (Connection conn = DriverManager.getConnection(urlNoDb, DB_USER, DB_PASS);
                 Statement st = conn.createStatement()) {
                st.execute(createSql);
                return;
            } catch (Exception e) {
                jdbcAvailable = false;
            }
        }
        runMysqlCli(createSql, false);
    }

    public static void executeRawSqlScript(String sqlContent) throws Exception {
        if (sqlContent == null || sqlContent.isBlank()) return;
        if (jdbcAvailable) {
            try (Connection conn = DriverManager.getConnection(JDBC_URL, DB_USER, DB_PASS);
                 Statement st = conn.createStatement()) {
                st.execute(sqlContent);
            }
        } else {
            runMysqlCli(sqlContent, true);
        }
    }

    public static boolean resetAndSeedDatabase() {
        try {
            createDatabaseIfNotExists();
            Path baseSql = Path.of("database/antojo_virtual.sql");
            boolean anyFound = false;
            if (Files.exists(baseSql)) {
                anyFound = true;
                String sqlContent = Files.readString(baseSql, StandardCharsets.UTF_8);
                executeRawSqlScript(sqlContent);
            }
            // Ejecutar todas las migraciones Liquibase (001 a 004) y registrar en DATABASECHANGELOG
            LiquibaseManager.runMigrations(true);
            return anyFound;
        } catch (Exception e) {
            e.printStackTrace();
            return false;
        }
    }

    public static List<Map<String, Object>> queryList(String sql) throws Exception {
        return queryList(sql, Collections.emptyList());
    }

    public static List<Map<String, Object>> queryList(String sql, List<?> params) throws Exception {
        Object[] arr = params != null ? params.toArray() : new Object[0];
        return queryListWithArray(sql, arr);
    }

    public static List<Map<String, Object>> queryListWithArray(String sql, Object... params) throws Exception {
        if (jdbcAvailable) {
            List<Map<String, Object>> rows = new ArrayList<>();
            try (Connection conn = DriverManager.getConnection(JDBC_URL, DB_USER, DB_PASS);
                 PreparedStatement ps = conn.prepareStatement(sql)) {
                for (int i = 0; i < params.length; i++) {
                    ps.setObject(i + 1, params[i]);
                }
                try (ResultSet rs = ps.executeQuery()) {
                    ResultSetMetaData meta = rs.getMetaData();
                    int colCount = meta.getColumnCount();
                    while (rs.next()) {
                        Map<String, Object> row = new LinkedHashMap<>();
                        for (int c = 1; c <= colCount; c++) {
                            String colName = meta.getColumnLabel(c);
                            String val = rs.getString(c);
                            row.put(colName, val);
                        }
                        rows.add(row);
                    }
                }
            }
            return rows;
        } else {
            String boundSql = bindParams(sql, params);
            String output = runMysqlCli(boundSql, true);
            return parseBatchOutput(output);
        }
    }

    public static Map<String, Object> queryOne(String sql) throws Exception {
        return queryOne(sql, Collections.emptyList());
    }

    public static Map<String, Object> queryOne(String sql, List<?> params) throws Exception {
        List<Map<String, Object>> list = queryList(sql, params);
        return list.isEmpty() ? null : list.get(0);
    }

    public static int queryScalarInt(String sql) throws Exception {
        Map<String, Object> row = queryOne(sql);
        if (row == null || row.isEmpty()) return 0;
        Object firstVal = row.values().iterator().next();
        if (firstVal == null || String.valueOf(firstVal).isBlank()) return 0;
        return (int) Double.parseDouble(String.valueOf(firstVal).trim());
    }

    public static int executeUpdate(String sql, List<?> params) throws Exception {
        Object[] arr = params != null ? params.toArray() : new Object[0];
        if (jdbcAvailable) {
            try (Connection conn = DriverManager.getConnection(JDBC_URL, DB_USER, DB_PASS);
                 PreparedStatement ps = conn.prepareStatement(sql)) {
                for (int i = 0; i < arr.length; i++) {
                    ps.setObject(i + 1, arr[i]);
                }
                return ps.executeUpdate();
            }
        } else {
            String boundSql = bindParams(sql, arr);
            runMysqlCli(boundSql, true);
            return 1;
        }
    }

    public static long insertAndGetId(String sql, List<?> params) throws Exception {
        Object[] arr = params != null ? params.toArray() : new Object[0];
        if (jdbcAvailable) {
            try (Connection conn = DriverManager.getConnection(JDBC_URL, DB_USER, DB_PASS);
                 PreparedStatement ps = conn.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
                for (int i = 0; i < arr.length; i++) {
                    ps.setObject(i + 1, arr[i]);
                }
                ps.executeUpdate();
                try (ResultSet keys = ps.getGeneratedKeys()) {
                    if (keys.next()) {
                        return keys.getLong(1);
                    }
                }
                return 0L;
            }
        } else {
            String boundSql = bindParams(sql, arr) + ";\nSELECT LAST_INSERT_ID() AS last_id;";
            String output = runMysqlCli(boundSql, true);
            List<Map<String, Object>> rows = parseBatchOutput(output);
            if (!rows.isEmpty()) {
                Object val = rows.get(rows.size() - 1).get("last_id");
                if (val != null && !String.valueOf(val).isBlank()) {
                    return Long.parseLong(String.valueOf(val).trim());
                }
            }
            return 0L;
        }
    }

    public static String bindParams(String sql, Object... params) {
        if (params == null || params.length == 0) return sql;
        StringBuilder sb = new StringBuilder();
        int paramIdx = 0;
        boolean inSingleQuote = false;
        for (int i = 0; i < sql.length(); i++) {
            char c = sql.charAt(i);
            if (c == '\'' && (i == 0 || sql.charAt(i - 1) != '\\')) {
                inSingleQuote = !inSingleQuote;
                sb.append(c);
            } else if (c == '?' && !inSingleQuote && paramIdx < params.length) {
                Object val = params[paramIdx++];
                sb.append(formatSqlValue(val));
            } else {
                sb.append(c);
            }
        }
        return sb.toString();
    }

    private static String formatSqlValue(Object val) {
        if (val == null) return "NULL";
        if (val instanceof Number) return val.toString();
        if (val instanceof Boolean b) return b ? "1" : "0";
        String s = String.valueOf(val);
        StringBuilder escaped = new StringBuilder("'");
        for (int i = 0; i < s.length(); i++) {
            char c = s.charAt(i);
            switch (c) {
                case '\\' -> escaped.append("\\\\");
                case '\'' -> escaped.append("\\'");
                case '\"' -> escaped.append("\\\"");
                case '\n' -> escaped.append("\\n");
                case '\r' -> escaped.append("\\r");
                case '\0' -> escaped.append("\\0");
                default -> escaped.append(c);
            }
        }
        escaped.append("'");
        return escaped.toString();
    }

    private static String runMysqlCli(String sql, boolean useDb) throws Exception {
        List<String> cmd = new ArrayList<>();
        cmd.add(mysqlBinaryPath);
        cmd.add("--default-character-set=utf8mb4");
        cmd.add("--batch");
        cmd.add("-h");
        cmd.add(DB_HOST);
        cmd.add("-P");
        cmd.add(DB_PORT);
        cmd.add("-u");
        cmd.add(DB_USER);
        if (DB_PASS != null && !DB_PASS.isEmpty()) {
            cmd.add("-p" + DB_PASS);
        }
        if (useDb) {
            cmd.add(DB_NAME);
        }

        ProcessBuilder pb = new ProcessBuilder(cmd);
        pb.redirectErrorStream(false);
        Process process = pb.start();

        try (OutputStream os = process.getOutputStream()) {
            String fullSql = "SET NAMES utf8mb4;\n" + sql + "\n";
            os.write(fullSql.getBytes(StandardCharsets.UTF_8));
            os.flush();
        }

        String stdout;
        try (BufferedReader reader = new BufferedReader(new InputStreamReader(process.getInputStream(), StandardCharsets.UTF_8))) {
            StringBuilder outSb = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) {
                outSb.append(line).append("\n");
            }
            stdout = outSb.toString();
        }

        String stderr;
        try (BufferedReader errReader = new BufferedReader(new InputStreamReader(process.getErrorStream(), StandardCharsets.UTF_8))) {
            StringBuilder errSb = new StringBuilder();
            String line;
            while ((line = errReader.readLine()) != null) {
                errSb.append(line).append("\n");
            }
            stderr = errSb.toString();
        }

        int exitCode = process.waitFor();
        if (exitCode != 0) {
            throw new RuntimeException("Error MySQL (" + exitCode + "): " + stderr.trim());
        }
        return stdout;
    }

    private static List<Map<String, Object>> parseBatchOutput(String output) {
        List<Map<String, Object>> rows = new ArrayList<>();
        if (output == null || output.isBlank()) return rows;

        String[] lines = output.split("\r?\n");
        if (lines.length < 2) return rows;

        String[] headers = lines[0].split("\t", -1);
        for (int i = 1; i < lines.length; i++) {
            String line = lines[i];
            if (line.isEmpty()) continue;
            String[] cols = line.split("\t", -1);
            Map<String, Object> row = new LinkedHashMap<>();
            for (int c = 0; c < headers.length; c++) {
                String rawVal = c < cols.length ? cols[c] : "";
                row.put(headers[c], unescapeMysqlBatch(rawVal));
            }
            rows.add(row);
        }
        return rows;
    }

    private static String unescapeMysqlBatch(String val) {
        if (val == null || "\\N".equals(val)) return null;
        StringBuilder sb = new StringBuilder(val.length());
        for (int i = 0; i < val.length(); i++) {
            char c = val.charAt(i);
            if (c == '\\' && i + 1 < val.length()) {
                char next = val.charAt(++i);
                switch (next) {
                    case 't' -> sb.append('\t');
                    case 'n' -> sb.append('\n');
                    case 'r' -> sb.append('\r');
                    case '\\' -> sb.append('\\');
                    case '0' -> sb.append('\0');
                    default -> sb.append(next);
                }
            } else {
                sb.append(c);
            }
        }
        return sb.toString();
    }
}
