package com.antojovirtual.config;

import com.antojovirtual.util.JsonUtil;

import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.security.MessageDigest;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * LiquibaseManager - Motor de Migraciones y Control de Versiones Liquibase en Java para MySQL (XAMPP).
 * Gestiona las tablas oficiales DATABASECHANGELOG y DATABASECHANGELOGLOCK,
 * lee src/main/resources/db/changelog/db.changelog-master.xml y aplica los changesets SQL.
 */
public class LiquibaseManager {

    public static final String MASTER_CHANGELOG = "src/main/resources/db/changelog/db.changelog-master.xml";
    public static final String LIQUIBASE_VERSION = "4.29.2";

    private static final Pattern INCLUDE_PATTERN = Pattern.compile("<include\\s+[^>]*file=\"([^\"]+)\"", Pattern.CASE_INSENSITIVE);
    private static final Pattern CHANGESET_HEADER = Pattern.compile("^--\\s*changeset\\s+([^:\\s]+):([^\\s]+)(.*)$", Pattern.CASE_INSENSITIVE);
    private static final Pattern COMMENT_HEADER = Pattern.compile("^--\\s*comment\\s+(.*)$", Pattern.CASE_INSENSITIVE);

    /**
     * Inicializa las tablas de control de Liquibase (DATABASECHANGELOG y DATABASECHANGELOGLOCK)
     * y ejecuta todos los changesets pendientes definidos en db.changelog-master.xml.
     */
    public static synchronized Map<String, Object> runMigrations(boolean forceReRun) {
        Map<String, Object> result = new LinkedHashMap<>();
        List<String> appliedNow = new ArrayList<>();
        List<String> skippedAlreadyExecuted = new ArrayList<>();

        try {
            DatabaseManager.createDatabaseIfNotExists();
            ensureLiquibaseTables();
            acquireLock();

            String deploymentId = String.valueOf(System.currentTimeMillis()).substring(3);
            List<String> changelogFiles = parseMasterChangelog(MASTER_CHANGELOG);

            int maxOrder = DatabaseManager.queryScalarInt("SELECT COALESCE(MAX(ORDEREXECUTED), 0) FROM `DATABASECHANGELOG`");

            for (String filePath : changelogFiles) {
                Path path = Path.of(filePath);
                if (!Files.exists(path)) {
                    continue;
                }
                String content = Files.readString(path, StandardCharsets.UTF_8);
                List<ChangesetDef> changesets = parseFormattedSqlChangelog(filePath, content);

                for (ChangesetDef cs : changesets) {
                    String md5 = "8:" + md5Hex(cs.sqlBody.trim());
                    Map<String, Object> existing = DatabaseManager.queryOne(
                            "SELECT ID, MD5SUM FROM `DATABASECHANGELOG` WHERE `ID` = ? AND `AUTHOR` = ? AND `FILENAME` = ? LIMIT 1",
                            Arrays.asList(cs.id, cs.author, cs.filename)
                    );

                    if (existing != null && !forceReRun) {
                        skippedAlreadyExecuted.add(cs.author + ":" + cs.id);
                        continue;
                    }

                    // Ejecutar SQL del changeset en MySQL
                    DatabaseManager.executeRawSqlScript(cs.sqlBody);

                    maxOrder++;
                    String nowStr = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));

                    if (existing != null) {
                        DatabaseManager.executeUpdate(
                                "UPDATE `DATABASECHANGELOG` SET `DATEEXECUTED` = ?, `ORDEREXECUTED` = ?, `EXECTYPE` = 'RERAN', `MD5SUM` = ?, `COMMENTS` = ?, `DEPLOYMENT_ID` = ? " +
                                "WHERE `ID` = ? AND `AUTHOR` = ? AND `FILENAME` = ?",
                                Arrays.asList(nowStr, maxOrder, md5, cs.comment, deploymentId, cs.id, cs.author, cs.filename)
                        );
                    } else {
                        DatabaseManager.executeUpdate(
                                "INSERT INTO `DATABASECHANGELOG` (`ID`, `AUTHOR`, `FILENAME`, `DATEEXECUTED`, `ORDEREXECUTED`, `EXECTYPE`, `MD5SUM`, `DESCRIPTION`, `COMMENTS`, `TAG`, `LIQUIBASE`, `CONTEXTS`, `LABELS`, `DEPLOYMENT_ID`) " +
                                "VALUES (?, ?, ?, ?, ?, 'EXECUTED', ?, 'sqlFile', ?, 'v3.0-neiva', ?, 'production,xampp', 'mysql,antojo-virtual', ?)",
                                Arrays.asList(cs.id, cs.author, cs.filename, nowStr, maxOrder, md5, cs.comment, LIQUIBASE_VERSION, deploymentId)
                        );
                    }
                    appliedNow.add(cs.author + ":" + cs.id + " (" + cs.filename + ")");
                }
            }

