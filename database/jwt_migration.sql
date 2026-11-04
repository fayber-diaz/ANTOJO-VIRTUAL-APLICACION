-- =========================================================
-- MIGRACIÓN JWT — Antojo Virtual
-- Ejecutar solo si ya tienes la BD creada y no quieres
-- borrar los datos existentes.
-- 
-- Para aplicarla en XAMPP:
--   phpMyAdmin → antojo_virtual → SQL → pegar y ejecutar
-- =========================================================

USE `antojo_virtual`;

-- ── Tabla de Refresh Tokens ────────────────────────────────
CREATE TABLE IF NOT EXISTS `jwt_refresh_tokens` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT NOT NULL,
  `token_hash`  VARCHAR(64) NOT NULL,
  `jti`         VARCHAR(64) NOT NULL UNIQUE,
  `expires_at`  DATETIME NOT NULL,
  `revoked`     TINYINT(1) DEFAULT 0,
  `user_agent`  VARCHAR(255) DEFAULT NULL,
  `ip_address`  VARCHAR(45) DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id`    (`user_id`),
  INDEX `idx_token_hash` (`token_hash`),
  INDEX `idx_expires`    (`expires_at`),
  CONSTRAINT `fk_rt_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabla de Blacklist de Access Tokens ────────────────────
CREATE TABLE IF NOT EXISTS `jwt_blacklist` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `jti`        VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_jti`     (`jti`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Evento programado: limpiar tokens expirados cada día ───
-- (Requiere que el Event Scheduler esté habilitado en MySQL)
DROP EVENT IF EXISTS `cleanup_expired_jwt`;

CREATE EVENT `cleanup_expired_jwt`
  ON SCHEDULE EVERY 1 DAY
  STARTS CURRENT_TIMESTAMP
  DO BEGIN
    DELETE FROM `jwt_blacklist`       WHERE `expires_at` < NOW();
    DELETE FROM `jwt_refresh_tokens`  WHERE `expires_at` < NOW();
  END;

-- Activar event scheduler si está deshabilitado
-- SET GLOBAL event_scheduler = ON;

SELECT 'Migración JWT aplicada correctamente ✅' AS resultado;