            releaseLock();

            result.put("success", true);
            result.put("liquibase_version", LIQUIBASE_VERSION);
            result.put("master_changelog", MASTER_CHANGELOG);
            result.put("applied_count", appliedNow.size());
            result.put("applied_changesets", appliedNow);
            result.put("skipped_count", skippedAlreadyExecuted.size());
            result.put("skipped_changesets", skippedAlreadyExecuted);
        } catch (Exception e) {
            try { releaseLock(); } catch (Exception ignored) {}
            e.printStackTrace();
            result.put("success", false);
            result.put("error", e.getMessage());
        }
        return result;
    }

    public static void ensureLiquibaseTables() throws Exception {
        String createChangelogSql =
                "CREATE TABLE IF NOT EXISTS `DATABASECHANGELOG` (" +
                "  `ID` VARCHAR(255) NOT NULL," +
                "  `AUTHOR` VARCHAR(255) NOT NULL," +
                "  `FILENAME` VARCHAR(255) NOT NULL," +
                "  `DATEEXECUTED` DATETIME NOT NULL," +
                "  `ORDEREXECUTED` INT NOT NULL," +
                "  `EXECTYPE` VARCHAR(10) NOT NULL," +
                "  `MD5SUM` VARCHAR(35) DEFAULT NULL," +
                "  `DESCRIPTION` VARCHAR(255) DEFAULT NULL," +
                "  `COMMENTS` VARCHAR(255) DEFAULT NULL," +
                "  `TAG` VARCHAR(255) DEFAULT NULL," +
                "  `LIQUIBASE` VARCHAR(20) DEFAULT NULL," +
                "  `CONTEXTS` VARCHAR(255) DEFAULT NULL," +
                "  `LABELS` VARCHAR(255) DEFAULT NULL," +
                "  `DEPLOYMENT_ID` VARCHAR(10) DEFAULT NULL," +
                "  INDEX `idx_dbcl_id_author` (`ID`, `AUTHOR`)" +
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        String createLockSql =
                "CREATE TABLE IF NOT EXISTS `DATABASECHANGELOGLOCK` (" +
                "  `ID` INT NOT NULL PRIMARY KEY," +
                "  `LOCKED` TINYINT(1) NOT NULL," +
                "  `LOCKGRANTED` DATETIME DEFAULT NULL," +
                "  `LOCKEDBY` VARCHAR(255) DEFAULT NULL" +
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        DatabaseManager.executeRawSqlScript(createChangelogSql + "\n" + createLockSql);

        int lockRows = DatabaseManager.queryScalarInt("SELECT COUNT(*) FROM `DATABASECHANGELOGLOCK` WHERE `ID` = 1");
        if (lockRows == 0) {
            DatabaseManager.executeUpdate(
                    "INSERT INTO `DATABASECHANGELOGLOCK` (`ID`, `LOCKED`, `LOCKGRANTED`, `LOCKEDBY`) VALUES (1, 0, NULL, NULL)",
                    Collections.emptyList()
            );
        }
    }

    private static void acquireLock() throws Exception {
        String nowStr = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
        DatabaseManager.executeUpdate(
                "UPDATE `DATABASECHANGELOGLOCK` SET `LOCKED` = 1, `LOCKGRANTED` = ?, `LOCKEDBY` = 'AntojoVirtual-Java-Liquibase (127.0.0.1)' WHERE `ID` = 1",
                Collections.singletonList(nowStr)
        );
    }

    private static void releaseLock() throws Exception {
        DatabaseManager.executeUpdate(
                "UPDATE `DATABASECHANGELOGLOCK` SET `LOCKED` = 0, `LOCKGRANTED` = NULL, `LOCKEDBY` = NULL WHERE `ID` = 1",
                Collections.emptyList()
        );
    }

    public static List<Map<String, Object>> getChangelogHistory() {
        try {
            ensureLiquibaseTables();
            return DatabaseManager.queryList(
                    "SELECT `ID`, `AUTHOR`, `FILENAME`, `DATEEXECUTED`, `ORDEREXECUTED`, `EXECTYPE`, `MD5SUM`, `DESCRIPTION`, `COMMENTS`, `TAG`, `LIQUIBASE`, `DEPLOYMENT_ID` " +
                    "FROM `DATABASECHANGELOG` ORDER BY `ORDEREXECUTED` ASC"
            );
        } catch (Exception e) {
            return Collections.emptyList();
        }
    }

    public static Map<String, Object> getLockStatus() {
        try {
            ensureLiquibaseTables();
            Map<String, Object> lock = DatabaseManager.queryOne("SELECT * FROM `DATABASECHANGELOGLOCK` WHERE `ID` = 1");
            return lock != null ? lock : Collections.emptyMap();
        } catch (Exception e) {
            return Collections.emptyMap();
        }
    }

    private static List<String> parseMasterChangelog(String masterPath) throws Exception {
        List<String> files = new ArrayList<>();
        Path p = Path.of(masterPath);
        if (!Files.exists(p)) {
            return files;
        }
        String xml = Files.readString(p, StandardCharsets.UTF_8);
        Matcher m = INCLUDE_PATTERN.matcher(xml);
        while (m.find()) {
            files.add(m.group(1).trim());
        }
        return files;
    }

    private static List<ChangesetDef> parseFormattedSqlChangelog(String filename, String content) {
        List<ChangesetDef> list = new ArrayList<>();
        String[] lines = content.split("\r?\n");

        ChangesetDef current = null;
        StringBuilder sqlBuilder = new StringBuilder();

        for (String line : lines) {
            String trimmed = line.trim();
            Matcher csMatcher = CHANGESET_HEADER.matcher(trimmed);
            if (csMatcher.matches()) {
                if (current != null) {
                    current.sqlBody = sqlBuilder.toString();
                    list.add(current);
                }
                current = new ChangesetDef();
                current.author = csMatcher.group(1).trim();
                current.id = csMatcher.group(2).trim();
                current.filename = filename.replace('\\', '/');
                current.comment = "Migración Liquibase MySQL";
                sqlBuilder.setLength(0);
                continue;
            }

            if (current != null) {
                Matcher commentMatcher = COMMENT_HEADER.matcher(trimmed);
                if (commentMatcher.matches()) {
                    current.comment = commentMatcher.group(1).trim();
                    continue;
                }
                if (!trimmed.startsWith("--liquibase")) {
                    sqlBuilder.append(line).append("\n");
                }
            }
        }

        if (current != null) {
            current.sqlBody = sqlBuilder.toString();
            list.add(current);
        }

        return list;
    }

    private static String md5Hex(String input) {
        try {
            MessageDigest md = MessageDigest.getInstance("MD5");
            byte[] digest = md.digest(input.getBytes(StandardCharsets.UTF_8));
            StringBuilder sb = new StringBuilder(32);
            for (byte b : digest) {
                sb.append(String.format("%02x", b & 0xff));
            }
            return sb.toString();
        } catch (Exception e) {
            return "00000000000000000000000000000000";
        }
    }

    private static class ChangesetDef {
        String id;
        String author;
        String filename;
        String comment;
        String sqlBody;
    }
}
